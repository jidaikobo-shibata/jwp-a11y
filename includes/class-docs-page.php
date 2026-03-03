<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DocsPage {

	public static function renderDocShortcode( $attrs = array() ) {
		unset( $attrs );
		$yml = \Jidaikobo\A11yc\Yaml::fetch();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query argument selects the documentation page to display.
		$criterion = isset( $_GET['criterion'] ) ? sanitize_text_field( wp_unslash( $_GET['criterion'] ) ) : '';

		if ( $criterion !== '' ) {
			return self::renderSingleDoc( $criterion, $yml );
		}

		return self::renderDocIndex( $yml );
	}

	private static function renderSingleDoc( $criterion, $yml ) {
		$data = $yml['criterions'][ $criterion ] ?? null;
		if ( ! is_array( $data ) ) {
			return '<p>' . esc_html__( 'The requested criterion was not found.', 'jwp_a11y' ) . '</p>';
		}

		$html  = '';
		$html .= '<div class="jwp-a11y-doc">';
		$html .= '<h2>' . esc_html( $criterion . ' ' . ( $data['name'] ?? $criterion ) ) . '</h2>';

		if ( ! empty( $data['summary'] ) ) {
			$html .= wp_kses_post( wpautop( self::linkifyCriterionReferences( (string) $data['summary'], $yml ) ) );
		}

		if ( ! empty( $data['doc'] ) ) {
			$html .= '<h3>' . esc_html__( 'この達成基準について', 'jwp_a11y' ) . '</h3>';
			$html .= wp_kses_post( wpautop( self::linkifyCriterionReferences( (string) $data['doc'], $yml ) ) );
		}

		$html .= '</div>';
		return $html;
	}

	private static function linkifyCriterionReferences( $text, $yml ) {
		$text = (string) $text;
		if ( $text === '' ) {
			return '';
		}

		return (string) preg_replace_callback(
			'/\[(\d+\.\d+\.\d+)\]/',
			function ( $matches ) use ( $yml ) {
				$display   = (string) $matches[1];
				$criterion = str_replace( '.', '-', $display );
				if ( ! isset( $yml['criterions'][ $criterion ] ) ) {
					return $matches[0];
				}

				$url = add_query_arg( 'criterion', rawurlencode( $criterion ), static::docsPageUrl() );
				return '<a href="' . esc_url( $url ) . '">[' . esc_html( $display ) . ']</a>';
			},
			$text
		);
	}

	private static function renderDocIndex( $yml ) {
		$grouped = array();
		foreach ( $yml['criterions'] as $criterion => $data ) {
			$level = self::criterionLevelLabel( $data );
			if ( $level === '' ) {
				$level = '-';
			}
			if ( ! isset( $grouped[ $level ] ) ) {
				$grouped[ $level ] = array();
			}
			$grouped[ $level ][ $criterion ] = $data;
		}
		uksort( $grouped, array( __CLASS__, 'compareLevels' ) );

		$html  = '';
		$html .= '<div class="jwp-a11y-doc-index">';

		foreach ( $grouped as $level => $criterions ) {
			/* translators: %s: WCAG conformance level label such as A, AA, or AAA. */
			$html .= '<h2>' . esc_html( sprintf( __( '適合レベル %s', 'jwp_a11y' ), $level ) ) . '</h2>';
			$html .= '<ul>';

			foreach ( $criterions as $criterion => $data ) {
				$link  = add_query_arg( 'criterion', rawurlencode( $criterion ), self::docsPageUrl() );
				$html .= '<li><a href="' . esc_url( $link ) . '">' . esc_html( $criterion . ' ' . ( $data['name'] ?? $criterion ) ) . '</a>';
				if ( ! empty( $data['summary'] ) ) {
					$html .= '<div class="jwp-a11y-doc-index-summary">' . esc_html( wp_strip_all_tags( (string) $data['summary'] ) ) . '</div>';
				}
				$html .= '</li>';
			}

			$html .= '</ul>';
		}

		$html .= '</div>';

		return $html;
	}

	private static function compareLevels( $left, $right ) {
		$order = array(
			'A'   => 1,
			'AA'  => 2,
			'AAA' => 3,
		);

		$left_rank  = $order[ $left ] ?? 99;
		$right_rank = $order[ $right ] ?? 99;

		if ( $left_rank === $right_rank ) {
			return strcmp( (string) $left, (string) $right );
		}

		return $left_rank <=> $right_rank;
	}

	private static function criterionLevelLabel( $criterion_data ) {
		if ( ! is_array( $criterion_data ) ) {
			return '';
		}

		$level = $criterion_data['level'] ?? '';
		if ( is_array( $level ) ) {
			$level = $level['name'] ?? '';
		}

		return is_scalar( $level ) ? (string) $level : '';
	}

	public static function registerAdminPage() {
		add_management_page(
			__( 'ウェブアクセシビリティの確保のために', 'jwp_a11y' ),
			__( 'ウェブアクセシビリティの確保のために', 'jwp_a11y' ),
			'edit_posts',
			'jwp-a11y-docs',
			array( __CLASS__, 'renderAdminPage' )
		);
	}

	public static function renderAdminPage() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'ウェブアクセシビリティの確保のために', 'jwp_a11y' ) . '</h1>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The method returns plugin-generated HTML with escaped dynamic values.
		echo self::renderDocShortcode();
		echo '</div>';
	}

	public static function docsPageUrl() {
		return admin_url( 'tools.php?page=jwp-a11y-docs' );
	}
}
