<?php
// Search with legacy support
// Can be extended later but by default
// will include base methods for adding 
// data to a search database and automatic
// indexing of that information
// 
// There will also be the ability to
// add search information
// There will be the ability to update keywords
// and such

/*
Define the search modes
*/

class Search extends BaseSearch
{
	public $score = 'relevance';
	public $fulltextString = "";
	
	private $database = 'search';
	private $table = 'data';
	private $wordsTable = 'words';
	private $recordIds = null;
	private $recordIndex = '';
	private $term = "";
	private $termMode = "";

	const SRCH_DEFAULT = "";
	const SRCH_BOOL = "IN BOOLEAN MODE";
	const SRCH_EXP = "WITH QUERY EXPANSION";
	const SRCH_NTRL = "IN NATURAL LANGUAGE MODE";
	const SRCH_NTRL_EXP = "IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION";
	
	public function format($string=null, $specific=null, $ob=null, $asc='n')
	{
		if($string == null)
		{
			return false;
		}
// 		$string = str_replace("'", '"', stripslashes($string));
		$this->term = $string;
		switch($asc)
		{
			case 'n':
			$asc = 'DESC';
			$asc_opp = "ASC";
			break;
			
			default:
			$asc = 'ASC';
			$asc_opp = "DESC";
			break;
		}
		switch($ob)
		{
			case null:
			case 'score':
			$ob = array($this->threshold['name']." $asc", 'res_added');
			break;
			
			default:
			$ob = array("res_added $asc", $ob);
			break;
		}
		$ob = parent::split_f(array_unique($ob));
		$ret_val = false;
		$query = null;
		$condition = null;
		$columns = array();
		$indexes = parent::get_indexes();
		$this->pr("<pre>", $indexes, "<pre>");
		if(sizeof($indexes) >= 1)
		{
			foreach($indexes as $index)
			{
				if($index['Index_type'] == 'FULLTEXT')
				{
					$columns[$index['Key_name']][] = $index['Column_name'];
				}
			}
			if(strlen($string) < 3)
			{
				return $ret_val;
			}
			//
			//match any quoted parts of the string and then match unquoted words by themselves
			preg_match_all("/[\"']{1}.*['\"]{1}|[^'\"]\w*[^'\"]/", $string, $prstring);
			$prepared_string = $this->prepare($prstring[0]);
			$fulltextString = $prepared_string;
			//$wstring = (isset($prstring[0][1])) ? preg_split("/[\s,]+/", trim($prstring[0][1])) : null;
			//$fulltextString = ($wstring != null) ? array_merge($wstring, array(trim($prstring[0][0]))) : $prstring[0][0];
			$match_numeric = array();
			$types = array('tinyint', 'smallint', 'int', 'mediumint', 'bigint', 'float', 'decimal', 'double');
			foreach(parent::get_fields() as $idx=>$field)
			{
				foreach($types as $type)
				{
					if((stripos($field['Type'], $type) !== false) && ($field['Field'] != parent::get_cur_pri()))
					{
						$match_numeric[$field['Field']] = $field['Field'];
					}
				}
			}
			if(!is_null($specific) && is_array($specific))
			{
				$condition = parent::split_c($specific['key'], $specific['data'], @$specific['operand'], @$specific['xor']);
			}
			$this->query = array();
			$obs = array();
			if(is_numeric($string))
			{
				foreach($fulltextString as $idx=>$str)
				{
					switch($str)
					{
						case is_numeric($str):
						case is_float($str):
						$query = "(SELECT DISTINCT *,".parent::get_cur_pri()." AS ".$this->threshold['name']." FROM ".$this->table." WHERE ";
						if(!is_null($condition))
						{
							$query .= $condition." AND (";
						}
						$query .= parent::get_cur_pri()."='$str' \n";
						if(sizeof($match_numeric) >= 1)
						{
							$or_num = " OR ".parent::split_f($match_numeric, "='$str' OR \n", false)."='$str'";
						}
						$query.= $or_num;
						if(!is_null($condition))
						{
							$query .= ")";
						}
						$query .= " ORDER BY ".$this->threshold['name']." $asc)\n";
						$this->query[] = $query;
						break;
						
						default;
						break;
					}
				}
			}
			else
			{
				$fulltextString_base = array_shift($fulltextString);
				$fulltextString_extra_base = parent::split_f($fulltextString, '');
				$fulltextString_extra = (cou_genericnt($fulltextString) >= 1) ? " (>+".parent::split_f($fulltextString, "*) (")."*))" : '';
				$queries = array('match_part' => array(), 'cond_part' => array());
				switch($this->termMode)
				{
					case SRCH_NTRL:
					case SRCH_NTRL_EXP:
					case SRCH_EXP;
					$fulltextString_base = implode(' ', $prepared_string);
					$fulltextString_extra = '';
// 					parent::flipob();
					break;
					
					case SRCH_BOOL:
					case SRCH_REG:
					$fulltextString_base = "+$fulltextString_base*";
					break;
					
				}
				$fields =array();
				foreach($columns as $idx=>$column)
				{
// 					$queries['match_part'][] = "(MATCH(`".parent::split_f($column, '`,`', false)."`) AGAINST('$fulltextString_base$fulltextString_extra' ".$this->termMode."))";
// 					$query .= "MATCH(`".parent::split_f($column, '`,`', false)."`) AGAINST('$fulltextString_base$fulltextString_extra' ".$this->termMode.")";
// 					$query .= ")\n\n";
					$fields = array_merge($fields, $column);
					$sel_fields = parent::split_f($column, '`,`', false);
					$query = "(SELECT *,MATCH(`$sel_fields`) AGAINST('$fulltextString_base$fulltextString_extra' ".$this->termMode.") AS ".$this->threshold['name']." FROM `".$this->table."` WHERE ";
					if(!is_null($condition))
					{
						$query .= "(".$condition.") AND (";
					}
					$query .= " MATCH(`$sel_fields`) AGAINST('$fulltextString_base$fulltextString_extra' ".$this->termMode.")";
					if(!is_null($condition))
					{
						$query .= ")";
					}
					$query .= ")\n\n";
					$this->query[] = $query;
				}

			}
			$this->max_query = "SELECT COUNT(*), MAX(".$this->threshold['name'].") AS ".$this->threshold['nameMax']." FROM (".parent::split_f($this->query, ' UNION DISTINCT ').') results';
			$this->max_query .= is_null($condition) ? '' : " WHERE (".$condition.")";
			$this->max_rows($this->max_query, null, false);
			
			$this->query = "SELECT * FROM (".parent::split_f($this->query, ' UNION DISTINCT ').') results';
			$this->query .= is_null($condition) ? '' : " WHERE (".$condition.") AND ((".$this->threshold['name']."/".$this->best_match.") > ".$this->threshold['min'].")";
			parent::set_ob($ob);
			$ret_val = true;
		}
		else
		{
			$ret_val = false;
		}
		return $ret_val;
	}
	
	public function parse()
	{
		if(parent::rows())
		{
			$ret_val = array();
			$this->recordIndex = parent::get_cur_pri();
			$this->resource = serialize(parent::result());
			$this->threshold['max'] = $this->best_match;
			foreach(parent::get_results() as $idx=>$value)
			{
				foreach($value as $key=>$data)
				{
					switch($key)
					{
						case $this->recordIndex:
						$this->recordIds[] = $data;
						case 'score':
						case 'searches':
						case 'indexed':
						continue;
						break;
						
						case $this->threshold['name']:
						$ret_val[$idx][$key] = $data;
						break;
						
						default:
						if(($data != null) || !empty($data))
						{
/*
							switch($type = @parent::get_field_type($key))
							{
*/
// 								case strpos($type, "blob") !== false:
// 								case strpos($type, "text") !== false:
// 								$ret_val[$idx][$key] = stripslashes(@convert_uudecode($data));
// 								echo "Gettype of ($data) == ".gettype($data)."<br />";
// 								break;
								
/*
								default:
*/
								$ret_val[$idx][$key] = $data;
/*
								break;
							}
*/
						}
						break;
					}
				}
				$ret_val[$idx]['score'] = ($ret_val[$idx][$this->threshold['name']] / $this->threshold['max']) * 100;
			}
			$this->logterm();
			return $ret_val;
		}
		else
		{
			return false;
		}
	}
	
	public function logterm()
	{
		if(!empty($this->term))
		{
			if(sizeof($this->recordIds) >= 1)
			{
				if(($key = parent::check("term", $this->term, $this->database, $this->wordsTable)) === false)
				{
					parent::insert(array('term','searches','resource','last_searched', 'recordIds'), array($this->term, '1', $this->resource, strtotime('now'), serialize($this->recordIds)), $this->wordsTable, null, true, null, true);
				}
				else
				{
					parent::update(array('searches','resource','last_searched', 'recordIds'), array("`searches`+1", $this->resource, strtotime('now'), serialize($this->recordIds)), array('key' => 'wid', 'data' => $key['wid'][0]), $this->wordsTable, null, 1, true);
				}
			}
		}
	}
	
	public function synonyms($term)
	{
	}
	
	private function prepare($string)
	{
		$len = 0;
		$ntrl_cnt = 0;
		$string = (is_array($string)) ? $string : array($string);
		foreach($string as $idx=>$str)
		{
			$str = trim($str);
			$len += strlen($str);
			switch(strlen($str) < $this->minLength)
			{
				case true:
				$ntrl_cnt++;
				break;
				
				default:
				$string[$idx] = $str;
				break;
			}
		}
		switch(true)
		{
			case (sizeof($string) == 1) && ($len < $this->minLength):
			$this->termMode = SRCH_EXP;
			break;
			
			case ($len >= (sizeof($string) * $this->minLength)) && (sizeof($string) <= 4) && ($ntrl_cnt < 2):
			$this->termMode = SRCH_BOOL;
			break;
			
			default:
			$this->termMode = SRCH_NTRL;
			break;
		}
		return $string;
	}
}
?>
