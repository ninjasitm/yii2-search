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
	
class IndexerElasticsearch extends BaseSearch
{
	public $mode;
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
	public $jdbc = [];
	public $totals = ["index" => 0, "update" => 0, "delete" => 0];
	public $reIndex;
	public $progress = ["complete" => false, "separator" => ":", "op" => ["start" => 0, "end" => 0]];
	
	protected $bulk = ["update" => [], "remove" => [], "index" => []];
	protected $dbModel;
	
	private $_logText = '';
	private $_info = ["info" => [], "table" => []];
	private $_tables = [];
	private $_classes = [];
	private $_attributes =[];
	private $_indexUpdate = [];
	
	const MODE_FEEDER = 'feeder';
	const MODE_RIVER = 'river';
	
	public function init()
	{
		parent::init();
		$this->dbModel = new DB;
	}
	
	public function attributes()
	{
		return is_object($this->model) ? $this->model->attributes() : $this->_attributes;
	}
	
	public function setTables($tables=[])
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
	public function setClasses($classes=[])
	{
		$this->_classes = $classes;
	}
	
	/**
		Wrapper function for legacy support
	*/
	public function finish()
	{
		$f = [];
		$types = ['varchar', 'tinytext', 'text', 'blob', 'mediumtext', 'mediumblob', 'longtext', 'longblob'];
		if($this->l)
		{			
			$this->log("Index Summary:\n\tOn ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." performed index operations. Summary as follows:\n\tIndexed (".$this->totals['index'].") Re-Indexed (".$this->totals['update'].") items De-Indexed (".$this->totals['delete'].") items Index total (".$this->_info['info']['Rows'].")");
			if($this->totals['index'] || $this->totals['delete'])
			{
				//$this->l->add_trans($this->subj['db'], $this->subj['table'], "Index Summary", "On ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." performed index operations. Summary as follows:\n Indexed (".$this->totals['index'].") Re-Indexed (".$this->totals['update'].") items De-Indexed (".$this->totals['delete'].") items Index total (".$this->_info['info']['Rows'].")");
			} 
			//$this->l->write($this->_logText);
		}
		parent::close();
		$this->progress['op']['end'] = microtime(true);
	}
	
	/**
	 * Prepare data to be indexed/checked
	 * @param int $mode
	 * @param boolean $useClasses Use the namespaced calss to pull data?
	 * @return bool
	 */
	public function prepare()
	{
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
		$this->$prepare();
	}
	
	public function prepareFromSql()
	{
		if(empty($this->_tables))
			return;
		$this->model = new DB;
		foreach($this->_tables as $table)
		{
			$this->setType($table);
			$this->model->setTable($table);
			$this->_attributes = $this->model->getFields();
			$this->_info['table'] = $this->model->getTableStatus($table);
			//Is the indexed column available? If not find everything
			$findAll = array_key_exists('indexed', $this->_attributes) ? false :true;
			if($findAll !== true)
				$query->where('indexed', 1);
			if($this->reIndex)
				$this->deleteAll(['_index' => $this->_database, '_type' => $table]);
			for($i=0; $i<($this->_info['table']['Rows']/$this->limit);$i++)
			{
				$counter = $i+1;
				$this->pushRiver($query->limit($this->limit)
					->offset($this->limit*$counter)
					->createCommand()->getSql());
			}
		}
	}
	
	public function pushRiver($query)
	{
		$options = [
			'type' => 'jdbc',
			'jdbc' => [
				'url' => $this->jdbc['url'].'/'.$this->_database,
				'user' => $this->jdbc['user'],
				'password' => $this->jdbc['password'],
				'sql' => $sql
			]
		];
		$this->createCommand()->db->put($this->url, $this->jdbc['options'], json_encode($options), true);
	}
	
	public function prepareFromClasses()
	{
		$namespace = array_shift($this->_classes);
		if(empty($this->_classes))
			return;
		foreach($this->_classes as $class=>$options)
		{
			$className = $namespace.$class;
			$this->model = @(new $className);
			$this->setType($this->model->tableName());
			$this->_info['table'] = $this->dbModel->getTableStatus($this->model->tableName());
			print_r($this->_info['table']);
			exit;
			$this->_attributes = $this->_info['table']['columns'];
			$query = $this->model->find();
			//Is the indexed column available? If not find everything
			$findAll = array_key_exists('indexed', $this->_attributes) ? false :true;
			if($findAll !== true)
				$query->where(['not', 'indexed=1']);
			if($this->reIndex)
				$this->model->deleteAll();
			for($i=0; $i<($this->_info['table']['Rows']/$this->limit);$i++)
			{
				$counter = $i+1;
				$this->parse($query->limit($this->limit)
					->offset($this->limit*$counter)
					->findAll()
					->asArray(),
				$this->model->primaryKey()[0]);
			}
		}
	}
	
	public function prepareFromTables()
	{
		if(empty($this->_tables))
			return;
		$this->model = new DB;
		foreach($this->_tables as $table)
		{
			$this->setType($table);
			$this->model->setTable($table);
			$this->_attributes = $this->model->getFields();
			$this->_info['table'] = $this->model->getTableStatus($table);
			//Is the indexed column available? If not find everything
			$findAll = array_key_exists('indexed', $this->_attributes) ? false :true;
			if($findAll !== true)
				$query->where('indexed', 1);
			if($this->reIndex)
				$this->deleteAll(['_type' => $table]);
			for($i=0; $i<($this->_info['table']['Rows']/$this->limit);$i++)
			{
				$counter = $i+1;
				$this->model->limit($this->limit, $this->limit*$counter);
				$this->parse($this->model->result(DB::R_ASS, true), $this->model->primaryKey());
			}
		}
	}
	
	public function reset()
	{
		$this->bulk['update'] = [];
		$this->bulk['create'] = [];
		$this->bulk['remove'] = [];
		$this->_indexUpdate = [];
	}
	
	/**
	 * Go through Data and sort entries by those that need to be updated, created and deleted
	 * @param array $data
	 */
	public function parse($data=[], $idKey) 
	{
		$ret_val = false;
		$this->reset();
		if(is_array($data) && !empty($data))
		{
			$this->log("\t\tFrom: ".$this->_database."->".$this->_info['table']['Name']." Items: ".$this->_info['table']['Rows']."\n");
			$this->log("\t\t\tPreparing: ");
			$this->stats['progress']['prepare'] = ["count" => 0];
 			$this->stats['progress']['prepare']['total'] = $this->_info['table']['Rows'];
			
			if(in_array('indexed', $this->_attributes) === false)
			{
				$this->addIndexField();
			}
			foreach($data as $idx=>$result)
			{
				$this->progress('prepare', null, null, null, true);
				if(!($found = $this->findOne([
					'_id' => $result[$idKey],
					'_type' => $type
				])))
				{
					$result['_id'] = $result[$idKey];
					$this->bulk['create'][] = $result;
				}
				else
				{
					$result = array_merge(ArrayHelper::toArray($found), $result);
					array_push($this->bulk['update'], $result);
				}
			}
			$ret_val = true;
			$this->log("\n");
		}
		return $ret_val;
	}
	
	public final function addItems()
	{
		$ret_val = false;
		$this->totals['current'] = 0;
		$now = strtotime('now');
		$index_update = [];
		$this->stats['progress']['addItems'] = ["count" => 0];
		$this->stats['progress']['addItems']['total'] = sizeof($this->bulk['create']);
		if(sizeof($this->bulk['create']) >= 1)
		{
			$this->log("\t\t\tIndexing: ");
			$create = [];
			array_walk($this->bulk['create'], function ($item, $idx) use (&$create) {
				$create[] = ['create' => ['_id' => $item['_id']]];
				unset($item['_id']);
				$create[] = $item;
			});
			$url = [$this->url, $this->index(), $this->type(), '_bulk'];
			$put = $this->createCommand()->db->put(implode('/', $url), $this->jdbc['options'], json_encode($create), true);
			if($put)
			{
				foreach($this->bulk['create'] as $idx=>$entry)
				{
					$this->progress('addItems', null, null, null, true);
					$curLogText = "Start index item summary:\n";
					$curLogText .= "\tres_added = $now\n";
					$curLogText .= '\t'.implode('\n\t', $entry);
					$curLogText .= "End index item summary:\n";
					$this->totals['current']++;
					$this->indexUpdate[$entry['_type']] = $entry['_id'];
					$this->log($curLogText);
				}
				$this->log("\n\t\t\tIndexed: ".$this->totals['current']." out of ".$this->_info['table']['Rows']." entries\n");
			}
			else
			{
				$this->log("\n\t\t\tNothing to Index\n");
			}
			$this->log("\n");
			$ret_val = true;
			$this->totals['index'] += $this->totals['current'];
			array_walk($this->_indexUpdate, function ($index) {
				parent::update('indexed', 1, ['key' => array_values($index), 'data' => array_keys($index), 'xor' => ' OR ']);
				
			});
		}
		$this->bulk['create'] = [];
	}
	
	public function maintain($global = false)
	{
		/*$ret_val = false;
		$this->totals['current'] = 0;
		$this->stats['progress']['addItems'] = ["count" => 0];
		$this->stats['progress']['addItems']['total'] = sizeof($this->bulk['create']);
		//here we need to query to solr engine for basic entry information and then compare the returned data
		$data_type = ($global === true) ? ['all', null] : ['srch_base_table', $this->subj['table']];
		if(($results = $this->searchArray($data_type[0], $data_type[1], null, null, $this->_query['fields'])) !== false)
		{
			$check_data = [];
			$this->log("\t\t\Maintaining: ".sizeof($result)." Items:");
			foreach($results as $idx=>$result)
			{
				$this->progress('maintain', null, null, null, true);
				$db = $result['srch_base_db'];
				$tbl = $result['srch_base_table'];
				foreach($this->table_fields as $jdx=>$field)
				{
					switch($field['Field'])
					{
						case 'indexed':
						continue;
						break;
						
						default:
						@$check_data[$field['Field']] = @$result[$field['Field']];
						break;
					}
				}
				if(!empty($check_data[$this->subj['pri']]) && !$this->exists($this->subj['pri'], $result[$this->subj['pri']], array_keys($check_data), array_values($check_data), $this->subj['data']))
				{
					if(($this->searchArray('srch_base_table', $result['srch_base_table'], ['key' => $this->subj['pri'], 'data' => $result[$this->subj['pri']]), $this->subj['data'])) != $this->subj['pri'])
					{
						$result['attr_table'] = $this->subj['table'];
						$result['attr_db'] = $this->subj['db'];
						$result['id'] = $result[$this->data['pri']];
						array_push($this->bulk['update'], $this->getRecord($result, true));
						$this->totals['update']++;
						if($this->l)
						{
							$this->log("Start re-index item summary:\n");
							$this->log("\tpri = ".$result[$this->data['pri']]."\n");
							foreach(array_keys($check_data) as $idx=>$lsf)
							{
								$this->log("\t$lsf = ".$check_data[$lsf]."\n");
							}
							$this->log("End re-index item summary:\n");
						}
							
					}
					else
					{
						$this->totals['delete']++;
						$this->bulk['remove'][$result[$this->data['pri']]] = parent::split_c(["attr_table", "attr_db", "id"), [$this->subj['table'], $this->subj['db'], $this->data['pri']), "AND", ":", false, false, true);
						if($this->l)
						{
							$this->log("Start de-index item summary:\n");
							$this->log("\tpri = ".$result[$this->data['pri']]."\n");
							foreach(array_keys($check_data) as $idx=>$lsf)
							{
								$this->log("\t$lsf = ".$check_data[$lsf]."\n");
							}
							$this->log("End de-index item summary:\n");
						}
					}
				}
			}
			$this->l->write($this->_logText);
		}*/
		//switch(empty($this->bulk['update']))
		//{
		//	case false:
			/**
			 * Need to update solr entry with relevant new information
			 * There's no update functionality in solr rightnow. Need to fully udpate by re-indexing
			 */
		//	$this->solr['client']->addDocuments($this->bulk['update'], false, 60);
		//	break;
		//}
		/**
		switch(empty($this->bulk['remove']))
		{
			case false:
			//Need to remove these entries from solr
			$this->solr['client']->deleteByQueries($this->bulk['remove']);
			$this->solr['client']->commit();
			break;
		}
		parent::free();*/
	}
	
	public function searchArray($in=null, $for=null, $c=null, $data=null, $fields=null)
	{
		$ret_val = false;
		$data = (empty($array)) ? $this->data['data'] : $data;
		switch(empty($in))
		{
			case true:
			$ret_val = $data;
			break;
			
			default:
			$keys = (is_array($data[$in])) ? array_keys($data[$in], $for) : null;
			switch(!empty($keys))
			{
				case true:
				$srch_data_keys = (empty($fields)) ? array_keys($data) : $fields;
				foreach($keys as $key)
				{
					$cond_keys = [];
					switch(empty($c['key']))
					{
						case false:
						$skip = true;
						$c['key'] = is_array($c['key']) ? $c['key'] : [$c['key']];
						$c['data'] = is_array($c['data']) ? $c['data'] : [$c['data']];
						foreach($c['key'] as $k)
						{
							$cond_keys[] = "\$data[$in][$k][$key]";
						}
						eval("if((".parent::split_c($cond_keys, $c['data'], @$c['oper'], @$oper['xor'], false, false, true).")){\$skip=false;}");
						switch($skip)
						{
							case false:
							foreach(array_keys($srch_data_keys) as $index)
							{
								$ret_val[$key][$index] = $data[$index][$key];
							}
							break;
						}
						break;
						
						default:
						foreach(array_keys($srch_data_keys) as $index)
						{
							$ret_val[$key][$index] = $data[$index][$key];
						}
						break;
					}
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/**
		Function to return the progress for a particular activity
		@param string $for The unique index to measure progress with
		@param int $count The current item being worked on
		@param int $total The total number of entries to gather progress for
		@param int $chunk The number of percentage chunks to check for
		@param boolean $print Print progress?
		@return int
	*/
	public function progress($for, $count=null, $total=null,  $chunks=null, $print=false)
	{
		$ret_val = null;
		$this->stats['progress'][$for]["count"] = is_null($count) ? $this->stats['progress'][$for]["count"]+1 : $count;
		$this->stats['progress'][$for]["chunks"] = is_null($chunks) ? 4 : $chunks;
		$this->stats['progress'][$for]["chunk"] = is_null($this->stats['progress'][$for]["chunk"]) ? 1 : $this->stats['progress'][$for]["chunk"];
		$this->stats['progress'][$for]["total"] = is_null($total) ? $this->stats['progress'][$for]["total"] : $total;
		$this->stats['progress'][$for]["sub_chunk"] = (!isset($this->stats['progress'][$for]["sub_chunk"])) ? (1/$this->stats['progress'][$for]["chunks"]) : $this->stats['progress'][$for]["sub_chunk"];
		
		//$this->log("Subchunk == ".$this->stats['progress'][$for]["sub_chunk"]."\n");
		switch($this->stats['progress'][$for]["total"] == 0)
		{
			case false:
			$this->stats['progress'][$for]['chunk_count'] = round(($this->stats['progress'][$for]['total']/$this->stats['progress'][$for]['chunks']) * $this->stats['progress'][$for]['chunk']);
			$this->stats['progress'][$for]['sub_chunk_count'] = round((($this->stats['progress'][$for]['chunk']-1) + $this->stats['progress'][$for]["sub_chunk"]) * ($this->stats['progress'][$for]['total']/$this->stats['progress'][$for]['chunks']));
			//$this->pr("Count = ".$this->stats['progress'][$for]["count"]." "." Chunk == ".$this->stats['progress'][$for]['chunk']." Chunk count = ".$this->stats['progress'][$for]['chunk_count']." Reached barrier?: ".$this->stats['progress'][$for]['count'] % ceil($this->stats['progress'][$for]['chunk_count'])."\n");
			//$this->pr(($this->stats['progress'][$for]['sub_chunk_count'])." Count = ".$this->stats['progress'][$for]["count"]." Sub Chunk = ".$this->stats['progress'][$for]['sub_chunk']." Chunk = ".$this->stats['progress'][$for]['chunk']." Chunk-1 = ".($this->stats['progress'][$for]['chunk']-1)." Chunk Count = ".$this->stats['progress'][$for]['chunk_count']." Subchunk Count == ".$this->stats['progress'][$for]['sub_chunk_count']." Sub Chunk multiplier == ".(($this->stats['progress'][$for]['chunk']-1) + $this->stats['progress'][$for]["sub_chunk"])."\n");
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
	
	/**
		Protected functions
	*/

	/**
		Get a solr input document from an array of values
		@param mixed $array Associative array containing fields and values
		@param bool $input Should an input document be returned?
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
	
	protected function log($text)
	{
		$this->_logText .= $text;
	}
	
	protected function printDebug($value)
	{
		if($this->verbose)
		{
			echo $this->_logtext;
		}
	}
	
	/**
		Search solr db and return results, usually by DB:Table
		@param mixed $keys Keys
		@param mixed $values Values
		@param bool $translate Should fields be translated?
		@param int $offset Where to start results from
		@return mixed
	*/
	protected function results($keys, $values, $translate=true, $offset=0)
	{
		$ret_val = false;
		$this->solr['query'] = new SolrQuery();
		switch($translate)
		{
			case true:
			$clone = $this;
			array_walk($keys, function (&$v, $k, $o) {
				$v = $o->translate_field($v);
			}, $clone);
			break;
		}
		/*$this->solr['query']->setQuery($this->split_c($keys, $values, ":", "AND", true, false, false));
		$this->solr['query']->setStart((gettype($offset) == 'int') ? $offset : 0);
		try {
			$this->solr['response']['query'] = @$this->solr['client']->query($this->solr['query']);
			$this->solr['response']['last_query'] = @$this->solr['response']['query']->getResponse();
			switch($this->solr['response']['last_query']['response']['numFound'] > 0)
			{
				case true;
				foreach($this->solr['response']['last_query']['response']['docs'] as $i=>$doc)
				{
					foreach($doc as $f=>$v)
					{
						$orig_f = $f;
						switch(1)
						{							
							case strpos($f, 'attr_') !== false:
							$f = substr_replace($f, '', 0, strlen('attr_'));
							break;
							
							default:
							$f = array_search($f, $this->solr['fields']['transform']);
							switch(empty($f))
							{
								case true:
								$f = $orig_f;
								break;
							}
							break;
						}
						$ret_val[$f] = $v;
					}
				}
				break;
			}
		} catch (SolrException $e) {
			//$ret_val = false;
			$this->log("Solr Client Error: \n\n");
			$this->log("Query: \n\n");
			$this->log("Error: ".$e->getMessage()."\n");
			$this->log($this->solr['query']->getQuery();
		}
		return $ret_val;*/
	}
	
	/**
	 * Add a field to the table
	 * @param array $field
	 */
	private function addIndexField()
	{
		$field = ['Field' => 'indexed', 'Type' => 'tinyint(1)', 'Null' => 'NO'];
		return (new DB)->addFieldTo($field, $this->index(), $this->type());
	}
	
	
	/**
	 * Check to see if a field exists in the current set of fields
	 * @param string $field The field to be checked
	 * return @bool
	 */
	private function checkKey($field)
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
}
?>