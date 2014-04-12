<?php
namespace nitm\traits;

use nitm\helpers\Session;

 /**
  * Configuration traits that can be shared
  */
trait Configer {
	
	/*
	 * Initialize configuration
	 * @param string $container
	 */
	public function initConfig($container=null)
	{
		$module = \Yii::$app->getModule('nitm');
		$container = empty($container) ? $module->configOptions['container'] : $container;
		$module->configModel->setEngine($module->configOptions['engine']);
		$module->configModel->setType($module->configOptions['engine'], $container);
		switch($module->configOptions['engine'])
		{
			case 'file':
			$module->setDir($module->configOptions['dir']);
			break;
		}
		switch(Session::isRegistered(Session::current.'.'.$container))
		{
			case true:
			static::$settings[$container] = Session::getval(Session::current.'.'.$container);
			break;
			
			default:
			$config = $module->configModel->getConfig($module->configOptions['engine'], $container, true);
			switch($container)
			{
				case $module->configOptions['container']:
				Session::set(Session::settings, $config);
				break;
				
				default:
				static::$settings[$container] = $config;
				Session::set(Session::current.'.'.$container, $config);
				break;
			}
			break;
		}
	}
}
?>