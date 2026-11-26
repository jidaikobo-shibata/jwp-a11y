<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! in_array( wp_parse_url( site_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
	WP_CLI::error( 'Audit checks may only run on localhost.' );
}

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
};

// Verify completeness independently of the frontend rendering.
$label = new ReflectionMethod( JwpA11y\ResultsPage::class, 'legacyConformanceLabel' );
$assert( $label->invoke( null, array( '1-1-1' => array( 'result' => 2 ) ), 2 ) === __( 'Test results incomplete', 'jwp-a11y' ), 'A single pass cannot establish AA conformance.' );
$criteria = array_slice( Jidaikobo\A11yc\Yaml::fetch()['criterions'], 0, 61, true );
$complete = array();
foreach ( $criteria as $key => $data ) {
	if ( in_array( $data['level']['name'], array( 'A', 'AA' ), true ) ) {
		$complete[ $key ] = array( 'result' => 2 );
	}
}
$assert( count( $complete ) === 38, 'Expected all 38 WCAG 2.0 A/AA criteria.' );
$complete['1-2-1']['result'] = 1; // Not applicable is evaluated.
$assert( $label->invoke( null, $complete, 2 ) === __( 'AA conforming', 'jwp-a11y' ), 'Complete passing and not-applicable results should retain AA.' );
$complete['1-4-3']['result'] = 0;
$assert( $label->invoke( null, $complete, 2 ) === __( 'A conforming / AA partially conforming', 'jwp-a11y' ), 'A failed AA criterion must not be reported as AA conforming.' );
$complete['1-4-3']['result'] = -1;
$assert( $label->invoke( null, $complete, 2 ) === __( 'Test results incomplete', 'jwp-a11y' ), 'An untested criterion must remain incomplete.' );

// Use only the disposable E2E account and post created by prepare.php.
$user = get_user_by( 'login', 'jwp_a11y_e2e' );
$post = get_post( intval( $args[0] ?? 0 ) );
$assert( $user instanceof WP_User, 'Missing E2E fixture user.' );
$assert( $post instanceof WP_Post && get_post_meta( $post->ID, '_jwp_a11y_e2e_fixture', true ) === '1', 'Missing E2E fixture post.' );
wp_set_current_user( $user->ID );

$count = 0;
$filter = static function ( $content ) use ( &$count ) {
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) {
		if ( ( $frame['class'] ?? '' ) === JwpA11y\PostAnalysis::class && ( $frame['function'] ?? '' ) === 'buildPostContent' ) {
			++$count;
			break;
		}
	}
	return $content;
};
add_filter( 'the_content', $filter );
try {
	$updated = wp_update_post( array( 'ID' => $post->ID, 'post_content' => $post->post_content ), true );
	$assert( ! is_wp_error( $updated ), 'Could not save the E2E post.' );
	$assert( $count === 1, 'Expected exactly one analysis per normal save.' );
} finally {
	remove_filter( 'the_content', $filter );
}

$issue = array( 'id' => 'audit-fixture', 'type' => 'notice', 'message' => 'E2E notice fixture', 'snippet' => '<p>Fixture</p>' );
$result = array( 'summary' => array( 'error_count' => 0 ), 'issues' => array( $issue ) );
$payload = new ReflectionMethod( JwpA11y\EditorNotices::class, 'buildNoticePayload' );
$key = ( new ReflectionMethod( JwpA11y\EditorNotices::class, 'issueSuppressionKey' ) )->invoke( null, $issue );
$transient = ( new ReflectionMethod( JwpA11y\EditorNotices::class, 'suppressionTransientKey' ) )->invoke( null, $user->ID, $post->ID, $key );
$visible = $payload->invoke( null, $post->ID, $result );
$assert( $visible['noticeCount'] === 1 && $visible['hiddenNoticeCount'] === 0 && $visible['successMessage'] === '', 'Visible notice should remain a notice.' );
try {
	set_transient( $transient, 1, 60 );
	$hidden = $payload->invoke( null, $post->ID, $result );
	$assert( $hidden['noticeCount'] === 0 && $hidden['hiddenNoticeCount'] === 1, 'Expected one hidden notice.' );
	$assert( $hidden['successMessage'] === '' && $hidden['successHtml'] === '', 'Hidden notices must not produce a success message.' );
	$assert( strpos( $hidden['noticeHtml'], 'jwp-a11y-hidden-notices' ) !== false, 'Hidden notice information should remain visible.' );
} finally {
	delete_transient( $transient );
}
$empty = $payload->invoke( null, $post->ID, array( 'summary' => array( 'error_count' => 0 ), 'issues' => array() ) );
$assert( $empty['successMessage'] !== '', 'A genuinely empty result should still produce a success message.' );

echo "Audit regression checks passed.\n";
