<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$allowed_hosts = array( 'localhost', '127.0.0.1', '::1' );
$site_host     = wp_parse_url( site_url(), PHP_URL_HOST );
if ( ! in_array( $site_host, $allowed_hosts, true ) ) {
	WP_CLI::error( 'E2E fixtures may only be installed on localhost.' );
}

global $wpdb;

$fixture_version = 990001;
$older_version   = 990000;
$fixture_user    = 'jwp_a11y_e2e';
$fixture_email   = 'jwp-a11y-e2e@localhost.invalid';
$fixture_pass    = getenv( 'JWP_A11Y_E2E_PASSWORD' );
$fixture_pass    = is_string( $fixture_pass ) && $fixture_pass !== ''
	? $fixture_pass
	: 'JwpA11y-E2E-Local-Only-2026!';

$user = get_user_by( 'login', $fixture_user );
if ( $user instanceof WP_User ) {
	wp_update_user(
		array(
			'ID'        => $user->ID,
			'user_pass' => $fixture_pass,
			'role'      => 'administrator',
		)
	);
} else {
	$user_id = wp_create_user( $fixture_user, $fixture_pass, $fixture_email );
	if ( is_wp_error( $user_id ) ) {
		WP_CLI::error( $user_id->get_error_message() );
	}
	$user = new WP_User( $user_id );
	$user->set_role( 'administrator' );
}

$create_or_update_post = static function ( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );
	$post     = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => $slug === 'jwp-a11y-e2e-results' ? 'page' : 'post',
		'meta_input'   => array( '_jwp_a11y_e2e_fixture' => '1' ),
	);
	if ( $existing instanceof WP_Post ) {
		$post['ID'] = $existing->ID;
	}

	$post_id = wp_insert_post( $post, true );
	if ( is_wp_error( $post_id ) ) {
		WP_CLI::error( $post_id->get_error_message() );
	}
	return intval( $post_id );
};

$editor_post_id = $create_or_update_post(
	'jwp-a11y-e2e-editor',
	'jwp-a11y E2E editor fixture',
	'<!-- wp:paragraph --><p>Initial E2E content.</p><!-- /wp:paragraph -->'
);
$results_page_id = $create_or_update_post(
	'jwp-a11y-e2e-results',
	'jwp-a11y E2E legacy results',
	'[jwp_a11y_results]'
);
$results_page_url = get_permalink( $results_page_id );
$tested_url       = home_url( '/jwp-a11y-e2e-tested-page/' );

$table        = $wpdb->prefix . 'jwp_a11yc_data';
$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
if ( ! $table_exists ) {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset_collate = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) NOT NULL DEFAULT 0,
			`key` varchar(256) NOT NULL DEFAULT '',
			url varchar(2048) NOT NULL DEFAULT '',
			value longtext NULL,
			is_array tinyint(1) NOT NULL DEFAULT 0,
			version bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY a11yc_data_idx (group_id, url(191), `key`(191), version)
		) {$charset_collate};"
	);
}

$wpdb->delete( $table, array( 'version' => $fixture_version ), array( '%d' ) );
$wpdb->delete( $table, array( 'version' => $older_version ), array( '%d' ) );

$insert_fixture = static function ( $key, $url, $value, $version = null ) use ( $wpdb, $table, $fixture_version ) {
	$result = $wpdb->insert(
		$table,
		array(
			'group_id' => 1,
			'key'      => $key,
			'url'      => $url,
			'value'    => wp_json_encode( $value ),
			'is_array' => 1,
			'version'  => $version ?? $fixture_version,
		),
		array( '%d', '%s', '%s', '%s', '%d', '%d' )
	);
	if ( false === $result ) {
		WP_CLI::error( 'Could not insert E2E legacy fixture row.' );
	}
};

// A distinct historical version lets navigation tests detect a switch to the latest results.
$insert_fixture( 'setting', 'common', array(
	'policy'       => '<p>Historical E2E policy fixture.</p>',
	'show_results' => true,
	'title'        => 'Historical E2E Report',
	'target_level' => 2,
), $older_version );
$insert_fixture( 'page', $tested_url, array(
	'url'   => $tested_url,
	'title' => 'Historical E2E tested page',
	'trash' => false,
), $older_version );
$insert_fixture( 'result', $tested_url, array(
	'1-1-1' => array( 'result' => 2, 'memo' => 'Historical E2E pass memo.' ),
), $older_version );

$insert_fixture(
	'setting',
	'common',
	array(
		'policy'            => '<p>E2E accessibility policy fixture.</p>',
		'show_results'      => true,
		'title'             => 'E2E Accessibility Report',
		'target_level'      => 2,
		'test_period'       => '2026-08-01 to 2026-08-22',
		'dependencies'      => 'HTML, CSS, JavaScript',
		'contact'           => 'e2e@localhost.invalid',
		'report'            => 'E2E additional information.',
		'hide_date_results' => false,
	)
);
$insert_fixture(
	'version',
	'common',
	array(
		(string) $fixture_version => array( 'name' => 'E2E fixture version' ),
		(string) $older_version => array( 'name' => 'Historical E2E fixture version' ),
	)
);
$insert_fixture(
	'page',
	$tested_url,
	array(
		'url'   => $tested_url,
		'title' => 'E2E tested page',
		'date'  => '2026-08-22',
		'level' => 1,
		'trash' => false,
	)
);
$insert_fixture(
	'result',
	$tested_url,
	array(
		'1-1-1' => array(
			'result' => 0,
			'memo'   => 'E2E failure memo.',
		),
		'1-3-1' => array(
			'result' => 2,
			'memo'   => 'E2E pass memo.',
		),
	)
);

echo wp_json_encode(
	array(
		'editorPostId'   => $editor_post_id,
		'editorEditUrl'  => admin_url( 'post.php?post=' . $editor_post_id . '&action=edit' ),
		'resultsPageId'  => $results_page_id,
		'resultsPageUrl' => $results_page_url,
		'testedUrl'      => $tested_url,
	)
);
