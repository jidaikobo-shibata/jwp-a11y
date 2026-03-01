<?php
/*
Plugin Name: jwp-a11y
Plugin URI: https://wordpress.org/plugins/jwp-a11y/
Description: A plugin to check accessibility.  Help to generate Evaluation Report (need PHP 5.6) and Check each posts (can be used PHP 5.3).  with consideration for the JIS X 8341-3:2016 and WCAG 2.0.
Author: Jidaikobo Inc.
Text Domain: jwp_a11y
Domain Path: /languages/
Version: 4.1.7
Author URI: https://www.jidaikobo.com/
License: GPL2

Copyright 2023 jidaikobo (email : support@jidaikobo.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// WP_INSTALLING
if (defined('WP_INSTALLING') && WP_INSTALLING)
{
	return;
}

// load core
define('A11YC_CONFIG_PATH', __DIR__.'/config/a11yc.php');
require 'a11yc/main.php';

// session
add_action('template_redirect', array('\Kontiki\Session', 'forge'), 10, 0);

// language
load_plugin_textdomain(
	'jwp_a11y',
	FALSE,
	plugin_basename(__DIR__).'/languages'
);

// classes
include 'classes/Bulk.php';
include 'classes/Center.php';
include 'classes/Icl.php';
include 'classes/Checklist.php';
include 'classes/Result.php';
include 'classes/Doc.php';
include 'classes/Page.php';
include 'classes/Setting.php';
include 'classes/Validate.php';
include 'classes/Uninstall.php';
include 'classes/Upgrade.php';
include 'classes/Issue.php';
include 'classes/Download.php';
include 'classes/Image.php';
include 'classes/Live.php';

// backup and version check, this must not run so frequently.
if (\A11yc\Maintenance::isFisrtOfToday())
{
	// security check
	\A11yc\Security::denyHttpDirectories();
}

// base url
$base_url = \A11yc\Model\Data::baseUrl();
if ($base_url == 'https://example.com')
{
	$sites[] = \A11yc\Util::urldec(home_url());
	\A11yc\Model\Data::update('sites', 'global', $sites);
}

// view
\A11yc\View::addTplPath(__DIR__.'/views');

// admin_menu
add_action(
	'admin_menu',
	function ()
	{
		add_menu_page(
			__('Accessibility Center', 'jwp_a11y'),
			__("<abbr title=\"Accessibility\">A11y</abbr> Center", "jwp_a11y"),
			'edit_posts',
			'jwp-a11y',
			array('\JwpA11y\Center', 'index'));

		add_submenu_page(
			'jwp-a11y',
			__('jwp-a11y Setting', 'jwp_a11y'),
			__('jwp-a11y Setting', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_setting',
			array('\JwpA11y\Setting', 'front'));

		add_submenu_page(
			'jwp-a11y',
			__('<abbr title="Inplement Check List">ICL</abbr>', 'jwp_a11y'),
			__('<abbr title="Inplement Check List">ICL</abbr>', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_icl',
			array('\JwpA11y\Icl', 'index'));

		add_submenu_page(
			'jwp-a11y',
			__('Page', 'jwp_a11y'),
			__('Page', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_page',
			array('\JwpA11y\Page', 'index'));

		add_submenu_page(
			'jwp-a11y',
			\JwpA11y\Checklist::pageTitleByAction(),
			__('Checklist', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_checklist',
			array('\JwpA11y\Checklist', 'checklist'));

		add_submenu_page(
			'jwp-a11y',
			__('Accessibility Bulk Checklist', 'jwp_a11y'),
			__('Bulk', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_bulk',
			array('\JwpA11y\Bulk', 'checklist'));

		add_submenu_page(
			'jwp-a11y',
			__('Accessibility Issue', 'jwp_a11y'),
			__('Issue', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_issue',
			array('\JwpA11y\Issue', 'routing'));

		add_submenu_page(
			'jwp-a11y',
			\JwpA11y\Doc::title(),
			__('Document', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_doc',
			array('\JwpA11y\Doc', 'show'));


		add_submenu_page(
			'jwp-a11y',
			__('Download Issue', 'jwp_a11y'),
			__('Download Issue', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_download',
			array('\JwpA11y\Download', 'index'));

		add_submenu_page(
			'jwp-a11y',
			__('Show Result', 'jwp_a11y'),
			__('Show Result', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_result',
			array('\JwpA11y\Result', 'index'));

		add_submenu_page(
			'jwp-a11y',
			__('Show Images', 'jwp_a11y'),
			__('Show Images', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_image',
			array('\JwpA11y\Image', 'view'));

		add_submenu_page(
			'jwp-a11y',
			__('Show Live', 'jwp_a11y'),
			__('Show Live', 'jwp_a11y'),
			'edit_posts',
			'jwp-a11y/jwp_a11y_live',
			array('\JwpA11y\Live', 'view'));
	});

// header
function jwp_a11y_enqueue_styles()
{
	if ( ! defined('A11YC_ASSETS_URL')) return;
	wp_enqueue_style(
		'jwp-a11y_css',
		A11YC_ASSETS_URL.'/css/a11yc.css'
	);
	wp_enqueue_style(
		'jwp-jwp-a11y_css',
		A11YC_ASSETS_URL.'/css/jwp-a11y.css'
	);
	wp_enqueue_style(
		'jwp-a11y_font-awesome',
		A11YC_ASSETS_URL.'/css/font-awesome/css/font-awesome.min.css'
	);
}

function jwp_a11y_enqueue_scripts()
{
	if ( ! defined('A11YC_ASSETS_URL')) return;
	wp_enqueue_script(
		'jwp-a11y_js',
		A11YC_ASSETS_URL.'/js/a11yc.js'
	);
	wp_enqueue_script(
		'jwp-jwp-a11y_js',
		A11YC_ASSETS_URL.'/js/jwp-a11y.js'
	);
}
add_action('wp_enqueue_scripts', 'jwp_a11y_enqueue_styles');
add_action('admin_enqueue_scripts', 'jwp_a11y_enqueue_styles');
add_action('admin_enqueue_scripts', 'jwp_a11y_enqueue_scripts');

// admin bar
add_action(
	'admin_bar_menu',
	function ($wp_admin_bar)
	{
		global $post;

		// not for list or so
		$is_admin = strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/') !== false;
		if ($is_admin && \A11yc\Input::get('action') != 'edit') return;
		if ( ! isset($post->ID)) return;

		// admin
		$url = $is_admin ? get_permalink($post->ID) : \A11yc\Util::uri();

		// level
		$result = \A11yc\Model\Page::fetch($url);
		$settings = \A11yc\Model\Setting::fetchAll();
		if ( ! $result)
		{
			$level = '-';
			$level_str = __('Not added yet', 'jwp_a11y');
		}
		else if (\A11yc\Arr::get($result, 'done') == 0)
		{
			$level = '*';
			$level_str = __('In checking', 'jwp_a11y');
		}
		else if ($settings['target_level'])
		{
			$level = \A11yc\Evaluate::resultStr($result['level'], $settings['target_level'], false);
			$level_str = \A11yc\Evaluate::resultStr($result['level'], $settings['target_level']);
		}

		// check php version, because Guzzle's paren usage is not work under lower php
		$edit_link = isset($post->ID) && ! is_home() ?
							 admin_url().'post.php?post='.intval($post->ID).'&amp;action=edit&amp;jwp-a11y_check_here=1' :
							 '';

		// if lower php version, check link became edit link
		$check_link = \A11yc\Guzzle::envCheck() ?
								A11YC_CHECKLIST_URL.\A11yc\Util::urlenc($url) :
								$edit_link;

		// Goto jwp-a11y
		$wp_admin_bar->add_menu(array(
				'id'    => 'jwp-a11y',
				'title' => 'A11y ('.$level.')',
				'href'  => $check_link,
				'meta'  => array(
					'class' => 'dashicons dashicons-yes',
					'title' => $check_link ?
					__('Check here by jwp-a11yc', 'jwp_a11y').__(' (current: ', 'jwp_a11y').$level_str.')' :
					''
				)
			));

		// redundancy for usability
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'jwp-a11y',
				'id'     => 'jwp-a11y_redundancy',
				'title'  => __('Accessibility Check', 'jwp_a11y'),
				'href'   => $check_link,
				'meta'  => array(
					'title' => __('Check here by jwp-a11yc', 'jwp_a11y')
				)
			));

		// below here not for admin
		if ($is_admin) return;

		// Goto edit page
		if ( ! empty($edit_link))
		{
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'jwp-a11y',
					'id'     => 'jwp-a11y_child',
					'title'  => __('Check and Edit', 'jwp_a11y'),
					'href'   => admin_url().'post.php?post='.intval($post->ID).'&amp;action=edit&amp;jwp-a11y_check_here=1',
				'meta'  => array(
					'title' => __('Go to edit page and do accessibility check', 'jwp_a11y')
				)
				));
		}
	},
	100);

// shortcode
add_shortcode("jwp_a11y_disclosure", array('\JwpA11y\Result', 'disclosure')); // lower compati
add_shortcode("jwp_a11y_results", array('\JwpA11y\Result', 'disclosure'));

// titles for disclosure
add_filter( 'document_title_parts', function ($title)
{
	if (\A11yc\Input::get('a11yc_report'))
	{
		$title['title'] = A11YC_LANG_REPORT;
	}
	elseif (\A11yc\Input::get('a11yc_policy'))
	{
		$title['title'] = A11YC_LANG_POLICY;
	}
	elseif (\A11yc\Input::get('a11yc_pages'))
	{
		$title['title'] = A11YC_LANG_CHECKED_PAGES;
	}
	elseif (\A11yc\Input::get('a11yc_checklist') && \A11yc\Input::get('url'))
	{
		$page_title = \A11yc\Model\Html::fetchPageTitle(\A11yc\Input::get('url'));
		$title['title'] = A11YC_LANG_TEST_RESULT.': '.\A11yc\Util::s($page_title);
	}
	return $title;
},
	PHP_INT_MAX
);

// non save_post check
add_action('admin_head', array('\JwpA11y\Validate', 'non_post_validate'));

// save_post check
add_action('save_post', array('\JwpA11y\Validate', 'validate'));

if (strpos($_SERVER['SCRIPT_NAME'], 'post.php') !== false)
{
	add_action('admin_notices', array('\JwpA11y\Validate', 'show_messages'));
}

// custom field for link check
if (\A11yc\Guzzle::envCheck())
{
	add_action('admin_menu', 'jwp_a11y_add_custom_box');
	function jwp_a11y_add_custom_box()
	{
		foreach (get_post_types(array('public' => true)) as $v)
		{
			add_meta_box(
				'jwp_a11y_add_custom_box_field_'.$v,
				__('Additional Accessibility Check', 'jwp_a11y'),
				'jwp_a11y_add_custom_box_field',
				$v,
				'side',
				'default'
			);
		}
	}

	function jwp_a11y_add_custom_box_field()
	{
		$html = '';
		$html.= '<p><input type="checkbox" name="jwp_a11y_css_check" id="jwp_a11y_css_check" value="1" />';
		$html.= '<label for="jwp_a11y_css_check">'.A11YC_LANG_CHECKLIST_DO_CSS_CHECK.'</label></p>';

		$html.= '<p><input type="checkbox" name="jwp_a11y_link_check" id="jwp_a11y_link_check" value="1" />';
		$html.= '<label for="jwp_a11y_link_check">'.A11YC_LANG_CHECKLIST_DO_LINK_CHECK.'</label></p>';
		echo $html;
	}
}

// uninstall
if (function_exists('register_uninstall_hook'))
{
	register_uninstall_hook(__FILE__, array('\JwpA11y\Uninstall', 'uninstall'));
}

// upgrade
add_action(
	'upgrader_process_complete',
	function ($upgrader_object, $options)
	{
    $current_plugin_path_name = plugin_basename( __FILE__ );
		if ($options['action'] == 'update' && $options['type'] == 'plugin' )
		{
			if (isset($options['plugins']) && is_array($options['plugins']))
			{
				foreach($options['plugins'] as $each_plugin)
				{
					if ($each_plugin == $current_plugin_path_name)
					{
						\JwpA11y\Upgrade::upgrade();
					}
				}
			}
		}
	},
	10,
	2
);

// out buffer for in controller redirection
add_filter(
	'after_setup_theme',
	function ()
	{
		if (
			! is_admin() ||
			! isset($_SERVER['REQUEST_URI']) ||
			strpos($_SERVER['REQUEST_URI'], 'page=jwp-a11y') === false
		) return;

		ob_start();
		return;
	},
	20
);

add_filter(
	'shutdown',
	function ()
	{
		if (
			! is_admin() ||
			! isset($_SERVER['REQUEST_URI']) ||
			strpos($_SERVER['REQUEST_URI'], 'page=jwp-a11y') === false
		) return;

		$levels = ob_get_level();

		$final = '';
		for ($i = 0; $i < $levels; $i++)
		{
			$final .= ob_get_clean();
		}
		echo $final;

		return;
	},
	20
);
