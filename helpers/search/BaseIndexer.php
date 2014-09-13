<?php

namespace nitm\helpers\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseIndexer extends BaseSearch
{
	public $mock;
	public $mode;
	public $stats = [];
	public $totals = ["index" => 0, "update" => 0, "delete" => 0, 'total' => 0];
	public $reIndex;
	public $progress = ["complete" => false, "separator" => ":", "op" => ["start" => 0, "end" => 0]];
	
	protected $type = 'index';
	protected $idKey;
	
	protected $bulk = ["update" => [], "delete" => [], "index" => []];
	protected $dbModel;
	protected $currentUser;
	
	protected $_logText = '';
	protected $_info = ["info" => [], "table" => []];
	protected $_tables = [];
	protected $_classes = [];
	protected $_attributes =[];
	protected $_indexUpdate = [];
	protected $_operation = 'index';
	
	private $_stack = [];
	
    /**
	 * Function to initialize solf configuration
     * @param mixed $config Array for solr configuration      
     */
    public function init()
    {
		$this->start();
    }
	
	public function set_Tables($tables=[])
	{
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
	public function set_Classes($classes=[])
	{
		$this->_classes = $classes;
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
		$this->bulk['update'] = [];
		$this->bulk['index'] = [];
		$this->bulk['delete'] = [];
		$this->_indexUpdate = [];
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
	
	public function operation() 
	{
		throw new \yii\bas\Exception("operation() should be implemented in a clas extending from this one");
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
		$this->stats['progress'][$for]["chunk"] = @is_null($this->stats['progress'][$for]["chunk"]) ? 1 : $this->stats['progress'][$for]["chunk"];
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
				case $this->stats['progress'][$for]['count'] / @round($this->stats['progress'][$for]['chunk_count']) == 1:
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
	
	protected function progressStart($type)
	{
		$this->stats['progress'][$type]['count'] = 0;
	}
	
	protected function progressTotal($type, $total)
	{
		$this->stats['progress'][$type]['total'] = $total;
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
			$this->dbModel->update('indexed', 1)
				->where(null, array_values($this->_indexUpdate), array_keys($this->_indexUpdate), null, 'OR')
				->run();	
		}
		$this->_indexUpdate = [];
	}

	/**
		Get a solr input document from an array of values
		 * @param mixed $array Associative array containing fields and values
		 * @param bool $input Should an input document be returned?
	*/
	protected function getRecord($array, $input=true)
	{
		switch($input)
		{
			case true:
			$ret_val = new SolrInputDocument();
			break;
			
			default:
			$ret_val = new SolrDocument();
			break;
		}
		
		foreach($array as $field=>$val)
		{
			$field_translated = $this->solr['fields']['transform'][$field];
			//$val = $field.$this->progress['separator'].$val;
			switch(strpos($field_translated, '_tdt') !== false)
			{
				case true:
				//Need to convert mysql timestamps to solr timestamps
				$date = new DateTime($val, new DateTimeZone("UTC"));
				$val = str_replace('+00:00', 'Z', $date->format(DateTime::W3C));
				break;
				
				default:
				switch(is_string($val))
				{
					case true:
					$val = html_entity_decode($val, ENT_QUOTES);
					$val = htmlentities($val, ENT_COMPAT);
					break;
				}
				break;
			}
			switch($ret_val->fieldExists($field_translated))
			{
				case true:
				break;
				
				default:
				break;
			}
			$ret_val->addField($field_translated, $val, @$this->solr['fields']['boost'][$field]);
		}
		return $ret_val;
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
				$this->progress('addItems', null, null, null, true);
				$curLogText = "\n\tStart $bulkIndex item summary:\n";
				$curLogText .= "\t\t".\nitm\helpers\Helper::splitc(array_keys($entry), array_values($entry), '=', "\n\t\t", "'");
				$curLogText .= "\n\tEnd $bulkIndex item summary.\n";
				$this->totals['current']++;
				$this->_indexUpdate[$entry['id']] = $this->idKey;
				$this->log($curLogText, 2);
			}
		}
	}
	
	protected function log($text, $levelRequired=1)
	{
		$this->_logText .= $text;
		if($this->verbose >= $levelRequired)
			echo $text;
	}
	
	protected function printDebug($value)
	{
		echo $this->_logtext;
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
	
	/**
	 * Go through Data and sort entries by those that need to be updated, created and deleted
	 * @param array $data
	 */
	protected function parse($data=[]) 
	{
		$ret_val = false;
		$this->reset();
		if(is_array($data) && sizeof($data)>=1)
		{	
			foreach($data as $idx=>$result)
			{
				$id = $result[$this->idKey];
				$this->progress('prepare', null, null, null, true);
				$result['_id'] = $id;
				$result['_md5'] = isset($result['md5']) ? $result['_md5'] : $this->fingerprint($result);
				$this->bulkSet($this->type, $id, $result);
			}
			$ret_val = true;
			$this->log("\n");
		}
		else
		{
			$this->log("\n\tNothing to index from: ".static::index()."->".static::type()."\n");	
		}
		return $ret_val;
	}
	
	protected function fingerprint($item)
	{
		return md5(json_encode((array)$item));
	}
	
	protected function prepareMetainfo($type)
	{
		$this->setType($type);
		$this->dbModel->setTable($type);
		$this->_info['table'][$type] = $this->dbModel->getTableStatus($type);
		$this->_info['tableInfo'][$type] = $this->dbModel->getTableInfo(null, $type);
		$this->_attributes = ArrayHelper::toArray($this->_info['table'][$type]->columns);
		$this->idKey = $this->dbModel->primaryKey();
		$this->idKey = is_array($this->idKey) ? array_pop($this->idKey) : $this->idKey;
	}
	
	public function run()
	{
		foreach($this->_stack as $type=>$options)
		{
			call_user_func_array($options['worker'], $options['args']);
			unset($this->_stack[$type]);
		}
	}
	
	/**
	 * Parse the data in chunks to make it a bit more efficient
	 * @param object $query
	 * @param function $callback in the format:
	 * 	function ($query, $limit, $offset) {
		 ...
	 *	}
	 */
	protected function parseChunks($query, $callback)
	{
		//Is the indexed column available? If not find everything
		$findAll = array_key_exists('indexed', $this->_attributes) ? false :true;
		if($findAll !== true && !$this->reIndex)
			$query->where(['not', 'indexed=1']);
		$this->log("\tFrom: ".static::index()."->".static::type()." Items: ".$this->tableInfo('Rows')."\n");
		$this->offset = 0;
		$this->progressStart($this->_operation);
		for($i=0; $i<($this->tableInfo('Rows')/$this->limit);$i++)
		{
			$this->offset = $this->limit * $i;
			$this->progressStart('prepare');
			$this->log("\t\tPreparing chunk: $i :");
			switch(1)
			{
				case $this->tableInfo('Rows') <= $this->limit:
				$count =  $this->tableInfo('Rows');
				break;
				
				case ($this->tableInfo('Rows') - ($this->offset)) > $this->limit:
				$count = $this->limit;
				break;
				
				default:
				$count = $this->tableInfo('Rows') - ($this->offset);
				break;
			}
			$this->progressTotal('prepare', $count);
			$this->progressTotal($this->_operation, $count);
			$callback($query, $this);
		}
	}
	
	protected function bulk($index, $id=null)
	{
		if(is_null($id))
			return $this->bulk[static::type()][$index];
		else if(isset($this->bulk[static::type()][$index][$id]))
			return $this->bulk[static::type()][$index][$id];
	}
	
	protected function bulkSet($index, $id, $value=null)
	{
		if(is_null($value))
			$this->bulk[static::type()][$index] = $id;
		else
			$this->bulk[static::type()][$index][$id] = $value;
	}
	
	protected function tableInfo($key=null)
	{
		if(is_null($key))
			return $this->_info['tableInfo'][static::type()];
		else
			return $this->_info['tableInfo'][static::type()][$key];
	}
	
	/**
	 * Add operations to be completed
	 */
	protected function stack($id, $options)
	{
		$this->_stack[$id] = $options;
	}
}
?>