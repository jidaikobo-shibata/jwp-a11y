<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendAssets {

	public static function enqueueStyles() {
		if ( is_admin() ) {
			return;
		}

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
