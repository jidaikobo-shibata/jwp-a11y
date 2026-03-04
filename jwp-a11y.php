<?php
/**
 * Plugin Name: jwp-a11y
 * Plugin URI: https://wordpress.org/plugins/jwp-a11y/
 * Description: WordPress plugin that uses jidaikobo/a11yc for post accessibility checks and legacy result display.
 * Author: Jidaikobo Inc.
 * Version: 5.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author URI: https://www.jidaikobo.com/
 * License: GPL2
 * Text Domain: jwp_a11y
 * Domain Path: /languages
 *
 * @package JwpA11y
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

load_plugin_textdomain(
	'jwp_a11y',
	false,
	dirname( plugin_basename( __FILE__ ) ) . '/languages'
);

require_once __DIR__ . '/includes/class-results-page.php';
require_once __DIR__ . '/includes/class-docs-page.php';
require_once __DIR__ . '/includes/class-editor-notices.php';
require_once __DIR__ . '/includes/class-post-analysis.php';
require_once __DIR__ . '/includes/class-compatibility.php';
require_once __DIR__ . '/includes/class-frontend-assets.php';
require_once __DIR__ . '/includes/class-plugin.php';

\JwpA11y\Plugin::init();
