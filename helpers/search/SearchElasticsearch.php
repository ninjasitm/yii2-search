<?php
// Search with legacy support as well as solr, by default support
// Can be extended later but by default
// will include base methods for adding 
// data to a search database and automatic
// indexing of that information
// 
// There will also be the ability to
// add search information
// There will be the ability to update keywords
// and such

class SearchElastisearch extends BaseSearch
{
	public $params = [];
	public $condition = [];
	
	protected $query;
    protected $client; 
	protected $config = []; 
	protected $translate = []; 
	protected $mapping = [] ;
	protected $boost = ["PRI" => 50, "MUL" => 75, "UNI" => 100]; 
	protected $transform = [];
	
	private $wordTable = 'words';
	private $term = [
		"s" => null, 
		"condition" => null, 
		"query"
	];
	
	public function init()
	{
		parent::init();
	}
	
	public function attributes()
	{
		return [
			'title',
			'description'
		];
	}
	
	/*
		Add parameters to the query. This support multiple values per query
	*/
	public function addParam($param, $values)
	{
		$param = is_array($param) ? $param : [$param);
		$values = is_array($values) ? $values : [$values);
		foreach($param as $i=>$p)
		{
			switch(isset($values[$i]))
			{
				case true:
				$values[$i] = is_array($values[$i]) ? $values[$i] : [$values[$i]);
				switch($p)
				{
					case 'sort':
					$v = $this->split_c(array_keys($values[$i]), array_values($values[$i]), ' ', ', ', false, false, false);
					$this->solr['query']->setParam($p, $v);
					break;
					
					default:
					foreach($values[$i] as $k=>$v)
					{
						$this->solr['query']->setParam($p, $v);
					}
					break;
				}
				break;
			}
		}
	}
	
	/*
		Function to do actual search
	*/
	public function search()
	{
		$query = $this->find();
		return $ret_val;
	}
	
	/*
		Function to format search string
		@param string $string What to search for
		@param mixed $specific Some parameters which may be specific
		@return string
	*/
	public function format($string=null)
	{
		$ret_val = null;
		$string = explode(' ', $string);
		foreach($string as $idx=>$str)
		{
			switch(ctype_alpha($str[0]))
			{
				case true:
				switch($str)
				{
					case 'AND':
					$string[$idx] = $str;
					break;
					
					default:
					$string[$idx] = "+".$str;
					break;
				}
				break;
			}
		}
		$string = implode(' ', $string);
		switch(!empty($string))
		{
			case true:
			$this->term['s'] = $string;
			$ret_val = (empty($this->term['condition'])) ? $string : $string." AND ".$this->term['condition'];
			break;
			
			default:
			$ret_val = (empty($this->term['condition'])) ? null : $this->term['condition'];
			break;
		}
		$this->term['query'] = $ret_val;
		return $ret_val;
	}
	
	
	public function synonyms($term)
	{
	}
	
	/**
	 * Function to translate fields for solr
     * @param mixed $field
     * @return string
     */
    public function translateField($field)
    {
		$ret_val = $field['Field'];
		$f = [];
		$type = [];
		$type['paren_pos'] = strpos($field['Type'], '(');
		$type['exploded'] = explode('(', $field['Type']);
		$type['type'] = $type['exploded'][0];
		switch(isset($this->solr['fields']['translate'][$type['type']]))
		{
			//use predefined translation to choose field type for solr
			case true:
			switch($type['type'])
			{
				case 'tinyint':
				case 'int':
				switch($type['exploded'][1] > 1)
				{
					case true:
					$f['append'] = '_i';
					break;
					
					default:
					$f['append'] = '_b';
					break;
				}
				break;
				
				default:
				$f['append'] = $this->solr['fields']['translate'][$type['type']];
				break;
			}
			$f['matched'] = $field['Field'].$f['append'];
			break;
		}
		$ret_val = empty($f['append']) ? 'attr_'.$field['Field'] : $f['matched'];
		return $ret_val;
    }

    /**
	 *	Protected functions
     */
    
    /**
	 * Function to prepare fields and transformations
     * @return bool
     */
    protected function prepareFields()
    {
		$attributes = $this->atributes();
		foreach($attributes as $idx=>$field)
		{
			switch($field['Field'])
			{
				case 'indexed':
				continue;
				break;
				
				default:
				if(isset($this->solr['fields']['transform'][$field['Field']]))
				{
						return;
				}
				break;
			}
			switch($field['Key'])
			{
				case 'PRI':
				$this->solr['fields']['transform']['attr_unique'] = $field['Field'];
				$this->solr['fields']['boost'][$field['Field']] = $this->solr['fields']['boost_map'][$field['Key']];
				break;
				
				case 'UNI':
				$this->solr['fields']['boost'][$field['Field']] = $this->solr['fields']['boost_map'][$field['Key']];
				break;
				
				case 'MUL':
				$this->solr['fields']['boost'][$field['Field']] = $this->solr['fields']['boost_map'][$field['Key']];
				break;
			}
			$this->_query['fields'][] = $field['Field'];
			$f = [];
			switch(1)
			{
				case strpos($field['Type'], 'timestamp') !== false:
				$this->solr['fields']['transform'][$field['Field']] = $field['Field']."_tdt";
				break;
				
				case strpos($field['Type'], 'text') !== false:
				case strpos($field['Type'], 'varchar') !== false:
				case strpos($field['Type'], 'blob') !== false:
				foreach($this->solr['fields']['mapping'] as $parent_field=>$map)
				{
					switch(isset($this->solr['fields']['mapping'][$field['Field']]))
					{
						case true:
						$f['matched'] = $field['Field'];
						break;
					}
					switch(in_array($field['Field'], $map))
					{
						case true:
						$f['matched'] = $parent_field;
						break;
					}
				}
				$f['matched'] = (empty($f['matched'])) ? $this->translateField($field) : $f['matched'];
				$this->solr['fields']['transform'][$field['Field']] = (empty($f['matched'])) ? 'text' : $f['matched'];
				break;
				
				default:
				//Mysql returns parentheses in field names sometimes, checking for them here
				$f['matched'] = $this->translateField($field);
				$this->solr['fields']['transform'][$field['Field']] = (empty($f['matched'])) ? 'attr_'.$field['Field'] : $f['matched'];
				break;
			}
		}
		$this->solr['fields']['prepared'] = true;
		return true;
    }
	
	/*
	-----------------------------------
		Protected functions
	-----------------------------------
	*/
	
	/*
		Log the searched term. not sure if this is needed when using solr
	*/
	protected function logterm()
	{
		if(!empty($this->term))
		{
			if(sizeof($this->resids) >= 1)
			{
				if(($key = parent::check("term", $this->term, $this->data_db, $this->wordTable)) === false)
				{
					parent::insert(['term','searches','resource','last_searched', 'resids'), [$this->term, '1', $this->resource, strtotime('now'), serialize($this->resids)), $this->wordTable, null, true, null, true);
				}
				else
				{
					parent::update(['searches','resource','last_searched', 'resids'), ["`searches`+1", $this->resource, strtotime('now'), serialize($this->resids)), ['key' => 'wid', 'data' => $key['wid'][0]), $this->wordTable, null, 1, true);
				}
			}
		}
	}
	
	/*
	-----------------------------------
		Private functions
	-----------------------------------
	*/
	
}
?>
