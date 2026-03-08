<?php

namespace JwpA11y;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EditorNotices {

	/**
	 * Renders classic editor notices after a save redirect.
	 *
	 * @return void
	 */
	public static function renderEditScreenNotice() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'post' ) {
			return;
		}

		if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
			return;
		}

		$post_id = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This reads the current admin screen context only.
		if ( isset( $_GET['post'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This reads the current admin screen context only.
			$post_id = intval( wp_unslash( $_GET['post'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the classic editor post ID after the save redirect.
		} elseif ( isset( $_POST['post_ID'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the classic editor post ID after the save redirect.
			$post_id = intval( wp_unslash( $_POST['post_ID'] ) );
		}

		if ( ! $post_id ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These query arguments indicate a completed classic editor save redirect.
		if ( ! isset( $_GET['message'] ) && ! isset( $_GET['updated'] ) ) {
			return;
		}

		$payload = self::consumePendingNotice( $post_id );
		if ( is_null( $payload ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The method returns plugin-generated notice HTML with escaped dynamic values.
		echo self::buildNoticeHtmlFromPayload( $payload );
	}

	private static function storedAnalysis( $post_id ) {
		$value = get_post_meta( $post_id, Plugin::metaKey(), true );
		return is_array( $value ) ? $value : null;
	}

	private static function buildNoticeHtml( $result ) {
		return self::buildNoticeHtmlFromPayload( self::buildNoticePayload( 0, $result ) );
	}

	private static function buildNoticeHtmlFromPayload( $payload ) {
		$error_html   = isset( $payload['errorHtml'] ) ? (string) $payload['errorHtml'] : '';
		$notice_html  = isset( $payload['noticeHtml'] ) ? (string) $payload['noticeHtml'] : '';
		$success_html = isset( $payload['successHtml'] ) ? (string) $payload['successHtml'] : '';

		if ( $error_html === '' && $notice_html === '' && $success_html === '' ) {
			return '';
		}

		$html = '';

		if ( $error_html !== '' ) {
			$html .= '<div class="notice notice-error is-dismissible"><div>';
			$html .= $error_html;
			$html .= '</div></div>';
		}

		if ( $notice_html !== '' ) {
			$html .= '<div class="notice notice-warning is-dismissible"><div>';
			$html .= $notice_html;
			$html .= '</div></div>';
		}

		if ( $success_html !== '' ) {
			$html .= '<div class="notice notice-success is-dismissible"><div>';
			$html .= $success_html;
			$html .= '</div></div>';
		}

		return $html;
	}

	/**
	 * Enqueues the block editor notice integration script.
	 *
	 * @return void
	 */
	public static function enqueueBlockEditorNotice() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->base !== 'post' ) {
			return;
		}

		$post_id = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This reads the current admin screen context only.
		if ( isset( $_GET['post'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This reads the current admin screen context only.
			$post_id = intval( wp_unslash( $_GET['post'] ) );
		}
		if ( ! $post_id ) {
			return;
		}

		$script  = '';
		$script .= '(function(){';
		$script .= 'if(!window.wp||!wp.data||!wp.data.subscribe||!wp.data.select||!wp.data.dispatch){return;}';
		$script .= 'var postId=' . intval( $post_id ) . ';';
		$script .= 'var wasSaving=false;';
		$script .= 'var wasAutosaving=false;';
		$script .= 'var nonce=' . wp_json_encode( wp_create_nonce( 'jwp_a11y_notice' ) ) . ';';
		$script .= 'var requestNotice=function(){';
		$script .= 'var url=new URL(ajaxurl, window.location.origin);';
		$script .= 'url.searchParams.set("action","jwp_a11y_notice");';
		$script .= 'url.searchParams.set("post_id", String(postId));';
		$script .= 'url.searchParams.set("_ajax_nonce", nonce);';
		$script .= 'window.fetch(url.toString(), {credentials:"same-origin"})';
		$script .= '.then(function(response){ return response.json(); })';
		$script .= '.then(function(payload){';
		$script .= 'if(!payload||!payload.success||!payload.data){return;}';
		$script .= 'payload=payload.data;';
		$script .= 'var errorHtml=payload.errorHtml||"";';
		$script .= 'wp.data.dispatch("core/notices").removeNotice("jwp-a11y-result-errors");';
		$script .= 'wp.data.dispatch("core/notices").removeNotice("jwp-a11y-result-notices");';
		$script .= 'wp.data.dispatch("core/notices").removeNotice("jwp-a11y-result-success");';
		$script .= 'if(errorHtml){';
		$script .= 'wp.data.dispatch("core/notices").createNotice("error", errorHtml, {id:"jwp-a11y-result-errors", isDismissible:true, __unstableHTML:true});';
		$script .= '}';
		$script .= 'if(payload.noticeHtml){';
		$script .= 'wp.data.dispatch("core/notices").createNotice("warning", payload.noticeHtml, {id:"jwp-a11y-result-notices", isDismissible:true, __unstableHTML:true});';
		$script .= '}';
		$script .= 'if(payload.successMessage){';
		$script .= 'wp.data.dispatch("core/notices").createNotice("success", payload.successMessage, {id:"jwp-a11y-result-success", isDismissible:true});';
		$script .= '}';
		$script .= '})';
		$script .= '.catch(function(){});';
		$script .= '};';
		$script .= 'wp.data.subscribe(function(){';
		$script .= 'var editorSelect = wp.data.select("core/editor");';
		$script .= 'if(!editorSelect){return;}';
		$script .= 'var isSaving = !!editorSelect.isSavingPost();';
		$script .= 'var isAutosaving = !!editorSelect.isAutosavingPost();';
		$script .= 'if(wasSaving && !isSaving && !wasAutosaving){requestNotice();}';
		$script .= 'wasSaving = isSaving;';
		$script .= 'wasAutosaving = isAutosaving;';
		$script .= '});';
		$script .= '})();';

		wp_add_inline_script( 'wp-data', $script, 'after' );
	}

	/**
	 * Prints the inline script used by suppress buttons in classic notices.
	 *
	 * @return void
	 */
	public static function printSuppressNoticeScript() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->base !== 'post' ) {
			return;
		}

		echo '<script>';
		echo '(function(){';
		echo 'if(typeof window.fetch!=="function"||typeof window.FormData!=="function"){return;}';
		echo 'var nonce=' . wp_json_encode( wp_create_nonce( 'jwp_a11y_suppress_notice' ) ) . ';';
		echo 'document.addEventListener("click", function(event){';
		echo 'var button=event.target&&event.target.closest?event.target.closest(".jwp-a11y-suppress-notice"):null;';
		echo 'if(!button){return;}';
		echo 'event.preventDefault();';
		echo 'if(button.disabled){return;}';
		echo 'button.disabled=true;';
		echo 'var body=new FormData();';
		echo 'body.set("_ajax_nonce", nonce);';
		echo 'body.set("action","jwp_a11y_suppress_notice");';
		echo 'body.set("post_id", button.getAttribute("data-post-id")||"0");';
		echo 'body.set("issue_key", button.getAttribute("data-issue-key")||"");';
		echo 'window.fetch(ajaxurl, {method:"POST", credentials:"same-origin", body:body})';
		echo '.then(function(response){ return response.json(); })';
		echo '.then(function(payload){';
		echo 'if(!payload||!payload.success){ button.disabled=false; return; }';
		echo 'var item = button.closest("li");';
		echo 'var notice = button.closest(".notice.notice-warning");';
		echo 'if(item){';
		echo 'item.remove();';
		echo 'if(notice){';
		echo 'var remain=notice.querySelectorAll("li").length;';
		echo 'if(remain===0){ notice.remove(); }';
		echo '}';
		echo 'return;';
		echo '}';
		echo 'button.remove();';
		echo 'if(notice){';
		echo 'var remainAfter=notice.querySelectorAll("li").length;';
		echo 'if(remainAfter===0){ notice.remove(); }';
		echo '}';
		echo '})';
		echo '.catch(function(){ button.disabled=false; });';
		echo '});';
		echo '})();';
		echo '</script>';
	}

	/**
	 * Consumes the pending notice payload via AJAX.
	 *
	 * @return void
	 */
	public static function ajaxConsumeNotice() {
		check_ajax_referer( 'jwp_a11y_notice' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( wp_unslash( $_GET['post_id'] ) ) : 0;
		if ( $post_id <= 0 ) {
			wp_send_json_success( array() );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array(), 403 );
		}

		$payload = self::consumePendingNotice( $post_id );
		if ( ! is_array( $payload ) ) {
			$result  = self::storedAnalysis( $post_id );
			$payload = is_array( $result ) ? self::buildNoticePayloadInSiteLocale( $post_id, $result ) : array();
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Suppresses a notice-class issue for the current user.
	 *
	 * @return void
	 */
	public static function ajaxSuppressNotice() {
		check_ajax_referer( 'jwp_a11y_suppress_notice' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(), 403 );
		}

		$user_id   = get_current_user_id();
		$post_id   = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		$issue_key = isset( $_POST['issue_key'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_key'] ) ) : '';
		if ( ! $user_id || $post_id <= 0 || $issue_key === '' ) {
			wp_send_json_error( array(), 400 );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array(), 403 );
		}

		set_transient( self::suppressionTransientKey( $user_id, $post_id, $issue_key ), 1, MONTH_IN_SECONDS );
		wp_send_json_success( array() );
	}

	/**
	 * Builds a normalized notice payload from an analysis result.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $result  Analyzer result.
	 * @return array<string, mixed>
	 */
	private static function buildNoticePayload( $post_id, $result ) {
		$summary = isset( $result['summary'] ) && is_array( $result['summary'] ) ? $result['summary'] : array();
		$issues  = self::splitIssues( $result );
		if ( $post_id > 0 ) {
			$issues['notices'] = array_values(
				array_filter(
					$issues['notices'],
					function ( $issue ) use ( $post_id ) {
						return ! static::isIssueSuppressed( $post_id, $issue );
					}
				)
			);
		}
		$error_count  = intval( $summary['error_count'] ?? 0 );
		$notice_count = count( $issues['notices'] );

		return array(
			'errorCount'     => $error_count,
			'noticeCount'    => $notice_count,
			'errorHtml'      => self::buildNoticeIssueHtml(
				__( 'Accessibility issues were detected', 'jwp-a11y' ),
				$issues['errors'],
				false,
				$post_id
			),
			'noticeHtml'     => self::buildNoticeIssueHtml(
				__( 'There may be accessibility issues', 'jwp-a11y' ),
				$issues['notices'],
				true,
				$post_id
			),
			'successHtml'    => ( $error_count === 0 && $notice_count === 0 )
				? self::buildSuccessNoticeHtml()
				: '',
			'successMessage' => ( $error_count === 0 && $notice_count === 0 )
				? __( 'No accessibility issues were detected', 'jwp-a11y' )
				: '',
		);
	}

	/**
	 * Builds notice payload in the site locale and ensures plugin translations are loaded.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $result  Analyzer result.
	 * @return array<string, mixed>
	 */
	private static function buildNoticePayloadInSiteLocale( $post_id, $result ) {
		$site_locale = (string) get_locale();
		$switched    = false;

		if ( function_exists( 'switch_to_locale' ) && function_exists( 'restore_previous_locale' ) && $site_locale !== '' ) {
			$current_locale = function_exists( 'determine_locale' ) ? (string) determine_locale() : $site_locale;
			if ( $current_locale !== $site_locale ) {
				$switched = switch_to_locale( $site_locale );
			}
		}

		self::ensureTextdomainForLocale( $site_locale );
		$payload = self::buildNoticePayload( $post_id, $result );

		if ( $switched && function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		}

		return $payload;
	}

	/**
	 * Loads the plugin translation file for the requested locale from bundled languages.
	 *
	 * @param string $locale Locale to load.
	 * @return void
	 */
	private static function ensureTextdomainForLocale( $locale ) {
		if ( $locale === '' || ! function_exists( 'load_textdomain' ) ) {
			return;
		}

		$domain   = 'jwp-a11y';
		$lang_dir = dirname( __DIR__ ) . '/languages/';
		$files    = array( $lang_dir . $domain . '-' . $locale . '.mo' );

		if ( false !== strpos( $locale, '_' ) ) {
			$parts = explode( '_', $locale );
			if ( ! empty( $parts[0] ) ) {
				$files[] = $lang_dir . $domain . '-' . $parts[0] . '.mo';
			}
		}

		foreach ( $files as $mo_file ) {
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

	private static function splitIssues( $result ) {
		$issues = isset( $result['issues'] ) && is_array( $result['issues'] ) ? $result['issues'] : array();
		$ret    = array(
			'errors'  => array(),
			'notices' => array(),
		);

		foreach ( $issues as $issue ) {
			if ( ! is_array( $issue ) ) {
				continue;
			}

			$type = isset( $issue['type'] ) ? (string) $issue['type'] : 'error';
			if ( $type === 'notice' ) {
				$ret['notices'][] = $issue;
				continue;
			}

			$ret['errors'][] = $issue;
		}

		return $ret;
	}

	private static function issueDocUrl( $issue ) {
		if ( ! is_array( $issue ) ) {
			return '';
		}

		$criterion_keys = isset( $issue['criterion_keys'] ) && is_array( $issue['criterion_keys'] )
			? $issue['criterion_keys']
			: array();
		if ( empty( $criterion_keys ) ) {
			return '';
		}

		$criterion = (string) reset( $criterion_keys );
		if ( $criterion === '' ) {
			return '';
		}

		return add_query_arg( 'criterion', rawurlencode( $criterion ), \JwpA11y\DocsPage::docsPageUrl() );
	}

	private static function issueSnippet( $issue ) {
		if ( ! is_array( $issue ) ) {
			return '';
		}

		$snippet = isset( $issue['snippet'] ) ? trim( (string) $issue['snippet'] ) : '';
		if ( $snippet !== '' ) {
			return trim( (string) preg_replace( '/\s+/', ' ', $snippet ) );
		}

		$place_id = isset( $issue['place_id'] ) ? trim( (string) $issue['place_id'] ) : '';
		if ( $place_id !== '' ) {
			return $place_id;
		}

		return '';
	}

	/**
	 * Builds the HTML for one notice section.
	 *
	 * @param string            $heading        Section heading.
	 * @param array<int, array> $issues         Issues to render.
	 * @param bool              $allow_suppress Whether suppress controls are shown.
	 * @param int               $post_id        Post ID.
	 * @return string
	 */
	private static function buildNoticeIssueHtml( $heading, $issues, $allow_suppress = false, $post_id = 0 ) {
		if ( empty( $issues ) ) {
			return '';
		}

		$html  = '';
		$html .= '<p><strong>' . esc_html( (string) $heading ) . '</strong></p>';
		$html .= '<ul style="margin-left:1.2em; list-style:disc;">';
		foreach ( array_slice( $issues, 0, 5 ) as $issue ) {
			$message = isset( $issue['message'] ) ? (string) $issue['message'] : '';
			if ( $message === '' ) {
				continue;
			}

			$html   .= '<li>' . wp_kses_post( $message );
			$doc_url = self::issueDocUrl( $issue );
			if ( $doc_url !== '' ) {
				$html .= ' ' . self::buildNoticeDocLinkHtml( $doc_url );
			}
			$snippet = self::issueSnippet( $issue );
			if ( $snippet !== '' ) {
				$html .= self::buildSnippetDetailsHtml( $snippet, $allow_suppress ? self::buildSuppressButtonHtml( $post_id, $issue ) : '' );
			}
			$html .= '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	private static function buildNoticeDocLinkHtml( $url ) {
		$html  = '';
		$html .= '<a href="' . esc_url( (string) $url ) . '" target="jwp-a11y-text" rel="noopener">';
		$html .= esc_html( __( 'About this issue', 'jwp-a11y' ) );
		$html .= ' <span class="dashicons dashicons-external" aria-hidden="true" style="text-decoration:none;"></span>';
		$html .= '<span class="screen-reader-text">' . esc_html( __( 'Opens in another tab', 'jwp-a11y' ) ) . '</span>';
		$html .= '</a>';

		return $html;
	}

	private static function buildSuccessNoticeHtml() {
		return '<p><strong>' . esc_html( __( 'No accessibility issues were detected', 'jwp-a11y' ) ) . '</strong></p>';
	}

	private static function buildSnippetDetailsHtml( $snippet, $extra_html = '' ) {
		$snippet = trim( (string) $snippet );
		if ( $snippet === '' && $extra_html === '' ) {
			return '';
		}

		$html  = '';
		$html .= '<details style="margin-top:0.35em;">';
		$html .= '<summary>' . esc_html( __( 'Show the affected markup', 'jwp-a11y' ) ) . '</summary>';
		if ( $snippet !== '' ) {
			$html .= '<div><code>' . esc_html( $snippet ) . '</code></div>';
		}
		if ( $extra_html !== '' ) {
			$html .= $extra_html;
		}
		$html .= '</details>';

		return $html;
	}

	private static function buildSuppressButtonHtml( $post_id, $issue ) {
		if ( $post_id <= 0 ) {
			return '';
		}

		$issue_key = self::issueSuppressionKey( $issue );
		if ( $issue_key === '' ) {
			return '';
		}

		$html  = '';
		$html .= '<p style="margin:0.5em 0 0;">';
		$html .= '<button type="button" class="button-link jwp-a11y-suppress-notice" data-post-id="' . intval( $post_id ) . '" data-issue-key="' . esc_attr( $issue_key ) . '">';
		$html .= esc_html( __( 'Hide this temporarily because it is not an issue', 'jwp-a11y' ) );
		$html .= '</button>';
		$html .= '</p>';

		return $html;
	}

	/**
	 * Stores the pending notice payload for the next editor load.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $result  Analyzer result.
	 * @return void
	 */
	public static function storePendingNotice( $post_id, $result ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		update_user_meta(
			$user_id,
			self::pendingNoticeMetaKey( $post_id ),
			array(
				'result' => $result,
			)
		);
	}

	private static function consumePendingNotice( $post_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}

		$key     = self::pendingNoticeMetaKey( $post_id );
		$payload = get_user_meta( $user_id, $key, true );
		delete_user_meta( $user_id, $key );

		if ( ! is_array( $payload ) ) {
			return null;
		}

		if ( isset( $payload['result'] ) && is_array( $payload['result'] ) ) {
			return self::buildNoticePayloadInSiteLocale( $post_id, $payload['result'] );
		}

		if ( isset( $payload['summary'] ) || isset( $payload['issues'] ) ) {
			return self::buildNoticePayloadInSiteLocale( $post_id, $payload );
		}

		if ( isset( $payload['errorHtml'] ) || isset( $payload['noticeHtml'] ) || isset( $payload['successMessage'] ) ) {
			return $payload;
		}

		return null;
	}

	private static function pendingNoticeMetaKey( $post_id ) {
		return '_jwp_a11y_notice_' . $post_id;
	}

	private static function issueSuppressionKey( $issue ) {
		if ( ! is_array( $issue ) ) {
			return '';
		}

		$parts = array(
			(string) ( $issue['id'] ?? '' ),
			self::issueSnippet( $issue ),
			(string) ( $issue['place_id'] ?? '' ),
			(string) ( $issue['message'] ?? '' ),
		);

		return md5( implode( '|', $parts ) );
	}

	private static function isIssueSuppressed( $post_id, $issue ) {
		$user_id = get_current_user_id();
		if ( ! $user_id || $post_id <= 0 ) {
			return false;
		}

		$issue_key = self::issueSuppressionKey( $issue );
		if ( $issue_key === '' ) {
			return false;
		}

		return false !== get_transient( self::suppressionTransientKey( $user_id, $post_id, $issue_key ) );
	}

	private static function suppressionTransientKey( $user_id, $post_id, $issue_key ) {
		return 'jwp_a11y_sup_' . intval( $user_id ) . '_' . intval( $post_id ) . '_' . $issue_key;
	}
}
