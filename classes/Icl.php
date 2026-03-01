<?php
/**
 * \JwpA11y\Icl
 *
 * @package    WordPress
 * @version    1.0
 * @author     Jidaikobo Inc.
 * @license    GPL
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace JwpA11y;

class Icl extends \A11yc\Controller\Icl
{
	/**
	 * Check Target Page.
	 *
	 * @return Void
	 */
	public static function index()
	{
		$a = \A11yc\Input::get('a', 'index');
		if ($a == 'import')
		{
			parent::actionImport(); // redirect
		}
		else if ($a == 'edit')
		{
			parent::edit();
		}
		else if ($a == 'read')
		{
			parent::read();
		}
		else if ($a == 'delete')
		{
			parent::delete();
		}
		else if ($a == 'view')
		{
			\A11yc\View::assign('is_view', true);
			\A11yc\View::assign('body',    \A11yc\View::fetchTpl('icl/implements_checklist.php'), false);
		}
		else
		{
			parent::index();
		}

		// prepare html
		$html = '';
		$html.= '<div id="a11yc_checklist_wrap" class="wrap">';
		$html.= '<div id="icon-themes" class="icon32"><br /></div>';
		$html.= '<h1 class="a11yc_skip">'.self::pageTitleByAction().'</h1>';
		$html.= '<div class="postbox">';
		$html.= '<div class="inside a11yc">';

		$close = '';
		$close.= '</div><!--/.inside-->';
		$close.= '</div><!--/.postbox-->';
		$close.= '</div><!--/.wrap-->';

				$html.= \A11yc\View::fetch('body');
				echo $html.$close;
	}

	/**
	 * page title
	 *
	 * @return String
	 */
	public static function pageTitleByAction()
	{
		// action
		switch (\A11yc\Input::get('a'))
		{
			case 'csv':
				return A11YC_LANG_EXPORT_ERRORS_CSV;
			case 'view':
				return A11YC_LANG_ICL_TITLE;
			default:
				return A11YC_LANG_CHECKLIST_TITLE;
		}
	}
}
