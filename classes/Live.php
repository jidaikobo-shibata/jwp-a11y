<?php
/**
 * \JwpA11y\Live
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Live extends \A11yc\Controller\Live
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
		echo \A11yc\View::fetch('body');
	}
}
