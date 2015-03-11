<?php

namespace nitm\search\console\controllers;

use nitm\models\DB;

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
		$this->getIndexer();
		$this->dbModel = new DB;
		$this->_config = $this->prepareDataSource();
		$this->index = !isset($this->index) ? $this->dbModel->getDbName() : $this->index;
		$this->model = new $this->indexer(array_merge([
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
		switch($this->indexer)
		{
			case 'elasticsearch':
			$this->indexer = '\nitm\search\IndexerElasticsearch';
			break;
			
			default:
			$this->indexer = '\nitm\search\Indexer';
			break;
		}
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
		$all = false;
		$modelDataSource = 'tables';
		switch(is_null($types))
		{
			case false:
			/**
			 * If we're given a specific list of types/tables to deindex then check to make sure it exists according to our config
			 */
			$toUnset = [];
			foreach($types as $idx=>$type)
			{
				if(isset($this->_config['tables']) && in_array($type, $this->_config['tables']))
				{
					$ret_val[] = $type;
					unset($toUnset[$type]);
				}
				else if(isset($this->_config['tables']) && !in_array($type, $this->_config['tables']))
					$toUnset[$type] = true;
				else if(isset($this->_config['classes']))
				{
					$modelDataSource = 'classes';
					foreach($this->_config['classes'] as $ns=>$classes)
					{
						foreach($classes as $class=>$options)
						{
							if(strtolower($class) == strtolower($type))
							{
								$ret_val[] = $type;
								unset($toUnset[$class]);
							}
							else if(!in_array(strtolower($class), $ret_val)) {
								$toUnset[$class] = true;
							}
						}
						
					}
				}
			}
			foreach($toUnset as $idx=>$remove)
			{
				switch($modelDataSource)
				{
					case 'tables':
					unset($this->_config[$modelDataSource][array_search($type, $this->_config[$modelDataSource])]);
					break;
					
					case 'classes':
					foreach($this->_config['classes'] as $ns=>$classes)
					{
						foreach($classes as $class=>$options)
						{
							if($idx == $class)
								unset($this->_config[$modelDataSource][$ns][$class]);
						}
					}
					break;
				}
			}
			break;
			
			default:
			$all = true;
			/**
			 * Otherwise were deleting everything!
			 */
			if(isset($this->_config['tables']) && in_array($type, $this->_config['tables']))
				$ret_val = $this->_config['tables'];
			else if(isset($this->_config['classes']))
			{
				$modelDataSource = 'classes';
				foreach($this->_config['classes'] as $ns=>$classes)
				{
					$classes = array_map('strtolower', array_keys($classes));
					$ret_val = array_merge($ret_val, $classes);
				}
			}
			break;
		}
		return ['validTypes' => $ret_val, 'all' => $all, 'source' => $modelDataSource];
	}
}

?>