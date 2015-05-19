<?php
namespace nitm\search\traits\controllers;

use yii\helpers\ArrayHelper;

/**
 * SearchController traits
	 */
trait SearchControllerTrait {
	
	public $namespace;
	public $type;
	
	protected $engine;
	protected $nestedPrefix = 'nested:';
	protected $forceType;
	
	public function getSearchModelClass($class)
	{
		return rtrim($this->namespace, '\\').'\\search\\'.array_pop(explode('\\', $class));
	}
	
	/**
	 * Execute an Elastic search using the _search API instead of the ActiveRecord
	 * @param array $options
	 * @return array
	 */
	public function search($options=[], $modelOptions=[])
	{
		$options = array_merge([
			'types' => '_search',
			'q' => \Yii::$app->request->get('q'),
			'params' => \Yii::$app->request->get(),
			'with' => [], 
			'viewOptions' => [], 
			'construct' => [
				'queryOptions' => [
					'orderBy' => ['_score' => SORT_DESC]
				]
			],
			'limit' => \Yii::$app->request->get('limit') ? \Yii::$app->request->get('limit') : 10,
			'sort' => [
				'_score' => ['order' => 'desc'],
				'id' => ['order' => 'desc', 'ignore_unmapped' => true]
			]
		], $options);
		
		$this->model->load($modelOptions);
		
		
		$this->type = $options['types'];
		//$this->model->setIndexType($this->type);
		
		//We can force types even if the user specified them in teh query string
		if(isset($options['forceType']))
			$this->forceType = $options['forceType'];	
		//Set filtering and url based options
		$params = \Yii::$app->request->get();
		$params['q'] = isset($params['q']) ? $params['q'] : $options['q'];
		
		/**
		 * Setup data parts
		 */
		
		$dataProvider = $this->model->search($params);
		//Parse the query and extract the parts
		$parts = $this->parseQuery($this->model->text);
		$query = $dataProvider->query;
		$command = $query->createCommand();		
		
		/**
		 * Setup the query parts
		 */
		$query->offset((int) \Yii::$app->request->get('page')*$options['limit']);
		//$query->highlight(true);
		$query->query(isset($parts['query']) ? $parts['query'] : $command->queryParts['query']);
		$query->orderBy($options['sort']);
		$parts['filter'] = ArrayHelper::getValue($parts, 'filter', []);
		$query->where(array_merge((array)$query->where, (array)$parts['filter'], ArrayHelper::getValue($options, 'where', [])));
		
		if($this->forceType === true)
			$query->type = $options['types'];
		else
			$query->type = (isset($parts['types']) ? $parts['types'] : $options['types']);

		$models = $results = [];
		
		if(sizeof($command->queryParts) >= 1 || !empty($this->model->text))
		{
			try {
				$results = $query->search();
				$success = true;
			} catch (\Exception $e) {
				$success = false;
				if(defined('YII_DEBUG') && YII_DEBUG === true) {
					throw $e;
				}
			}
			if($success)
			{
				/**
				 * The models are instantiated in Search::instantiate function
				 */
				$models = $results['hits']['hits'];
				/*if(is_array($results))
				foreach($results['hits']['hits'] as $attributes)
				{
					$properName = \nitm\models\Data::properClassName($attributes['_type']);
					print_r($attributes);
					exit;
					$class = $this->getSearchModelClass($properName);
					if(!class_exists($class))
						$class = '\nitm\models\search\\'.$properName;
					$model = new $class($attributes);
					$this->model->setIndexType($attributes['_type']);
					$model->setAttributes($attributes['_source'], false);
					$models[] = $model;
				}*/
			}
		}
		else
		{
			$this->model->addError('info', "Empty string provided!!");
		}
		//Setup data provider. Manually set the totalcount and models to enable proper pagination
        $dataProvider = new \yii\data\ArrayDataProvider;
		$dataProvider->setTotalCount($results['hits']['total']);
		//Must happen after setting the total count
		$dataProvider->setModels($models);
		$dataProvider->pagination->totalCount = $dataProvider->getTotalCount();
		//$dataProvider = $this->model->search(\Yii::$app->request->get());
		return [$results, $dataProvider];
	}
	
	/**
	 * Parse the query and extract speciial named parameters
	 * @param array $options
	 * @return array
	 */
	protected function parseQuery($string=null)
	{
		if(is_null($string))
			return [];
		$this->model->text = $string;
		$query = $this->filter(explode(' ', $string), ['_type']);
		
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
		$ret_val['route'] = $this->model->index().'/'.$ret_val['types'].'/_search/';
		$ret_val['query'] = $this->getTypeBoost($types, $mustMatch, $query);
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
		while(list($idx, $part) = each($array))
		{
			$parts = explode(':', $part);
			switch(1)
			{
				case (sizeof($parts = explode(':', $part)) == 2) || ($filters === true):
				$ret_val['nested'][$parts[0]] = explode(',', $parts[1]);
				unset($string[$idx]);
				break;
				
				//If this is an equal query: name=value then split it accordingly
				case((sizeof($parts = explode('=', $part)) == 2)):
				$ret_val['filter'][$parts[0]] = $parts[1];
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
				if(($prevKey !== false) && (((strpos($next, ':') !== false) && sizeof($parts) == 1) || !$next) && ($prevKey && is_array($filters) && !in_array($prevKey, $filters)))
				{
					$isNested = strpos($prev, ':') !== false;
					$ret_val[($isNested ? 'nested' : 'filter')][$prevKey][0] .= ' '.$parts[0];
					unset($string[$idx]);
				}
				break;
			}
		}
		/*if(isset($ret_val['filters']))
			$ret_val['filter'][($this->model->exclusiveSearch ? 'and' : 'or')] = array_map(function($value, $key) {
				return [
					'in' => [
						$key => [$value]
					]
				];
			}, $ret_val['filters'], array_keys($ret_val['filters']));*/
		
		$ret_val['parts'] = $string;
		//print_r($ret_val);
		//exit;
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
	
	/**
	 * Get a synonym value
	 */
	protected function getFilterSynonym($filter, $value)
	{
		$value = $this->translateValue($value);
		$ret_val = [$filter, $value];
		switch($filter)
		{
			case 'open':
			$filter = 'closed';
			$value = (int)!$value;
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Translate a value
	 * @param mixed $value
	 * @return mixed
	 */
	protected function translateValue($value)
	{
		$ret_val = $value;
		switch(1)
		{
			case $value == 'false':
			$ret_val = 0;
			break;
			
			case $value == 'true':
			$ret_val = 1;
			break; 
		}
		return $ret_val;
	}
}
?>