<?php
namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/**
 * IndexerElastisearch class
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
	
class IndexerElasticsearch extends BaseElasticSearch
{
	use traits\BaseIndexer;
	
	public $deleteIndexes;
	
	/**
	 * Schedule for the river 
	 * Example:  = "05 00 * * * ?" runs every 5 minutes
	 */
	public $schedule;
	/**
	 * By default use node 0
	 */
	public $node = 0;
	/**
	 * The URL for the elasticsearch server
	 */
	public $url;
	/**
	 * The JDBC infomration if necessary. The following format is supported:
	 * [
	 *		'url' => URL of the database server including port (jdbc:mysql:localhost:3306)
	 * 		'username' => Username,
	 *		'password' => Password,
	 * 		'options' => [] Options for the PUT request
	 * ]
	 */
	public $jdbc;
	
	public $nestedMapping = [];
	
	const MODE_FEEDER = 'feeder';
	const MODE_RIVER = 'river';
	
	public function init()
	{
		parent::init();
		$this->dbModel = new DB;
		$this->setIndex(!isset($this->_database) ? $this->dbModel->getDbName() : $this->_database);
		$this->url = !isset($this->url) ? \Yii::$app->elasticsearch->nodes[$this->node] : $this->url;
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
		 * Handle certain after index functions
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
				$columns = $event->sender->attributes();
				$attributes = array_keys($columns);
				if(is_array($options) && isset($options['withThese']))
					$attributes = array_merge($attributes, $options['withThese']);
				$mapping = $event->sender->getMapping();
				$mapping = sizeof($mapping) == 0 ? [] : $mapping;
				foreach($attributes as $attribute)
				{
					switch(isset($options['withThese']) && in_array($attribute, (array)$options['withThese']))
					{
						case true:
						@$mapping[$this->indexName()]['mappings'][$this->type()]['properties'][$attribute] = [
							'type' => 'nested',
							'include_in_parent' => true
						];
						$class = $this->namespace.$this->properClassName($event->sender->type());
						$primaryModel = new $class;
						$relationGetter = 'get'.$attribute;
						$relatedQuery = $primaryModel->$relationGetter();
						$linkModelClass = $relatedQuery->modelClass;
						$relatedColumns = $linkModelClass::getTableSchema()->columns;
						$relatedAttributes = is_array($relatedQuery->select) ? $relatedQuery->select : array_keys($relatedColumns);
						foreach($relatedAttributes as $relatedProperty)
						{
							switch($relatedColumns[$relatedProperty]->phpType)
							{
								case 'resource':
								case '':
								$type = 'string';
								break;
								
								default:
								$type = $relatedColumns[$relatedProperty]->phpType;
								break;
							}
							@$mapping[$this->indexName()]['mappings'][$this->type()]['properties'][$attribute]['properties'][$relatedProperty] = ['type' => $type];
						}
						break;
						
						default:
						switch($columns[$attribute]['phpType'])
						{
							case 'resource':
							case '':
							$type = 'string';
							break;
							
							default:
							$type = $columns[$attribute]['phpType'];
							break;
						}
						@$mapping[$this->indexName()]['mappings'][$this->type()]['properties'][$attribute]['type'] = $type;
						break;
					}
				}
				$put = [
					'url' => '_mapping?ignore_conflicts=true',
					json_encode($mapping[$this->indexName()]['mappings']), 
					true
				];
				//$this->api('delete', ['url' => '_mapping']);
				$this->api('put', $put);
			}
			$event->handled = true;
		});
		/**
		 * Delete the ammping after deleting an index
		 */
		$this->on(BaseIndexer::AFTER_SEARCH_DELETE, function ($event) {
			$this->api('delete', ['url' => '_mapping']);
			$event->handled = true;
		});
	}
	
	/**
	 * Get the mapping from the ElasticSearch server
	 * @return array
	 */
	public function getMapping()
	{
		return $this->api('get', ['url' => '_mapping', null, false]);
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
					'queryFilters' => [
						'indexby' => 'primaryKey'
					]
				];
				break;
				
				case 'delete':
				$options = [
					'queryFilters' => [
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
			$options['url'] = '_search';
			$oldJdbcOptions = $this->jdbc['options'];
			$this->jdbc['options'] = [
				'q' => '*',
				'search_type' => 'count'
			];
			$originalMock = $this->mock;
			$this->mock = false;
			$ret_val = $this->api('get', $options);
			$this->mock = $originalMock;
			$this->jdbc['options'] = $oldJdbcOptions;
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
	public function prepare($operation='index', $queryFilters=[])
	{
		$this->type = $operation;
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
			if(!isset($this->jdbc))
			{
				//By default use components value
				$this->jdbc = [
					'options' => [],
					'url' => 'mysql:'.$this->dbModel->host,
					'username' => $this->dbModel->username,
					'password' => $this->dbModel->getPassword()
					
				];
			}
			$prepare = 'FromSql';
			$dataSource = '_tables';
			break;
		}
		if(is_array($this->$dataSource) && empty($this->$dataSource))
			return false;
		$prepare = 'prepare'.$prepare;
		$this->$prepare($queryFilters);
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
			$this->log("\tDoing SQl River Push: ".static::indexName()."->$table Items: ".$this->tableInfo('Rows')."\n");
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
	 * Use SQL to push using a PUT command
	 * @param string $sql SQL
	 */
	public function pushRiver($sql)
	{
		$options = [
			'type' => 'jdbc',
			'jdbc' => [
				'url' => $this->jdbc['url'].'/'.static::indexName(),
				'user' => $this->jdbc['username'],
				'password' => $this->jdbc['password'],
				'sql' => $sql,
				'index' => static::indexName(),
				'type' => static::type(),
			]
		];
		if(isset($this->schedule))
			$options['jdbc']['schedule'] = $this->schedule;
		if(!$this->mock)
			$this->getDb()->post(['_river', static::indexName(), static::type()], $this->jdbc['options'], json_encode($options), true);
		else
			$this->log(json_encode($options, JSON_PRETTY_PRINT));
	}
	
	/**
	 * Use model classes to gather data
	 */
	public function prepareFromClasses($options=[])
	{
		if(empty($this->_classes))
			return;
		foreach($this->_classes as $namespace=>$classes)
		{
			foreach($classes as $modelName=>$attributes)
			{
				$localOptions = $options;
				$class = $namespace.$modelName;
				$class::$initClassConfig = false;
				$localOptions['initLocalConfig'] = false;
				$localOptions = array_merge((array)$attributes, $localOptions);
				$model = new $class($localOptions);
				$this->stack($model->tableName(), [
					'type' => $model->isWhat(),
					'namespace' => $namespace,
					'worker' => [$this, 'parse'],
					'args' => [
						$class::find($model), 
						function ($query, $self) {
							$results = $query->limit($self->limit)
								->offset($self->offset)
								->all();
							//Doing this here to merge related records
							foreach($results as $idx=>$related)
							{
								$related = array_merge($related->getAttributes(), ArrayHelper::toArray($related->relatedRecords));
								$results[$idx] = $related;
							}
							$self->parseChunk($results);
							return $self->runOperation();
						}
					]
				]);
			}
		}
	}
	
	/**
	 * Use tables to prepare the data
	 */
	public function prepareFromTables($options=[])
	{
		if(empty($this->_tables))
			return;
		foreach($this->_tables as $table)
		{
			$this->stack($table, [
				'worker' => [$this, 'parse'],
				'args' => [
					$this->dbModel, 
					function ($query, $self) use($options) {
						$query->select(@$options['queryFilters']['select'])
						 ->limit($self->limit, $self->offset);
						if(isset($options['queryFilters']['where']))
							call_user_func_array([$query, 'where'], $options['queryFilters']['where']);
						$query->run();
						$self->parseChunk($query->result(DB::R_ASS, true));
						return $self->runOperation();
					}
				]
			]);
		}
	}
	
	/**
	 * Perform an operation
	 * @param string $operation
	 * @param array $data
	 */
	public function api($operation, $options)
	{
		switch($this->mock)
		{
			case true:
			return true;
			break;
			
			default:
			$url = isset($options['index']) ? [$options['index']] : [$this->indexName()];
			$url[] = isset($options['type']) ? $options['type'] : $this->type();
			if(isset($options['url']))
				array_push($url, $options['url']);
			unset($options['index'], $options['type'], $options['url']);
			array_unshift($options, implode('/', array_filter($url)), (array)$this->jdbc['options']);
			return call_user_func_array([$this->getDb(), $operation], $options);
			break;
		}
	}
	
	protected function runOperation()
	{
		call_user_func([$this, $this->_operation]);
	}
	
	public final function operationIndex()
	{
		$ret_val = false;
		$this->totals['current'] = 0;
		$now = strtotime('now');
		$index_update = [];
		if(($this->mode != 'river') && (sizeof($this->bulkSize('index')) >= 1))
		{
			$create = [];
			$this->log("\n\t\tIndexing :");
			foreach($this->bulk('index') as $idx=>$item)
			{
				$this->progress('index', null, null, null, true);
				$create[] = json_encode(['index' => ['_type' => static::type(), '_id' => $item['_id']]]);
				$item['_md5'] = $this->fingerprint($item);
				$create[] = json_encode($item);
				$this->totals['current']++;
			};
			$options = [
				'url' => '_bulk', 
				implode("\n", $create), 
				true
			];
			if(sizeof($create) >= 1 && $this->api('post', $options))
			{
				$this->bulkLog('index');
			}
			else
			{
				$this->log("\n\t\tNothing to Index\n");
			}
			$this->log("\n");
			$ret_val = true;
			$this->totals['index'] += $this->totals['current'];
			$this->totals['total'] += $this->totals['current'];
			$this->totals['chunk'] += $this->totals['current'];
		}
		if(isset($put) && $put == true)
		{
			$this->updateIndexed();
		}
		$this->bulk[$this->type] = [];
	}
	
	public function operationUpdate()
	{
		if(($this->mode != 'river') && ($this->bulkSize('update') >= 1))
		{
			$this->totals['current'] = 0;
			$update = [];
			$delete = [];
			/**
			 * First get all of the ids and fingerprints
			 */
			$this->jdbc['options']['ids'] = array_map(function ($value) {
				return $value['_id'];
			}, $this->bulk('update'));
			$existing = $this->api('get', [
				'url' => '_mget',
			]);
			$this->log("\n\t\tUpdating :");
			foreach((array)$existing as $item=>$idx) 
			{
				$this->progress('update', null, null, null, true);
				if(array_key_exists($item['_id'], $this->bulk('update')))
				{
					if($self->bulk('update', $item['_id'])['_md5'] != $item['_md5'])
					{
						$update[] = ['update' => ['_id' => $item['_id']]];
						unset($item['_id']);
						$item['_md5'] = $this->fingerprint($this->bulk('update', $item['_id']));
						$update[] = $item;
						$sel->totals['current']++;
					}
				}
				else
				{
					$delete[] = $item['_id'];
				}
			};
			$url = [$this->indexName(), $this->type(), '_bulk'];
			if((sizeof($update) >= 1) && $this->api('post', [
				'url' => '_bulk',
				implode("\n", json_encode($udpate))
			]))
			{
				$this->log("\n\\t\tUpdated: ".$this->totals['current']." out of ".$this->tableInfo('Rows')." entries\n");
				$this->bulkLog('update');
			}
			else
			{
				$this->log("\n\t\tNothing to Update\n");
			}
			$this->log("\n");
			$ret_val = true;
			$this->totals['update'] += $this->totals['current'];
			$this->totals['chunk'] += $this->totals['current'];
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
			$this->totals['current'] = 0;
			if(!$this->mock)
			{
				$this->log("\n\t\tDeleting :");
				foreach($this->bulk('delete') as $idx=>$item)
				{
					$this->progress('delete', null, null, null, true);
					if(isset($item['_id']) && !is_null($item['_id']))
					{
						$this->bulkSet('delete', $idx, [
							'delete' => [
								'_id' => $item['_id'],
								'_type' => static::type(),
								'_index' => static::indexName()
							]
						]);
						$this->totals['current']++;
					}
				}
				$options = [
					'url' => '_bulk', 
					implode("\n", array_map('json_encode', $this->bulk('delete'))),
				];
				if($this->api('post', $options))
				{
					$this->log("\n\t\tDeleted: ".$this->totals['current']." entries\n");
					$this->bulkLog('delete');
				}
				$this->totals['total'] -= $this->totals['current'];
				$this->totals['chunk'] += $this->totals['current'];
			}
			else
			{
				$ret_val = true;
			}
		}
		
	}
}
?>