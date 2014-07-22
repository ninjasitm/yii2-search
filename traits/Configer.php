<?php
namespace nitm\traits;

use nitm\helpers\Session;
use nitm\helpers\Helper;

 /**
  * Configuration traits that can be shared
  */
trait Configer {
	
	public static $settings;
	
	/**
	 * Get a setting value 
	 * @param string $setting the locator for the setting
	 */
	public static function setting($setting)
	{
		@eval("\$ret_val = static::\$settings['".Helper::splitf(explode('.', $setting), "']['")."'];");
		return $ret_val;
	}
	
	/*
	 * Initialize configuration
	 * @param string $container
	 */
	public static function initConfig($container=null)
	{
		$module = \Yii::$app->getModule('nitm');
		$container = empty($container) ? $module->configOptions['container'] : $container;
		switch(1)
		{
			case !isset(static::$settings[$container]):
			case ($container == $module->configOptions['container']) && (!Session::isRegistered(Session::settings)):
			$module->config->setEngine($module->configOptions['engine']);
			$module->config->setType($module->configOptions['engine'], $container);
			switch($module->configOptions['engine'])
			{
				case 'file':
				$module->setDir($module->configOptions['dir']);
				break;
			}
			switch(1)
			{
				case Session::isRegistered(Session::current.'.'.$container):
				static::$settings[$container] = Session::getval(Session::current.'.'.$container);
				break;
				
				default:
				switch(1)
				{
					case ($container == $module->configOptions['container']) && (!Session::isRegistered(Session::settings)):
					$config = $module->config->getConfig($module->configOptions['engine'], $container, true);
					Session::set(Session::settings, $config);
					break;
					
					default:
					if(!isset(static::$settings[$container]))
					{
						$config = $module->config->getConfig($module->configOptions['engine'], $container, true);
						static::$settings[$container] = $config;
						Session::set(Session::current.'.'.$container, $config);
					}
					break;
				}
				break;
			}
		}
	}
}
?>