<?php

namespace nitm\search\traits;

use Yii;

use yii\helpers\ArrayHelper;

/**
 * trait Alerts
 * May merge into nitm Module
 * @package nitm\module
 */

trait ElasticSearchTrait
{	
	//public $_type;
	//public $_index;
	public static $_localType;
	
	protected static $_database;
	protected static $_table;
	protected static $_indexType;
	protected static $_columns = [];
	protected $_tableSchema;
	
	/*public static function tableName()
	{
		$class = __CLASS__;
		$modelClass = (new $class)->getModelClass(static::className());
		static::$tableName = $modelClass::tableName();
		return static::$tableName;
	}*/
	
	public function setIndex($index)
	{
		static::$_database = $index;
	}
	
	public function setIndexType($type, $table=null)
	{
		static::$_localType = $type;
		static::$_table = is_null($table) ? $type : $table;
		if(isset($this)) {
			$this->getPrimaryModelClass(true);
		}
	}
	
	public function formName()
	{
		if(isset($this))
			return $this->properFormName();
		else
			return static::properFormName();
	}
	
	public static function dbName()
	{
		return isset(static::$_database) ? static::$_database : \nitm\models\DB::getDbName();
	}
	
	public static function index()
	{
		return isset(\Yii::$app->getModule('nitm-search')->index) ? \Yii::$app->getModule('nitm-search')->index : static::indexName();
	}
	
	public static function type()
	{
		return static::$_localType;
	}
	
	public static function tableName()
	{
		return static::$_table;
	}
	
	public function getMapping()
	{
		return static::getDb()->get([static::index(), static::type(), '_mapping']);
	}
	
	public function columns()
	{
		if(!static::type())
			return static::defaultColumns([
				'_id' => 'int', 
				'_type' => 'string', 
				'_index' => 'string'
			]);
			
		if(!array_key_exists(static::type(), static::$_columns))
		{
			$columns = ArrayHelper::getValue(static::getMapping(), static::index().'.mappings.'.static::type().'.properties', []);
			//if(is_null($columns))
				//$columns = static::defaultColumns();
			//else
				//$columns = array_merge(static::defaultColumns(), $columns);
			
			static::$_columns[static::type()] = !empty($columns) ? array_combine(array_keys($columns), array_map(function ($col, $name){
				$type = isset($col['type']) ? $col['type'] : 'string';
				return new\yii\db\ColumnSchema([
					'name' => $name,
					'type' => $type,
					'phpType' => $type,
					'dbType' => $type
				]);
			}, $columns, array_keys($columns))) : [];
		}
		return ArrayHelper::getValue(static::$_columns, static::type(), static::defaultColumns([
			'_id' => 'int', 
			'_type' => 'string', 
			'_index' => 'string'
		]));
	}
	
	public function attributes()
	{
		return array_combine(array_keys((array)$this->columns()), array_keys((array)$this->columns()));
	}
	
	/**
	 * Parse the query and extract speciial named parameters
	 * @param array $options
	 * @return array
	 */
	public function parseQuery($string=null)
	{
		if(is_null($string))
			return [];
		$this->text = $string;
		$query = $this->filter(explode(' ', $string));
		
		//print_r($query);
		//exit;
		
		//If types were specified then get them
		if(isset($query['nested']['_type']) && sizeof($query['nested']['_type']) >= 1)
			$userSpecifiedTypes = $query['nested']['_type'];
		elseif(\Yii::$app->request->get('_type'))
			$userSpecifiedTypes = [\Yii::$app->request->get('_type')];
		$types = $this->getTypes((!$this->type ? $query['parts'] : explode(',', $this->type)), isset($userSpecifiedTypes) ? $userSpecifiedTypes : false);
		
		//We don't want these in our query anymore
		unset($query['nested']['_type'], $query['_index']);
		
		//If the user specified anything then we must match it otherwise we're doing a loose search
		$mustMatch = (sizeof($query) >= 2) ? true : false;
		if(isset($query['filter']))
		{
			$ret_val['filter'] = $query['filter'];
			unset($query['filter']);
		}
		$ret_val['types'] = (sizeof($types) == 0 ) ? '_all' : implode(',', $types);
		$ret_val['route'] = $this->index().'/'.$ret_val['types'].'/_search/';
		$ret_val['query'] = $this->getTypeBoost($types, $mustMatch, $query);
		foreach(['sort'] as $option)
			if(isset($query[$option]))
				$ret_val[$option] = $query[$option];
		return $ret_val;
	}
	
	/**
	 * Get the types that a user has specified
	 * @param string $string
	 * @param array $types
	 * @return array
	 */
	protected function getTypes($string=null, $types=null)
	{
		//If the type is forced then don't check the types further
		if($this->forceType === true)
			return [$this->type];
		
		$ret_val = [];	
		$types = array_filter(array_merge((array)$string, (array)$types));
		if(sizeof($types) >= 1)
		{
			foreach($types as $idx=>$type)
			{
				if(strlen($type) < 3)
					continue;
				$type = strtolower($type);
				//echo "Checking for $type<br>";
				switch (1)
				{
					//If this exact type is already predefined then get all the matches
					case sizeof($matching = @preg_grep("/^$type*/", array_keys(\Yii::$app->getModule('nitm-search')->settings[$this->engine]['boost']))) >= 1:
					//Otherwise if it is close to a certain type then get them
					case sizeof(($matching = array_filter(array_keys(\Yii::$app->getModule('nitm-search')->settings[$this->engine]['boost']), function ($correctType) use($type) {
						similar_text($type, strtolower($correctType), $percent);
						//echo "Percentage match for ".$correctType." against $type is ".$percent."<br>";
						return $percent >= 80;
					}))) >= 1:
					unset($types[$idx]);
					$ret_val = array_merge((array)$ret_val, array_map('strtolower', (array)$matching));
					break;
				}
			}
		}
		else 
			$ret_val =  array_keys(\Yii::$app->getModule('nitm-search')->settings[$this->engine]['boost']);
			
		return $ret_val;
	}
	
	/**
	 * Fitler an array based on colon (:) separated parameters
	 * @param array $array
	 * @param array $filters
	 * @return array
	 */
	protected function filter($array, $filters=null)
	{
		$ret_val = [];
		$string = $array;
		$filtersWere = $filters;
		$typeFilters = ['_type', 'type'];
		if(is_array($filters))
			$filters = array_merge($filters, $typeFilters);
		else
			$filters = $typeFilters;
		while(list($idx, $part) = each($array))
		{
			$parts = explode(':', $part);
			$operator = $this->exclusiveSearch ? 'must' : 'should';
			if(array_key_exists($parts[0], array_keys($this->columns())))
				$depth = $this->columns()[$parts[0]]->type == 'nested' ? 'nested' : 'filters';
			else
				$depth = 'filters';
			switch(1)
			{
				case (sizeof($parts = explode(':', $part)) >= 2) || ($filtersWere === true):
				$values = explode(',', $parts[1]);
				switch($parts[0])
				{
					case '_type':
					case 'type':
					$ret_val[$depth][$parts[0]] = count($values) == 1 ? array_pop($values) : $values;
					break;
					
					case 'sort':
					$ret_val[$parts[0]][$parts[1]] = isset($parts[2]) ? $parts[2] : 'desc';
					break;
					
					default:
					$operator = 'should';
					$parts[0] = array_pop(explode('.', $parts[0]));
					$ret_val[$depth][$parts[0]] = count($values) == 1 ? array_pop($values) : $values;
					break;
				}
				unset($string[$idx]);
				break;
				
				//If this is an equal query: name=value then split it accordingly
				case((sizeof($parts = explode('=', $part)) == 2)):
				$ret_val['filters'][$parts[0]] = $parts[1];
				unset($string[$idx]);
				break;
				
				default:
				$next = isset($array[$idx+1]) ? $array[$idx+1] : '';
				$prev = (isset($array[$idx-1]) && strpos($array[$idx-1], ':') !== false) ? $array[$idx-1] : false;
				$prevKey = $prev !== false ? array_shift(explode(':', $prev)) : false;
				/** 
				 * If this is a string and the next part in the $array is a filter 
				 * then combine this with the previous string value
				 */
				if(($prevKey !== false) && (((strpos($next, ':') !== false) && sizeof($parts) == 1) || !$next) && ($prevKey && is_array($filters) && in_array($prevKey, $filters)))
				{
					if($depth == 'nested') {
						switch($prevKey)
						{
							case '_type':
							case 'type':
							$ret_val[$depth]['_type'][] = $parts[0];
							unset($ret_val[$depth]['type']);
							break;
							
							default:
							$ret_val[$depth][$prevKey][0] .= ' '.$parts[0];
							break;
						}
					}
				}
				break;
			}
		}
		if(isset($ret_val['filters'])) 
		{
			foreach($ret_val['filters'] as $key=>$value)
			{
				if(is_array($value))
					$ret_val['filter']['bool'][$operator][] = [
						'terms' => [$key = $value]
					];
				else if(is_string($value) || is_numeric($value))
					$ret_val['filter']['bool'][$operator][] = [
						'term' => [$key => $value]
					];
					
			}
			/*foreach(['terms', 'term'] as $filterType)
			{
				if(isset($ret_val['filter'][$filterType])) {
					$ret_val['filter'][$filterType]['execution'] = $operator;
				}
			}*/
			unset($ret_val['filters']);
		}
		
		$ret_val['parts'] = $string;
		return $ret_val;
		
	}
	
	/**
	 * Get the boost values for these types
	 * @param array $types
	 * @param boolean $must Should must be used for a boolean query?
	 * @param array $filters
	 * @return array
	 */
	protected function getTypeBoost($types, $must=false, $filters=null)
	{
		$ret_val = [];
		$key = 'functions';
		$boostMethod = 'function_score';
		$boolMethod = $must === false ? 'should' : 'must';
		$query = [];
		//Get the query string
		$string = implode(' ', $filters['parts']);
		unset($filters['parts']);
		if($string)
			$query['bool']['should'] = [
				[
					'multi_match' => [
						'fields' => ['_all'],
						'type' => 'cross_fields',
						'query' => $string,
						'operator' => $boolMethod == 'must' ? 'and' : 'or'
					]
				]
			];
		/**
		 * Support for nested queries here
		 */
		if(isset($filters['nested']) && is_array($filters['nested']))
		{
			unset($filters['parts']);
			$query['bool'][$boolMethod] = [];
			/*else
				$query['bool'][$boolMethod] = [
					['match' => ['_all' => ['query' => '*']]]
				];*/
			$nested = [];
			foreach($filters['nested'] as $match=>$value)
			{
				$nested[] = [
					'nested' => [
						'path' => $match,
						'score_mode' => 'max',
						'query' => [
							'multi_match' => [
								"fields" => "_all",
								"query" => $this->translateValue($value),
							]
						]
					]
				];
			}
			if(sizeof($nested) >= 1)
				$query['bool'][$boolMethod] = array_merge($query['bool'][$boolMethod], $nested);
			$query['bool'] = array_filter($query['bool']);
			$query = array_filter($query);
		}
		$ret_val[$boostMethod]['query'] = $query;
		foreach((array)$types as $type)
		{
			if(!isset(\Yii::$app->getModule('nitm-search')->settings[$this->engine]['boost'][$type]))
				continue;
			$boost = \Yii::$app->getModule('nitm-search')->settings[$this->engine]['boost'][$type];
			$ret_val[$boostMethod][$key][] = [
				'filter' => [
					'term' => [
						'_type' => $type,
					]
				],
				'boost_factor' => $boost
			];
		}
		$ret_val[$boostMethod]['score_mode'] = 'multiply';
		$ret_val[$boostMethod]['boost_mode'] = 'sum';
		$ret_val[$boostMethod] = array_filter($ret_val[$boostMethod]);
		//print_r($ret_val);
		//exit;
		return $ret_val;
	}
}
?>