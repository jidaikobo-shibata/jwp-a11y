<?php
/**
 * \JwpA11y\Download
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Download extends \A11yc\Controller\Download
{
	/**
	 * index
	 *
	 * @return Void
	 */
	public static function index()
	{
		wp_die(
			esc_html__('Download Issue is disabled in the WordPress plugin.', 'jwp_a11y'),
			esc_html__('Feature Disabled', 'jwp_a11y'),
			array('response' => 403)
		);

	}

}
