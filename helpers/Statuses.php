<?php

namespace nitm\helpers;

class Statuses 
{
	/**
	 * Indicator types supports
	 */
	protected static $indicators = [
		'error' => 'bg-danger',
		'resolved' => 'bg-resolved',
		'duplicate' => 'bg-duplicate',
		'default' => '',
		'success' => 'bg-success',
		'info' => 'bg-info',
		'disabled' => 'bg-disabled',
		'warning' => 'bg-warning'
	];
	
	/**
	 * Indicator types supports
	 */
	protected static $listIindicators = [
		'error' => 'list-group-item list-group-item-danger',
		'default' => 'list-group-item',
		'success' => 'list-group-item  list-group-item-success',
		'info' => 'list-group-item list-group-item-info',
		'warning' => 'list-group-item list-group-item-warning',
		'disabled' => 'list-group-item list-group-item-disabled'
	];
	
	/**
	 * Get the class indicator value for a generic item
	 * @param string $indicator
	 * @return string $css class
	 */
	public static function getIndicator($indicator=null)
	{
		$indicator = is_null($indicator) ? 'default' : $indicator;
		return self::$indicators[$indicator];
	}
	
	/**
	 * Get the class indicator value for a list item
	 * @param string $indicator
	 * @return string $css class
	 */
	public static function getListIndicator($indicator=null)
	{
		$indicator = is_null($indicator) ? 'default' : $indicator;
		return self::$listIndicators[$indicator];
	}
}
?>