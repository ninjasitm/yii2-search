<?php

namespace nitm\search;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
	use \nitm\traits\EventTraits;

	/**
	 * @string the this id
	 */
	public $id = 'nitmSearch';

	public $engine;

	public $settings = [];

	public $disableIndexing = false;

	/**
	 * Callback used to get/prepare the changed atributes for indexing
	 * Should have the follwing signature:
	 * function ($model) {
	 * 	return [$attributes, $targetModel];
	 * }
	 * @var function
	 */
	public $attributesFilter;

	/**
	 * The class mappings in the format:
	 * ```
	 * 	{namespace} => [
	 * 		These defaults will bemergd with the model that identitifes by {type}
	 * 		{default} => [
	 * 			{global} => [
	 * 				Options global default attributes shared among all models
	 * 			],
	 * 			{type} => [
	 * 				options,
	 * 				...
	 * 			]
	 * 		]
	 * 		{modelName} => [
	 * 			{type} => The type that is used to fetch the default options,
	 * 			SearchClass options,
	 * 			...
	 * 		]
	 * 	]
	 * ```
	 * @var $classes
	 */
	public $classes = [];

	public $namespaces;

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

	const EVENT_START = 'nitm.search.start.single';
	const EVENT_PROCESS = 'nitm.search.process.single';
	const EVENT_START_INDEX = 'nitm.search.start.index';
	const EVENT_PROCESS_INDEX = 'nitm.search.process.index';

	public function init()
	{
		parent::init();
		/**
		 * Aliases for nitm search this
		 */
		\Yii::setAlias('nitm/search', dirname(__DIR__));

		$this->namespaces = array_merge($this->defaultNamespaces(), (array)$this->namespaces);
	}

	public function getUrls($id='nitm-search')
	{
		return [
            $id => $id,
            $id . '/<controller:[\w\-]+>' => $id . '/<controller>/index',
            $id . '/<controller:[\w\-]+>/<action:[\w\-]+>' => $id . '/<controller>/<action>'
        ];
	}

	public function bootstrap($app)
	{
		/**
		 * Setup urls
		 */
        $app->getUrlManager()->addRules($this->getUrls(), false);

		//Setup the event handlers for two events: start and process
		$this->attachToEvents([
			self::EVENT_START => [$this, 'prepareRecord'],
			self::EVENT_PROCESS =>  [$this, 'processRecord']
		]);
	}

	public function getNamespaces($namespace=[])
	{
		if(!empty($namespace))
			$this->namespaces = array_merge($this->namespaces, (array) $namespace);
		return $this->namespaces;
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
		if(isset($this->index))
			$ret_val = $this->index;
		else
			$ret_val = \nitm\models\DB::getDbName();
		return $ret_val;
	}

	public function fingerprint($item)
	{
		return BaseIndexer::fingerprint($item);
	}

	public function getModelOptions($class)
	{
		$class = explode('\\', $class);
		$modelClass = array_pop($class);
		$namespace = '\\'.implode('\\', $class).'\\';
		$attributes = \yii\helpers\ArrayHelper::getValue($this->classes, $namespace.'.'.$modelClass, null);
		return $attributes;
	}

	protected function prepareRecord($event)
	{
		return $event;
	}

	public function processRecord($event)
	{
		if($this->disableIndexing)
			return;

		$indexer = $this->getIndexerObject();
		/**
		 * Need to enable better support for different search indexers here
		 */
		if($event->sender instanceof \nitm\models\Data)
			return $indexer->processModel($event->sender);
	}

	public function getAttributes($model)
	{
		if(is_callable($this->attributesFilter))
			return call_user_func_array($this->attributesFilter, [$model]);
		else
		 	return $model->getAttributes();
	}

	protected function defaultNamespaces()
	{
		return [
			'\\nitm\\widgets\\models',
			'\\nitm\\models'
		];
	}
}
