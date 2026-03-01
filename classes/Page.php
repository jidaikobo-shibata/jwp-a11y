<?php
/**
 * \JwpA11y\Page
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Page extends \A11yc\Controller\Page
{
	/**
	 * Manage Target Pages.
	 *
	 * @return  void
	 */
	public static function index()
	{
		// nonce check
		if ($_POST)
		{
			if (
				! isset($_POST['jwp_a11y_nonce']) ||
				(
					! wp_verify_nonce($_POST['jwp_a11y_nonce'], 'jwp_a11y_page_add') &&
					! wp_verify_nonce($_POST['jwp_a11y_nonce'], 'jwp_a11y_page_get')
				)
			)
			{
				print 'nonce check failed.';
				exit;
			}
		}

		// nonce
		\A11yc\View::assign(
			'add_nonce',
			wp_nonce_field('jwp_a11y_page_add', 'jwp_a11y_nonce', true, false),
			false
		);

		\A11yc\View::assign(
			'get_nonce',
			wp_nonce_field('jwp_a11y_page_get', 'jwp_a11y_nonce', true, false),
			false
		);

		$ramdom_num = \A11yc\Input::post('jwp_a11y_page_ramdom_add_num', 0);
		if ($ramdom_num)
		{
			$args = array(
				'posts_per_page' => 100,
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'orderby'        => 'rand'
			);
			$pages = array();
			$n = 0;
			foreach (get_posts($args) as $v)
			{
				$post_type = get_post_type_object($v->post_type);
				if ( ! $post_type->show_ui || ! $post_type->show_in_nav_menus) continue;
				$pages[] = get_permalink($v->ID);
				$n++;
				if ($n >= $ramdom_num) break;
			}
			\A11yc\Controller\Page\Add::addPages($is_force = false, $pages);
		}

		// parent edit
		elseif (\A11yc\Input::get('a') == 'edit')
		{
			parent::edit();
		}

		// parent add
		elseif (\A11yc\Input::get('a') == 'add')
		{
			\A11yc\Controller\PageAdd::targetPages();
		}

		// update html
		elseif (\A11yc\Input::get('a') == 'updatehtml')
		{
			$url = \A11yc\Util::enuniqueUri(\A11yc\Input::param('url', ''));
			\A11yc\Controller\PageUpdate::updateHtml($url);
		}
		else
		{
			parent::index();
		}

		// html
		$html = '';
		$html.= '<div class="wrap">';
		$html.= '<div id="icon-themes" class="icon32"><br /></div>';
		$html.= '<h1>'.__("Pages", "jwp_a11y").'</h1>';
		$html.= '<div class="postbox" style="margin-top: 15px;">';
		$html.= '<div class="inside">';
		$html.= \A11yc\View::fetchTpl('messages.php');
		$html.= \A11yc\View::fetch('body');

		if (\A11yc\Input::get('a') == 'add')
		{
			$html.= '<form action="'.A11YC_PAGE_URL.'add" method="POST">';
			$html.= '<h2>'.__('Get Urls Ramdom (WordPress Pages Only)', 'jwp_a11y').'</h2>';
			$html.= '<label for="jwp_a11y_page_ramdom_add_num">'.__('Num', 'jwp_a11y').'</label> ';
			$html.= '<select id="jwp_a11y_page_ramdom_add_num" name="jwp_a11y_page_ramdom_add_num">';
			for ($n = 1; $n <= 30; $n++)
			{
				$html.= '<option value="'.$n.'" />'.$n.'</option>';
			}
			$html.= '</select>';
			$html.= \A11yc\View::fetch('add_nonce');
			$html.= '<input type="submit" value="'.A11YC_LANG_CTRL_SEND.'" />';
			$html.= '</form>';
		}

		$html.= '</div><!--/.inside-->';
		$html.= '</div><!--/.postbox-->';
		$html.= '</div><!--/.wrap-->';
		echo $html;
	}
}
