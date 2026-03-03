<?php

namespace JwpA11y;

if (! defined('ABSPATH'))
{
	exit;
}

final class Plugin
{
	private const META_KEY = '_jwp_a11y_analysis';
	private const LEGACY_GROUP_ID = 1;
	private static $analyzing_posts = array();

	public static function init()
	{
		if (! static::loadAutoloader())
		{
			add_action('admin_notices', array(__CLASS__, 'renderMissingAutoloaderNotice'));
			return;
		}

		static::defineCompatibilityConstants();

		add_action('save_post', array(__CLASS__, 'analyzePostOnSave'), 20, 3);
		add_action('wp_after_insert_post', array(__CLASS__, 'analyzePostAfterInsert'), 20, 3);
		add_action('admin_notices', array(__CLASS__, 'renderEditScreenNotice'));
		add_action('admin_print_footer_scripts', array(__CLASS__, 'printSuppressNoticeScript'));
		add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueueBlockEditorNotice'));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueueFrontendStyles'));
		add_action('wp_ajax_jwp_a11y_notice', array(__CLASS__, 'ajaxConsumeNotice'));
		add_action('wp_ajax_jwp_a11y_suppress_notice', array(__CLASS__, 'ajaxSuppressNotice'));
		add_shortcode('jwp_a11y_results', array(__CLASS__, 'renderResultsShortcode'));
		add_shortcode('jwp_a11y_doc', array(__CLASS__, 'renderDocShortcode'));
		add_shortcode('jwp_a11y_docs', array(__CLASS__, 'renderDocShortcode'));
		add_action('admin_menu', array(__CLASS__, 'registerAdminPage'));
	}

	private static function loadAutoloader()
	{
		$plugin_dir = dirname(__DIR__);
		$autoloaders = array($plugin_dir.'/vendor/autoload.php');

		foreach ($autoloaders as $autoload)
		{
			if (! file_exists($autoload))
			{
				continue;
			}

			require_once $autoload;

			if (class_exists('\\Jidaikobo\\A11yc\\Analyzer'))
			{
				return true;
			}
		}

		return false;
	}

	public static function renderMissingAutoloaderNotice()
	{
		if (! current_user_can('manage_options'))
		{
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__('jwp-a11y could not load jidaikobo/a11yc. Run composer install in the plugin directory or keep jwp-a11y enabled as a temporary fallback.', 'jwp_a11y');
		echo '</p></div>';
	}

	public static function enqueueFrontendStyles()
	{
		if (is_admin())
		{
			return;
		}

		$css_file = dirname(__DIR__).'/assets/css/frontend.css';
		if (! file_exists($css_file))
		{
			return;
		}

		wp_enqueue_style(
			'jwp-a11y-frontend',
			plugins_url('assets/css/frontend.css', dirname(__DIR__).'/jwp-a11y.php'),
			array(),
			filemtime($css_file)
		);
	}

	private static function defineCompatibilityConstants()
	{
		$lang = static::currentLanguage();

		if (! defined('A11YC_LANG'))
		{
			define('A11YC_LANG', $lang);
		}

		if (! defined('A11YC_LANG_HERE'))
		{
			define(
				'A11YC_LANG_HERE',
				$lang === 'ja' ? 'こちら,ここ,ここをクリック,コチラ' : 'here, click here, click'
			);
		}

		if (! defined('A11YC_LANG_IMAGE'))
		{
			define('A11YC_LANG_IMAGE', $lang === 'ja' ? '画像' : 'Image');
		}

		if (! defined('A11YC_LANG_COUNT_ITEMS'))
		{
			define('A11YC_LANG_COUNT_ITEMS', $lang === 'ja' ? '%s件' : '%s items');
		}
	}

	public static function analyzePostOnSave($post_id, $post, $update)
	{
		unset($update);

		static::analyzePost($post_id, $post);
	}

	public static function analyzePostAfterInsert($post_id, $post, $update)
	{
		unset($update);

		static::analyzePost($post_id, $post);
	}

	private static function analyzePost($post_id, $post)
	{
		if (! $post instanceof \WP_Post)
		{
			return;
		}

		if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))
		{
			return;
		}

		if ($post->post_status === 'auto-draft')
		{
			return;
		}

		if (isset(static::$analyzing_posts[$post_id]))
		{
			return;
		}

		static::$analyzing_posts[$post_id] = true;

		$content = static::buildPostContent($post_id, $post);
		if ($content === '')
		{
			delete_post_meta($post_id, self::META_KEY);
			unset(static::$analyzing_posts[$post_id]);
			return;
		}

		$analyzer = new \Jidaikobo\A11yc\Analyzer();
		$result = $analyzer->analyzeHtml(
			static::buildAnalysisDocument($content, $post),
			array(
				'url' => static::postUrl($post_id),
				'lang' => static::currentLanguage(),
			)
		);

		$result['analyzed_at'] = current_time('mysql');
		update_post_meta($post_id, self::META_KEY, $result);
		static::storePendingNotice($post_id, $result);
		unset(static::$analyzing_posts[$post_id]);
	}

	private static function buildPostContent($post_id, \WP_Post $post)
	{
		$meta_values = '';

		foreach (get_post_meta($post_id) as $meta_key => $meta_value)
		{
			if (! isset($meta_key[0]) || $meta_key[0] === '_')
			{
				continue;
			}

			if ($meta_key === 'dashi_search')
			{
				continue;
			}

			$meta_values .= isset($meta_value[0]) ? wp_specialchars_decode((string) $meta_value[0]) : '';
		}

		return apply_filters('the_content', $post->post_content).$meta_values;
	}

	private static function buildAnalysisDocument($content, \WP_Post $post)
	{
		$lang = static::currentLanguage();
		$title = get_the_title($post);
		$title = is_string($title) && $title !== '' ? $title : __('Untitled', 'jwp_a11y');

		return '<!doctype html><html lang="'.esc_attr($lang).'"><head><meta charset="utf-8"><title>'.
			esc_html($title).
			'</title></head><body>'.$content.'</body></html>';
	}

	private static function postUrl($post_id)
	{
		$url = get_permalink($post_id);
		if (is_string($url) && $url !== '')
		{
			return $url;
		}

		return home_url('/?p='.$post_id);
	}

	private static function currentLanguage()
	{
		$locale = function_exists('determine_locale') ? determine_locale() : get_locale();
		return strpos((string) $locale, 'ja') === 0 ? 'ja' : 'en';
	}

	private static function label($ja, $en)
	{
		unset($en);
		return __($ja, 'jwp_a11y');
	}

	public static function renderEditScreenNotice()
	{
		if (! is_admin() || ! function_exists('get_current_screen'))
		{
			return;
		}

		$screen = get_current_screen();
		if (! $screen || $screen->base !== 'post')
		{
			return;
		}

		if (method_exists($screen, 'is_block_editor') && $screen->is_block_editor())
		{
			return;
		}

		$post_id = 0;
		if (isset($_GET['post']))
		{
			$post_id = intval(wp_unslash($_GET['post']));
		}
		elseif (isset($_POST['post_ID']))
		{
			$post_id = intval(wp_unslash($_POST['post_ID']));
		}

		if (! $post_id || ! current_user_can('edit_post', $post_id))
		{
			return;
		}

		if (! isset($_GET['message']) && ! isset($_GET['updated']))
		{
			return;
		}

		$payload = static::consumePendingNotice($post_id);
		if (! is_array($payload))
		{
			return;
		}

		echo static::buildNoticeHtmlFromPayload($payload);
	}

	private static function storedAnalysis($post_id)
	{
		$result = get_post_meta($post_id, self::META_KEY, true);
		return is_array($result) ? $result : null;
	}

	private static function buildNoticeHtml($result)
	{
		return static::buildNoticeHtmlFromPayload(static::buildNoticePayload(0, $result));
	}

	private static function buildNoticeHtmlFromPayload($payload)
	{
		$error_count = intval($payload['errorCount'] ?? 0);
		$notice_count = intval($payload['noticeCount'] ?? 0);
		$error_html = isset($payload['errorHtml']) ? (string) $payload['errorHtml'] : '';
		$notice_html = isset($payload['noticeHtml']) ? (string) $payload['noticeHtml'] : '';
		$success_html = isset($payload['successHtml']) ? (string) $payload['successHtml'] : '';
		if ($error_html === '' && $notice_html === '' && $success_html === '')
		{
			return '';
		}
		$notice_class = 'notice-success';
		if ($error_count > 0)
		{
			$notice_class = 'notice-error';
		}
		elseif ($notice_count > 0)
		{
			$notice_class = 'notice-warning';
		}

		$html = '';
		$html .= '<div class="notice '.$notice_class.'">';

		if ($error_html !== '')
		{
			$html .= $error_html;
		}

		if ($notice_html !== '')
		{
			$html .= $notice_html;
		}

		if ($error_html === '' && $notice_html === '' && $success_html !== '')
		{
			$html .= $success_html;
		}

		$html .= '</div>';

		return $html;
	}

	public static function enqueueBlockEditorNotice()
	{
		if (! function_exists('get_current_screen'))
		{
			return;
		}

		$screen = get_current_screen();
		if (! $screen || $screen->base !== 'post')
		{
			return;
		}

		if (! method_exists($screen, 'is_block_editor') || ! $screen->is_block_editor())
		{
			return;
		}

		global $post;
		if (! $post instanceof \WP_Post)
		{
			return;
		}

		$script = '(function(){'.
			'if(!window.wp||!wp.data||!wp.data.subscribe||!wp.data.select||!wp.data.dispatch){return;}'.
			'var wasSaving=false;'.
			'var wasAutosaving=false;'.
			'var postId='.intval($post->ID).';'.
			'var ajaxUrl='.wp_json_encode(admin_url('admin-ajax.php')).';'.
			'var nonce='.wp_json_encode(wp_create_nonce('jwp_a11y_notice')).';'.
			'var fetchNotice=function(){'.
				'var url=new URL(ajaxUrl, window.location.origin);'.
				'url.searchParams.set("action","jwp_a11y_notice");'.
				'url.searchParams.set("post_id", String(postId));'.
				'url.searchParams.set("_ajax_nonce", nonce);'.
				'window.fetch(url.toString(), {credentials:"same-origin"})'.
					'.then(function(res){return res.json();})'.
					'.then(function(res){'.
						'if(!res||!res.success||!res.data){return;}'.
						'var payload=res.data;'.
						'wp.data.dispatch("core/notices").removeNotice("jwp-a11y-result-errors");'.
						'wp.data.dispatch("core/notices").removeNotice("jwp-a11y-result-notices");'.
						'wp.data.dispatch("core/notices").removeNotice("jwp-a11y-result-success");'.
						'if(payload.errorCount || payload.errorHtml){'.
							'var errorHtml=(payload.errorHtml||"");'.
							'wp.data.dispatch("core/notices").createNotice("error", errorHtml, {id:"jwp-a11y-result-errors", isDismissible:true, __unstableHTML:true});'.
						'}'.
						'if(payload.noticeHtml){'.
							'wp.data.dispatch("core/notices").createNotice("warning", payload.noticeHtml, {id:"jwp-a11y-result-notices", isDismissible:true, __unstableHTML:true});'.
						'}'.
						'if(!payload.errorHtml && !payload.noticeHtml && payload.successMessage){'.
							'wp.data.dispatch("core/notices").createNotice("success", payload.successMessage, {id:"jwp-a11y-result-success", isDismissible:true});'.
						'}'.
					'})'.
					'.catch(function(){});'.
			'};'.
			'wp.data.subscribe(function(){'.
				'var editor=wp.data.select("core/editor");'.
				'if(!editor){return;}'.
				'var isSaving=!!editor.isSavingPost();'.
				'var isAutosaving=!!editor.isAutosavingPost();'.
				'if(wasSaving && !isSaving && !wasAutosaving){fetchNotice();}'.
				'wasSaving=isSaving;'.
				'wasAutosaving=isAutosaving;'.
			'});'.
		'})();';

		wp_add_inline_script('wp-edit-post', $script, 'after');
	}

	public static function printSuppressNoticeScript()
	{
		if (! is_admin() || ! function_exists('get_current_screen'))
		{
			return;
		}

		$screen = get_current_screen();
		if (! $screen || $screen->base !== 'post')
		{
			return;
		}

		$script = '(function(){'.
			'if(!window.fetch||!window.document){return;}'.
			'var ajaxUrl=' . wp_json_encode(admin_url('admin-ajax.php')) . ';'.
			'var nonce=' . wp_json_encode(wp_create_nonce('jwp_a11y_suppress_notice')) . ';'.
			'document.addEventListener("click", function(event){'.
				'var button=event.target&&event.target.closest?event.target.closest(".jwp-a11y-suppress-notice"):null;'.
				'if(!button){return;}'.
				'event.preventDefault();'.
				'if(button.disabled){return;}'.
				'var postId=button.getAttribute("data-post-id")||"";'.
				'var issueKey=button.getAttribute("data-issue-key")||"";'.
				'if(!postId||!issueKey){return;}'.
				'button.disabled=true;'.
				'var body=new window.URLSearchParams();'.
				'body.set("action","jwp_a11y_suppress_notice");'.
				'body.set("post_id", postId);'.
				'body.set("issue_key", issueKey);'.
				'body.set("_ajax_nonce", nonce);'.
				'window.fetch(ajaxUrl, {'.
					'method:"POST", credentials:"same-origin", headers:{"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}, body:body.toString()'.
				'})'.
				'.then(function(res){return res.json();})'.
				'.then(function(res){'.
					'if(!res||!res.success){button.disabled=false;return;}'.
					'var item=button.closest("li");'.
					'if(item){item.remove();}'.
					'document.querySelectorAll(".components-notice, .notice").forEach(function(box){'.
						'var lists=box.querySelectorAll("ul");'.
						'lists.forEach(function(list){'.
							'if(list.children.length){return;}'.
							'var prev=list.previousElementSibling;'.
							'if(prev&&prev.tagName==="P"){prev.remove();}'.
							'list.remove();'.
						'});'.
						'if(!box.querySelector("ul")&&!box.textContent.replace(/\\s+/g,"")){box.remove();}'.
					'});'.
				'})'.
				'.catch(function(){button.disabled=false;});'.
			'});'.
		'})();';

		echo '<script>'.$script.'</script>';
	}

	public static function ajaxConsumeNotice()
	{
		check_ajax_referer('jwp_a11y_notice');

		$post_id = isset($_GET['post_id']) ? intval(wp_unslash($_GET['post_id'])) : 0;
		if (! $post_id || ! current_user_can('edit_post', $post_id))
		{
			wp_send_json_error();
		}

		$payload = static::consumePendingNotice($post_id);
		if (! is_array($payload))
		{
			wp_send_json_success(null);
		}

		wp_send_json_success($payload);
	}

	public static function ajaxSuppressNotice()
	{
		check_ajax_referer('jwp_a11y_suppress_notice');

		$post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
		$issue_key = isset($_POST['issue_key']) ? sanitize_text_field(wp_unslash($_POST['issue_key'])) : '';
		if (! $post_id || $issue_key === '' || ! current_user_can('edit_post', $post_id))
		{
			wp_send_json_error();
		}

		$user_id = get_current_user_id();
		if (! $user_id)
		{
			wp_send_json_error();
		}

		set_transient(
			static::suppressionTransientKey($user_id, $post_id, $issue_key),
			1,
			MONTH_IN_SECONDS
		);

		wp_send_json_success();
	}

	private static function buildNoticePayload($post_id, $result)
	{
		$summary = isset($result['summary']) && is_array($result['summary']) ? $result['summary'] : array();
		$issues = static::splitIssues($result);
		if ($post_id > 0)
		{
			$issues['notices'] = array_values(array_filter($issues['notices'], function ($issue) use ($post_id) {
				return ! static::isIssueSuppressed($post_id, $issue);
			}));
		}
		$error_count = intval($summary['error_count'] ?? 0);
		$notice_count = count($issues['notices']);

		return array(
			'errorCount' => $error_count,
			'noticeCount' => $notice_count,
			'errorHtml' => static::buildNoticeIssueHtml(
				static::label(
					'アクセシビリティ上の問題を検出しました',
					'Accessibility issues were detected'
				),
				$issues['errors'],
				false,
				$post_id
			),
			'noticeHtml' => static::buildNoticeIssueHtml(
				static::label(
					'アクセシビリティ上の問題があるかもしれません',
					'Potential accessibility issues may exist'
				),
				$issues['notices'],
				true,
				$post_id
			),
			'successHtml' => ($error_count === 0 && $notice_count === 0)
				? static::buildSuccessNoticeHtml()
				: '',
			'successMessage' => ($error_count === 0 && $notice_count === 0)
				? static::label(
					'アクセシビリティ上の問題は検出されませんでした',
					'No accessibility issues were detected'
				)
				: '',
		);
	}

	private static function splitIssues($result)
	{
		$issues = isset($result['issues']) && is_array($result['issues']) ? $result['issues'] : array();
		$ret = array(
			'errors' => array(),
			'notices' => array(),
		);

		foreach ($issues as $issue)
		{
			if (! is_array($issue))
			{
				continue;
			}

			$type = isset($issue['type']) ? (string) $issue['type'] : 'error';
			if ($type === 'notice')
			{
				$ret['notices'][] = $issue;
				continue;
			}

			$ret['errors'][] = $issue;
		}

		return $ret;
	}

	private static function issueDocUrl($issue)
	{
		if (! is_array($issue))
		{
			return '';
		}

		$criterion_keys = isset($issue['criterion_keys']) && is_array($issue['criterion_keys'])
			? $issue['criterion_keys']
			: array();
		if (empty($criterion_keys))
		{
			return '';
		}

		$criterion = (string) reset($criterion_keys);
		if ($criterion === '')
		{
			return '';
		}

		return add_query_arg('criterion', rawurlencode($criterion), static::docsPageUrl());
	}

	private static function issueSnippet($issue)
	{
		if (! is_array($issue))
		{
			return '';
		}

		$snippet = isset($issue['snippet']) ? trim((string) $issue['snippet']) : '';
		if ($snippet !== '')
		{
			return trim((string) preg_replace('/\s+/', ' ', $snippet));
		}

		$place_id = isset($issue['place_id']) ? trim((string) $issue['place_id']) : '';
		if ($place_id !== '')
		{
			return $place_id;
		}

		return '';
	}

	private static function buildNoticeIssueHtml($heading, $issues, $allow_suppress = false, $post_id = 0)
	{
		if (empty($issues))
		{
			return '';
		}

		$html = '';
		$html .= '<p><strong>'.esc_html((string) $heading).'</strong></p>';
		$html .= '<ul style="margin-left:1.2em; list-style:disc;">';
		foreach (array_slice($issues, 0, 5) as $issue)
		{
			$message = isset($issue['message']) ? (string) $issue['message'] : '';
			if ($message === '')
			{
				continue;
			}

			$html .= '<li>'.wp_kses_post($message);
			$doc_url = static::issueDocUrl($issue);
			if ($doc_url !== '')
			{
				$html .= ' '.static::buildNoticeDocLinkHtml($doc_url);
			}
			$snippet = static::issueSnippet($issue);
			if ($snippet !== '')
			{
				$html .= static::buildSnippetDetailsHtml($snippet, $allow_suppress ? static::buildSuppressButtonHtml($post_id, $issue) : '');
			}
			$html .= '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	private static function buildNoticeDocLinkHtml($url)
	{
		$html = '';
		$html .= '<a href="'.esc_url((string) $url).'" target="jwp-a11y-text" rel="noopener">';
		$html .= esc_html(static::label('この指摘の説明', 'About this issue'));
		$html .= ' <span class="dashicons dashicons-external" aria-hidden="true" style="text-decoration:none;"></span>';
		$html .= '<span class="screen-reader-text">'.esc_html(static::label('別のタブで開きます', 'Opens in another tab')).'</span>';
		$html .= '</a>';

		return $html;
	}

	private static function buildSuccessNoticeHtml()
	{
		return '<p><strong>'.esc_html(static::label(
			'アクセシビリティ上の問題は検出されませんでした',
			'No accessibility issues were detected'
		)).'</strong></p>';
	}

	private static function buildSnippetDetailsHtml($snippet, $extra_html = '')
	{
		$snippet = trim((string) $snippet);
		if ($snippet === '' && $extra_html === '')
		{
			return '';
		}

		$html = '';
		$html .= '<details style="margin-top:0.35em;">';
		$html .= '<summary>'.esc_html(static::label('問題箇所を表示', 'Show the relevant code')).'</summary>';
		if ($snippet !== '')
		{
			$html .= '<div><code>'.esc_html($snippet).'</code></div>';
		}
		if ($extra_html !== '')
		{
			$html .= $extra_html;
		}
		$html .= '</details>';

		return $html;
	}

	private static function buildSuppressButtonHtml($post_id, $issue)
	{
		if ($post_id <= 0)
		{
			return '';
		}

		$issue_key = static::issueSuppressionKey($issue);
		if ($issue_key === '')
		{
			return '';
		}

		$html = '';
		$html .= '<p style="margin:0.5em 0 0;">';
		$html .= '<button type="button" class="button-link jwp-a11y-suppress-notice" data-post-id="'.intval($post_id).'" data-issue-key="'.esc_attr($issue_key).'">';
		$html .= esc_html(static::label('問題はないのでしばらく非表示にする', 'Hide this for a while because it is not an issue'));
		$html .= '</button>';
		$html .= '</p>';

		return $html;
	}

	private static function storePendingNotice($post_id, $result)
	{
		$user_id = get_current_user_id();
		if (! $user_id)
		{
			return;
		}

		update_user_meta($user_id, static::pendingNoticeMetaKey($post_id), static::buildNoticePayload($post_id, $result));
	}

	private static function consumePendingNotice($post_id)
	{
		$user_id = get_current_user_id();
		if (! $user_id)
		{
			return null;
		}

		$key = static::pendingNoticeMetaKey($post_id);
		$payload = get_user_meta($user_id, $key, true);
		delete_user_meta($user_id, $key);

		return is_array($payload) ? $payload : null;
	}

	private static function pendingNoticeMetaKey($post_id)
	{
		return '_jwp_a11y_notice_'.$post_id;
	}

	private static function issueSuppressionKey($issue)
	{
		if (! is_array($issue))
		{
			return '';
		}

		$parts = array(
			(string) ($issue['id'] ?? ''),
			static::issueSnippet($issue),
			(string) ($issue['place_id'] ?? ''),
			(string) ($issue['message'] ?? ''),
		);

		return md5(implode('|', $parts));
	}

	private static function isIssueSuppressed($post_id, $issue)
	{
		$user_id = get_current_user_id();
		if (! $user_id || $post_id <= 0)
		{
			return false;
		}

		$issue_key = static::issueSuppressionKey($issue);
		if ($issue_key === '')
		{
			return false;
		}

		return false !== get_transient(static::suppressionTransientKey($user_id, $post_id, $issue_key));
	}

	private static function suppressionTransientKey($user_id, $post_id, $issue_key)
	{
		return 'jwp_a11y_sup_' . intval($user_id) . '_' . intval($post_id) . '_' . $issue_key;
	}

	public static function renderResultsShortcode($attrs = array())
	{
		return static::renderSimpleResultsShortcode($attrs);
	}

	private static function renderSimpleResultsShortcode($attrs = array())
	{
		unset($attrs);

		$base_url = static::currentContentUrl();
		if ($base_url === '')
		{
			return '';
		}

		$version = static::selectedLegacyVersion();
		$settings = static::loadLegacySettings($version);

		if (isset($_GET['a11yc_each']) && isset($_GET['url']))
		{
			$url = sanitize_text_field(wp_unslash($_GET['url']));
			return static::renderLegacyEachPage($base_url, $url, $version, $settings);
		}

		if (isset($_GET['a11yc_page']))
		{
			return static::renderLegacyPageList($base_url, $version);
		}

		if (isset($_GET['a11yc_report']))
		{
			return static::renderLegacyReportPage($base_url, $version, $settings);
		}

		return static::renderLegacyPolicyPage($base_url, $version, $settings);
	}

	private static function renderLegacyPolicyPage($base_url, $version, $settings)
	{
		$versions = static::loadLegacyVersions();
		$report_link = add_query_arg('a11yc_report', 1, $base_url);

		$html = '';
		$html .= '<div class="jwp-a11y-results">';

		$html .= static::renderLegacyVersionSwitcher($base_url, $version, $versions);

		if (! empty($settings['policy']))
		{
			$html .= '<div class="jwp-a11y-policy">'.wp_kses_post($settings['policy']).'</div>';
		}

		if (! empty($settings['show_results']))
		{
			$html .= '<h2>'.esc_html__('アクセシビリティ報告書', 'jwp_a11y').'</h2>';
			$html .= '<p class="a11yc_link"><a href="'.esc_url($report_link).'">'.
				esc_html__('アクセシビリティ報告書', 'jwp_a11y').
				'</a></p>';
		}

		$html .= '</div>';

		return $html;
	}

	private static function currentContentUrl()
	{
		if (is_singular())
		{
			$post_id = get_queried_object_id();
			if ($post_id)
			{
				return get_permalink($post_id);
			}
		}

		global $post;
		if ($post instanceof \WP_Post)
		{
			return get_permalink($post->ID);
		}

		return '';
	}

	private static function renderLegacyReportPage($base_url, $version, $settings)
	{
		$pages = static::loadLegacyPages($version);
		if (empty($pages))
		{
			return '<p>'.esc_html__('No saved accessibility results were found for this page.', 'jwp_a11y').'</p>';
		}

		$yml = \Jidaikobo\A11yc\Yaml::fetch();
		$results = static::evaluateLegacyTotal($pages, $version);
		$done_pages = array_values(array_filter($pages, function ($page) use ($version) {
			return empty($page['trash']) && ! empty(static::loadLegacyPageResult($page, $version));
		}));
		$total_pages = array_values(array_filter($pages, function ($page) {
			return empty($page['trash']);
		}));
		$target_level = intval($settings['target_level'] ?? 0);
		$current_level = static::legacyConformanceLabel($results);
		$pages_link = add_query_arg('a11yc_page', 1, $base_url);
		$standards = \Jidaikobo\A11yc\Yaml::each('standards');
		$standard_key = $settings['standard'] ?? 0;
		$standard_name = is_array($standards) && array_key_exists($standard_key, $standards)
			? (string) $standards[$standard_key]
			: '';

		$html = '';
		$html .= '<div class="jwp-a11y-results">';
		$html .= static::renderLegacyVersionSwitcher($base_url, $version, static::loadLegacyVersions(), array(
			'a11yc_report' => 1,
		));
		$html .= '<h2>'.esc_html((string) ($settings['title'] ?? __('アクセシビリティ報告書', 'jwp_a11y'))).'</h2>';
		$html .= '<table class="a11yc_table a11yc_table_report"><tbody>';
		if ($standard_name !== '')
		{
			$html .= '<tr><th scope="row">'.esc_html__('規格の規格番号及び改正年', 'jwp_a11y').'</th><td>'.esc_html($standard_name).'</td></tr>';
		}
		$html .= '<tr><th scope="row">'.esc_html__('目標とする適合レベル', 'jwp_a11y').'</th><td>'.esc_html(static::formatLegacyLevel($target_level)).'</td></tr>';
		$html .= '<tr><th scope="row">'.esc_html__('満たしている適合レベル', 'jwp_a11y').'</th><td>'.esc_html($current_level).'</td></tr>';
		if (! empty($settings['dependencies']))
		{
			$html .= '<tr><th scope="row">'.esc_html__('依存したウェブコンテンツ技術のリスト', 'jwp_a11y').'</th><td>'.nl2br(esc_html((string) $settings['dependencies'])).'</td></tr>';
		}
		$html .= '<tr><th scope="row">'.esc_html__('試験を行ったウェブページのURL', 'jwp_a11y').'</th><td><a href="'.esc_url($pages_link).'">'.esc_html__('URLの一覧', 'jwp_a11y').'</a> ('.intval(count($done_pages)).' / '.intval(count($total_pages)).')</td></tr>';
		if (empty($settings['hide_date_results']) && ! empty($settings['test_period']))
		{
			$html .= '<tr><th scope="row">'.esc_html__('試験実施期間', 'jwp_a11y').'</th><td>'.esc_html((string) $settings['test_period']).'</td></tr>';
		}
		if (! empty($settings['contact']))
		{
			$html .= '<tr><th scope="row">'.esc_html__('アクセシビリティに関する連絡先', 'jwp_a11y').'</th><td>'.nl2br(esc_html((string) $settings['contact'])).'</td></tr>';
		}
		$html .= '</tbody></table>';
		if (! empty($settings['report']))
		{
			$html .= '<h2>'.esc_html__('追加の表示事項', 'jwp_a11y').'</h2>';
			$html .= wp_kses_post(wpautop((string) $settings['report']));
		}

		$rows = '';
		foreach ($results as $criterion => $result)
		{
			$criterion_data = $yml['criterions'][$criterion] ?? array();
			if (! static::shouldDisplayCriterion($criterion_data, $target_level))
			{
				continue;
			}
			$rows .= static::renderLegacyStyleResultRow($criterion, $criterion_data, $result);
		}
		if ($rows === '')
		{
			foreach ($results as $criterion => $result)
			{
				$criterion_data = $yml['criterions'][$criterion] ?? array();
				$rows .= static::renderLegacyStyleResultRow($criterion, $criterion_data, $result);
			}
		}

		$html .= '<h2>'.esc_html__('達成基準チェックリスト', 'jwp_a11y').'</h2>';
		$html .= '<table class="a11yc_table"><thead><tr>';
		$html .= '<th scope="col">'.esc_html__('達成基準', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result">'.esc_html__('適合レベル', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">'.esc_html__('対象', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">'.esc_html__('結果', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result">'.esc_html__('備考', 'jwp_a11y').'</th>';
		$html .= '</tr></thead><tbody>';
		$html .= $rows;
		$html .= '</tbody></table>';
		$html .= '</div>';

		return $html;
	}

	private static function renderLegacyVersionSwitcher($base_url, $version, $versions, $extra_args = array())
	{
		if (empty($versions))
		{
			return '';
		}

		$html = '';
		$html .= '<form action="'.esc_url($base_url).'" method="get">';
		$html .= '<div><label for="a11yc_version">'.esc_html__('方針・報告書・試験の版を切り替える', 'jwp_a11y').'</label> ';
		$html .= '<select name="a11yc_version" id="a11yc_version">';
		$html .= '<option value="">'.esc_html__('最新版', 'jwp_a11y').'</option>';
		foreach ($versions as $version_name => $version_row)
		{
			$selected = (string) $version === (string) $version_name ? ' selected="selected"' : '';
			$html .= '<option value="'.esc_attr((string) $version_name).'"'.$selected.'>'.
				esc_html((string) ($version_row['name'] ?? $version_name)).
				'</option>';
		}
		$html .= '</select> ';
		foreach ($extra_args as $key => $value)
		{
			$html .= '<input type="hidden" name="'.esc_attr((string) $key).'" value="'.esc_attr((string) $value).'">';
		}
		$html .= '<button type="submit">'.esc_html__('送信', 'jwp_a11y').'</button>';
		if ((string) $version !== '' && intval($version) !== 0)
		{
			$html .= ' <a href="'.esc_url(add_query_arg($extra_args, $base_url)).'">'.esc_html__('最新版', 'jwp_a11y').'</a>';
		}
		$html .= '</div></form>';

		return $html;
	}

	private static function renderLegacyPageList($base_url, $version)
	{
		$pages = array_values(array_filter(static::loadLegacyPages($version), function ($page) use ($version) {
			return empty($page['trash']) && ! empty(static::loadLegacyPageResult($page, $version));
		}));

		$html = '';
		$html .= '<div class="jwp-a11y-results">';
		$html .= '<h2>'.esc_html__('URLの一覧', 'jwp_a11y').'</h2>';
		$html .= '<table class="a11yc_table"><thead><tr>';
		$html .= '<th scope="col">'.esc_html__('ページ', 'jwp_a11y').'</th>';
		$html .= '<th scope="col">'.esc_html__('ページで達成しているレベル', 'jwp_a11y').'</th>';
		$html .= '<th scope="col">'.esc_html__('試験結果', 'jwp_a11y').'</th>';
		$html .= '</tr></thead><tbody>';

		foreach ($pages as $page)
		{
			$url = (string) ($page['url'] ?? '');
			$title = (string) ($page['title'] ?? $url);
			$link = add_query_arg(
				array(
					'a11yc_each' => 1,
					'url' => rawurlencode($url),
				),
				$base_url
			);

			$html .= '<tr>';
			$html .= '<th scope="row">'.esc_html($title).'</th>';
			$html .= '<td>'.esc_html(static::formatLegacyLevel(static::legacyPageLevel($page, $version))).'</td>';
			$html .= '<td><a href="'.esc_url($link).'">'.esc_html__('試験結果', 'jwp_a11y').'</a></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';
		$html .= '</div>';

		return $html;
	}

	private static function renderLegacyEachPage($base_url, $url, $version, $settings)
	{
		$data = static::loadLegacyResultData($url, $version);
		if (empty($data['page']) || empty($data['result']))
		{
			return '<p>'.esc_html__('No saved accessibility results were found for this page.', 'jwp_a11y').'</p>';
		}

		$yml = \Jidaikobo\A11yc\Yaml::fetch();
		$page = $data['page'];
		$target_level = intval($settings['target_level'] ?? 0);
		$back_link = add_query_arg('a11yc_page', 1, $base_url);

		$html = '';
		$html .= '<div class="jwp-a11y-results">';
		$html .= '<p><a href="'.esc_url($back_link).'">'.esc_html__('URLの一覧へ戻る', 'jwp_a11y').'</a></p>';
		$html .= '<h2>'.esc_html((string) ($page['title'] ?? $url)).'</h2>';
		$html .= '<table class="a11yc_table a11yc_table_report"><tbody>';
		$html .= '<tr><th scope="row">'.esc_html__('目標とする適合レベル', 'jwp_a11y').'</th><td>'.esc_html(static::formatLegacyLevel($target_level)).'</td></tr>';
		$html .= '<tr><th scope="row">'.esc_html__('ページで達成しているレベル', 'jwp_a11y').'</th><td>'.esc_html(static::legacyConformanceLabel($data['result'])).'</td></tr>';
		$html .= '<tr><th scope="row">'.esc_html__('試験実施日', 'jwp_a11y').'</th><td>'.esc_html((string) ($page['date'] ?? '')).'</td></tr>';
		$html .= '</tbody></table>';

		$html .= '<h2>'.esc_html__('達成基準チェックリスト', 'jwp_a11y').'</h2>';
		$html .= '<table class="a11yc_table"><thead><tr>';
		$html .= '<th scope="col">'.esc_html__('達成基準', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result">'.esc_html__('適合レベル', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">'.esc_html__('対象', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result a11yc_result_exist">'.esc_html__('結果', 'jwp_a11y').'</th>';
		$html .= '<th scope="col" class="a11yc_result">'.esc_html__('備考', 'jwp_a11y').'</th>';
		$html .= '</tr></thead><tbody>';
		foreach ($data['result'] as $criterion => $raw_result)
		{
			$criterion_data = $yml['criterions'][$criterion] ?? array();
			if (! static::shouldDisplayCriterion($criterion_data, $target_level))
			{
				continue;
			}
			$html .= static::renderLegacyStyleResultRow($criterion, $criterion_data, $raw_result);
		}
		$html .= '</tbody></table>';

		$html .= '</div>';

		return $html;
	}

	private static function loadLegacyResultData($url, $version = null)
	{
		global $wpdb;

		$table = $wpdb->prefix.'jwp_a11yc_data';
		$version = is_null($version) ? static::selectedLegacyVersion() : intval($version);
		foreach (static::legacyUrlCandidates($url) as $candidate)
		{
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `key`, `value`, `is_array` FROM {$table} WHERE url = %s AND version = %d AND group_id = %d AND `key` IN (%s, %s)",
					$candidate,
					intval($version),
					self::LEGACY_GROUP_ID,
					'page',
					'result'
				),
				ARRAY_A
			);

			$data = array();
			foreach ($rows as $row)
			{
				$key = $row['key'];
				$is_array = ! empty($row['is_array']);
				$data[$key] = $is_array ? json_decode($row['value'], true) : $row['value'];
			}

			if (! empty($data))
			{
				return $data;
			}
		}

		return array();
	}

	private static function loadLegacyPages($version = null)
	{
		$version = is_null($version) ? static::selectedLegacyVersion() : intval($version);
		$pages = static::loadLegacyValuesByKey('page', $version);

		foreach ($pages as $url => $page)
		{
			if (! is_array($page))
			{
				unset($pages[$url]);
				continue;
			}

			$pages[$url]['url'] = $url;
		}

		return $pages;
	}

	private static function legacyUrlCandidates($url)
	{
		$url = trim((string) $url);
		if ($url === '')
		{
			return array();
		}

		$candidates = array($url);
		$trimmed = untrailingslashit($url);
		$slashed = trailingslashit($trimmed);

		$candidates[] = $trimmed;
		$candidates[] = $slashed;

		if (strpos($trimmed, 'https://') === 0)
		{
			$candidates[] = 'http://'.substr($trimmed, 8);
			$candidates[] = trailingslashit('http://'.substr($trimmed, 8));
		}
		elseif (strpos($trimmed, 'http://') === 0)
		{
			$candidates[] = 'https://'.substr($trimmed, 7);
			$candidates[] = trailingslashit('https://'.substr($trimmed, 7));
		}

		return array_values(array_unique(array_filter($candidates)));
	}

	private static function loadLegacySettings($version = null)
	{
		$version = is_null($version) ? static::selectedLegacyVersion() : intval($version);
		return static::loadLegacyValue('setting', 'common', $version);
	}

	private static function loadLegacyVersions()
	{
		$versions = static::loadLegacyValue('version', 'common', 0);
		return is_array($versions) ? $versions : array();
	}

	private static function selectedLegacyVersion()
	{
		$versions = static::loadLegacyVersions();
		$query_version = isset($_GET['a11yc_version']) ? sanitize_text_field(wp_unslash($_GET['a11yc_version'])) : '';
		if ($query_version !== '' && array_key_exists($query_version, $versions))
		{
			return intval($query_version);
		}

		$current = static::loadLegacyValue('current_version', 'common', 0);
		return is_scalar($current) ? intval($current) : 0;
	}

	private static function loadLegacyValue($key, $url, $version)
	{
		global $wpdb;

		$table = $wpdb->prefix.'jwp_a11yc_data';
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT `value`, `is_array` FROM {$table} WHERE url = %s AND version = %d AND group_id = %d AND `key` = %s LIMIT 1",
				$url,
				intval($version),
				self::LEGACY_GROUP_ID,
				$key
			),
			ARRAY_A
		);

		if (! is_array($row))
		{
			return array();
		}

		if (! empty($row['is_array']))
		{
			$decoded = json_decode($row['value'], true);
			return is_array($decoded) ? $decoded : array();
		}

		return $row['value'];
	}

	private static function loadLegacyValuesByKey($key, $version)
	{
		global $wpdb;

		$table = $wpdb->prefix.'jwp_a11yc_data';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT url, `value`, `is_array` FROM {$table} WHERE version = %d AND group_id = %d AND `key` = %s",
				intval($version),
				self::LEGACY_GROUP_ID,
				$key
			),
			ARRAY_A
		);

		$values = array();
		foreach ($rows as $row)
		{
			$values[$row['url']] = ! empty($row['is_array']) ? json_decode($row['value'], true) : $row['value'];
		}

		return $values;
	}

	private static function countLegacyResults($results)
	{
		$count = array(
			'passed' => 0,
			'failed' => 0,
			'manual' => 0,
		);

		foreach ($results as $result)
		{
			$state = intval($result['result'] ?? 0);
			if ($state > 0)
			{
				$count['passed']++;
				continue;
			}

			if ($state < 0)
			{
				$count['manual']++;
				continue;
			}

			$count['failed']++;
		}

		return $count;
	}

	private static function evaluateLegacyUrl($results)
	{
		$evaluated = array();
		foreach ($results as $criterion => $result)
		{
			if (! isset($result['result']))
			{
				continue;
			}

			$state = intval($result['result']);
			$evaluated[$criterion] = array(
				'passed' => $state >= 1,
				'non_exist' => $state === 1,
				'memo' => (string) ($result['memo'] ?? ''),
			);
		}

		return $evaluated;
	}

	private static function evaluateLegacyTotal($pages, $version)
	{
		$results = array();
		$passes = array();
		$non_exists = array();
		$total = array();

		foreach ($pages as $page)
		{
			if (! empty($page['trash']))
			{
				continue;
			}

			$data = static::loadLegacyPageResult($page, $version);
			if (empty($data['result']) || ! is_array($data['result']))
			{
				continue;
			}
			$evaluated = static::evaluateLegacyUrl($data['result']);
			foreach ($evaluated as $criterion => $result)
			{
				$total[$criterion] = isset($total[$criterion]) ? $total[$criterion] + 1 : 1;
				$passes[$criterion] = isset($passes[$criterion]) ? $passes[$criterion] : 0;
				$passes[$criterion] += $result['passed'] ? 1 : 0;

				if (! isset($non_exists[$criterion]) || $non_exists[$criterion] !== false)
				{
					$non_exists[$criterion] = (bool) $result['non_exist'];
				}
			}
		}

		foreach ($total as $criterion => $num)
		{
			$results[$criterion] = array(
				'result' => ($passes[$criterion] === $num) ? ($non_exists[$criterion] ? 1 : 2) : 0,
				'memo' => $passes[$criterion].'/'.$num,
			);
		}

		return $results;
	}

	private static function legacyFailures($results)
	{
		$failures = array();

		foreach ($results as $criterion => $result)
		{
			if (intval($result['result'] ?? 0) !== 0)
			{
				continue;
			}

			$failures[$criterion] = $result;
		}

		return $failures;
	}

	private static function renderFailureItem($criterion, $criterion_data, $result)
	{
		$html = '';
		$html .= '<li class="jwp-a11y-result-item">';
		$html .= '<strong>'.esc_html($criterion.' '.($criterion_data['name'] ?? $criterion)).'</strong>';

		$level_label = static::criterionLevelLabel($criterion_data);
		if ($level_label !== '')
		{
			$html .= ' <span class="jwp-a11y-level">['.esc_html($level_label).']</span>';
		}

		if (! empty($criterion_data['summary']))
		{
			$html .= '<div class="jwp-a11y-summary">'.wp_kses_post(wpautop((string) $criterion_data['summary'])).'</div>';
		}

		if (! empty($result['memo']))
		{
			$html .= '<div class="jwp-a11y-memo"><em>'.esc_html((string) $result['memo']).'</em></div>';
		}

		if (! empty($criterion_data['doc']))
		{
			$html .= '<details class="jwp-a11y-guidance">';
			$html .= '<summary>'.esc_html__('この指摘の説明', 'jwp_a11y').'</summary>';
			$html .= wp_kses_post(wpautop((string) $criterion_data['doc']));
			$html .= '</details>';
		}

		$html .= '</li>';

		return $html;
	}

	private static function renderLegacyStyleResultRow($criterion, $criterion_data, $result)
	{
		$state = intval($result['result'] ?? 0);
		$exists = $state === 1 ? __('適用なし', 'jwp_a11y') : __('適用あり', 'jwp_a11y');
		$pass = $state > 0 ? __('適合', 'jwp_a11y') : ($state < 0 ? '-' : __('不適合', 'jwp_a11y'));
		$memo = isset($result['memo']) ? (string) $result['memo'] : '';
		$level = static::criterionLevelLabel($criterion_data);
		$label = $criterion.' '.($criterion_data['name'] ?? $criterion);
		$class = $state === 0 ? ' class="a11yc_not_passed"' : '';

		$html = '';
		$html .= '<tr'.$class.'>';
		$html .= '<th scope="row" class="a11yc_result a11yc_result_string">'.esc_html($label).'</th>';
		$html .= '<td class="a11yc_result a11yc_level">'.esc_html($level).'</td>';
		$html .= '<td class="a11yc_result a11yc_result_exist">'.esc_html($exists).'</td>';
		$html .= '<td class="a11yc_result a11yc_result_exist">'.esc_html($pass).'</td>';
		$html .= '<td class="a11yc_result">'.nl2br(esc_html($memo)).'</td>';
		$html .= '</tr>';

		return $html;
	}

	private static function formatLegacyLevel($level)
	{
		$level = intval($level);
		if ($level <= 0)
		{
			return __('Not available', 'jwp_a11y');
		}

		$map = array(
			1 => 'A',
			2 => 'AA',
			3 => 'AAA',
		);

		return $map[$level] ?? (string) $level;
	}

	private static function legacySiteLevel($pages)
	{
		$levels = array();
		foreach ($pages as $page)
		{
			$level = static::legacyPageLevel($page);
			if ($level > 0)
			{
				$levels[] = $level;
			}
		}

		return empty($levels) ? 0 : min($levels);
	}

	private static function loadLegacyPageResult($page, $version = null)
	{
		foreach (static::legacyPageDataUrls($page) as $url)
		{
			$data = static::loadLegacyResultData($url, $version);
			if (! empty($data))
			{
				return $data;
			}
		}

		return array();
	}

	private static function legacyPageDataUrls($page)
	{
		if (! is_array($page))
		{
			return array();
		}

		$urls = array();
		if (! empty($page['alt_url']))
		{
			$urls[] = (string) $page['alt_url'];
		}
		if (! empty($page['url']))
		{
			$urls[] = (string) $page['url'];
		}

		return array_values(array_unique(array_filter($urls)));
	}

	private static function legacyPageLevel($page, $version = null)
	{
		$level = intval($page['level'] ?? 0);
		if ($level > 0)
		{
			return $level;
		}

		$data = static::loadLegacyPageResult($page, $version);
		if (empty($data['result']) || ! is_array($data['result']))
		{
			return 0;
		}

		return static::deriveLegacyLevelFromResults($data['result']);
	}

	private static function deriveLegacyLevelFromResults($results)
	{
		$yml = \Jidaikobo\A11yc\Yaml::fetch();
		$level = 3;

		foreach ($results as $criterion => $result)
		{
			if (intval($result['result'] ?? 0) !== 0)
			{
				continue;
			}

			$criterion_level = (string) ($yml['criterions'][$criterion]['level'] ?? '');
			if ($criterion_level === 'A')
			{
				return 0;
			}

			if ($criterion_level === 'AA')
			{
				$level = min($level, 1);
				continue;
			}

			if ($criterion_level === 'AAA')
			{
				$level = min($level, 2);
			}
		}

		return $level;
	}

	private static function legacyConformanceLabel($results)
	{
		if (! is_array($results) || empty($results))
		{
			return __('Not available', 'jwp_a11y');
		}

		$yml = \Jidaikobo\A11yc\Yaml::fetch();
		$failed_levels = array();

		foreach ($results as $criterion => $result)
		{
			if (intval($result['result'] ?? 0) !== 0)
			{
				continue;
			}

			$key = isset($yml['criterions'][$criterion]) ? $criterion : str_replace('.', '-', (string) $criterion);
			$criterion_data = $yml['criterions'][$key] ?? array();
			$level = static::criterionLevelLabel($criterion_data);
			if ($level !== '')
			{
				$failed_levels[$level] = true;
			}
		}

		if (isset($failed_levels['A']))
		{
			return __('A 一部準拠', 'jwp_a11y');
		}

		if (isset($failed_levels['AA']))
		{
			return __('A準拠 / AA 一部準拠', 'jwp_a11y');
		}

		if (isset($failed_levels['AAA']))
		{
			return __('AA 準拠', 'jwp_a11y');
		}

		return __('AAA 準拠', 'jwp_a11y');
	}

	private static function shouldDisplayCriterion($criterion_data, $target_level)
	{
		if (! is_array($criterion_data) || empty($criterion_data))
		{
			return true;
		}

		if ($target_level <= 0)
		{
			return true;
		}

		$level = static::criterionLevelLabel($criterion_data);
		if ($level === '')
		{
			return true;
		}

		return strlen($level) <= $target_level;
	}

	private static function criterionLevelLabel($criterion_data)
	{
		if (! is_array($criterion_data))
		{
			return '';
		}

		$level = $criterion_data['level'] ?? '';
		if (is_array($level))
		{
			$level = $level['name'] ?? '';
		}

		return is_scalar($level) ? (string) $level : '';
	}

	public static function renderDocShortcode($attrs = array())
	{
		$attrs = shortcode_atts(
			array(
				'criterion' => '',
			),
			$attrs,
			'jwp_a11y_doc'
		);

		$criterion = (string) $attrs['criterion'];
		if ($criterion === '' && isset($_GET['criterion']))
		{
			$criterion = sanitize_text_field(wp_unslash($_GET['criterion']));
		}

		$yml = \Jidaikobo\A11yc\Yaml::fetch();

		if ($criterion !== '')
		{
			return static::renderSingleDoc($criterion, $yml);
		}

		return static::renderDocIndex($yml);
	}

	private static function renderSingleDoc($criterion, $yml)
	{
		$data = $yml['criterions'][$criterion] ?? null;
		if (! is_array($data))
		{
			return '<p>'.esc_html__('The requested criterion was not found.', 'jwp_a11y').'</p>';
		}

		$html = '';
		$html .= '<div class="jwp-a11y-doc">';
		$html .= '<h2>'.esc_html($criterion.' '.($data['name'] ?? $criterion)).'</h2>';

		if (! empty($data['summary']))
		{
			$html .= wp_kses_post(wpautop(static::linkifyCriterionReferences((string) $data['summary'], $yml)));
		}

		if (! empty($data['doc']))
		{
			$html .= '<h3>'.esc_html__('この達成基準について', 'jwp_a11y').'</h3>';
			$html .= wp_kses_post(wpautop(static::linkifyCriterionReferences((string) $data['doc'], $yml)));
		}

		$html .= '</div>';
		return $html;
	}

	private static function linkifyCriterionReferences($text, $yml)
	{
		$text = (string) $text;
		if ($text === '')
		{
			return '';
		}

		return (string) preg_replace_callback(
			'/\[(\d+\.\d+\.\d+)\]/',
			function ($matches) use ($yml) {
				$display = (string) $matches[1];
				$criterion = str_replace('.', '-', $display);
				if (! isset($yml['criterions'][$criterion]))
				{
					return $matches[0];
				}

				$url = add_query_arg('criterion', rawurlencode($criterion), static::docsPageUrl());
				return '<a href="'.esc_url($url).'">['.esc_html($display).']</a>';
			},
			$text
		);
	}

	private static function renderDocIndex($yml)
	{
		$grouped = array();
		foreach ($yml['criterions'] as $criterion => $data)
		{
			$level = static::criterionLevelLabel($data);
			if ($level === '')
			{
				$level = '-';
			}
			if (! isset($grouped[$level]))
			{
				$grouped[$level] = array();
			}
			$grouped[$level][$criterion] = $data;
		}
		uksort($grouped, array(__CLASS__, 'compareLevels'));

		$html = '';
		$html .= '<div class="jwp-a11y-doc-index">';

		foreach ($grouped as $level => $criterions)
		{
			$html .= '<h2>'.esc_html(sprintf(__('適合レベル %s', 'jwp_a11y'), $level)).'</h2>';
			$html .= '<ul>';

			foreach ($criterions as $criterion => $data)
			{
				$link = add_query_arg('criterion', rawurlencode($criterion), static::docsPageUrl());
				$html .= '<li><a href="'.esc_url($link).'">'.esc_html($criterion.' '.($data['name'] ?? $criterion)).'</a>';
				if (! empty($data['summary']))
				{
					$html .= '<div class="jwp-a11y-doc-index-summary">'.esc_html(wp_strip_all_tags((string) $data['summary'])).'</div>';
				}
				$html .= '</li>';
			}

			$html .= '</ul>';
		}

		$html .= '</div>';

		return $html;
	}

	private static function compareLevels($left, $right)
	{
		$order = array(
			'A' => 1,
			'AA' => 2,
			'AAA' => 3,
		);

		$left_rank = $order[$left] ?? 99;
		$right_rank = $order[$right] ?? 99;

		if ($left_rank === $right_rank)
		{
			return strcmp((string) $left, (string) $right);
		}

		return $left_rank <=> $right_rank;
	}

	public static function registerAdminPage()
	{
		add_management_page(
			__('ウェブアクセシビリティの確保のために', 'jwp_a11y'),
			__('ウェブアクセシビリティの確保のために', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y-docs',
			array(__CLASS__, 'renderAdminPage')
		);
	}

	public static function renderAdminPage()
	{
		echo '<div class="wrap">';
		echo '<h1>'.esc_html__('ウェブアクセシビリティの確保のために', 'jwp_a11y').'</h1>';
		echo static::renderDocShortcode();
		echo '</div>';
	}

	private static function docsPageUrl()
	{
		return admin_url('tools.php?page=jwp-a11y-docs');
	}
}

Plugin::init();
