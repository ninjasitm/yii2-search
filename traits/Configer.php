<?php
namespace nitm\module\traits;

use nitm\module\models\Helper;

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
		switch(Helper::isRegistered(Helper::current.'.'.$container))
		{
			case true:
			$this->settings[$container] = Helper::getval(Helper::current.'.'.$container);
			break;
			
			default:
			$config = $module->configModel->getConfig($module->configOptions['engine'], $container, true);
			switch($container)
			{
				case $module->configOptions['container']:
				Helper::set(Helper::settings, $config);
				break;
				
				default:
				$this->settings[$container] = $config;
				Helper::getval(Helper::current.'.'.$container, $config);
				break;
			}
			break;
		}
	}
}
?>