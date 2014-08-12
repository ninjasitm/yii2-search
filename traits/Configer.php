<?php
namespace nitm\traits;

use nitm\helpers\Session;
use nitm\helpers\Helper;
use nitm\helpers\Cache as CacheHelper;

 /**
  * Configuration traits that can be shared
  */
trait Configer {
	
	public static $settings = [];
	
	/**
	 * Get a setting value 
	 * @param string $setting the locator for the setting
	 */
	public static function setting($setting)
	{
		$hierarchy = explode('.', $setting);
		switch($hierarchy[0])
		{
			case '@':
			array_pop($hierarchy[0]);
			break;
			
			case static::isWhat():
			break;
			
			default:
			array_unshift($hierarchy, static::isWhat());
			break;
		}
		@eval("\$ret_val = static::\$settings['".Helper::splitf($hierarchy, "']['")."'];");
		return $ret_val;
	}
	
	/*
	 * Initialize configuration
	 * @param string $container
	 */
	public static function initConfig($container=null)
	{
		Session::del('settings.alerts');
		$module = \Yii::$app->getModule('nitm');
		$container = empty($container) ? $module->configOptions['container'] : $container;
		switch(1)
		{
			case !CacheHelper::cache()->exists('config-'.$container):
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
					case CacheHelper::cache()->exists('config-'.$container):
					$config = CacheHelper::cache()->get('config-'.$container);
					Session::set(Session::current.'.'.$container, $config);
					static::$settings[$container] = $config;
					break;
					
					case ($container == $module->configOptions['container']) && (!Session::isRegistered(Session::settings)):
					$config = $module->config->getConfig($module->configOptions['engine'], $container, true);
					Session::set(Session::settings, $config);
					break;
					
					case ($container != $module->configOptions['container']) && !isset(static::$settings[$container]):
					$config = $module->config->getConfig($module->configOptions['engine'], $container, true);
					CacheHelper::cache()->set('config-'.$container, $config, 120);
					Session::set(Session::current.'.'.$container, $config);
					static::$settings[$container] = $config;
					break;
				}
				break;
			}
		}
	}
}
?>