<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResultsPage {

	private const LEGACY_GROUP_ID = 1;

	/**
	 * Renders the legacy public results shortcode.
	 *
	 * @param array<string, mixed> $attrs Shortcode attributes.
	 * @return string
	 */
	public static function renderResultsShortcode( $attrs = array() ) {
		return self::renderSimpleResultsShortcode( $attrs );
	}

	/**
	 * Routes result rendering based on legacy query arguments.
	 *
	 * @param array<string, mixed> $attrs Shortcode attributes.
	 * @return string
	 */
	private static function renderSimpleResultsShortcode( $attrs = array() ) {
		unset( $attrs );

		$base_url = self::currentContentUrl();
		if ( $base_url === '' ) {
			return '';
		}

		$version  = self::selectedLegacyVersion();
		$settings = self::loadLegacySettings( $version );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These query arguments control front-end report navigation.
		if ( isset( $_GET['a11yc_each'] ) && isset( $_GET['url'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query argument controls front-end report navigation.
			$url = sanitize_text_field( wp_unslash( $_GET['url'] ) );
			return self::renderLegacyEachPage( $base_url, $url, $version, $settings );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query argument controls front-end report navigation.
		if ( isset( $_GET['a11yc_page'] ) ) {
			return self::renderLegacyPageList( $base_url, $version );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query argument controls front-end report navigation.
		if ( isset( $_GET['a11yc_report'] ) ) {
			return self::renderLegacyReportPage( $base_url, $version, $settings );
		}

		return self::renderLegacyPolicyPage( $base_url, $version, $settings );
	}

	/**
	 * Renders the legacy policy page.
	 *
	 * @param string               $base_url Base report URL.
	 * @param int|null             $version  Selected result version.
	 * @param array<string, mixed> $settings Legacy settings.
	 * @return string
	 */
	private static function renderLegacyPolicyPage( $base_url, $version, $settings ) {
		$versions    = self::loadLegacyVersions();
		$report_link = add_query_arg( 'a11yc_report', 1, $base_url );

		$html  = '';
		$html .= '<div class="jwp-a11y-results">';

		$html .= self::renderLegacyVersionSwitcher( $base_url, $version, $versions );

		if ( ! empty( $settings['policy'] ) ) {
			$html .= '<div class="jwp-a11y-policy">' . wp_kses_post( $settings['policy'] ) . '</div>';
		}

		if ( ! empty( $settings['show_results'] ) ) {
			$html .= '<h2>' . esc_html__( 'Accessibility Report', 'jwp_a11y' ) . '</h2>';
			$html .= '<p class="a11yc_link"><a href="' . esc_url( $report_link ) . '">' .
				esc_html__( 'Accessibility Report', 'jwp_a11y' ) .
				'</a></p>';
		}

		$html .= '</div>';

		return $html;
	}

	private static function currentContentUrl() {
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				return get_permalink( $post_id );
			}
		}

		global $post;
		if ( $post instanceof \WP_Post ) {
			return get_permalink( $post->ID );
		}

		return '';
	}

	/**
	 * Renders the legacy report page.
	 *
	 * @param string               $base_url Base report URL.
	 * @param int|null             $version  Selected result version.
	 * @param array<string, mixed> $settings Legacy settings.
	 * @return string
	 */
	private static function renderLegacyReportPage( $base_url, $version, $settings ) {
		$pages = self::loadLegacyPages( $version );
		if ( empty( $pages ) ) {
			return '<p>' . esc_html__( 'No saved accessibility results were found for this page.', 'jwp_a11y' ) . '</p>';
		}

		$yml           = \Jidaikobo\A11yc\Yaml::fetch();
		$results       = self::evaluateLegacyTotal( $pages, $version );
		$done_pages    = array_values(
			array_filter(
				$pages,
				function ( $page ) use ( $version ) {
					return empty( $page['trash'] ) && ! empty( static::loadLegacyPageResult( $page, $version ) );
				}
			)
		);
		$total_pages   = array_values(
			array_filter(
				$pages,
				function ( $page ) {
					return empty( $page['trash'] );
				}
			)
		);
		$target_level  = intval( $settings['target_level'] ?? 0 );
		$current_level = self::legacyConformanceLabel( $results );
		$pages_link    = add_query_arg( 'a11yc_page', 1, $base_url );
		$standards     = \Jidaikobo\A11yc\Yaml::each( 'standards' );
		$standard_key  = $settings['standard'] ?? 0;
		$standard_name = is_array( $standards ) && array_key_exists( $standard_key, $standards )
			? (string) $standards[ $standard_key ]
			: '';

		$html  = '';
		$html .= '<div class="jwp-a11y-results">';
		$html .= self::renderLegacyVersionSwitcher(
			$base_url,
			$version,
			self::loadLegacyVersions(),
			array(
				'a11yc_report' => 1,
			)
		);
		$html .= '<h2>' . esc_html( (string) ( $settings['title'] ?? __( 'Accessibility Report', 'jwp_a11y' ) ) ) . '</h2>';
		$html .= '<table class="a11yc_table a11yc_table_report"><tbody>';
		if ( $standard_name !== '' ) {
			$html .= '<tr><th scope="row">' . esc_html__( 'Standard identifier and revision year', 'jwp_a11y' ) . '</th><td>' . esc_html( $standard_name ) . '</td></tr>';
		}
		$html .= '<tr><th scope="row">' . esc_html__( 'Target conformance level', 'jwp_a11y' ) . '</th><td>' . esc_html( self::formatLegacyLevel( $target_level ) ) . '</td></tr>';
		$html .= '<tr><th scope="row">' . esc_html__( 'Achieved conformance level', 'jwp_a11y' ) . '</th><td>' . esc_html( $current_level ) . '</td></tr>';
		if ( ! empty( $settings['dependencies'] ) ) {
			$html .= '<tr><th scope="row">' . esc_html__( 'List of relied-upon web content technologies', 'jwp_a11y' ) . '</th><td>' . nl2br( esc_html( (string) $settings['dependencies'] ) ) . '</td></tr>';
		}
		$html .= '<tr><th scope="row">' . esc_html__( 'URLs of tested web pages', 'jwp_a11y' ) . '</th><td><a href="' . esc_url( $pages_link ) . '">' . esc_html__( 'URL list', 'jwp_a11y' ) . '</a> (' . intval( count( $done_pages ) ) . ' / ' . intval( count( $total_pages ) ) . ')</td></tr>';
		if ( empty( $settings['hide_date_results'] ) && ! empty( $settings['test_period'] ) ) {
			$html .= '<tr><th scope="row">' . esc_html__( 'Test period', 'jwp_a11y' ) . '</th><td>' . esc_html( (string) $settings['test_period'] ) . '</td></tr>';
		}
		if ( ! empty( $settings['contact'] ) ) {
			$html .= '<tr><th scope="row">' . esc_html__( 'Accessibility contact information', 'jwp_a11y' ) . '</th><td>' . nl2br( esc_html( (string) $settings['contact'] ) ) . '</td></tr>';
		}
		$html .= '</tbody></table>';
		if ( ! empty( $settings['report'] ) ) {
			$html .= '<h2>' . esc_html__( 'Additional information', 'jwp_a11y' ) . '</h2>';
			$html .= wp_kses_post( wpautop( (string) $settings['report'] ) );
		}

		$rows = '';
		foreach ( $results as $criterion => $result ) {
			$criterion_data = $yml['criterions'][ $criterion ] ?? array();
			if ( ! self::shouldDisplayCriterion( $criterion_data, $target_level ) ) {
				continue;
			}
			$rows .= self::renderLegacyStyleResultRow( $criterion, $criterion_data, $result );
		}
		if ( $rows === '' ) {
			foreach ( $results as $criterion => $result ) {
				$criterion_data = $yml['criterions'][ $criterion ] ?? array();
				$rows          .= self::renderLegacyStyleResultRow( $criterion, $criterion_data, $result );
			}
		}

		$html .= '<h2>' . esc_html__( 'Checklist of Success Criteria', 'jwp_a11y' ) . '</h2>';
		$html .= '<table class="a11yc_table"><thead><tr>';
		$html .= '<th scope="col">' . esc_html__( 'Success criterion', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result">' . esc_html__( 'Conformance level', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">' . esc_html__( 'Applicable', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">' . esc_html__( 'Result', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result">' . esc_html__( 'Notes', 'jwp_a11y' ) . '</th>';
		$html .= '</tr></thead><tbody>';
		$html .= $rows;
		$html .= '</tbody></table>';
		$html .= '</div>';

		return $html;
	}

	private static function renderLegacyVersionSwitcher( $base_url, $version, $versions, $extra_args = array() ) {
		if ( empty( $versions ) ) {
			return '';
		}

		$html  = '';
		$html .= '<form action="' . esc_url( $base_url ) . '" method="get">';
		$html .= '<div><label for="a11yc_version">' . esc_html__( 'Switch the policy, report, and test version', 'jwp_a11y' ) . '</label> ';
		$html .= '<select name="a11yc_version" id="a11yc_version">';
		$html .= '<option value="">' . esc_html__( 'Latest', 'jwp_a11y' ) . '</option>';
		foreach ( $versions as $version_name => $version_row ) {
			$selected = (string) $version === (string) $version_name ? ' selected="selected"' : '';
			$html    .= '<option value="' . esc_attr( (string) $version_name ) . '"' . $selected . '>' .
				esc_html( (string) ( $version_row['name'] ?? $version_name ) ) .
				'</option>';
		}
		$html .= '</select> ';
		foreach ( $extra_args as $key => $value ) {
			$html .= '<input type="hidden" name="' . esc_attr( (string) $key ) . '" value="' . esc_attr( (string) $value ) . '">';
		}
		$html .= '<button type="submit">' . esc_html__( 'Submit', 'jwp_a11y' ) . '</button>';
		if ( (string) $version !== '' && intval( $version ) !== 0 ) {
			$html .= ' <a href="' . esc_url( add_query_arg( $extra_args, $base_url ) ) . '">' . esc_html__( 'Latest', 'jwp_a11y' ) . '</a>';
		}
		$html .= '</div></form>';

		return $html;
	}

	private static function renderLegacyPageList( $base_url, $version ) {
		$pages = array_values(
			array_filter(
				self::loadLegacyPages( $version ),
				function ( $page ) use ( $version ) {
					if ( ! empty( $page['trash'] ) ) {
						return false;
					}

					$data = static::loadLegacyPageResult( $page, $version );
					return ! empty( $data['result'] ) && is_array( $data['result'] );
				}
			)
		);

		$html  = '';
		$html .= '<div class="jwp-a11y-results">';
		$html .= '<h2>' . esc_html__( 'URL List', 'jwp_a11y' ) . '</h2>';
		$html .= '<table class="a11yc_table"><thead><tr>';
		$html .= '<th scope="col">' . esc_html__( 'Page', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col">' . esc_html__( 'Achieved conformance level for this page', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col">' . esc_html__( 'Test result', 'jwp_a11y' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $pages as $page ) {
			$url   = (string) ( $page['url'] ?? '' );
			$title = (string) ( $page['title'] ?? $url );
			$data  = self::loadLegacyPageResult( $page, $version );
			$link  = add_query_arg(
				array(
					'a11yc_each' => 1,
					'url'        => $url,
				),
				$base_url
			);

			$html .= '<tr>';
			$html .= '<th scope="row">' . esc_html( $title );
			if ( $url !== '' ) {
				$html .= '<br><small>' . esc_html( $url ) . '</small>';
			}
			$html .= '</th>';
			$html .= '<td>' . esc_html( self::legacyConformanceLabel( $data['result'] ?? array() ) ) . '</td>';
			$html .= '<td><a href="' . esc_url( $link ) . '">' . esc_html__( 'Test result', 'jwp_a11y' ) . '</a></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';
		$html .= '</div>';

		return $html;
	}

	private static function renderLegacyEachPage( $base_url, $url, $version, $settings ) {
		$data = self::loadLegacyResultData( $url, $version );
		if ( empty( $data['page'] ) || empty( $data['result'] ) ) {
			return '<p>' . esc_html__( 'No saved accessibility results were found for this page.', 'jwp_a11y' ) . '</p>';
		}

		$yml          = \Jidaikobo\A11yc\Yaml::fetch();
		$page         = $data['page'];
		$target_level = intval( $settings['target_level'] ?? 0 );
		$back_link    = add_query_arg( 'a11yc_page', 1, $base_url );

		$html  = '';
		$html .= '<div class="jwp-a11y-results">';
		$html .= '<p><a href="' . esc_url( $back_link ) . '">' . esc_html__( 'Back to URL list', 'jwp_a11y' ) . '</a></p>';
		$html .= '<h2>' . esc_html( (string) ( $page['title'] ?? $url ) ) . '</h2>';
		$html .= '<table class="a11yc_table a11yc_table_report"><tbody>';
		$html .= '<tr><th scope="row">' . esc_html__( 'Target conformance level', 'jwp_a11y' ) . '</th><td>' . esc_html( self::formatLegacyLevel( $target_level ) ) . '</td></tr>';
		$html .= '<tr><th scope="row">' . esc_html__( 'Achieved conformance level for this page', 'jwp_a11y' ) . '</th><td>' . esc_html( self::legacyConformanceLabel( $data['result'] ) ) . '</td></tr>';
		$html .= '<tr><th scope="row">' . esc_html__( 'Test date', 'jwp_a11y' ) . '</th><td>' . esc_html( (string) ( $page['date'] ?? '' ) ) . '</td></tr>';
		$html .= '</tbody></table>';

		$html .= '<h2>' . esc_html__( 'Checklist of Success Criteria', 'jwp_a11y' ) . '</h2>';
		$html .= '<table class="a11yc_table"><thead><tr>';
		$html .= '<th scope="col">' . esc_html__( 'Success criterion', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result">' . esc_html__( 'Conformance level', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">' . esc_html__( 'Applicable', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">' . esc_html__( 'Result', 'jwp_a11y' ) . '</th>';
		$html .= '<th scope="col" class="a11yc_result">' . esc_html__( 'Notes', 'jwp_a11y' ) . '</th>';
		$html .= '</tr></thead><tbody>';
		foreach ( $data['result'] as $criterion => $raw_result ) {
			$criterion_data = $yml['criterions'][ $criterion ] ?? array();
			if ( ! self::shouldDisplayCriterion( $criterion_data, $target_level ) ) {
				continue;
			}
			$html .= self::renderLegacyStyleResultRow( $criterion, $criterion_data, $raw_result );
		}
		$html .= '</tbody></table>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Loads one page/result pair from the legacy data table.
	 *
	 * @param string   $url     Page URL.
	 * @param int|null $version Optional result version.
	 * @return array<string, mixed>
	 */
	private static function loadLegacyResultData( $url, $version = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'jwp_a11yc_data';
		foreach ( self::legacyUrlCandidates( $url ) as $candidate ) {
			if ( null !== $version ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from the trusted WordPress table prefix.
				$page_query = $wpdb->prepare(
					"SELECT value, version FROM {$table} WHERE `key` = %s AND url = %s AND group_id = %d AND version = %d ORDER BY id DESC LIMIT 1",
					'page',
					$candidate,
					self::LEGACY_GROUP_ID,
					intval( $version )
				);
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from the trusted WordPress table prefix.
				$page_query = $wpdb->prepare(
					"SELECT value, version FROM {$table} WHERE `key` = %s AND url = %s AND group_id = %d ORDER BY id DESC LIMIT 1",
					'page',
					$candidate,
					self::LEGACY_GROUP_ID
				);
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The query string is prepared in the conditional branches above.
			$page = $wpdb->get_row( $page_query, ARRAY_A );
			if ( empty( $page['value'] ) ) {
				continue;
			}

			$version_to_use = isset( $page['version'] ) ? intval( $page['version'] ) : intval( $version );
			$result_version = ( $version_to_use > 0 ) ? $version_to_use : $version;
			$result_row     = self::loadLegacyValue( 'result', $candidate, $result_version );
			if ( ! is_array( $result_row ) ) {
				$result_row = array();
			}
			if ( empty( $result_row ) ) {
				continue;
			}

			return array(
				'page'    => json_decode( (string) $page['value'], true ),
				'result'  => $result_row,
				'url'     => $candidate,
				'version' => $version_to_use,
			);
		}

		return array();
	}

	private static function loadLegacyPages( $version = null ) {
		$rows = self::loadLegacyValuesByKey( 'page', $version );

		$pages = array();
		foreach ( $rows as $row ) {
			$page = json_decode( (string) ( $row['value'] ?? '' ), true );
			if ( ! is_array( $page ) ) {
				continue;
			}

			if ( empty( $page['url'] ) && ! empty( $row['url'] ) ) {
				$page['url'] = (string) $row['url'];
			}
			$page['version'] = intval( $row['version'] ?? 0 );
			$pages[]         = $page;
		}

		return $pages;
	}

	private static function legacyUrlCandidates( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return array();
		}

		$candidates = array( $url );
		$trimmed    = untrailingslashit( $url );
		$trailed    = trailingslashit( $trimmed );

		$candidates[] = $trimmed;
		$candidates[] = $trailed;

		if ( strpos( $trimmed, 'https://' ) === 0 ) {
			$candidates[] = 'http://' . substr( $trimmed, 8 );
			$candidates[] = trailingslashit( 'http://' . substr( $trimmed, 8 ) );
		} elseif ( strpos( $trimmed, 'http://' ) === 0 ) {
			$candidates[] = 'https://' . substr( $trimmed, 7 );
			$candidates[] = trailingslashit( 'https://' . substr( $trimmed, 7 ) );
		}

		return array_values( array_unique( array_filter( $candidates ) ) );
	}

	private static function loadLegacySettings( $version = null ) {
		return (array) self::loadLegacyValue( 'setting', 'common', $version );
	}

	private static function loadLegacyVersions() {
		return (array) self::loadLegacyValue( 'version', 'common' );
	}

	private static function selectedLegacyVersion() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query argument controls front-end report navigation.
		if ( ! isset( $_GET['a11yc_version'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query argument controls front-end report navigation.
		$version = intval( wp_unslash( $_GET['a11yc_version'] ) );
		if ( $version <= 0 ) {
			return null;
		}

		return $version;
	}

	/**
	 * Loads a single legacy value row from the data table.
	 *
	 * @param string   $key     Data key.
	 * @param string   $url     Data URL.
	 * @param int|null $version Optional result version.
	 * @return mixed
	 */
	private static function loadLegacyValue( $key, $url, $version = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'jwp_a11yc_data';

		if ( null !== $version ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from the trusted WordPress table prefix.
			$query = $wpdb->prepare(
				"SELECT value FROM {$table} WHERE `key` = %s AND url = %s AND group_id = %d AND version = %d LIMIT 1",
				$key,
				$url,
				self::LEGACY_GROUP_ID,
				intval( $version )
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from the trusted WordPress table prefix.
			$query = $wpdb->prepare(
				"SELECT value FROM {$table} WHERE `key` = %s AND url = %s AND group_id = %d ORDER BY id DESC LIMIT 1",
				$key,
				$url,
				self::LEGACY_GROUP_ID
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The query string is prepared in the conditional branches above.
		$value = $wpdb->get_var( $query );
		if ( ! is_string( $value ) || $value === '' ) {
			return array();
		}

		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	private static function loadLegacyValuesByKey( $key, $version ) {
		global $wpdb;

		$table = $wpdb->prefix . 'jwp_a11yc_data';

		if ( null !== $version ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from the trusted WordPress table prefix.
			$query = $wpdb->prepare(
				"SELECT value, url, version FROM {$table} WHERE `key` = %s AND group_id = %d AND version = %d ORDER BY id DESC",
				$key,
				self::LEGACY_GROUP_ID,
				intval( $version )
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from the trusted WordPress table prefix.
			$query = $wpdb->prepare(
				"SELECT value, url, version FROM {$table} WHERE `key` = %s AND group_id = %d ORDER BY id DESC",
				$key,
				self::LEGACY_GROUP_ID
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The query string is prepared in the conditional branches above.
		$rows = $wpdb->get_results( $query, ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	private static function countLegacyResults( $results ) {
		$counts = array(
			'errors'  => 0,
			'notices' => 0,
		);

		foreach ( $results as $criterion => $result ) {
			$criterion = str_replace( '.', '-', (string) $criterion );
			if ( strpos( $criterion, 'notice_' ) === 0 ) {
				++$counts['notices'];
				continue;
			}

			if ( intval( $result['result'] ?? 0 ) === 0 ) {
				++$counts['errors'];
			}
		}

		return $counts;
	}

	private static function evaluateLegacyUrl( $results ) {
		return self::countLegacyResults( $results );
	}

	/**
	 * Aggregates the overall conformance result for all saved pages.
	 *
	 * @param array<int, array<string, mixed>> $pages   Legacy page rows.
	 * @param int|null                         $version Optional result version.
	 * @return array<string, mixed>
	 */
	private static function evaluateLegacyTotal( $pages, $version ) {
		$yml     = \Jidaikobo\A11yc\Yaml::fetch();
		$results = array();

		foreach ( $pages as $page ) {
			if ( ! empty( $page['trash'] ) ) {
				continue;
			}

			$data = self::loadLegacyPageResult( $page, $version );
			if ( empty( $data['result'] ) || ! is_array( $data['result'] ) ) {
				continue;
			}

			foreach ( $data['result'] as $criterion => $result ) {
				$key = isset( $yml['criterions'][ $criterion ] ) ? $criterion : str_replace( '.', '-', (string) $criterion );
				if ( ! isset( $results[ $key ] ) ) {
					$results[ $key ] = array(
						'result' => intval( $result['result'] ?? 0 ),
						'memo'   => isset( $result['memo'] ) ? (string) $result['memo'] : '',
					);
					continue;
				}

				$current = intval( $results[ $key ]['result'] ?? 0 );
				$next    = intval( $result['result'] ?? 0 );
				if ( $next < $current ) {
					$results[ $key ]['result'] = $next;
				}
			}
		}

		return $results;
	}

	private static function legacyFailures( $results ) {
		$failures = array();

		foreach ( $results as $criterion => $result ) {
			if ( intval( $result['result'] ?? 0 ) !== 0 ) {
				continue;
			}

			$failures[ $criterion ] = $result;
		}

		return $failures;
	}

	private static function renderFailureItem( $criterion, $criterion_data, $result ) {
		$html  = '';
		$html .= '<li class="jwp-a11y-result-item">';
		$html .= '<strong>' . esc_html( $criterion . ' ' . ( $criterion_data['name'] ?? $criterion ) ) . '</strong>';

		$level_label = self::criterionLevelLabel( $criterion_data );
		if ( $level_label !== '' ) {
			$html .= ' <span class="jwp-a11y-level">[' . esc_html( $level_label ) . ']</span>';
		}

		if ( ! empty( $criterion_data['summary'] ) ) {
			$html .= '<div class="jwp-a11y-summary">' . wp_kses_post( wpautop( (string) $criterion_data['summary'] ) ) . '</div>';
		}

		if ( ! empty( $result['memo'] ) ) {
			$html .= '<div class="jwp-a11y-memo"><em>' . esc_html( (string) $result['memo'] ) . '</em></div>';
		}

		if ( ! empty( $criterion_data['doc'] ) ) {
			$html .= '<details class="jwp-a11y-guidance">';
			$html .= '<summary>' . esc_html__( 'About this issue', 'jwp_a11y' ) . '</summary>';
			$html .= wp_kses_post( wpautop( (string) $criterion_data['doc'] ) );
			$html .= '</details>';
		}

		$html .= '</li>';

		return $html;
	}

	private static function renderLegacyStyleResultRow( $criterion, $criterion_data, $result ) {
		$state  = intval( $result['result'] ?? 0 );
		$exists = $state === 1 ? __( 'Not applicable', 'jwp_a11y' ) : __( 'Applicable', 'jwp_a11y' );
		$pass   = $state > 0 ? __( 'Pass', 'jwp_a11y' ) : ( $state < 0 ? '-' : __( 'Fail', 'jwp_a11y' ) );
		$memo   = isset( $result['memo'] ) ? (string) $result['memo'] : '';
		$level  = self::criterionLevelLabel( $criterion_data );
		$label  = $criterion . ' ' . ( $criterion_data['name'] ?? $criterion );
		$class  = $state === 0 ? ' class="a11yc_not_passed"' : '';

		$html  = '';
		$html .= '<tr' . $class . '>';
		$html .= '<th scope="row" class="a11yc_result a11yc_result_string">' . esc_html( $label ) . '</th>';
		$html .= '<td class="a11yc_result a11yc_level">' . esc_html( $level ) . '</td>';
		$html .= '<td class="a11yc_result a11yc_result_exist">' . esc_html( $exists ) . '</td>';
		$html .= '<td class="a11yc_result a11yc_result_exist">' . esc_html( $pass ) . '</td>';
		$html .= '<td class="a11yc_result">' . nl2br( esc_html( $memo ) ) . '</td>';
		$html .= '</tr>';

		return $html;
	}

	private static function formatLegacyLevel( $level ) {
		$level = intval( $level );
		if ( $level <= 0 ) {
			return __( 'Not available', 'jwp_a11y' );
		}

		$map = array(
			1 => 'A',
			2 => 'AA',
			3 => 'AAA',
		);

		return $map[ $level ] ?? (string) $level;
	}

	private static function legacySiteLevel( $pages ) {
		$levels = array();
		foreach ( $pages as $page ) {
			$level = self::legacyPageLevel( $page );
			if ( $level > 0 ) {
				$levels[] = $level;
			}
		}

		return empty( $levels ) ? 0 : min( $levels );
	}

	private static function loadLegacyPageResult( $page, $version = null ) {
		foreach ( self::legacyPageDataUrls( $page ) as $url ) {
			$data = self::loadLegacyResultData( $url, $version );
			if ( ! empty( $data ) ) {
				return $data;
			}
		}

		return array();
	}

	private static function legacyPageDataUrls( $page ) {
		if ( ! is_array( $page ) ) {
			return array();
		}

		$urls = array();
		if ( ! empty( $page['alt_url'] ) ) {
			$urls[] = (string) $page['alt_url'];
		}
		if ( ! empty( $page['url'] ) ) {
			$urls[] = (string) $page['url'];
		}

		return array_values( array_unique( array_filter( $urls ) ) );
	}

	private static function legacyPageLevel( $page, $version = null ) {
		$level = intval( $page['level'] ?? 0 );
		if ( $level > 0 ) {
			return $level;
		}

		$data = self::loadLegacyPageResult( $page, $version );
		if ( empty( $data['result'] ) || ! is_array( $data['result'] ) ) {
			return 0;
		}

		return self::deriveLegacyLevelFromResults( $data['result'] );
	}

	private static function deriveLegacyLevelFromResults( $results ) {
		$yml   = \Jidaikobo\A11yc\Yaml::fetch();
		$level = 3;

		foreach ( $results as $criterion => $result ) {
			if ( intval( $result['result'] ?? 0 ) !== 0 ) {
				continue;
			}

			$criterion_level = (string) ( $yml['criterions'][ $criterion ]['level'] ?? '' );
			if ( $criterion_level === 'A' ) {
				return 0;
			}

			if ( $criterion_level === 'AA' ) {
				$level = min( $level, 1 );
				continue;
			}

			if ( $criterion_level === 'AAA' ) {
				$level = min( $level, 2 );
			}
		}

		return $level;
	}

	private static function legacyConformanceLabel( $results ) {
		if ( ! is_array( $results ) || empty( $results ) ) {
			return __( 'Not available', 'jwp_a11y' );
		}

		$yml           = \Jidaikobo\A11yc\Yaml::fetch();
		$failed_levels = array();

		foreach ( $results as $criterion => $result ) {
			if ( intval( $result['result'] ?? 0 ) !== 0 ) {
				continue;
			}

			$key            = isset( $yml['criterions'][ $criterion ] ) ? $criterion : str_replace( '.', '-', (string) $criterion );
			$criterion_data = $yml['criterions'][ $key ] ?? array();
			$level          = self::criterionLevelLabel( $criterion_data );
			if ( $level !== '' ) {
				$failed_levels[ $level ] = true;
			}
		}

		if ( isset( $failed_levels['A'] ) ) {
			return __( 'A, partially conforming', 'jwp_a11y' );
		}

		if ( isset( $failed_levels['AA'] ) ) {
			return __( 'A conforming / AA partially conforming', 'jwp_a11y' );
		}

		if ( isset( $failed_levels['AAA'] ) ) {
			return __( 'AA conforming', 'jwp_a11y' );
		}

		return __( 'AA conforming', 'jwp_a11y' );
	}

	private static function shouldDisplayCriterion( $criterion_data, $target_level ) {
		if ( ! is_array( $criterion_data ) || empty( $criterion_data ) ) {
			return true;
		}

		if ( $target_level <= 0 ) {
			return true;
		}

		$level = self::criterionLevelLabel( $criterion_data );
		if ( $level === '' ) {
			return true;
		}

		return strlen( $level ) <= $target_level;
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
}
