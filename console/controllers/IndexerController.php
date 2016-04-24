<?php

namespace nitm\search\console\controllers;

use nitm\models\DB;
use nitm\helpers\ArrayHelper;

class IndexerController extends \yii\console\Controller
{
	public $defaultAction = 'index';

	/**
	 * The mode for the indexer
	 */
	public $mode = 'feeder';

	/**
	 * The indexer to use
	 */
	public $indexer;

	/**
	 * The index to work on
	 */
	public $index;

	/**
	 * Should we re-isert all items?
	 */
	public $reIndex;

	/**
	 * The data type
	 */
	public $dataType;

	/**
	 * The data source. Either a string or
	 */
	public $dataSource;

	/**
	 * Verbosity
	 * 0 - No output
	 * 1 = Summary output
	 * 2 = Summary output + Item outpout
	 */
	public $verbose;

	/**
	 * Only do a dry run and don't actually index
	 */
	public $mock;

	protected $model;
	protected $dbModel;

	private $_config;

	public function initParameters()
	{
		$this->dbModel = new DB;
		$this->_config = $this->prepareDataSource();
		$this->indexer = $this->indexer ?: ArrayHelper::remove($this->_config, 'indexer', null);
		$this->getIndexer();
		$this->index = !isset($this->index) ? $this->dbModel->getDbName() : $this->index;
		$indexerClass = $this->indexer;
		$this->model = \Yii::createObject(array_merge([
			'class' => $this->indexer,
			'mode' => $this->mode,
			'index' => $this->index,
			'reIndex' => isset($this->reIndex) ? true : false,
			'verbose' => $this->verbose,
			'mock' => isset($this->mock) ? true : false
		], $this->_config));
	}

    public function actionIndex(array $types=null)
	{
		$this->initParameters();
		extract($this->getValidTypes($types));
		call_user_func([$this->model, 'set'.$source], $this->_config[$source]);
		$this->model->operation('index');
		return 0;
	}

	public function actionUpdate(array $types=null)
	{
		$this->initParameters();
		extract($this->getValidTypes($types));
		call_user_func([$this->model, 'set'.$source], $this->_config[$source]);
		$this->model->operation('update');
		return 0;
	}

    public function actionDeleteIndex(array $types=null)
	{
		$this->initParameters();
		$this->model->log("\n\tDeleting from index: ".$this->index."\n");
		extract($this->getValidTypes($types));
		if(sizeof($validTypes) >= 1)
		{
			if($all)
				echo "\n\t\e[31mWARNING\e[0m: You didn't specify which types to delete. Deleting ALL!!. Specify a CSV list if you need to be precise!!\n";
			call_user_func([$this->model, 'set'.$source], $this->_config[$source]);
			$this->model->log("\n\tSummary: \n");
			$this->model->log("\n\t".implode("\n\t", array_map(function ($type) {
				$stats = $this->getStats($type);
				return $type." -> Count: ".$stats['hits']['total'];
			}, $validTypes)));
			if($this->confirm("\n\n\tAre you sure you want to continue?"))
				$this->model->operation('delete');
			else
				echo "\nAborting delete operation\n";
		}
		else
		{
			echo "\tThere was an error doing delete on index: ".$this->model->index().":\n";
			echo "\n\t".implode("\t\n\t", $types)."\n";
		}
		return 0;
	}

	public function options($actionId)
	{
		return [
			'mode',
			'index',
			'indexer',
			'dataType',
			'dataSource',
			'verbose',
			'mock',
			'reIndex'
		];
	}

	/**
	 * Determine where the config information comes form
	 * @return array $config;
	 */
	protected function prepareDatasource()
	{
		if(file_exists($configFile = \Yii::getAlias($this->dataSource)))
			$config = require($configFile);
		else
		{
			echo "Not using a config file\nDatasource is ".$this->dataSource."\n";
			if(!isset($this->dataType))
				die("The dataType value must be set if not using a config file\n");
			else if(!isset($this->dataSource))
				die("No dataSource set!\n");
		}

		if(!isset($config))
			$config = [
				'_'.$this->dataType => explode(',', $this->dataSource)
			];
		return $config;
	}

	/**
	 * Determine where the indexer to user
	 * @return string $idexer Class;
	 */
	protected function getIndexer()
	{
		if(!class_exists($this->indexer))
			$this->indexer = \Yii::$app->getModule('nitm-search')->getIndexer($this->indexer);
		return $this->indexer;
	}

	protected function getStats($type)
	{
		return (array) $this->model->operation('stats', ['index' => $this->model->index(), 'type' => $type]);
	}

	protected function getValidTypes($types=null)
	{
		switch(1)
		{
			case isset($this->_config['classes']):
			$resolvedTypes = $this->_config['classes'];
			break;

			case isset($this->_config['tables']):
			$resolvedTypes = $this->_config['tables'];
			break;

			default:
			$resolvedTypes = $types;
			break;
		}
		$ret_val = [];
		$modelDataSource = 'not set';
		$all = false;
		/**
		 * If we're given a specific list of types/tables to deindex then check to make sure it exists according to our config
		 */
		if(isset($this->_config['tables'])) {
			$modelDataSource = 'tables';
			$this->configureConfigTables($types, $ret_val);
		} else if(isset($this->_config['classes'])) {
			$modelDataSource = 'classes';
			$this->configureConfigClasses($types, $ret_val);
		}

		return ['validTypes' => $ret_val, 'all' => count($types) == 0, 'source' => $modelDataSource];
	}

	/**
	 * Perform some configuration check if using tables
	 * @param  array $types      The types we want to specify. If empty then all tables will be indexed
	 * @param  array $validTypes We're going to be returning these calid types
	 * @return void
	 */
	protected function configureConfigTables($types, &$validTypes)
	{
		if(count($types)) {
			foreach($types as $idx=>$type) {
				if(isset($this->_config['tables']) && !in_array($type, $this->_config['tables'])) {
					unset($this->_config['tables'][array_search($type, $this->_config['tables'])]);
				} else {
					$validTypes[] = $type;
				}
			}
		}
	}

	/**
	 * Perform some configuration check if using tables
	 * @param  array $types      The types we want to specify. If empty then all tables will be indexed
	 * @param  array $validTypes We're going to be returning these calid types
	 * @return void
	 */
	protected function configureConfigClasses($types, &$validTypes)
	{
		if(count($types)) {
			foreach($this->_config['classes'] as $ns=>$classes)
			{
				$classTypes = array_map('strtolower', array_keys($classes));
				$toRemove = array_diff($classTypes, $types);
				foreach($toRemove as $remove) {
					if($remove === 'default')
						continue;
					$realKeys = preg_grep("/$remove/i", array_keys($this->_config['classes'][$ns]));
					foreach($realKeys as $key) {
						unset($this->_config['classes'][$ns][$key]);
					}
				}
			}
		}
		foreach($this->_config['classes'] as $ns=>$classes)
		{
			$userOptions = ArrayHelper::remove($classes, 'default', []);
			foreach($classes as $class=>$options)
			{
				$validTypes[] = strtolower($class);
				$customOptions = ArrayHelper::remove($options, 'exclusive', false) === true ? [] : array_merge_recursive($options, ArrayHelper::getValue($userOptions, 'global', []), ArrayHelper::getValue($userOptions, ArrayHelper::remove($options, 'type', null), []));
				$classes[$class] = $customOptions;
			}
			$this->_config['classes'][$ns] = $classes;
		}
	}
}

?>
