<?php
/**
 * \JwpA11y\Issue
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Issue extends \A11yc\Controller\Issue
{
	private static function verify_issue_nonce()
	{
		if ( ! \A11yc\Input::isPostExists()) return;
		$nonce = \A11yc\Input::post('jwp_a11y_nonce', false);
		if ( ! $nonce || ! wp_verify_nonce($nonce, 'jwp_a11y_issue_action'))
		{
			print 'nonce check failed.';
			exit;
		}
	}

	private static function can_manage_issue($issue)
	{
		if (empty($issue)) return false;
		if (current_user_can('administrator')) return true;
		return intval(\A11yc\Arr::get($issue, 'uid', 0)) === intval(get_current_user_id());
	}

	private static function current_issue($issue_id)
	{
		$issue_id = intval($issue_id);
		return $issue_id ? \A11yc\Model\Issue::fetch($issue_id) : array();
	}

	private static function require_issue_access($issue_id)
	{
		$issue = self::current_issue($issue_id);
		if (empty($issue))
		{
			\A11yc\Util::error('issue not found');
		}
		if ( ! self::can_manage_issue($issue))
		{
			\A11yc\Util::error('forbidden');
		}
		return $issue;
	}

	/**
	 * routing
	 *
	 * @return void
	 */
	public static function routing()
	{
		// users
		$userinfo = wp_get_current_user();

		// users
		$users = array();
		foreach (get_users() as $v)
		{
			$users[$v->data->ID] = esc_html($v->data->user_nicename);
		}
		$is_admin = current_user_can('administrator');
		$is_edit = false;
		\A11yc\View::assign('current_user_id', $userinfo->ID);
		\A11yc\View::assign('is_admin', $is_admin);
		\A11yc\View::assign(
			'issue_action_nonce',
			wp_nonce_field('jwp_a11y_issue_action', 'jwp_a11y_nonce', true, false),
			false
		);

		// nonce
		self::verify_issue_nonce();

		switch ($action = \A11yc\Input::get('a', 'yet'))
		{
			case 'add':
				static::edit($is_add = true, $users, $userinfo->ID);
				$is_edit = true;
				break;
			case 'edit':
				self::require_issue_access(\A11yc\Input::get('id'));
				static::edit($is_add = false, $users, $userinfo->ID);
				$is_edit = true;
				break;
			case 'read':
				if (\A11yc\Input::isPostExists())
				{
					self::require_issue_access(\A11yc\Input::get('id'));
				}
				\A11yc\Controller\IssueRead::issue($users, $userinfo->ID, $is_admin);
				$is_edit = true;
				break;
			case 'delete':
				if ( ! \A11yc\Input::isPostExists()) \A11yc\Util::error('wrong request');
				self::require_issue_access(\A11yc\Input::get('id'));
				static::actionDelete();
				break;
			case 'undelete':
				if ( ! \A11yc\Input::isPostExists()) \A11yc\Util::error('wrong request');
				self::require_issue_access(\A11yc\Input::get('id'));
				static::actionUndelete();
				break;
			case 'purge':
				if ( ! \A11yc\Input::isPostExists()) \A11yc\Util::error('wrong request');
				self::require_issue_access(\A11yc\Input::get('id'));
				static::actionPurge();
				break;
			case 'index':
				static::failures();
				break;
			default:
				$issue_types = array(
					'yet'      => 0,
					'progress' => 1,
					'done'     => 2,
					'trash'    => 3,
				);
				\A11yc\Controller\IssueIndex::any($issue_types[$action]);
				break;
		}

		$html = '';
		$html.= '<div class="wrap">';
		$html.= '<div id="icon-themes" class="icon32"><br /></div>';
		$html.= '<h1>'.\A11yc\View::fetch('title').'</h1>';
		$html.= '<div class="postbox" style="margin-top: 15px;">';
		$html.= '<div class="inside">';
		$html.= \A11yc\View::fetchTpl('messages.php');
		$html.= '<div id="a11yc_docs">';

		if ($is_edit)
		{
			$html.= '<form action="'.\A11yc\Util::uri().'" method="POST" class="a11yc" enctype="multipart/form-data">';

			$html.= \A11yc\View::fetch('form');
			$html.= '<div id="a11yc_submit">';

			$html.= wp_nonce_field('jwp_a11y_issue_action', 'jwp_a11y_nonce', true, false);

			$html.= '<input type="submit" value="'.A11YC_LANG_CTRL_SEND.'" class="button button-primary button-large" />';
			$html.= '</div>';
			$html.= '</form>';
		}
		else
		{
			$html.= \A11yc\View::fetch('body');
		}

		$html.= '</div><!--/#a11yc_docks-->';
		$html.= '</div><!--/.inside-->';
		$html.= '</div><!--/.postbox-->';
		$html.= '</div><!--/.wrap-->';
		echo $html;
	}
}
