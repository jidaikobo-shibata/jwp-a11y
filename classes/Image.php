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
		wp_die(
			esc_html__('Show Images is disabled in the WordPress plugin.', 'jwp_a11y'),
			esc_html__('Feature Disabled', 'jwp_a11y'),
			array('response' => 403)
		);
	}
}
