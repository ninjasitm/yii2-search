<?php

namespace nitm;

use nitm\helpers\Session;
use nitm\models\DB;

class Module extends \yii\base\Module
{
	public $controllerNamespace = 'nitm\controllers';
	
	/*
	 * @var array options for nitm\models\Configer
	 */
	public $configOptions = [
		'dir' => './config/ini/',
		'engine' => 'db',
		'container' => 'globals'
	];
	
	/*
	 * @var array options for nitm\models\Logger
	 */
	public $logOptions = [
		'db' => null,
		'table' => 'logs',
	];
	
	/*
	 * @var nitm\models\Configer object
	 */
	public $configModel;
	
	/*
	 * @var nitm\models\Logger object
	 */
	public $logModel;

	public function init()
	{
		parent::init();
		// custom initialization code goes here
		$this->configModel = new models\Configer($this->configOptions);
		$this->logOptions['db'] = DB::getDefaultDbName();
		$this->logModel = new models\Logger();
		Session::del(Session::current);
		
		/**
		 * Aliases for nitm module
		 */
		\Yii::setAlias('nitm', dirname(__DIR__)."/yii2-nitm-module");
		\Yii::setAlias('nitm/widgets', dirname(__DIR__)."/yii2-nitm-widgets");
	}
}
