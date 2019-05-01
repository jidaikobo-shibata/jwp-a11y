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
		$action = \A11yc\Input::get('a11yc_page', '').\A11yc\Input::get('a11yc_each', '');
		parent::report(home_url(), empty($action));
		return \A11yc\View::fetch('body');
	}
}
