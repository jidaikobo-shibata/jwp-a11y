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
    if (\A11yc\Input::get('a') == 'issue')
    {
      // trait: DownloadIssue
      static::issue();
    }
    else
    {
      // trait: DownloadCsv
      static::csv();
    }

	}

}
