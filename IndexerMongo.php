<?php
namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/**
 * IndexerMongo class
 * Can be extended later but by default
 * will include base methods for adding 
 * data to a search database and automatic
 * indexing of that information
 * 
 * There will also be the ability to
 * add search information
 * There will be the ability to update keywords
 * and such
*/
	
class IndexerMongo extends BaseMongo
{
	use traits\BaseIndexerTrait;
	
	public $deleteIndexes;
	
	/**
	 * By default use node 0
	 */
	public $node = 0;
	
	/**
	 * The URL for the mongo server
	 */
	public $url;
	
	public $nestedMapping = [];
	
	protected $columns = [];
	
	const MODE_FEEDER = 'feeder';
	const MODE_RIVER = 'river';
	
	public function init()
	{
		parent::init();
		$this->setIndex(!isset($this->_database) ? static::getDbModel()->getDbName() : $this->_database);
		$this->url = !isset($this->url) ? \Yii::$app->mongo->nodes[$this->node] : $this->url;
		$this->initEvents();
	}
	
	public function behaviors()
	{
		$behaviors = [
			'BaseIndexer' => [
				'class' => BaseIndexer::className()
			]
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	protected function initEvents()
	{
		/**
		 * Handle certain before index functions
		 */
		$this->on(BaseIndexer::BEFORE_SEARCH_INDEX, function ($event) {
			$getter = 'get'.$event->sender->getSource();
			if($event->sender->hasMethod($getter))
			{
				$allInfo = $event->sender->$getter();
				switch($event->sender->getSource())
				{
					case 'classes':
					$options = $allInfo[$this->namespace][$this->properClassName($event->sender->type())];
					break;
					
					case 'tables':
					break;
					
					default:
					return;
					break;
				}
				$this->columns = $event->sender->attributes();
				$attributes = array_keys($this->columns);
				if(is_array($options) && isset($options['queryOptions']['with']))
					$attributes = array_merge($attributes, $options['queryOptions']['with']);
					
				/*
				 * Update the mapping
				 */
				$this->updateMapping($event->sender->getMapping(), $attributes, $options);
			}
			$event->handled = true;
		});
		/**
		 * Delete the ammping after deleting an index
		 */
		$this->on(BaseIndexer::AFTER_SEARCH_DELETE, function ($event) {
			$this->apiInternal('delete', ['url' => '_mapping']);
			$event->handled = true;
		});
	}
	
	/**
	 * Update the mapping for an elsasticsearch index
	 */
	public function updateMapping($mapping, $attributes, $options=[])
	{
		$mapping = count($mapping) == 0 ? [] : $mapping;
		foreach($attributes as $attribute)
		{
			switch(isset($options['queryOptions']['with']) && in_array($attribute, (array)$options['queryOptions']['with']))
			{
				case true:
				$class = $this->namespace.$this->properClassName($event->sender->type());
				$primaryModel = new $class;
				$relationGetter = 'get'.$attribute;
				$relatedQuery = $primaryModel->$relationGetter();
				$linkModelClass = $relatedQuery->modelClass;
				$relatedColumns = $linkModelClass::getTableSchema()->columns;
				$relatedAttributes = is_array($relatedQuery->select) ? $relatedQuery->select : array_keys($relatedColumns);
				@$mapping[$attribute] = ['name' => $attribute];
				foreach($relatedAttributes as $relatedProperty)
					@$mapping[$attribute][$relatedProperty] = $this->getFieldAttributes($attribute, $relatedColumns[$relatedProperty], true);
				break;
				
				default:
				@$mapping[$attribute] = $this->getFieldAttributes($attribute, $this->columns[$attribute]);
				break;
			}
		}
		if($this->reIndex)
			$this->apiInternal('deleteIndex');
		if(count($mapping) >= 1)
			foreach($mapping as $index)
				$this->apiInternal('createIndex', $index);
	}
	
	protected function getFieldAttributes($field, $info, $all = false)
	{
		$info = \yii\helpers\ArrayHelper::toArray($info);
		$ret_val = ['name' => $field];
		$userMapping = isset($this->module->settings['mongo']['mapping'][$field]) ? $this->module->settings['mongo']['mapping'][$field] : null;
		$ret_val = is_null($userMapping) ? $ret_val : $userMapping;
		return $ret_val;
	}
	
	/**
	 * Get the mapping from the Mongo server
	 * @return array
	 */
	public function getMapping()
	{
		return static::api('getIndexes');
	}
	
	public function operation($operation, $options=[])
	{
		$operation = strtolower($operation);
		switch($operation)
		{
			case 'index':
			case 'delete':
			case 'update':
			switch($operation)
			{
				case 'update':
				$options = [
					'queryOptions' => [
						'indexby' => 'primaryKey'
					]
				];
				break;
				
				case 'delete':
				$options = [
					'queryOptions' => [
						'select' => 'primaryKey',
					]
				];
				break;
				
				default:
				$options = [];
				break;
			}
			$this->prepare($operation, $options);
			$this->run();
			$this->finish();
			break;
			
			case 'stats':
			$originalMock = $this->mock;
			$this->mock = false;
			//$ret_val = $this->apiInternal('get', $options);
			$ret_val = [];
			$this->mock = $originalMock;
			return $ret_val;
			break;
			
			default:
			echo "\n\tUnknown operation: $operation. Exiting...";
			break;
		}
	}
	
	/**
	 * Prepare data to be indexed/checked
	 * @param int $mode
	 * @param boolean $useClasses Use the namespaced calss to pull data?
	 * @return bool
	 */
	public function prepare($operation='index', $queryOptions=[])
	{
		$this->type = 'prepare';
		$this->_operation = 'operation'.ucfirst($operation);
		switch($this->mode)
		{
			case self::MODE_FEEDER:
			switch(is_array($this->_classes) && !empty($this->_classes))
			{
				case true:
				$prepare = 'FromClasses';
				$dataSource = '_classes';
				break;
				
				default:
				$prepare = 'FromTables';
				$dataSource = '_tables';
				break;
			}
			break;
			
			default:
			$prepare = 'FromSql';
			$dataSource = '_tables';
			break;
		}
		if(is_array($this->$dataSource) && empty($this->$dataSource))
			return false;
		$prepare = 'prepare'.$prepare;
		$this->$prepare($queryOptions);
		$this->type = $operation;
	}
	
	/**
	 * Use SQL to push using a PUT command
	 */
	public function prepareFromSql()
	{
		if(empty($this->_tables))
			return;
		$success = false;
		foreach($this->_tables as $table)
		{
			$this->log("\tDoing SQl River Push: ".static::index()."->$table Items: ".$this->tableInfo('Rows')."\n");
			$this->stack($table, [
				'worker' => [$this, 'parse'],
				'args' => [
					$model, 
					function ($query, $self) {
						$query->select()
							->limit($self->limit, $self->offset)
							->build();
						$sql = $query->getSql();
						$self->bulkSet($self->type, $sql);
						return $self->pushRiver($sql);
					}
				]
			]);
		}
		
		if($success == true)
		{
			$this->updateIndexed();
		}
	}
	
	/**
	 * Perform an operation
	 * @param string $operation
	 * @param array $data
	 */
	protected function apiInternal($operation, $options)
	{
		$options['mock'] = $this->mock;
		$options['index'] = isset($options['index']) ? $options['index'] : $this->index();
		$options['type'] = isset($options['type']) ? $options['type'] : $this->type();
		$this->log("\n\t\tUrl is ".strtoupper($operation)." ".implode('/', array_filter((array)$options['url'])), 3);
		$this->log("\n\t\t".var_export($options, true), 5);
		return static::api($operation, $options);
	}
	
	public static function api($operation, $options)
	{
		//print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3));
		//exit;
		switch(isset($options['mock']) && ($options['mock'] === true))
		{
			case true:
			return true;
			break;
			
			default:
			unset($options['index'], $options['type'], $options['url'], $options['mock']);
			//$apiOptions = isset($options['apiOptions']) ? $options['apiOptions'] : [];
			//array_unshift($options, implode('/', array_filter($url)), (array)$apiOptions);
			return call_user_func_array([static::getDb()->getCollection(static::collectionName()), $operation], $options);
			break;
		}
	}
	
	protected function runOperation()
	{
		$result = call_user_func([$this, $this->_operation]);
		$resultArray = json_decode($result, true);
		$this->log("\n\t\t\t"."Result: Took \e[1m".$resultArray['took']."ms\e[0m Errors: ".($resultArray['errors'] ? "\e[31myes" : "\e[32mno")."\e[0m");
		$this->log("\n\t\t\t".($this->verbose >= 2 ? "Debug: ".var_export(@$result, true) : ''), 2);
	}
	
	public final function operationIndex()
	{
		$ret_val = [
			'success' => false,
		];
		$now = strtotime('now');
		$index_update = [];
		if(($this->mode != 'river') && ($this->bulkSize('index') >= 1))
		{
			$create = [];
			$this->log("\n\t\tIndexing :");
			foreach($this->bulk('index') as $idx=>$item)
			{
				$this->normalize($item);
				$this->progress('index', null, null, null, true);
				$item['_md5'] = $this->fingerprint($item);
				$create[] = $item;
				$this->totals['current']++;
			};
			$options = [
				$create
			];
			if(sizeof($create) >= 1 && ($result = $this->apiInternal('batchInsert', $options)))
			{
				$this->bulkLog('index');
			}
			else
			{
				$this->log("\n\t\tNothing to Index\n");
			}
			$ret_val = $result;
		}
		if(isset($put) && $put == true)
		{
			$this->updateIndexed();
		}
		$this->bulk[$this->type] = [];
		return $ret_val;
	}
	
	public function operationUpdate()
	{
		if(($this->mode != 'river') && ($this->bulkSize('update') >= 1))
		{
			$update = [];
			$delete = [];
			
			$existing = iterator_to_array($this->apiInternal('find'), false);
			
			$this->log("\n\t\tUpdating :");
			foreach((array)$existing as $item=>$idx) 
			{
				$this->normalize($item);
				$this->progress('update', null, null, null, true);
				if(array_key_exists($item['_id'], $this->bulk('update')))
				{
					if($self->bulk('update', $item['_id'])['_md5'] != $item['_md5'])
					{
						$condition = ['_id' => new \MongoId($item['_id'])];
						$item['_md5'] = $this->fingerprint($this->bulk('update', $item['_id']));
						if($this->apiInternal('update', $condition, $item))
						{
							$update[] = $item;
							$sel->totals['current']++;
						}
					}
				}
				else
				{
					$delete[] = $item['_id'];
				}
			};
			if(sizeof($update) >= 1) {
				$this->log("\n\\t\tUpdated: ".$this->totals['current']." out of ".$this->tableInfo('Rows')." entries\n");
				$this->bulkLog('update');
			}
			else {
				$this->log("\n\t\tNothing to Update\n");
			}
			$this->log("\n\tDebug: ".var_export($result, true)."\n", 2);
			$this->log("\n");
			$ret_val = $result;
			if(sizeof($delete) >= 1)
			{
				$this->bulkSet('delete', $delete);
				$this->operationDelete();
			}
		}
	}
	
	public function operationDeleteIndex()
	{
		
	}
	
	public function operationDelete()
	{
		$ret_val = false;
		if($this->bulkSize('delete') >= 1)
		{
			if(!$this->mock)
			{
				$this->log("\n\t\tDeleting :");
				foreach($this->bulk('delete') as $idx=>$item)
				{
					$this->progress('delete', null, null, null, true);
					if(isset($item['_id']) && !is_null($item['_id']))
					{
						$this->bulkSet('delete', $idx, [
							'_id' =>new \MongoID( $item['_id'])
						]);
						$this->totals['current']++;
					}
				}
				$options = [
					$this->bulk('delete')
				];
				if($result = $this->apiInternal('update', $options))
				{
					$this->log("\n\t\tDeleted: ".$this->totals['current']." entries\n");
					$this->bulkLog('delete');
				}
				$this->log("\n\tDebug: ".var_export($result, true)."\n", 2);
				$this->totals['total'] -= $this->totals['current'];
				$ret_val = $result;
			}
			else
			{
				$ret_val = '{"took":"1ms","Errors":false}';
			}
		}
		
	}
}
?>