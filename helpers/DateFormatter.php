<?php

namespace nitm\module\helpers;

/**
 * This is a utility classs for date formatting
 *
 */
class DateFormatter extends \yii\db\ActiveRecord
{
	private static $_formats = [
		"mysql_hr" => "%a %b %d %Y %l:%i%p",
		"mysql_ts" => "%Y-%m-%d %H:%i:%s",
		"mysql_today" => "Y-m-d H:i:s",
		"default" => "D M d Y h:iA",
		"today" => "D M j, Y",
	];

	const FORMAT_MYSQL_HR = 'mysql_hr';
	const FORMAT_MYSQL_TS = 'mysql_ts';
	const FORMAT_MYSQL_TODAY = 'mysql_hr';
	const FORMAT_TODAY = 'today';
	const FORMAT_DEFAULT = 'default';
	
	/**
	 * Return a date and formate to use
	 * @param string $format
	 * @param int | string $date
	 */
	public static function formatDate($format=null, $date=null)
	{
		$format = is_null($format) ? static::$_formats['default'] : static::getFormat($format);
		return date($format, strtotime(is_null($date) ? 'now' : $date));
	}
	
	/**
	 * Return the format if it is supported
	 */
	public static function getFormat($format='default')
	{
		switch(isset(static::$_formats[$format]))
		{
			case true:
			$ret_val = static::$_formats[$format];
			break;
			
			default:
			$ret_val = static::$_formats['default'];
			break;
		}
		return $ret_val;
	}
	
}
