<?php

namespace nitm\module;

class Module extends \yii\base\Module
{
	public $controllerNamespace = 'nitm\module\controllers';
	
	/*
	 * @var array options for nitm\module\models\Configer
	 */
	public $configOptions = [
		'dir' => './config/ini/',
		'engine' => 'db',
		'container' => 'globals'
	];
	
	/*
	 * @var array options for nitm\module\models\Logger
	 */
	public $logOptions = [
		'db' => null,
		'table' => 'logs',
	];
	
	/*
	 * @var nitm\module\models\Configer object
	 */
	public $configModel;
	
	/*
	 * @var nitm\module\models\Logger object
	 */
	public $logModel;

	public function init()
	{
		parent::init();
		// custom initialization code goes here
		$this->configModel = new models\Configer($this->configOptions);
		$this->logModel = new models\Logger($this->logOptions);
		
		/*
		 * Aliases for nitm module
		 */
		\Yii::setAlias('nitm', __DIR__);
		\Yii::setAlias('nitm/widgets', dirname(dirname(__DIR__))."/yii2-nitm-widgets");
		//Alias for dektrium user module
		//\Yii::setAlias('dektrium/user',  '../../../../dektrium/yii2-user');
	}
}
