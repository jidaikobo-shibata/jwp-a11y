<?php
/**
 * A11yc\Db
 *
 * @package    part of A11yc
 * @author     Jidaikobo Inc.
 * @license    The MIT License (MIT)
 * @copyright  Jidaikobo Inc.
 * @link       http://www.jidaikobo.com
 */
namespace A11yc;

use A11yc\Model;

class Db extends \Kontiki\Db
{
	protected static $version = null;

	public static function hasDataTable($name = 'default')
	{
		if (A11YC_DB_TYPE == 'none') return false;
		return static::isTableExist(A11YC_TABLE_DATA, $name);
	}

	public static function hasLegacyTables($name = 'default')
	{
		if (A11YC_DB_TYPE == 'none') return false;

		$tables = array(
			A11YC_TABLE_SETUP_OLD,
			A11YC_TABLE_PAGES_OLD,
			A11YC_TABLE_CHECKS_OLD,
			A11YC_TABLE_CHECKS_NGS_OLD,
			A11YC_TABLE_BULK_OLD,
			A11YC_TABLE_BULK_NGS_OLD,
			A11YC_TABLE_MAINTENANCE_OLD,
			A11YC_TABLE_PAGES,
			A11YC_TABLE_UAS,
			A11YC_TABLE_CACHES,
			A11YC_TABLE_VERSIONS,
			A11YC_TABLE_RESULTS,
			A11YC_TABLE_BRESULTS,
			A11YC_TABLE_CHECKS,
			A11YC_TABLE_BCHECKS,
			A11YC_TABLE_BNGS,
			A11YC_TABLE_ISSUES,
			A11YC_TABLE_ISSUESBBS,
			A11YC_TABLE_SETTINGS,
			A11YC_TABLE_MAINTENANCE,
			A11YC_TABLE_ICLS,
			A11YC_TABLE_ICLSSIT,
		);

		foreach ($tables as $table)
		{
			if (static::isTableExist($table, $name)) return true;
		}
		return false;
	}

	/**
	 * init table
	 *
	 * @param String $name
	 * @return Void
	 */
	public static function initTable($name = 'default')
	{
		if (A11YC_DB_TYPE == 'none') return;
		// init default tables
		if (static::hasDataTable($name)) return;
		if (
			defined('A11YC_AUTO_CREATE_TABLES') &&
			A11YC_AUTO_CREATE_TABLES === false &&
			! static::hasLegacyTables($name)
		) return;
		self::initDefault($name);
	}

	/**
	 * init tables
	 *
	 * @param String $name
	 * @return Void
	 */
	private static function initDefault($name = 'default')
	{
		$set_utf8 = A11YC_DB_TYPE == 'mysql' ? ' SET utf8' : '';
		$auto_increment = A11YC_DB_TYPE == 'mysql' ? 'auto_increment' : '' ;

		// init store table
		$sql = 'CREATE TABLE '.A11YC_TABLE_DATA.' (';
		$sql.= '`id`       INTEGER NOT NULL PRIMARY KEY '.$auto_increment.',';
		$sql.= '`group_id` INTEGER NOT NULL DEFAULT 0,';
		$sql.= '`key`      VARCHAR(256) NOT NULL DEFAULT "",';
		$sql.= '`url`      VARCHAR(2048) NOT NULL DEFAULT "",';
		$texttype = A11YC_DB_TYPE == 'mysql' ? 'LONGTEXT' : 'TEXT' ;
		$sql.= '`value`    '.$texttype.' CHARACTER'.$set_utf8.',';
		$sql.= '`is_array` BOOL NOT NULL DEFAULT 0,';
		$sql.= '`version`  INTEGER NOT NULL DEFAULT 0';
		$sql.= ');';
		Db::execute($sql, array(), $name);

			if (A11YC_DB_TYPE == 'mysql')
			{
				$sql = 'ALTER TABLE '.A11YC_TABLE_DATA;
				// Limit indexed prefix lengths for long VARCHAR columns so modern
				// MySQL/MariaDB InnoDB setups can create the composite index.
				$sql.= ' ADD INDEX a11yc_data_idx(`group_id`, `url`(191), `key`(191), `version`)';
				Db::execute($sql, array(), $name);
			}
		else
		{
			$sql = 'CREATE INDEX a11yc_data_idx ON '.A11YC_TABLE_DATA;
			$sql.= ' (`group_id`, `url`, `key`, `version`)';
			Db::execute($sql, array(), $name);
		}

		// first create flag
		Model\Data::insert('db_create', 'global', true);
	}
}
