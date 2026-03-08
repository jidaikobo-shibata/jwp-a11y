<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private const META_KEY = '_jwp_a11y_analysis';

	/**
	 * Boots the plugin and registers all WordPress hooks.
	 *
	 * @return void
	 */
	public static function init() {
		self::ensureTextdomainLoaded();

		if ( ! self::loadAutoloader() ) {
			add_action( 'admin_notices', array( __CLASS__, 'renderMissingAutoloaderNotice' ) );
			return;
		}

		\JwpA11y\Compatibility::defineConstants();

		add_action( 'save_post', array( '\\JwpA11y\\PostAnalysis', 'analyzePostOnSave' ), 20, 3 );
		add_action( 'wp_after_insert_post', array( '\\JwpA11y\\PostAnalysis', 'analyzePostAfterInsert' ), 20, 3 );
		add_action( 'admin_notices', array( '\\JwpA11y\\EditorNotices', 'renderEditScreenNotice' ) );
		add_action( 'admin_print_footer_scripts', array( '\\JwpA11y\\EditorNotices', 'printSuppressNoticeScript' ) );
		add_action( 'admin_enqueue_scripts', array( '\\JwpA11y\\FrontendAssets', 'enqueueAdminStyles' ) );
		add_action( 'enqueue_block_editor_assets', array( '\\JwpA11y\\EditorNotices', 'enqueueBlockEditorNotice' ) );
		add_action( 'wp_enqueue_scripts', array( '\\JwpA11y\\FrontendAssets', 'enqueueStyles' ) );
		add_action( 'wp_ajax_jwp_a11y_notice', array( '\\JwpA11y\\EditorNotices', 'ajaxConsumeNotice' ) );
		add_action( 'wp_ajax_jwp_a11y_suppress_notice', array( '\\JwpA11y\\EditorNotices', 'ajaxSuppressNotice' ) );
		add_shortcode( 'jwp_a11y_results', array( '\\JwpA11y\\ResultsPage', 'renderResultsShortcode' ) );
		add_shortcode( 'jwp_a11y_doc', array( '\\JwpA11y\\DocsPage', 'renderDocShortcode' ) );
		add_shortcode( 'jwp_a11y_docs', array( '\\JwpA11y\\DocsPage', 'renderDocShortcode' ) );
		add_action( 'admin_menu', array( '\\JwpA11y\\DocsPage', 'registerAdminPage' ) );
	}

	/**
	 * Loads bundled translation files without using load_plugin_textdomain().
	 *
	 * @return void
	 */
	private static function ensureTextdomainLoaded() {
		if ( ! function_exists( 'load_textdomain' ) ) {
			return;
		}

		$domain   = 'jwp-a11y';
		$lang_dir = dirname( __DIR__ ) . '/languages/';
		$locales  = array();

		if ( function_exists( 'determine_locale' ) ) {
			$detected = (string) determine_locale();
			if ( $detected !== '' ) {
				$locales[] = $detected;
			}
		}

		$site_locale = (string) get_locale();
		if ( $site_locale !== '' ) {
			$locales[] = $site_locale;
		}

		$locales = array_values( array_unique( $locales ) );
		foreach ( $locales as $locale ) {
			$candidates = array( $locale );
			if ( false !== strpos( $locale, '_' ) ) {
				$parts = explode( '_', $locale );
				if ( ! empty( $parts[0] ) ) {
					$candidates[] = $parts[0];
				}
			}

			foreach ( array_values( array_unique( $candidates ) ) as $candidate_locale ) {
				$mo_file = $lang_dir . $domain . '-' . $candidate_locale . '.mo';
				if ( ! file_exists( $mo_file ) ) {
					continue;
				}

				if ( function_exists( 'unload_textdomain' ) ) {
					unload_textdomain( $domain );
				}
				load_textdomain( $domain, $mo_file );
				return;
			}
		}
	}

	/**
	 * Loads Composer autoloading and validates the a11yc dependency.
	 *
	 * @return bool
	 */
	private static function loadAutoloader() {
		$plugin_dir  = dirname( __DIR__ );
		$autoloaders = array( $plugin_dir . '/vendor/autoload.php' );

		foreach ( $autoloaders as $autoload ) {
			if ( ! file_exists( $autoload ) ) {
				continue;
			}

			require_once $autoload;

			if ( class_exists( '\\Jidaikobo\\A11yc\\Analyzer' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renders the admin notice shown when the Composer autoloader is missing.
	 *
	 * @return void
	 */
	public static function renderMissingAutoloaderNotice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'jwp-a11y could not load jidaikobo/a11yc. Run composer install in the plugin directory or keep jwp-a11y enabled as a temporary fallback.', 'jwp-a11y' );
		echo '</p></div>';
	}

	/**
	 * Returns the post meta key used to store the latest analysis result.
	 *
	 * @return string
	 */
	public static function metaKey() {
		return self::META_KEY;
	}
}
