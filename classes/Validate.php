<?php
/**
 * \JwpA11y\Validate
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Validate extends \A11yc\Validate
{
	static $errors = array();
	protected static $current_post_titles = array();

	protected static function transient_key($type, $post_id, $user_id = 0)
	{
		$post_id = absint($post_id);
		$user_id = $user_id ? absint($user_id) : get_current_user_id();
		return sprintf('jwp_a11y_%s_%d_%d', $type, $user_id, $post_id);
	}

	protected static function store_messages($post_id, $errors, $notices, $no_errors = array())
	{
		$ttl = 10 * MINUTE_IN_SECONDS;
		$keys = array(
			'errors' => static::transient_key('errors', $post_id),
			'notices' => static::transient_key('notices', $post_id),
			'no_errors' => static::transient_key('no_errors', $post_id),
		);

		if (empty($errors))
		{
			delete_transient($keys['errors']);
		}
		else
		{
			set_transient($keys['errors'], $errors, $ttl);
		}

		if (empty($notices))
		{
			delete_transient($keys['notices']);
		}
		else
		{
			set_transient($keys['notices'], $notices, $ttl);
		}

		if (empty($no_errors))
		{
			delete_transient($keys['no_errors']);
		}
		else
		{
			set_transient($keys['no_errors'], $no_errors, $ttl);
		}
	}

	protected static function get_messages($post_id, $delete = false)
	{
		$post_id = absint($post_id);
		if ( ! $post_id)
		{
			return array(
				'errors' => array(),
				'notices' => array(),
				'no_errors' => array(),
			);
		}

		$keys = array(
			'errors' => static::transient_key('errors', $post_id),
			'notices' => static::transient_key('notices', $post_id),
			'no_errors' => static::transient_key('no_errors', $post_id),
		);

		$messages = array(
			'errors' => get_transient($keys['errors']),
			'notices' => get_transient($keys['notices']),
			'no_errors' => get_transient($keys['no_errors']),
		);

		$messages['errors'] = is_array($messages['errors']) ? $messages['errors'] : array();
		$messages['notices'] = is_array($messages['notices']) ? $messages['notices'] : array();
		$messages['no_errors'] = is_array($messages['no_errors']) ? $messages['no_errors'] : array();

		if ($delete)
		{
			delete_transient($keys['errors']);
			delete_transient($keys['notices']);
			delete_transient($keys['no_errors']);
		}

		return $messages;
	}

	protected static function normalize_message_list($messages)
	{
		if (empty($messages) || ! is_array($messages))
		{
			return array();
		}

		if (
			isset($messages[0]) &&
			is_array($messages[0]) &&
			! isset($messages[0]['li']) &&
			! isset($messages[0]['dt'])
		)
		{
			return $messages[0];
		}

		return $messages;
	}

	protected static function current_post_id()
	{
		$post_id = absint(\A11yc\Input::get('post'));
		if ($post_id) return $post_id;

		global $post;
		return ($post && ! empty($post->ID)) ? absint($post->ID) : 0;
	}

	protected static function render_messages($messages)
	{
		$errors = isset($messages['errors']) ? static::normalize_message_list($messages['errors']) : array();
		$notices = isset($messages['notices']) ? static::normalize_message_list($messages['notices']) : array();
		$no_errors = isset($messages['no_errors']) ? $messages['no_errors'] : array();

		$html = '';
		$show_link_to_issue = false;

		if ( ! empty($errors))
		{
			$html.= '<div class="notice error is-dismissible" id="jwp_a11y_error">';
			$html.= \A11yc\Message\Plugin::error($errors, $show_link_to_issue);
			$html.= '</div>';
		}
		elseif ($no_errors)
		{
			$html.= '<div class="notice notice-success is-dismissible" id="jwp_a11y_no_error">';
			$html.= \A11yc\Message\Plugin::noError(\A11yc\Arr::get($no_errors, 0));
			$html.= '</div>';
		}

		if ( ! empty($notices))
		{
			$html.= '<div class="notice notice-warning is-dismissible" id="jwp_a11y_notice">';
			$html.= \A11yc\Message\Plugin::notice($notices, $show_link_to_issue);
			$html.= '</div>';
		}

		return $html;
	}

	protected static function render_editor_messages($messages)
	{
		$html = static::render_messages($messages);

		if (
			empty($messages['errors']) &&
			empty($messages['notices']) &&
			! empty($messages['no_errors'])
		)
		{
			$html = preg_replace(
				'/^<div class="notice notice-success is-dismissible" id="jwp_a11y_no_error">(.*)<\/div>$/s',
				'$1',
				$html
			);
		}

		return $html;
	}

	/**
	 * Check accessibility $post->content.
	 *
	 * @param   int     $post_id
	 * @return  void
	 */
	public static function non_post_validate()
	{
		if (substr(\A11yc\Input::server('SCRIPT_NAME'), -18) != '/wp-admin/post.php') return;
		if ( ! \A11yc\Input::get('jwp-a11y_check_here')) return;
		global $post;
		static::set_errors($post);
	}

	/**
	 * Check accessibility $post->content.
	 *
	 * @param   int     $post_id
	 * @return  void
	 */
	public static function validate($post_id)
	{
		$do_nothing = false;

		// do nothing with menus
		if (\A11yc\Input::post('menu-item')) $do_nothing = true;
		if (\A11yc\Input::post('save_menu')) $do_nothing = true;

		// autosave, revision, acl
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) $do_nothing = true;
		if (wp_is_post_autosave($post_id)) $do_nothing = true;
		if (wp_is_post_revision($post_id)) $do_nothing = true;
		if ( ! current_user_can('edit_post', $post_id)) $do_nothing = true;

		// check status
		$obj = get_post($post_id);
		if ( ! $obj) $do_nothing = true;

		if (
			$obj &&
			! in_array($obj->post_status, array('publish', 'private', 'draft', 'future', 'pending'))
		)
		{
			$do_nothing = true;
		}

		// do nothing
		if ($do_nothing)
		{
			return $post_id;
		}

		static::set_errors($obj);
		return $post_id;
	}

	/**
	 * set_errors
	 *
	 * @return  void
	 */
	public static function set_errors($obj)
	{
		// accessibility check
		$e = new \WP_Error();

		// code and function
		\A11yc\Validate::$is_partial    = true;
		\A11yc\Validate::$do_link_check = \A11yc\Input::post('jwp_a11y_link_check', false);
		\A11yc\Validate::$do_css_check  = \A11yc\Input::post('jwp_a11y_css_check', false);

		$codes = \A11yc\Validate::$codes;
		if ( ! \A11yc\Db::hasDataTable())
		{
			$codes = array_values(array_diff(
				$codes,
				array(
					'IsseusElements',
					'IssuesSingle',
					'IssuesNonTag',
				)
			));
		}

		// check same title
		\A11yc\Validate::$codes_alias['SamePageTitleInSameSite'] = array(
			'\\JwpA11y\\Validate',
			'same_page_title_in_same_site'
		);

		// get all custom_fields
		$meta_values = '';
		foreach (get_post_meta($obj->ID) as $meta_key => $meta_value)
		{
			if ($meta_key[0] == '_') continue;
			if ($meta_key == 'dashi_search') continue; // secret hard coding. but low influential...
			$meta_values.= wp_specialchars_decode($meta_value[0]);
		}
		$url = get_permalink($obj->ID);
		$content_for_validation = apply_filters('the_content', $obj->post_content).$meta_values;
		static::$current_post_titles[$url] = $obj->post_title;
		static::html($url, $content_for_validation, $codes);

		// add errors
		$all_errs = \A11yc\Validate\Get::errors($url, $codes);
		$error_messages = isset($all_errs['errors']) && is_array($all_errs['errors']) ? $all_errs['errors'] : array();
		$notice_messages = isset($all_errs['notices']) && is_array($all_errs['notices']) ? $all_errs['notices'] : array();
		$e->add('errors', $error_messages);
		$e->add('notices', $notice_messages);

		$no_error_messages = array();
		if (empty($error_messages))
		{
			$no_error_messages = array('no_errors' => true);
			if (\A11yc\Input::post('jwp_a11y_link_check'))
			{
				$no_error_messages['no_dead_link'] = true;
			}
		}

		static::store_messages(
			$obj->ID,
			$error_messages,
			$notice_messages,
			$no_error_messages
		);
	}

	/**
	 * same page title in same site
	 *
	 * @param  String $url
	 * @return Void
	 */
	public static function same_page_title_in_same_site($url)
	{
		global $wpdb;
		$title = isset(static::$current_post_titles[$url]) ?
			static::$current_post_titles[$url] :
			\A11yc\Input::post('post_title');
		if ( ! $title) return;

		$sql = $wpdb->prepare(
			'SELECT ID FROM '.$wpdb->posts.' WHERE `post_title` = %s and `post_status` = "publish"',
			$title
		);
		$results = $wpdb->get_results($sql);

		if (count($results) >= 2)
		{
			$strs = array();
			foreach ($results as $v)
			{
				$strs[] = $v->ID;
			}

			\A11yc\Validate\Set::error(
				$url,
				'same_page_title_in_same_site',
				0,
				\A11yc\Util::s($title),
				'IDs: '.join(', ', $strs)
			);
		}
		static::addErrorToHtml($url, 'SamePageTitleInSameSite', static::$error_ids[$url]);
	}

	/**
	 * Show message to editor.
	 *
	 * @return  void
	 */
	public static function show_messages()
	{
		$post_id = static::current_post_id();
		if ( ! $post_id) return;
		echo static::render_messages(static::get_messages($post_id, true));
	}

	public static function register_rest_routes()
	{
		register_rest_route(
			'jwp-a11y/v1',
			'/post-check-result/(?P<id>\d+)',
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array(__CLASS__, 'rest_post_check_result'),
				'permission_callback' => array(__CLASS__, 'rest_post_check_result_permission'),
			)
		);
	}

	public static function rest_post_check_result_permission($request)
	{
		$post_id = absint($request['id']);
		return $post_id && current_user_can('edit_post', $post_id);
	}

	public static function rest_post_check_result($request)
	{
		$post_id = absint($request['id']);
		$messages = static::get_messages($post_id, true);

		return rest_ensure_response(
			array(
				'has_errors' => ! empty($messages['errors']),
				'has_notices' => ! empty($messages['notices']),
				'has_success' => empty($messages['errors']) && ! empty($messages['no_errors']),
				'html' => static::render_editor_messages($messages),
			)
		);
	}
}
