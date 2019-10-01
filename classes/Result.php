<?php
/**
 * \JwpA11y\Result
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Result extends \A11yc\Controller\Result
{
	/**
	 * shortcode for disclosure page
	 *
	 * @param   array  $attrs
	 * @param   string $content
	 * @return  string
	 */
	public static function disclosure($attrs, $content = null)
	{
		global $post;
		$action = \A11yc\Input::get('a11yc_page', '').\A11yc\Input::get('a11yc_each', '');
		parent::report(get_permalink($post->ID), empty($action));
		return \A11yc\View::fetch('body');
	}

	/**
	 * results
	 *
	 * @return Void
	 */
	public static function index()
	{
		parent::report(home_url());

		$html = '';
		$html.= '<div class="wrap">';
		$html.= '<div id="icon-themes" class="icon32"><br /></div>';
//		$html.= '<h1>'.self::title().'</h1>';
		$html.= '<div class="postbox" style="margin-top: 15px;">';
		$html.= '<div class="inside">';
		$html.= \A11yc\View::fetchTpl('messages.php');
		$html.= '<div id="a11yc_docs">';

		$html.= \A11yc\View::fetch('body');

		$html.= '</div><!--/#a11yc_docks-->';
		$html.= '</div><!--/.inside-->';
		$html.= '</div><!--/.postbox-->';
		$html.= '</div><!--/.wrap-->';
		echo $html;
	}
}
