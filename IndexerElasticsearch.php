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
	use traits\BaseIndexerTrait;

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

	protected $columns = [];

	const MODE_FEEDER = 'feeder';
	const MODE_RIVER = 'river';

	public function init()
	{
		parent::init();
		$this->setIndex(!isset($this->_database) ? static::getDbModel()->getDbName() : $this->_database);
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
		$mapping[$this->index()]['mappings'][$this->type()]['_all'] = ['enabled' => true];
		foreach($attributes as $attribute)
		{
			switch(isset($options['queryOptions']['with']) && in_array($attribute, (array)$options['queryOptions']['with']))
			{
				case true:
				$attribute = current(explode('.', $attribute));
				$class = $this->namespace.$this->properClassName($this->type());
				$primaryModel = new $class;
				$relationGetter = 'get'.$attribute;
				$relatedQuery = $primaryModel->$relationGetter();
				$linkModelClass = $relatedQuery->modelClass;
				$relatedColumns = $linkModelClass::getTableSchema()->columns;
				$hasWildcardFields = false;
				$relatedAttributes = array_keys($relatedColumns);
				if(is_array($relatedQuery->select))
				{
					foreach($relatedQuery->select as $field)
					{
						if(strpos($field, '*') !== false || $field instanceof \yii\db\Expression) {
							$hasWildcardFields = true;
							break;
						}
					}
					$relatedAttributes = $hasWildcardFields ? array_keys($relatedColumns) : $relatedAttributes;
				}
				@$mapping[$this->index()]['mappings'][$this->type()]['properties'][$attribute] = [
					'type' => 'nested',
					'include_in_all' => true,
					'include_in_parent' => true
				];
				foreach($relatedAttributes as $relatedProperty)
				{
					@$mapping[$this->index()]['mappings'][$this->type()]['properties'][$attribute]['properties'][$relatedProperty] = $this->getFieldAttributes($attribute, $relatedColumns[$relatedProperty], true);
				}
				break;

				default:
				@$mapping[$this->index()]['mappings'][$this->type()]['properties'][$attribute] = $this->getFieldAttributes($attribute, $this->columns[$attribute]);
				break;
			}
		}
		$put = [
			'url' => '_mapping?ignore_conflicts=true',
			json_encode($mapping[$this->index()]['mappings']),
			true
		];
		if($this->reIndex)
			$this->apiInternal('delete', ['url' => '_mapping']);
		$this->apiInternal('put', $put);
	}

	protected function getFieldAttributes($field, $info, $all = false)
	{
		$info = \yii\helpers\ArrayHelper::toArray($info);
		$ret_val = [
			'include_in_all' => $all,
			'type' => $info['phpType'],
			'null_value' => 0
		];
		$baseType = array_shift(explode('(', $info['dbType']));
		switch($baseType)
		{
			case 'timestamp':
			$ret_val['null_value'] = '0000-00-00 00:00:00';
			$ret_val['type'] = 'date';
			$ret_val['format'] = "yyyy-MM-dd HH:mm:ss";
			$ret_val['boost'] = 2;
			$ret_val['store'] = true;
			$ret_val['ignore_malformed'] = true;
			$ret_val['include_in_all'] = true;
			break;

			case 'tinyint':
			case 'boolean':
			switch($info['dbType'])
			{
				case 'tinyint(1)':
				case 'boolean':
				$ret_val['type'] = 'boolean';
				$ret_val['null_value'] = false;
				break;

				default:
				$ret_val['type'] = 'integer';
				$ret_val['null_value'] = 0;
				break;
			}
			$ret_val['store'] = true;
			$ret_val['include_in_all'] = true;
			break;

			case 'bigint':
			$ret_val['store'] = true;
			$ret_val['include_in_all'] = true;
			$ret_val['type'] = 'long';
			break;

			case 'smallint':
			case 'int':
			$ret_val['type'] = 'integer';
			$ret_val['store'] = true;
			$ret_val['include_in_all'] = true;
			$ret_val['null_value'] = 0;
			$ret_val['ignore_malformed'] = true;
			$ret_val['doc_values'] = true;
			$ret_val['index'] = 'not_analyzed';
			break;

			case 'resource':
			case 'binary':
			case 'blob':
			$ret_val['type'] = 'string';
			$ret_val['store'] = 'no';
			$ret_val['index'] = 'no';
			break;

			case 'text':
			case 'varchar':
			$ret_val['type'] = 'string';
			$ret_val['store'] = true;
			$ret_val['index'] = 'analyzed';
			$ret_val['boost'] = 2;
			$ret_val['norms']['enabled'] = true;
			$ret_val['include_in_all'] = true;
			break;
		}
		$userMapping = isset($this->module->settings['elasticsearch']['mapping'][$field]) ? $this->module->settings['elasticsearch']['mapping'][$field] : null;
		$ret_val = is_null($userMapping) ? $ret_val : array_merge($ret_val, $userMapping);
		return $ret_val;
	}

	/**
	 * Get the mapping from the ElasticSearch server
	 * @return array
	 */
	public function getMapping()
	{
		return static::api('get', ['url' => '_mapping', null, false]);
	}

	protected function operationStats($options)
	{
		$options['url'] = '_search';
		$oldJdbcOptions = $this->jdbc['options'];
		$this->jdbc['options'] = [
			'q' => '*',
			'search_type' => 'count'
		];
		$originalMock = $this->mock;
		$this->mock = false;
		$ret_val = $this->apiInternal('get', $options);
		$this->mock = $originalMock;
		$this->jdbc['options'] = $oldJdbcOptions;
		return $ret_val;
	}

	protected static function getJdbcOptions()
	{
		return [
			'options' => [],
			'url' => 'mysql:'.static::getDbModel()->host,
			'username' => static::getDbModel()->username,
			'password' => static::getDbModel()->getPassword()

		];
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
	 * Use SQL to push using a PUT command
	 * @param string $sql SQL
	 */
	public function pushRiver($sql)
	{
		$options = [
			'type' => 'jdbc',
			'jdbc' => [
				'url' => $this->jdbc['url'].'/'.static::index(),
				'user' => $this->jdbc['username'],
				'password' => $this->jdbc['password'],
				'sql' => $sql,
				'index' => static::index(),
				'type' => static::type(),
			]
		];
		if(isset($this->schedule))
			$options['jdbc']['schedule'] = $this->schedule;
		if(!$this->mock)
			static::getDb()->post(['_river', static::index(), static::type()], $this->jdbc['options'], json_encode($options), true);
		else
			$this->log(json_encode($options, JSON_PRETTY_PRINT));
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
			$url = isset($options['index']) ? [$options['index']] : [static::index()];
			$url[] = isset($options['type']) ? $options['type'] : static::type();
			if(isset($options['url']))
				array_push($url, $options['url']);
			else
				$options['url'] = [$url];
			unset($options['index'], $options['type'], $options['url']);
			$apiOptions = isset($options['apiOptions']) ? $options['apiOptions'] : [];
			array_unshift($options, implode('/', array_filter($url)), (array)$apiOptions);
			return call_user_func_array([static::getDb(), $operation], $options);
			break;
		}
	}

	public final function operationIndex($baseModel)
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
				$create[] = json_encode(['index' => ['_type' => $baseModel->isWhat(), '_id' => $item['id']]]);
				$item['_md5'] = $this->fingerprint($item);
				$create[] = json_encode($item);
				$this->totals['current']++;
			};
			$create[] = "{}";
			$options = [
				'url' => '_bulk',
				implode("\n", $create),
				true
			];
			if(sizeof($create) >= 1 && ($result = $this->apiInternal('post', $options))) {
				$this->bulkLog('index');
			} else {
				$this->log("\n\t\tNothing to Index\n");
			}
			$ret_val = $result;
		}

		if(isset($put) && $put == true)
			$this->updateIndexed();

		$this->bulk[$this->type] = [];
		return $ret_val;
	}

	public function operationUpdate()
	{
		if(($this->mode != 'river') && ($this->bulkSize('update') >= 1))
		{
			$update = [];
			$delete = [];
			/**
			 * First get all of the ids and fingerprints
			 */
			$this->jdbc['options']['ids'] = array_map(function ($value) {
				return $value['id'];
			}, $this->bulk('update'));
			$existing = $this->apiInternal('get', [
				'url' => '_mget',
			]);
			$this->log("\n\t\tUpdating :");
			foreach((array)$existing as $item=>$idx)
			{
				$this->normalize($item);
				$this->progress('update', null, null, null, true);
				if(array_key_exists($item['id'], $this->bulk('update')))
				{
					if($self->bulk('update', $item['id'])['_md5'] != $item['_md5'])
					{
						$update[] = ['update' => ['_id' => $item['id']]];
						unset($item['id']);
						$item['_md5'] = $this->fingerprint($this->bulk('update', $item['id']));
						$update[] = $item;
						$sel->totals['current']++;
					}
				}
				else
				{
					$delete[] = $item['id'];
				}
			};
			$url = [$this->index(), $this->type(), '_bulk'];
			if((sizeof($update) >= 1) && ($result = $this->apiInternal('post', [
				'url' => '_bulk',
				implode("\n", json_encode($udpate))
			])))
			{
				$this->log("\n\\t\tUpdated: ".$this->totals['current']." out of ".$this->tableInfo('Rows')." entries\n");
				$this->bulkLog('update');
			}
			else
			{
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
					if(isset($item['id']) && !is_null($item['id']))
					{
						$this->bulkSet('delete', $idx, [
							'delete' => [
								'_id' => $item['id'],
								'_type' => static::type(),
								'_index' => static::index()
							]
						]);
						$this->totals['current']++;
					}
				}
				$options = [
					'url' => '_bulk',
					implode("\n", array_map('json_encode', $this->bulk('delete'))),
				];
				if($result = $this->apiInternal('post', $options))
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
