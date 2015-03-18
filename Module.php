<?php

namespace nitm\search;

class Module extends \yii\base\Module
{	
	/**
	 * @string the this id
	 */
	public $id = 'nitmSearch';
	
	public $engine;
	
	public $settings = [];
	
	public $disableIndexing = false;
	
	public $classes = [];
	
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
	
	private $_indexers = [];
	
	const EVENT_PREPARE = 'prepare';
	const EVENT_PROCESS = 'process';

	public function init()
	{
		parent::init();
		/**
		 * Aliases for nitm search this
		 */
		\Yii::setAlias('nitm/search', dirname(__DIR__));
		
		$this->on(self::EVENT_PROCESS, [$this, 'processRecord']);
	}
	
	public function getIndexer($name=null)
	{
		$name = is_null($name) ? $this->engine : $name;
		if(isset($this->_supportedIndexers[$name]))
			return $this->_supportedIndexers[$name];
	}
	
	public function getIndexerObject($name=null)
	{
		$name = is_null($name) ? $this->engine : $name;
		if(!isset($this->_indexers[$name])) {
			$class = $this->getIndexer($name);
			$this->_indexers[$name] = new $class([
				'classes' => $this->classes
			]);
		}
		if(isset($this->_indexers[$name]))
			return $this->_indexers[$name];
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
	
	protected function getModelOptions($class)
	{
		$class = explode('\\', $class);
		$modelClass = array_pop($class);
		$namespace = '\\'.implode('\\', $class).'\\';
		$attributes = \yii\helpers\ArrayHelper::getValue($this->classes, $namespace.'.'.$modelClass, null);
		return $attributes;
	}
	
	protected function updateSearchEntry($event)
	{
		if($this->disableIndexing)
			return;
			
		$indexer = $this->getIndexer();
		/**
		 * Need to enable better support for different search indexers here
		 */
		$attributes = $indexer::prepareModel($event->sender, (array)$this->getModelOptions($event->sender->className()));
		$attributes['_md5'] = $this->fingerprint($attributes);
		
		switch($event->sender->getScenario())
		{
			case 'create':
			$op = '_'.$event->sender->getScenario();
			$method = 'put';
			break;
			
			case 'delete':
			$op = '';
			$method = 'delete';
			break;
			
			default:
			$op = '_update';
			$method = 'post';
			break;
		}
		
		$options = [
			'url' => $event->sender->isWhat().'/'.$event->sender->getId().$op, 
			json_encode($attributes), 
			true
		];
		return $indexer::api($method, $options);
	}
	
	protected function handleSearchRecord(&$event)
	{
		return $event;
	}
	
	public function processRecord($event)
	{
		$this->handleSearchRecord($event);
		return $this->updateSearchEntry($event);
	}
}
