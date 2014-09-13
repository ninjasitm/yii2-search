<?php
namespace nitm\helpers\search;

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
	
class IndexerElasticsearch extends BaseIndexer implements IndexerInterface
{
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
	
	const MODE_FEEDER = 'feeder';
	const MODE_RIVER = 'river';
	
	public function init()
	{
		parent::init();
		$this->dbModel = new DB;
		$this->setIndex(!isset($this->_database) ? $this->dbModel->getDbName() : $this->_database);
		$this->url = !isset($this->url) ? \Yii::$app->elasticsearch->nodes[$this->node] : $this->url;
	}
	
	public function operation($operation)
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
			$model = new DB;
			$model->setTable($table);
			$this->prepareMetainfo($table);
			$this->stack('pushRiver'.$table, [
				'worker' => [$this, 'parseChunks'],
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
			foreach($classes as $class=>$attributes)
			{
				$localOptions = $options;
				$className = $namespace.$class;
				$className::$initClassConfig = false;
				$localOptions['initLocalConfig'] = false;
				$localOptions = array_merge((array)$attributes, $localOptions);
				$model = (new $className($localOptions));
				$this->prepareMetainfo($className::tableName());
				$this->stack('prepareFromClasses'.$class, [
					'worker' => [$this, 'parseChunks'],
					'args' => [
						$className::find($model), 
						function ($query, $self){
							$results = $query->limit($self->limit)
								->offset($self->offset)
								->all();
							foreach($results as $idx=>$model)
							{
								$model = array_merge($model->getAttributes(), ArrayHelper::toArray($model->relatedRecords));
								$results[$idx] = $model;
							}
							$self->parse($results);
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
			$model = new DB;
			$model->setTable($table);
			$this->prepareMetainfo($table);
			$this->stack('prepareFromTables'.$table, [
				'worker' => [$this, 'parseChunks'],
				'args' => [
					$model, 
					function ($query, $self) use($options) {
						$query->select(@$options['queryFilters']['select'])
						 ->limit($self->limit, $self->offset);
						if(isset($options['queryFilters']['where']))
							call_user_func_array([$query, 'where'], $options['queryFilters']['where']);
						$query->run();
						$self->parse($query->result(DB::R_ASS, true));
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
	public function execute($operation, $options)
	{
		switch($this->mock || ($operation != 'get'))
		{
			case true:
			return true;
			break;
			
			default:
			$url = [$this->indexName(), $this->type()];
			if(isset($options['url']))
			{
				array_push($url, $options['url']);
				unset($options['url']);
			}
			array_unshift($options, array_filter($url), $this->jdbc['options']);
			return json_decode(call_user_func_array([$this->getDb(), $operation], $options));
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
		if(($this->mode != 'river') && (sizeof($this->bulk('index')) >= 1))
		{
			$this->log("\tAdding to index: ".static::indexName().": ");
			$create = [];
			foreach($this->bulk('index') as $item=>$idx)
			{
				$this->progress('operationIndex', null, null, null, true);
				$create[] = ['index' => ['_id' => $item['_id']]];
				unset($item['_id']);
				$item['_md5'] = $this->fingerprint($item);
				$create[] = $item;
			};
			$options = [
				'url' => '_bulk', 
				implode("\n", json_encode($create)), 
				true
			];
			if($this->execute('post', $options))
			{
				$this->log("\n\tIndexed: ".$this->totals['current']." out of ".$this->tableInfo('Rows')." entries\n");
				$this->bulkLog('index');
			}
			else
			{
				$this->log("\n\t\tNothing to Index\n");
			}
			$this->log("\n");
			$ret_val = true;
			$this->totals['index'] += $this->totals['current'];
			$this->totals['totals'] += $this->totals['current'];
		}
		if(isset($put) && $put == true)
		{
			$this->updateIndexed();
		}
		$this->bulk[$this->type] = [];
	}
	
	public function operationUpdate()
	{
		if(($this->mode != 'river') && (sizeof($this->bulk('update')) >= 1))
		{
			$this->log("\tUpdating index: ".static::indexName().": ");
			$this->totals['current'] = 0;
			$update = [];
			$delete = [];
			/**
			 * First get all of the ids and fingerprints
			 */
			$this->jdbc['options']['ids'] = array_map(function ($value) {
				return $value['_id'];
			}, $this->bulk('update'));
			$existing = $this->execute('get', [
				'url' => '_mget',
			]);
			foreach((array)$existing as $item=>$idx) 
			{
				$this->progress('operationUpdate', null, null, null, true);
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
			if((sizeof($update) >= 1) && $this->execute('post', [
				'url' => '_bulk',
				implode("\n", json_encode($udpate))
			]))
			{
				$this->log("\n\Updated: ".$this->totals['current']." out of ".$this->tableInfo('Rows')." entries\n");
				$this->bulkLog('update');
			}
			else
			{
				$this->log("\n\t\tNothing to Update\n");
			}
			$this->log("\n");
			$ret_val = true;
			$this->totals['update'] += $this->totals['current'];
			if(sizeof($delete) >= 1)
			{
				$this->bulkSet('delete', $delete);
				$this->operationDelete();
			}
		}
	}
	
	public function operationDelete()
	{
		$ret_val = false;
		if(!sizeof($this->bulk('delete')) >= 1)
		{
			$this->log("\n\tDeleting from index: ".static::indexName().": ");
			$this->totals['current'] = 0;
			if(!$this->mock)
			{
				foreach($this->bulk('delete') as $idx=>$item)
				{
					$this->progress('operationDelete', null, null, null, true);
					$this->bulkSet('delete', $idx, [
						'delete' => [
							'_id' => $item['_id'],
							'_type' => static::type(),
							'_index' => static::indexName()
						]
					]);
					$this->totals['current']++;
					$this->totals['totals']--;
				}
				$options = [
					'url' => '_bulk', 
					implode("\n", json_encode($this->bulk('delete'))), 
					true
				];
				if((sizeof($this->bulk('delete')) >= 1) && $this->execute('delete', [
					'url' => '_bulk',
					implode("\n", json_encode($this->bulk('delete')))
				]))
				{
					$this->log("\n\Deleted: ".$this->totals['current']." entries\n");
					$this->bulkLog('delete');
				}
				else
				{
					$this->log("\n\t\tNothing to Delete\n");
				}
			}
			else
			{
				$ret_val = true;
			}
			$this->log('\n');
		}
		
	}
}
?>