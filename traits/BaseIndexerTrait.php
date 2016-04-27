<?php

namespace nitm\search\traits;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */

trait BaseIndexerTrait
{
	public $mock;
	public $mode;
	public $stats = [];
	public $totals = ["index" => 0, "update" => 0, "delete" => 0, 'total' => 0, 'current' => 0];
	public $reIndex;
	public $progress = ["complete" => false, "separator" => ":", "op" => ["start" => 0, "end" => 0]];
	public $verbose = 0;
	public $offset = 0;
	public $limit = 100;
	public $model;
	public $info = [];

	protected $type;
	protected $idKey;

	protected $bulk = ["update" => [], "delete" => [], "index" => []];
	protected static $dbModel;
	protected $currentUser;
	protected $currentQuery;

	protected $_logText = '';
	protected $_info = ["info" => [], "table" => []];
	protected $_tables = [];
	protected $_classes = [];
	protected $_source;
	protected $_attributes =[];
	protected $_indexUpdate = [];
	protected $_operation = 'index';

	private $_stack = [];
	private $_queries = [];

	public function set_Tables($tables=[])
	{
		$this->_source = 'tables';
		$this->_tables = $tables;
	}

	/**
	 * Set the classes being used for this operation
	 * @param array $classes
	 * [
	 *		'namespace'
	 * 		'class' => [options]
	 * 		...
	 * ]
	 */
	public function setClasses($classes=[])
	{
		$this->_source = 'classes';
		$this->_classes = $classes;
	}

	public function getClasses()
	{
		return $this->_classes;
	}

	protected static function getDbModel()
	{
		if(!isset(static::$dbModel))
			static::$dbModel = new DB;
		return static::$dbModel;
	}

	public function getTables()
	{
		return $this->_tables;
	}

	public function getSource()
	{
		return $this->_source;
	}

	public function attributes()
	{
		return is_object($this->model) ? $this->model->attributes() : $this->_attributes;
	}

    /**
	 * Get the duration of the seach query
     */
    public function duration()
    {
		return $this->stats['end'] - $this->stats['start'];
    }

	public function reset()
	{
		$this->bulk = [];
		$this->_indexUpdate = [];
		$this->totals = ["index" => 0, "update" => 0, "delete" => 0, 'total' => 0, 'current' => 0];
	}

	public function start()
	{
		$this->stats['start'] = microtime(1);
	}

	/**
		Wrapper function for legacy support
	*/
	public function finish()
	{
		$this->log("\n\tIndex Summary:\n\tOn ".date("F j, Y @ g:i a")." user ".$this->currentUser." performed index operations. Summary as follows:\n\tIndexed (".$this->totals['index'].") Re-Indexed (".$this->totals['update'].") items De-Indexed (".$this->totals['delete'].") items Index total (".$this->totals['total'].")\n");
		$this->progress['op']['end'] = microtime(true);
		$this->stats['end'] = microtime(true);
	}

	/**
	 * Function to return the progress for a particular activity
	 * @param string $for The unique index to measure progress with
	 * @param int $count The current item being worked on
	 * @param int $total The total number of entries to gather progress for
	 * @param int $chunk The number of percentage chunks to check for
	 * @param boolean $print Print progress?
	 * @return int
	*/
	public function progress($for, $count=null, $total=null,  $chunks=null, $print=false)
	{
		$ret_val = null;
		$this->stats['progress'][$for]["count"] = is_null($count) ? $this->stats['progress'][$for]["count"]+1 : $count;
		$this->stats['progress'][$for]["chunks"] = is_null($chunks) ? 4 : $chunks;
		$this->stats['progress'][$for]["chunk"] = (!isset($this->stats['progress'][$for]["chunk"]) || $this->stats['progress'][$for]["chunk"] > $this->stats['progress'][$for]["chunks"]) ? 1 : $this->stats['progress'][$for]["chunk"];
		$this->stats['progress'][$for]["total"] = is_null($total) ? $this->stats['progress'][$for]["total"] : $total;
		$this->stats['progress'][$for]["sub_chunk"] = (!isset($this->stats['progress'][$for]["sub_chunk"])) ? (1/$this->stats['progress'][$for]["chunks"]) : $this->stats['progress'][$for]["sub_chunk"];

		//$this->log("Subchunk == ".$this->stats['progress'][$for]["sub_chunk"]."\n");
		switch($this->stats['progress'][$for]["total"] == 0)
		{
			case false:
			$this->stats['progress'][$for]['chunk_count'] = round(($this->stats['progress'][$for]['total']/$this->stats['progress'][$for]['chunks']) * $this->stats['progress'][$for]['chunk']);
			$this->stats['progress'][$for]['sub_chunk_count'] = round((($this->stats['progress'][$for]['chunk']-1) + $this->stats['progress'][$for]["sub_chunk"]) * ($this->stats['progress'][$for]['total']/$this->stats['progress'][$for]['chunks']));
			switch(1)
			{
				case (round($this->stats['progress'][$for]['chunk_count']) > 0) && ($this->stats['progress'][$for]['count'] / round($this->stats['progress'][$for]['chunk_count']) == 1):
				$this->stats['progress'][$for]['chunk']++;
				$ret_val = round((($this->stats['progress'][$for]['chunk_count']/$this->stats['progress'][$for]['total']) * 100));
				switch($print)
				{
					case true:
					$this->log(" $ret_val% ");
					break;
				}
				break;

				case $this->stats['progress'][$for]['sub_chunk_count'] == $this->stats['progress'][$for]["count"]:
				$this->stats['progress'][$for]["sub_chunk"] += (1/$this->stats['progress'][$for]["chunks"]);
				switch($print)
				{
					case true:
					$this->log(".");
					break;
				}
				break;
			}
			switch(($this->stats['progress'][$for]["sub_chunk"] + (1/$this->stats['progress'][$for]["chunks"])) > 1)
			{
				case true:
				$this->stats['progress'][$for]["sub_chunk"] = (1/$this->stats['progress'][$for]["chunks"]);
				break;
			}
			break;
		}
		return $ret_val;
	}

	protected function progressStart($type, $total=null)
	{
		$this->stats['progress'][$type]['count'] = 0;
		if(!is_null($total))
			$this->stats['progress'][$type]['total'] = $total;
	}

	protected function progressTotal($type, $total)
	{
	}

	/**
		Protected functions
	*/

	/**
	 * Set the indexed field to 1
	 */
	protected function updateIndexed()
	{
		if(!$this->mock)
		{
			if(array_key_exists('indexed', $this->_attributes) === false)
			{
				$this->addIndexField();
			}
			static::getDbModel()->update('indexed', 1)
				->where(null, array_values($this->_indexUpdate), array_keys($this->_indexUpdate), null, 'OR')
				->run();
		}
		$this->_indexUpdate = [];
	}

	/**
	 * Perform logging of data is necessary
	 * @param string $bulkIndex The index to pull summary informaiton from
	 */
	protected function bulkLog($bulkIndex)
	{
		if(isset($this->bulk[$bulkIndex]) && ($this->verbose >= 2))
		{
			foreach($this->bulk[$bulkIndex] as $idx=>$entry)
			{
				//$this->progress($bulkIndex, null, null, null, true);
				$curLogText = "\n\tStart $bulkIndex item summary:\n";
				$curLogText .= "\t\t".\nitm\helpers\Helper::splitc(array_keys($entry), array_values($entry), '=', "\n\t\t", "'");
				$curLogText .= "\n\tEnd $bulkIndex item summary.\n";
				$this->totals['current']++;
				$this->_indexUpdate[$entry['id']] = $this->idKey;
				$this->log($curLogText, 2);
			}
		}
	}

	public function log($text, $levelRequired=1)
	{
		$this->_logText .= $text;
		if((int)$this->verbose >= $levelRequired)
			echo $text;
	}

	protected function printDebug($value)
	{
		echo $this->_logtext."\n";
	}

	/**
	 * Add a field to the table
	 * @param array $field
	 */
	protected function addIndexField()
	{
		$field = ['Field' => 'indexed', 'Type' => 'tinyint(1)', 'Null' => 'NO'];
		return (new DB)->addFieldTo($field, static::index(), static::type());
	}

	/**
	 * Check to see if a field exists in the current set of fields
	 * @param string $field The field to be checked
	 * return @bool
	 */
	protected function checkKey($field)
	{
		$ret_val = false;
		if($field)
		{
			foreach($this->keys as $idx=>$key)
			{
				if($key['Field'] == $field)
				{
					$ret_val = true;
					break;
				}
			}
		}
		return $ret_val;
	}

	public static function fingerprint($item, $length=24)
	{
		$string = json_encode($item);
		$ret_val = hash('tiger128,3', $string);
		if($length >= 1)
			return substr(hash('tiger128,3', $string), 0, $length);
		return $ret_val;
	}

	protected function prepareMetainfo($type, $table, $query)
	{
		$this->setIndexType($type, $table);
		$schema = \Yii::$app->db->schema;
		$this->_attributes = ArrayHelper::toArray($schema->getTableSchema($table)->columns);
		$this->idKey = $schema->getTableSchema($table)->primaryKey;
		$this->idKey = is_array($this->idKey) ? array_pop($this->idKey) : $this->idKey;
	}

	public function run()
	{
		foreach($this->_stack as $table=>$options)
		{
			if(isset($options['namespace']))
				$this->namespace = $options['namespace'];
			$this->prepareMetainfo(ArrayHelper::getValue($options, 'type', $table), ArrayHelper::getValue($options, 'table', $table), $options['args'][0]);
			$result = call_user_func_array($options['worker'], $options['args']);
			unset($this->_stack[$table]);
		}
	}

	/**
	 * Go through Data and sort entries by those that need to be updated, created and deleted
	 * @param array $data
	 */
	protected function parseChunk($data=[])
	{
		$ret_val = false;
		if(is_array($data) && count($data)) {
			$this->totals['chunk'] = count($data);
			$this->offset += $this->totals['chunk'];
			$this->log(" [".$this->totals['chunk']."]: ");
			$this->progressStart('prepare', sizeof($data));
			foreach($data as $idx=>$result)
			{
				$this->progress('prepare', null, null, null, true);
				if(!isset($result['_id'])) {
					$id = $result[$this->idKey];
					$result['_id'] = $id;
				} else {
					$id = $result['_id'];
				}
				$result['_md5'] = isset($result['md5']) ? $result['_md5'] : $this->fingerprint($result);
				$this->bulkSet($this->type, $id, $result);
			}
			$ret_val = true;
		} else {
			$this->bulkSet($this->type, null);
			$this->log("\n\t\tNothing to ".$this->type." from: ".static::index()."->".static::type());
		}
		return $ret_val;
	}

	/**
	 * Parse the data in chunks to make it a bit more efficient
	 * @param object $query
	 * @param function $callback in the format:
	 * 	function ($query, $limit, $offset) {
		 ...f
	 *	}
	 */
	protected function parse($query, $callback)
	{
		$this->currentQuery = $query;
		//Is the indexed column available? If not find everything
		$findAll = array_key_exists('indexed', $this->_attributes) ? false :true;
		if(($findAll === false && !$this->reIndex) && ($this->type != 'delete'))
			$query->where(['not', 'indexed=1']);
		$this->log("\n\tPerforming: ".$this->type." on ".static::index()."->".static::type()." Items: ".$query->count());

		//Do something before $this->type
		$eventName = strtoupper('before_search_'.$this->type);
		$this->trigger(constant('\nitm\search\BaseIndexer::'.$eventName));

		$this->totals[$this->type] = $query->count();
		$this->totals['current'] = $this->totals['chunk'] = $this->offset = 0;

		for($i=0; $i<($query->count()/$this->limit);$i++)
		{
			$this->totals['current'] = 0;
			$this->offset = $this->limit * $i;
			$this->log("\n\t\tPreparing chunk: $i [starting at ".$this->offset."] ");
			switch(1)
			{
				case $query->count() <= $this->limit:
				$count =  $query->count();
				break;

				case ($query->count() - ($this->offset)) > $this->limit:
				$count = $this->limit;
				break;

				default:
				$count = $query->count() - ($this->offset);
				break;
			}
			$this->progressStart($this->type, $count);
			$callback($query, $this);
			$this->currentQuery = null;
		}

		//Do something after indexing
		$eventName = strtoupper('after_search_'.$this->type);
		$this->trigger(constant('\nitm\search\BaseIndexer::'.$eventName));

		$this->totals['total'] += $this->totals['current'];
		$this->log("\tResult: ".$this->totals['current']." out of ".$this->totals[$this->type]." entries\n\n");
	}

	protected function bulk($index=null, $id=null)
	{
		if($index == static::type())
			return $this->bulk[static::type()];
		else if(is_null($id))
			return $this->bulk[static::type()][$index];
		else if(isset($this->bulk[static::type()][$index][$id]))
			return $this->bulk[static::type()][$index][$id];
	}

	protected function bulkSize($index)
	{
		$ret_val = 0;
		if(isset($this->bulk[static::type()][$index]))
			$ret_val = sizeof($this->bulk[static::type()][$index]);
		return $ret_val;
	}

	protected function bulkSet($index, $id, $value=null)
	{
		if(is_null($value))
			$this->bulk[static::type()][$index] = $id;
		else
			$this->bulk[static::type()][$index][$id] = $value;
	}

	protected function tableInfo($key=null, $table=null)
	{
		$table = $table ?: static::$_table;
		if(is_null($key))
			return static::getDbModel()->getTableStatus($table);
		else
			return ArrayHelper::getValue(static::getDbModel()->getTableStatus($table), $key, null);
	}

	/**
	 * Add operations to be completed
	 */
	protected function stack($id, $options)
	{
		$this->_stack[$id] = $options;
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

	public static function prepareModel($model, $options)
	{
		$ret_val = $model->getAttributes();
		if(isset($options['queryOptions']['with']))
		{
			foreach((array) $options['queryOptions']['with'] as $with)
			{
				$relation = 'get'.$with;
				if($model->hasMethod($relation)) {
					$query = $model->$relation();
					$ret_val[$with] = $query->asArray()->all();
					if(!$query->multiple)
						$ret_val[$with] = array_shift($ret_val[$with]);
				}
			}
		}
		return static::normalize($ret_val, false, $model->getTableSchema()->columns);
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
				$class = $namespace.$modelName;
				if(is_null($class::getDb()->schema->getTableSchema($class::tablename(), true)))
					continue;
				$localOptions = array_merge((array)$attributes, $options);
				$model = new $class($localOptions);
				$tableName = \yii\helpers\Inflector::slug($class::tableName(), '');
				$this->stack($modelName, [
					'type' => $model->isWhat(null, true),
					'table' => $tableName,
					'namespace' => $namespace,
					'worker' => [$this, 'parse'],
					'args' => [
						$class::find($model),
						function ($query, $self) use($model) {
							$start = microtime(true);
							$self->log("\n\t\t".$query->limit($self->limit)
								->offset($self->offset)->createCommand()->getSql(), 3);
							$results = $query->limit($self->limit)
								->offset($self->offset)
								->all();
							//Doing this here to merge related records
							foreach($results as $idx=>$record) {
								$results[$idx] = array_merge($record->toArray(), static::populateRelatedRecords($record));
								if(!isset($results[$idx]['_id']))
									$results[$idx]['_id'] = $record->getId();
							}
							$self->parseChunk($results);
							$result = $self->runOperation($model);
							$result['took'] = round(microtime(true) - $start, 2);
							$this->log("\n\t\tResult: Took \e[1m".$result['took']."s\e[0m Errors: ".($result['errors'] ? "\e[31myes" : "\e[32mno")."\e[0m")."\n";
							$this->log("\n\t\t\t".($this->verbose >= 2 ? "Debug: ".var_export(@$result, true) : ''), 2);
							$this->log("\n");
							return $result;
						}
					]
				]);
			}
		}
	}

	/**
	 * Use tables to prepare the data
	 * @param array $optionsOptions for the query
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
					new Query(),
					function ($query, $self) use($options, $table) {
						$query->from($table)
							->limit($self->limit, $self->offset);
						foreach($options as $method=>$params) {
							$query->$method($params);
						}
						$self->parseChunk($query->all());
						return $self->runOperation($table);
					}
				]
			]);
		}
	}

	protected function runOperation($baseModel)
	{
		$result = call_user_func_array([$this, $this->_operation], [$baseModel]);
		$resultArray = @json_decode($result, true);
		return $resultArray;
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
			return $this->operationStats($options);
			break;

			default:
			echo "\n\tUnknown operation: $operation. Exiting...";
			break;
		}
	}

	/**
	 * Populate related records for the specified object
	 * @param  \nitm\models\Data $object [description]
	 * @return array         Populte records
	 */
	protected static function populateRelatedRecords($object, $parent='')
	{
		$ret_val = [];
		foreach($object->relatedRecords as $name=>$value)
		{
			$path = implode('.', array_filter(array_merge([$name], explode('.', $parent))));
			if(is_array($value)) {
				foreach($value as $v) {
					if(is_object($v))
						$ret_val[$name][] = array_merge(ArrayHelper::toArray($v), static::populateRelatedRecords($v, $path));
					else
						$ret_val[$name][] = $v;
				}
			} else if(is_object($value) && $object->hasRelation($name)) {
				$ret_val[$name] = $value->toArray();
			} else if(is_object($value)) {
				$ret_val[$name] = ArrayHelper::toArray($value);
			} else {
				$ret_val[$name] = $value;
			}
		}
		return $ret_val;
	}
}
?>
