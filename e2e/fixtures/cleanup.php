<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$allowed_hosts = array( 'localhost', '127.0.0.1', '::1' );
$site_host     = wp_parse_url( site_url(), PHP_URL_HOST );
if ( ! in_array( $site_host, $allowed_hosts, true ) ) {
	WP_CLI::error( 'E2E fixtures may only be removed on localhost.' );
}

$editor_post = get_page_by_path( 'jwp-a11y-e2e-editor', OBJECT, 'post' );
if ( $editor_post instanceof WP_Post ) {
	wp_delete_post( $editor_post->ID, true );
}

$fixture_user = get_user_by( 'login', 'jwp_a11y_e2e' );
if ( $fixture_user instanceof WP_User ) {
	wp_delete_user( $fixture_user->ID );
}

echo "Transient E2E fixtures removed.\n";
