<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DocsPage {

	/**
	 * Renders the documentation shortcode output.
	 *
	 * @param array<string, mixed> $attrs Shortcode attributes.
	 * @return string
	 */
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

	/**
	 * Renders a single success criterion page.
	 *
	 * @param string               $criterion Criterion key such as 1-1-1.
	 * @param array<string, mixed> $yml       Parsed YAML data.
	 * @return string
	 */
	private static function renderSingleDoc( $criterion, $yml ) {
		$data = $yml['criterions'][ $criterion ] ?? null;
		if ( ! is_array( $data ) ) {
			return '<p>' . esc_html__( 'The requested criterion was not found.', 'jwp-a11y' ) . '</p>';
		}

		$html  = '';
		$html .= '<div class="jwp-a11y-doc">';
		$html .= '<h2>' . esc_html( $criterion . ' ' . ( $data['name'] ?? $criterion ) ) . '</h2>';
		$html .= self::renderCriterionContextTable( $criterion, $data );

		if ( ! empty( $data['summary'] ) ) {
			$html .= wp_kses_post( wpautop( self::linkifyCriterionReferences( (string) $data['summary'], $yml ) ) );
		}

		if ( ! empty( $data['doc'] ) ) {
			$html .= '<h3>' . esc_html__( 'About this success criterion', 'jwp-a11y' ) . '</h3>';
			$html .= wp_kses_post( wpautop( self::linkifyCriterionReferences( (string) $data['doc'], $yml ) ) );
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Renders a context table for the current principle, guideline, and criterion.
	 *
	 * @param string               $criterion Criterion code.
	 * @param array<string, mixed> $data      Criterion data.
	 * @return string
	 */
	private static function renderCriterionContextTable( $criterion, $data ) {
		$guideline = $data['guideline'] ?? null;
		if ( ! is_array( $guideline ) ) {
			return '';
		}

		$principle = $guideline['principle'] ?? null;
		if ( ! is_array( $principle ) ) {
			return '';
		}

		$criterion_name  = (string) ( $data['name'] ?? $criterion );
		$criterion_label = $criterion_name;
		$level_label     = self::criterionLevelLabel( $data );
		if ( $level_label !== '' ) {
			$criterion_label .= sprintf(
				' (%s %s)',
				$criterion,
				$level_label
			);
		}

		$principle_label = (string) ( $principle['name'] ?? '' );
		$guideline_label = (string) ( $guideline['name'] ?? '' );

		if ( ! empty( $principle['url'] ) ) {
			$principle_label = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( (string) $principle['url'] ),
				esc_html( $principle_label )
			);
		} else {
			$principle_label = esc_html( $principle_label );
		}

		if ( ! empty( $guideline['url'] ) ) {
			$guideline_label = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( (string) $guideline['url'] ),
				esc_html( $guideline_label )
			);
		} else {
			$guideline_label = esc_html( $guideline_label );
		}

		if ( ! empty( $data['url'] ) ) {
			$criterion_label = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( (string) $data['url'] ),
				esc_html( $criterion_label )
			);
		} else {
			$criterion_label = esc_html( $criterion_label );
		}

		$html  = '<table class="a11yc_table_info a11yc_table">';
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html__( 'Principle', 'jwp-a11y' ) . '</th>';
		$html .= '<td>' . $principle_label . '</td>';
		$html .= '<td>' . esc_html( (string) ( $principle['summary'] ?? '' ) ) . '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html__( 'Guideline', 'jwp-a11y' ) . '</th>';
		$html .= '<td>' . $guideline_label . '</td>';
		$html .= '<td>' . esc_html( (string) ( $guideline['summary'] ?? '' ) ) . '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html__( 'Success Criterion', 'jwp-a11y' ) . '</th>';
		$html .= '<td>' . $criterion_label . '</td>';
		$html .= '<td>' . esc_html( (string) ( $data['summary'] ?? '' ) ) . '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		return $html;
	}

	/**
	 * Converts bracketed criterion references to internal links.
	 *
	 * @param string               $text Source text.
	 * @param array<string, mixed> $yml  Parsed YAML data.
	 * @return string
	 */
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

	/**
	 * Renders the documentation index grouped by conformance level.
	 *
	 * @param array<string, mixed> $yml Parsed YAML data.
	 * @return string
	 */
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
			$html .= '<h2>' . esc_html( sprintf( __( 'Conformance level %s', 'jwp-a11y' ), $level ) ) . '</h2>';
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

	/**
	 * Registers the Tools submenu page for documentation.
	 *
	 * @return void
	 */
	public static function registerAdminPage() {
		add_management_page(
			__( 'For Better Web Accessibility', 'jwp-a11y' ),
			__( 'For Better Web Accessibility', 'jwp-a11y' ),
			'edit_posts',
			'jwp-a11y-docs',
			array( __CLASS__, 'renderAdminPage' )
		);
	}

	/**
	 * Renders the admin documentation page.
	 *
	 * @return void
	 */
	public static function renderAdminPage() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'For Better Web Accessibility', 'jwp-a11y' ) . '</h1>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The method returns plugin-generated HTML with escaped dynamic values.
		echo self::renderDocShortcode();
		echo '</div>';
	}

	/**
	 * Returns the admin URL for the docs page.
	 *
	 * @return string
	 */
	public static function docsPageUrl() {
		return admin_url( 'tools.php?page=jwp-a11y-docs' );
	}
}
