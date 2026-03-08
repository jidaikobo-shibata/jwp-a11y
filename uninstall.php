<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$jwp_a11y_post_meta_key          = '_jwp_a11y_analysis';
$jwp_a11y_user_meta_like         = $wpdb->esc_like( '_jwp_a11y_notice_' ) . '%';
$jwp_a11y_transient_like         = $wpdb->esc_like( '_transient_jwp_a11y_sup_' ) . '%';
$jwp_a11y_transient_timeout_like = $wpdb->esc_like( '_transient_timeout_jwp_a11y_sup_' ) . '%';

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete(
	$wpdb->postmeta,
	array(
		'meta_key' => $jwp_a11y_post_meta_key,
		),
		array( '%s' )
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			$jwp_a11y_user_meta_like
		)
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$jwp_a11y_transient_like,
			$jwp_a11y_transient_timeout_like
		)
	);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
