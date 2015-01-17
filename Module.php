<?php

namespace nitm\search;

class Module extends \yii\base\Module
{	
	/**
	 * @string the module id
	 */
	public $id = 'nitmSearch';
	
	public $engine;
	
	public $settings = [];
	
	/**
	 * The name of the index to use
	 */
	public $index;
	
	public $controllerNamespace = 'nitm\search\controllers';
	
	private $_supportedIndexers = [
		'elasticsearch' => '\nitm\search\IndexerElasticsearch',
		'mongo' => '\nitm\search\IndexerMongo',
		'db' => '\nitm\search\Indexer',
	];

	public function init()
	{
		parent::init();
		/**
		 * Aliases for nitm search module
		 */
		\Yii::setAlias('nitm/search', dirname(__DIR__));
	}
	
	public function getIndexer($name=null)
	{
		$name = is_null($name) ? $this->engine : $name;
		if(isset($this->_supportedIndexers[$name]))
			return $this->_supportedIndexers[$name];
	}
	
	public function getIndex()
	{
		switch(1)
		{
			case isset($this->index):
			$ret_val = $this->index;
			break;
			
			default:
			$ret_val = \nitm\models\DB::getDbName();
			break;
		}
		return $ret_val;
	}
	
	public function fingerprint($item)
	{
		return BaseIndexer::fingerprint($item);
	}
}
