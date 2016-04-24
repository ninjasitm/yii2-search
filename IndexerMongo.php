<?php
namespace nitm\search;

use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
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
					$options = $allInfo[$this->namespace][$this->properFormName($event->sender->type())];
					break;

					case 'tables':
					break;

					default:
					return;
					break;
				}
				$modelClass = $event->sender->currentQuery->modelClass;
				$model = new $modelClass;
				$fields = $model->allFields();
				$this->columns = $fields[2];
				$attributes = $this->columns;
				if(is_array($options) && isset($options['queryOptions']['with']))
					$attributes = array_unique(array_merge($attributes, $options['queryOptions']['with']));

				/*
				 * Update the mapping
				 */
				$this->updateMapping($event->sender->getMapping(), $attributes, $event, $options);
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
	 * Update the mapping for an elsasticsearch index
	 */
	public function updateMapping($mapping, $attributes, $event, $options=[])
	{
		$mapping = count($mapping) == 0 ? [] : $mapping;
		foreach($attributes as $attribute)
		{
			switch(isset($options['queryOptions']['with']) && in_array($attribute, (array)$options['queryOptions']['with']))
			{
				case true:
				$class = $this->namespace.$this->properFormName($event->sender->type());
				$primaryModel = new $class;
				$relationGetter = 'get'.$attribute;
				$relatedQuery = $primaryModel->$relationGetter();
				$linkModelClass = $relatedQuery->modelClass;
				$relatedColumns = $linkModelClass::getTableSchema()->columns;
				$relatedAttributes = is_array($relatedQuery->select) ? $relatedQuery->select : array_keys($relatedColumns);
				@$mapping[$attribute] = ['name' => $attribute];
				foreach($relatedAttributes as $relatedProperty) {
					$relatedProperty = Inflector::variablize($relatedProperty,'');
					@$mapping[$attribute][$relatedProperty] = $this->getFieldAttributes($attribute, $relatedColumns[$relatedProperty], true);
				}
				break;

				default:
				@$mapping[$attribute] = $this->getFieldAttributes($attribute, $this->columns[$attribute]);
				break;
			}
		}
		if($this->reIndex) {
			try {
				$this->api('dropAllIndexes');
			} catch (\Exception $e) {}
		}
		if(count($mapping)) {
			foreach($mapping as $name=>$index) {
				if(!is_string($name))
					continue;
				$result = static::getDb()->getCollection(static::collectionName())->mongoCollection->createIndex([
					'key' => $name
				]);
				//$result = $this->api('createIndex', [$name]);
			}
		}
	}

	protected function getFieldAttributes($field, $info, $all = false)
	{
		$info = \yii\helpers\ArrayHelper::toArray($info);
		$ret_val = ['name' => $field];
		$userMapping = isset($this->module->settings['mongo']['mapping'][$field]) ? $this->module->settings['mongo']['mapping'][$field] : null;
		$ret_val = is_null($userMapping) ? $ret_val : $userMapping;
		//if(@$info['isPrimaryKey']) {
		//	$ret_val['unique'] = true;
		//}
		return $ret_val;
	}

	/**
	 * Get the mapping from the Mongo server
	 * @return array
	 */
	public function getMapping()
	{
		return iterator_to_array(static::getDb()->getCollection(static::collectionName())->mongoCollection->listIndexes());
	}

	public function operationStats($options=[])
	{
		$originalMock = $this->mock;
		$this->mock = false;
		//$ret_val = $this->api('get', $options);
		$ret_val = [];
		$this->mock = $originalMock;
		return $ret_val;
	}

	/**
	 * Perform an operation
	 * @param string $operation
	 * @param array $data
	 */
	protected static function apiInternal($operation, $options=[])
	{
		//print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3));
		//exit;
		if(isset($options['mock']) && ($options['mock'] === true))
			return true;
		else {
			unset($options['index'], $options['type'], $options['url'], $options['mock']);
			//$apiOptions = isset($options['apiOptions']) ? $options['apiOptions'] : [];
			//array_unshift($options, implode('/', array_filter($url)), (array)$apiOptions);
			return call_user_func_array([static::getDb()->getCollection(static::collectionName()), $operation], $options);
		}
	}

	public function api($operation, $options=[])
	{
		$options['mock'] = $this->mock;
		$options['index'] = isset($options['index']) ? $options['index'] : $this->index();
		$options['type'] = isset($options['type']) ? $options['type'] : $this->type();
		$this->log("\n\t\tUrl is ".strtoupper($operation)." ".implode('/', array_filter((array)@$options['url'])), 3);
		$this->log("\n\t\t".var_export($options, true), 5);
		return static::apiInternal($operation, $options);
	}

	protected function runOperation($type)
	{
		$result = call_user_func_array([$this, $this->_operation], [$type]);
		$resultArray = is_array($result) ? $result : json_decode($result, true);
		return $result;
	}

	public final function operationIndex($baseModel)
	{
		$ret_val = [
			'success' => false,
			'took' => '0',
			'errors' => null
		];
		$start = strtotime('now');
		$index_update = [];
		if(($this->mode != 'river') && ($this->bulkSize('index') >= 1)) {
			$create = [];
			$this->log("\n\t\tIndexing :");
			$searchField = 'id';
			foreach($this->bulk('index') as $idx=>$item)
			{
				$this->normalize($item);
				$this->progress('index', null, null, null, true);
				$item['_type'] = $baseModel->isWhat();
				$item['_md5'] = $this->fingerprint($item, 24);
				$item['_id'] = new \MongoDB\BSON\ObjectID($item['_md5']);
				$create[] = $item;
				$this->totals['current']++;
			};
			if($this->reIndex) {
				$result = $this->api('remove', [
					[
						$searchField => ArrayHelper::getColumn($create, $searchField)
					]
				]);
			}
			$cursor = $this->api('find', [
				[
					$searchField => ArrayHelper::getColumn($create, $searchField)
				], [
					$searchField => true
				]
			]);
			$existing = [];
			if($cursor instanceof \MongoDB\Driver\Cursor)
				$existing = ArrayHelper::getColumn(iterator_to_array($cursor), $searchField);
			if(count($existing)) {
				$create = array_filter($create, function ($c) use($existing) {
					if(in_array($c['_md5'], $existing))
						return false;
					return true;
				});
			}
			$create = array_values($create);
			$options = [
				//Need to use a reference for the rows as MongoCollection reqires this for batch insert
				&$create, [
					'continueOnError' => true
				]
			];
			if(count($create) && ($result = $this->api('batchInsert', $options)))
				$this->bulkLog('index');
			else
				$this->log("\n\t\tNothing to Index\n");
			$ret_val['result'] = $result;
			$ret_val['success'] = true;
		}
		if(isset($put) && $put == true)
			$this->updateIndexed();
		$this->bulk[static::type()] = [];
		echo "\n\t\tFinished index operation on ".count($create)." items";
		return $ret_val;
	}

	public function operationUpdate()
	{
		if(($this->mode != 'river') && ($this->bulkSize('update') >= 1))
		{
			$update = [];
			$delete = [];

			$existing = iterator_to_array($this->api('find'), false);

			$this->log("\n\t\tUpdating :");
			foreach((array)$existing as $item=>$idx)
			{
				$this->normalize($item);
				$this->progress('update', null, null, null, true);
				if(array_key_exists($item['_id'], $this->bulk('update')))
				{
					if($self->bulk('update', $item['_id'])['_md5'] != $item['_md5'])
					{
						$condition = ['_id' => new \MongoDB\BSON\ObjectID($item['_id'])];
						$item['_md5'] = $this->fingerprint($this->bulk('update', $item['_id']));
						if($this->api('update', $condition, $item))
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
				if($result = $this->api('update', $options))
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
