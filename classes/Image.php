<?php
/**
 * \JwpA11y\Image
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Image extends \A11yc\Controller\Image
{
	/**
	 * view
	 *
	 * @param String $url
	 * @return Void
	 */
	public static function view($url)
	{
		parent::view(\A11yc\Util::enuniqueUri(\A11yc\Input::param('url', '')));

		$html = '';
		$html.= '<div class="wrap">';
		$html.= '<div id="icon-themes" class="icon32"><br /></div>';
		$html.= '<h1>Image List</h1>';
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
