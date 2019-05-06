<?php
/**
 * config
 *
 * @package    part of A11yc
 */

// base url
define('A11YC_URL', admin_url('admin.php'));

// a11yc language
include_once WP_PLUGIN_DIR.'/jwp-a11y/classes/Locale.php';
define('A11YC_LANG', \JwpA11y\Locale::get_simple_locale(get_locale()));

// time zone
define('A11YC_TIMEZONE', date_default_timezone_get());

// mysql for WordPress
define('A11YC_DB_TYPE', 'mysql');
define('A11YC_DB_NAME', DB_NAME);
define('A11YC_DB_USER', DB_USER);
define('A11YC_DB_HOST', DB_HOST);
define('A11YC_DB_PASSWORD', DB_PASSWORD);

// for css and js
define('A11YC_ASSETS_URL', plugins_url('jwp-a11y').'/assets');

// pathes
define('A11YC_LIB_PATH',      WP_PLUGIN_DIR.'/jwp-a11y/a11yc/libs');
define('A11YC_PATH',          WP_PLUGIN_DIR.'/jwp-a11y/a11yc');
define('A11YC_CLASSES_PATH',  A11YC_PATH.'/classes');

$wp_upload_dir = wp_upload_dir();
define('A11YC_UPLOAD_PATH',   $wp_upload_dir['basedir'].'/jwp_a11y/screenshots');
define('A11YC_UPLOAD_URL',    $wp_upload_dir['baseurl'].'/jwp_a11y/screenshots');

// out of date. but leave it for lower compatibility
define('A11YC_CACHE_PATH', dirname(WP_PLUGIN_DIR).'/jwp-a11y_cache');

// sqlite for lower compatibility
define('A11YC_DATA_PATH', dirname(WP_PLUGIN_DIR).'/jwp-a11y_db');
define('A11YC_DATA_FILE', '/db.sqlite');

// old tables
global $wpdb;
define('A11YC_TABLE_SETUP_OLD',       $wpdb->prefix.'jwp_a11y_setup');
define('A11YC_TABLE_PAGES_OLD',       $wpdb->prefix.'jwp_a11y_pages');
define('A11YC_TABLE_CHECKS_OLD',      $wpdb->prefix.'jwp_a11y_checks');
define('A11YC_TABLE_CHECKS_NGS_OLD',  $wpdb->prefix.'jwp_a11y_checks_ngs');
define('A11YC_TABLE_BULK_OLD',        $wpdb->prefix.'jwp_a11y_bulk');
define('A11YC_TABLE_BULK_NGS_OLD',    $wpdb->prefix.'jwp_a11y_bulk_ngs');
define('A11YC_TABLE_MAINTENANCE_OLD', $wpdb->prefix.'jwp_a11y_maintenance');

// tables
define('A11YC_TABLE_PAGES',       $wpdb->prefix.'jwp_a11yc_pages');
define('A11YC_TABLE_UAS',         $wpdb->prefix.'jwp_a11yc_uas');
define('A11YC_TABLE_CACHES',      $wpdb->prefix.'jwp_a11yc_caches');
define('A11YC_TABLE_VERSIONS',    $wpdb->prefix.'jwp_a11yc_versions');
define('A11YC_TABLE_RESULTS',     $wpdb->prefix.'jwp_a11yc_results');
define('A11YC_TABLE_BRESULTS',    $wpdb->prefix.'jwp_a11yc_bresults');
define('A11YC_TABLE_CHECKS',      $wpdb->prefix.'jwp_a11yc_checks');
define('A11YC_TABLE_BCHECKS',     $wpdb->prefix.'jwp_a11yc_bchecks');
define('A11YC_TABLE_BNGS',        $wpdb->prefix.'jwp_a11yc_bngs');
define('A11YC_TABLE_ISSUES',      $wpdb->prefix.'jwp_a11yc_issues');
define('A11YC_TABLE_ISSUESBBS',   $wpdb->prefix.'jwp_a11yc_issuesbbs');
define('A11YC_TABLE_SETTINGS',    $wpdb->prefix.'jwp_a11yc_settings');
define('A11YC_TABLE_MAINTENANCE', $wpdb->prefix.'jwp_a11yc_maintenance');
define('A11YC_TABLE_ICLS',        $wpdb->prefix.'jwp_a11yc_icls');
define('A11YC_TABLE_ICLSSIT',     $wpdb->prefix.'jwp_a11yc_iclssit');

// table
define('A11YC_TABLE_DATA', $wpdb->prefix.'jwp_a11yc_data');

// urls
$urlbase = A11YC_URL.'?page=jwp-a11y%2F';
define('A11YC_SETTING_URL',     $urlbase.'jwp_a11y_setting&amp;a=');
define('A11YC_BULK_URL',        $urlbase.'jwp_a11y_bulk&amp;a=');
define('A11YC_PAGE_URL',        $urlbase.'jwp_a11y_page&amp;a=');
define('A11YC_ISSUE_URL',       $urlbase.'jwp_a11y_issue&amp;a=');
define('A11YC_DATA_URL',        $urlbase.'jwp_a11y_data&amp;a=');
define('A11YC_DOWNLOAD_URL',    $urlbase.'jwp_a11y_download&amp;a=');
define('A11YC_ICL_URL',         $urlbase.'jwp_a11y_icl&amp;a=');
define('A11YC_CHECKLIST_URL',   $urlbase.'jwp_a11y_checklist&amp;a=check&amp;url=');
define('A11YC_RESULT_EACH_URL', $urlbase.'jwp_a11y_result&amp;a=each&amp;a11yc_each=1&amp;url=');
define('A11YC_IMAGELIST_URL',   $urlbase.'jwp_a11y_image&amp;a=view&amp;url=');
define('A11YC_DOC_URL',         $urlbase.'jwp_a11y_doc&amp;a=each&amp;criterion=');
define('A11YC_LIVE_URL',        $urlbase.'jwp_a11y_live&amp;a=view&amp;url=');
