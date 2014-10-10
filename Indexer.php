<?php
namespace nitm\search;

// Search_Indexer class
// Can be extended later but by default
// will include base methods for adding 
// data to a search database and automatic
// indexing of that information
// 
// There will also be the ability to
// add search information
// There will be the ability to update keywords
// and such

class Indexer extends \nitm\models\Data
{
	public $start_op = 0;
	public $end_op = 0;
	public $info = array();
	public $tbl_info = array();
	public $index_total = 0;
	public $update_total = 0;
	public $delete_total = 0;
	public $l = null;
	public $log_text = '';
	public $complete = false;
	
	protected $table_data = array();
	protected $data = array();
	protected $subj_db = '';
	protected $subj_table = '';
	protected $pri = '';
	protected $query_lim = 1000;
	protected $query_cur_offset = 0;
	protected $query_fields = null;
	
	private $subj_table_fields = array();
	private $data_db = 'search';
	private $data_table = 'jouhou';
	private $to_index = array();
	private $to_update = array();
	private $to_remove = array();
	private $search_data = array();
	
	const index_name = 'full_text';
	
	public function __construct($db=null, $table=null, $data_db=null, $data_table=null, $log=true, $logdir=null, $complete=false)
	{
		$this->data_db = (empty($data_db)) ? $this->data_db : $data_db;
		$this->data_table = (empty($data_table)) ? $this->data_table : $data_table;
		parent::__construct($this->data_db, $this->data_table);
		$this->keys = parent::get_fields();
		$this->srch_pri = parent::get_cur_pri();
		if($log === true)
		{
			if(class_exists("Logger"))
			{
				$this->l = new Logger(null, null, null, Logger::LT_DB);
				$this->l->prepare_file("Search-Index", $logdir);
			}
		}
		$this->complete = $complete;
		switch($this->complete)
		{
			case true:
			parent::change_dbt($this->data_db, $this->data_table);
			parent::execute("TRUNCATE TABLE `$this->data_table`");
			parent::perform_op(DB::OP_FLU);
			parent::revert_dbt();
			break;
		}
		$this->collect_stats = false;
		$this->start_op = microtime(1);
		parent::free();
	}
	
	
	public function __destruct()
	{
		$this->l->end_log();
		parent::pr(parent::get_query_stats(true));
		parent::close();
		unset($this);
	}
	
	public function get_db_cred($db=1)
	{
		switch($db)
		{
			case 1:
			return $this->data_db;
			break;
			
			case 2:
			return $this->data_table;
			break;
			
			case 3:
			return array($this->data_db, $this->data_table);
			break;
		}
	}
	
	public function duration()
	{
		return $this->end_op - $this->start_op;
	}
	
	public function finish()
	{
		$f = array();
		$types = array('varchar', 'tinytext', 'text', 'blob', 'mediumtext', 'mediumblob', 'longtext', 'longblob');
		parent::perform_op(DB::OP_FLU);
		parent::change_dbt($this->data_db, $this->data_table);
		$fields = parent::get_fields();
		foreach($fields as $field)
		{
			foreach($types as $type)
			{
				if(strpos($field['Type'], $type) !== false)
				{
					$f[$field['Field']] = $field['Field'];
				}
			}
		}
		$indexes = parent::get_indexes();
		if(sizeof($indexes) >= 1)
		{
			$size = 0;
			$name = array();
			foreach($indexes as $idx=>$index)
			{
				if((strpos($index['Key_name'], self::index_name) !== false))
				{
					$name[] = $index['Key_name'];
				}
			}
			$name = array_unique($name);
			if(sizeof($name) >= 1)
			{
				foreach($name as $ftextidx)
				{
					parent::execute("ALTER TABLE `".$this->data_table."` DROP INDEX `".$ftextidx."`");
				}
			}
			$field_arrays = array_chunk($f, 16, false);
			parent::perform_op(DB::OP_FLU);
			foreach($field_arrays as $idx=>$fs)
			{
				parent::execute("ALTER TABLE `".$this->data_table."` ADD FULLTEXT ".self::index_name."_$idx (`".parent::split_f($fs, "`,`")."`)");
			}
		}
		parent::perform_op(DB::OP_FLU);
		parent::perform_op(DB::OP_ANA);
		parent::perform_op(DB::OP_CHK);
		parent::perform_op(DB::OP_OPT);
		$this->info = parent::get_table_status();
		parent::revert_dbt();
		if($this->l)
		{			
			$this->log_text = "Index Summary:\n\tOn ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." performed index operations. Summary as follows:\n\tIndexed (".$this->index_total.") Re-Indexed (".$this->update_total.") items De-Indexed (".$this->delete_total.") items Index total (".$this->info['Rows'].")";
			if($this->index_total || $this->delete_total)
			{
				$this->l->add_trans($this->get_db_cred(1), $this->get_db_cred(2), "Index Summary", "On ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." performed index operations. Summary as follows:\n Indexed (".$this->index_total.") Re-Indexed (".$this->update_total.") items De-Indexed (".$this->delete_total.") items Index total (".$this->info['Rows'].")");
			} 
			$this->l->write($this->log_text);
		}
		parent::close();
		$this->end_op = microtime(true);
	}
	
	//prepare data to be indexed/checked
	public function prepare($db, $table, $adding_data=true)
	{
		$ret_val = false;
		$this->table_data = array();
		$this->to_update = array();
		$this->to_index = array();
		$this->to_remove = array();
		$this->query_fields = null;
		if($table && $db)
		{
			//get information from subject table to use for organizing query in search DB
			$this->subj_table = $table;
			$this->subj_db = $db;
			parent::set_db($this->subj_db, $this->subj_table);
			$this->pri = parent::get_cur_pri();
			$this->tbl_info = parent::get_table_status();
			$this->table_fields = parent::get_fields();
			$max_rows = $this->tbl_info['Rows'];
			$iters = ceil($max_rows/$this->query_lim);
			
			//search for information in search DB by DB and table and return by primary key for table
			$cond = array('key' => array('srch_base_db', 'srch_base_table'), 'data' => array($this->subj_db, $this->subj_table));
			parent::set_db($this->data_db, $this->data_table);
			for($i=0;$i<=$iters;$i++)
			{
				$offset = $this->query_lim*$i;
				parent::select(null, null, $cond, $this->pri, null, $this->query_lim, $this->data_table, $this->data_db, true, $offset);
				$this->search_data = parent::result(DB::R_ASS, true, true);
				
				//return to subject table and DB to check data
				parent::set_db($this->subj_db, $this->subj_table);
				$this->query_fields = array();
// 				pr($this->table_fields);
				foreach($this->table_fields as $idx=>$field)
				{
					$this->query_fields[] = $field['Field'];
					switch($field['Field'])
					{
						case 'indexed':
						continue;
						break;
						
						default:
						if($this->check_key($field['Field']) === false)
						{
							$this->add_field_to($field, $this->data_db, $this->data_table);
						}
						break;
					}
				}
				if(in_array('indexed', $this->query_fields) === false)
				{
					$this->add_field_to(array('Field' => 'indexed', 'Type' => 'tinyint(1)', 'Null' => 'NO'), $this->subj_db, $this->subj_table, false);
				}
				parent::perform_op(DB::OP_FLU);
				$this->index_count = 0;
				switch($this->tbl_info['Rows'] >= 1)
				{
					case true:
					switch($this->complete)
					{
						case true:
						parent::select($this->query_fields, false, null, null, null, $this->query_lim, $this->subj_table, $this->subj_db, false, $offset);
						break;
						
						default:
						parent::select($this->query_fields, false, array('key' => 'indexed', 'data' => '1', 'operand' => '<>'), null, null, $this->query_lim, $this->subj_table, $this->subj_db, false, $offset);
						break;
					}
// 					echo parent::rows()."/$max_rows with limit $this->query_lim for ".$this->subj_table." currently using ".parent::memory_usage()." Peak usage is ".parent::memory_usage(true)."\n";
					$this->table_data = parent::result(DB::R_ASS, true, false);
// 					$this->query_cur_offset = (parent::rows(true) > $this->query_lim) ? $this->query_cur_offset + $this->query_lim : $this->query_cur_offset;
					switch($adding_data)
					{
						case true:
						if(sizeof($this->table_data) >= 1)
						{
							foreach($this->table_data as $idx=>$result)
							{
								if(!isset($this->to_index[$this->pri.'_'.$result[$this->pri]]))
								{
									unset($result['indexed']);
									if($this->check_entry($this->pri, $result[$this->pri], array_keys($result), array_values($result), $this->search_data) === false)
									{
										$this->to_index[$this->pri.'_'.$result[$this->pri]] = $result;
	// 									$this->to_index[$this->pri.'_'.$result[$this->pri]] = $result[$this->pri];
										$this->index_count++;	
									}
									else
									{
										array_push($this->to_update, array(array('indexed'), array((int)1), array('key' => array($this->pri), 'data' => array($result[$this->pri])), $this->subj_table, $this->subj_db));
									}
								}
							}
			// 				$this->index_total += $this->index_count;
							$ret_val = true;
						}
						break;
					}
					break;
				}
				unset($this->search_data);
				unset($this->table_data);
			}
		}
		else
		{
			die("Empty DB and Table prepare($db, $table)");
		}
// 		echo "Preparing to index $this->index_count items in $this->subj_table\n";
		return $ret_val;
	}
	
	public final function additems()
	{
		$ret_val = false;
		$this->log_text = '';
		$now = strtotime('now');
		parent::perform_op(DB::OP_FLU);
		parent::set_db($this->data_db, $this->data_table);
		$index_update = array();
		if(sizeof($this->to_index) >= 1)
		{
			//search jouhou table fields data and SQL variables
			$search_fvd = array('fields' => array('srch_base_table', 'srch_base_db', 'pri', 'res_added'), 'values' => array($this->subj_table, $this->subj_db, $this->pri, $now), 'variables' => array('@var_tbl', '@var_db', '@var_pri', '@var_added'));
			$fvd_cnt = sizeof($search_fvd['fields']);
			//get query fields and organize select and insert fields according to info in search_fvd
			$query_fields = $this->query_fields;
			unset($query_fields[array_search('indexed', $query_fields)]);
			$fields_insert = parent::split_c(array_fill(0, sizeof($query_fields)+$fvd_cnt, $this->data_table), array_merge($query_fields, $search_fvd['fields']), '.',  ',', false, false, false);
			$fields_select = parent::split_c(array_fill(0, sizeof($query_fields), $this->subj_table), $query_fields, '.',  ',', false, false, false);
			//set mysql variables and get ready for insert
			foreach($this->to_index as $idx=>$result)
			{
				parent::execute("SET ".parent::split_c($search_fvd['variables'], $search_fvd['values'], null, ',', false, false)."; INSERT INTO $this->data_db.$this->data_table ($fields_insert) SELECT $fields_select,".implode(',', $search_fvd['variables'])." FROM $this->subj_db.$this->subj_table WHERE $this->pri=".$result[$this->pri]." ORDER BY $this->pri");
// 				parent::insert(array_merge(array('srch_base_table','srch_base_db','res_added','pri'), array_keys($result)), array_merge(array($this->subj_table, $this->subj_db, $now, $this->pri), array_values($result)), $this->data_table, $this->data_db);
				$this->index_total++;
				$index_update[$result[$this->pri]] = $this->pri;
				if($this->l)
				{
					$this->log_text .= "Start index item summary:\n";
					$this->log_text .= "\tpri = $this->pri\n";
					$this->log_text .= "\tes_added = $now\n";
					$v_arr = array_unique($result);
					foreach($v_arr as $iidx=>$lsf)
					{
						$this->log_text .= "\t$iidx = $lsf\n";
					}
					$this->log_text .= "End index item summary:\n";
				}
			}
			$ret_val = true;
			switch(sizeof($index_update) >= 1)
			{
				case true:
				parent::change_dbt($this->subj_db, $this->subj_table);
				parent::update(array('indexed'), array((int)1), array('key' => array_values($index_update), 'data' => array_keys($index_update), 'xor' => ' OR '));
				parent::revert_dbt();
				break;
			}
			$this->l->write($this->log_text);
		}
		$this->to_index = array();
	}
	
	public function maintain($global = false)
	{
		$ret_val = false;
		$this->log_text = '';
		parent::set_db($this->data_db, $this->data_table);
		$data_type = ($global === true) ? array('all', null) : array('srch_base_table', $this->subj_table);
		if(($results = $this->search_array($data_type[0], $data_type[1], null, null, $this->query_fields)) !== false)
		{
			$check_data = array();
			foreach($results as $idx=>$result)
			{
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
				if(!empty($check_data[$this->pri]) && !$this->check_entry($this->pri, $result[$this->pri], array_keys($check_data), array_values($check_data), $this->table_data))
				{
					if(($reinsert = $this->search_array('srch_base_table', $result['srch_base_table'], array('key' => $this->pri, 'data' => $result[$this->pri]), $this->table_data)) != $this->pri)
					{
						array_push($this->to_update, array(array_keys($reinsert), array_values($reinsert), array('key' => array($this->srch_pri, $this->pri), 'data' => array($result[$this->srch_pri], $check_data[$this->pri]))));
						$this->update_total++;
						if($this->l)
						{
							$this->log_text .= "Start re-index item summary:\n";
							$this->log_text .= "\tpri = ".$result[$this->srch_pri]."\n";
							foreach(array_keys($check_data) as $idx=>$lsf)
							{
								$this->log_text .= "\t$lsf = ".$check_data[$lsf]."\n";
							}
							$this->log_text .= "End re-index item summary:\n";
						}
							
					}
					else
					{
						$this->delete_total++;
						$this->to_remove[$result[$this->srch_pri]] = $this->srch_pri;
						if($this->l)
						{
							$this->log_text .= "Start de-index item summary:\n";
							$this->log_text .= "\tpri = ".$result[$this->srch_pri]."\n";
							foreach(array_keys($check_data) as $idx=>$lsf)
							{
								$this->log_text .= "\t$lsf = ".$check_data[$lsf]."\n";
							}
							$this->log_text .= "End de-index item summary:\n";
						}
					}
				}
			}
			$this->l->write($this->log_text);
		}
		switch(empty($this->to_update))
		{
			case false:
			foreach($this->to_update as $item)
			{
				call_user_func_array(array($this, 'update'), $item);
			}
			break;
		}
		switch(empty($this->to_remove))
		{
			case false:
			parent::remove(array_values($this->to_remove), array_keys($this->to_remove), null, null, null, '=', ' OR ');
			break;
		}
		parent::free();
	}
	
	public function check_entry($pri, $pri_val, $keys, $values, $array)
	{
		$ret_val = false;
		$keys = is_array($keys) ? $keys : array($keys);
		$values = is_array($values) ? $values : array($values);
		switch(is_array($array))
		{
			case true:
			$idx = array_search($pri_val, $array[$pri]);
			$ret_val = $idx;
			foreach($keys as $i=>$v)
			{
				if($array[$v][$idx] != $values[$i])
				{
					break;
				}
			}
			break;
		}
		return $ret_val;
	}
	
	public function search_array($in=null, $for=null, $c=null, $data=null, $fields=null)
	{
		$ret_val = false;
		$data = (empty($array)) ? $this->search_data : $data;
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
					$cond_keys = array();
					switch(empty($c['key']))
					{
						case false:
						$skip = true;
						$c['key'] = (is_array($c['key'])) ? $c['key'] : array($c['key']);
						$c['data'] = (is_array($c['data'])) ? $c['data'] : array($c['data']);
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
	
	private function check_key($field)
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