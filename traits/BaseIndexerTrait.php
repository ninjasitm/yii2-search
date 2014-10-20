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
	public $limit = 500;
	public $model;
	public $info = [];
	
	protected $type;
	protected $idKey;
	
	protected $bulk = ["update" => [], "delete" => [], "index" => []];
	protected $dbModel;
	protected $currentUser;
	
	protected $_logText = '';
	protected $_info = ["info" => [], "table" => []];
	protected $_tables = [];
	protected $_classes = [];
	protected $_source;
	protected $_attributes =[];
	protected $_indexUpdate = [];
	protected $_operation = 'index';
	
	private $_stack = [];
	
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
	public function set_Classes($classes=[])
	{
		$this->_source = 'classes';
		$this->_classes = $classes;
	}
	
	public function getClasses()
	{
		return $this->_classes;
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
			$this->dbModel->update('indexed', 1)
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
	
	protected function fingerprint($item)
	{
		return md5(json_encode((array)$item));
	}
	
	protected function prepareMetainfo($type, $table)
	{
		$this->setIndexType($type, $table);
		$this->dbModel->setTable(static::$_table);
		$this->_info['table'][static::$_table] = $this->dbModel->getTableStatus(static::$_table);
		$this->_info['tableInfo'][static::$_table] = $this->dbModel->getTableInfo(null, static::$_table);
		$this->_attributes = ArrayHelper::toArray($this->_info['table'][static::$_table]->columns);
		$this->idKey = $this->dbModel->primaryKey();
		$this->idKey = is_array($this->idKey) ? array_pop($this->idKey) : $this->idKey;
	}
	
	public function run()
	{
		foreach($this->_stack as $table=>$options)
		{
			//echo "\n\tPulling from stack: ".$options['type']." $table\n";
			if(isset($options['namespace']))
				$this->namespace = $options['namespace'];
			$this->prepareMetainfo((isset($options['type']) ? $options['type'] : $table), $table);
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
		if(is_array($data) && sizeof($data)>=1)
		{	
			$this->totals['chunk'] = sizeof($data);
			$this->log(" [".$this->totals['chunk']."]: ");
			$this->progressStart('prepare', sizeof($data));
			foreach($data as $idx=>$result)
			{
				$id = $result[$this->idKey];
				$this->progress('prepare', null, null, null, true);
				$result['_id'] = $id;
				$result['_md5'] = isset($result['md5']) ? $result['_md5'] : $this->fingerprint($result);
				$this->bulkSet($this->type, $id, $result);
			}
			$ret_val = true;
		}
		else
		{
			$this->bulkSet($this->type, []);
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
		//Is the indexed column available? If not find everything
		$findAll = array_key_exists('indexed', $this->_attributes) ? false :true;
		if(($findAll === false && !$this->reIndex) && ($this->type != 'delete'))
			$query->where(['not', 'indexed=1']);
		$this->log("\n\tPerforming: ".$this->type." on ".static::index()."->".static::type()." Items: ".$this->tableRows());
		
		//Do something before $this->type
		$event = strtoupper('before_search_'.$this->type);
		$this->trigger(constant('\nitm\search\BaseIndexer::'.$event));
		
		$this->totals[$this->type] = $this->tableRows();
		$this->totals['current'] = $this->totals['chunk'] = $this->offset = 0;
		
		for($i=0; $i<($this->tableRows()/$this->limit);$i++)
		{
			$this->totals['current'] = 0;
			$this->offset = $this->limit * $i;
			$this->log("\n\t\tPreparing chunk: $i [starting at ".$this->offset."] ");
			switch(1)
			{
				case $this->tableRows() <= $this->limit:
				$count =  $this->tableRows();
				break;
				
				case ($this->tableRows() - ($this->offset)) > $this->limit:
				$count = $this->limit;
				break;
				
				default:
				$count = $this->tableRows() - ($this->offset);
				break;
			}
			$this->progressStart($this->type, $count);
			$callback($query, $this);
		}
		
		//Do something after indexing
		$event = strtoupper('after_search_'.$this->type);
		$this->trigger(constant('\nitm\search\BaseIndexer::'.$event));
		
		$this->totals['total'] += $this->totals['current'];
		$this->log("\n\tResult: ".$this->totals['current']." out of ".$this->totals[$this->type]." entries\n");
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
	
	protected function tableInfo($key=null)
	{
		if(is_null($key))
			return $this->_info['tableInfo'][static::tableName()];
		else
			return $this->_info['tableInfo'][static::tableName()][$key];
	}
	
	protected function tableRows($key=null)
	{
		$this->dbModel->execute("SELECT COUNT(*) FROM ".$this->dbModel->tableName());
		return $this->dbModel->result()[0];
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