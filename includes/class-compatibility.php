<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Compatibility {

	/**
	 * Defines compatibility constants expected by legacy checks.
	 *
	 * @return void
	 */
	public static function defineConstants() {
		$lang = self::currentLanguage();

		if ( ! defined( 'A11YC_LANG' ) ) {
			define( 'A11YC_LANG', $lang );
		}

		if ( ! defined( 'A11YC_LANG_HERE' ) ) {
			define(
				'A11YC_LANG_HERE',
				$lang === 'ja' ? 'こちら,ここ,ここをクリック,コチラ' : 'here, click here, click'
			);
		}

		if ( ! defined( 'A11YC_LANG_IMAGE' ) ) {
			define( 'A11YC_LANG_IMAGE', $lang === 'ja' ? '画像' : 'Image' );
		}

		if ( ! defined( 'A11YC_LANG_COUNT_ITEMS' ) ) {
			define( 'A11YC_LANG_COUNT_ITEMS', $lang === 'ja' ? '%s件' : '%s items' );
		}
	}

	/**
	 * Returns the current plugin language as a simple ja/en code.
	 *
	 * @return string
	 */
	public static function currentLanguage() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return strpos( (string) $locale, 'ja' ) === 0 ? 'ja' : 'en';
	}
}
