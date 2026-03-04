<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendAssets {

	/**
	 * Enqueues the minimal table stylesheet on the docs admin screen.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueueAdminStyles( $hook_suffix ) {
		if ( 'tools_page_jwp-a11y-docs' !== $hook_suffix ) {
			return;
		}

		self::enqueueSharedStyles();
	}

	/**
	 * Enqueues the minimal frontend stylesheet used by result tables.
	 *
	 * @return void
	 */
	public static function enqueueStyles() {
		if ( is_admin() ) {
			return;
		}

		self::enqueueSharedStyles();
	}

	/**
	 * Enqueues the shared stylesheet when it exists.
	 *
	 * @return void
	 */
	private static function enqueueSharedStyles() {
		$css_file = dirname( __DIR__ ) . '/assets/css/frontend.css';
		if ( ! file_exists( $css_file ) ) {
			return;
		}

		wp_enqueue_style(
			'jwp-a11y-frontend',
			plugins_url( 'assets/css/frontend.css', dirname( __DIR__ ) . '/jwp-a11y.php' ),
			array(),
			filemtime( $css_file )
		);
	}
}
