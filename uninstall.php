<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$post_meta_key          = '_jwp_a11y_analysis';
$user_meta_like         = $wpdb->esc_like( '_jwp_a11y_notice_' ) . '%';
$transient_like         = $wpdb->esc_like( '_transient_jwp_a11y_sup_' ) . '%';
$transient_timeout_like = $wpdb->esc_like( '_transient_timeout_jwp_a11y_sup_' ) . '%';

$wpdb->delete(
	$wpdb->postmeta,
	array(
		'meta_key' => $post_meta_key,
	),
	array( '%s' )
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
		$user_meta_like
	)
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$transient_like,
		$transient_timeout_like
	)
);
