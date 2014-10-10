<?php

namespace nitm\search;

class Module extends \yii\base\Module
{	
	/**
	 * @string the module id
	 */
	public $id = 'nitmSearch';
	
	public $controllerNamespace = 'nitm\search\controllers';

	public function init()
	{
		parent::init();
		/**
		 * Aliases for nitm search module
		 */
		\Yii::setAlias('nitm/search', dirname(__DIR__));
	}
}
