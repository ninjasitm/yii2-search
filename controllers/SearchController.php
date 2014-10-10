<?php

namespace nitm\search\controllers;

use Yii;
use nitm\controllers\DefaultController;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\helpers\Response;

/**
 * SearchController implements the CRUD actions for Search model.
 */
class SearchController extends DefaultController
{
	public $legend = [
		'normal' => 'Normal',
		'info' => 'Important',
		'danger' => 'Critical',
		'disabled' => 'Hidden'
	];
	public $namespace;
	public $searchClass;
	protected $engine;
	private $nestedPrefix = 'nested:';
	
	public function init()
	{
		$class = isset($this->searchClass) ? \nitm\models\BaseSearch::className() : $this->searchClass;
		$this->model = new $class([
			'scenario' => 'default',
			'primaryModelClass' => $class::className(),
			'useEmptyParams' => true,
		]);
		$class::$noSanitizeType = true;
		$this->engine = \Yii::$app->params['components.search']['engine'];
		parent::init();
	}
	
    /*public function behaviors()
    {
		$behaviors = [
		];
		return array_merge_recursive(parent::behaviors(), $behaviors);
    }*/

    /**
     * Lists all Search models.
     * @return mixed
     */
    public function actionIndex()
    {
		$options = [
			'params' => \Yii::$app->request->getQueryParams(),
			'with' => [], 
			'viewOptions' => [], 
			'construct' => [
				'queryOptions' => [
					'orderBy' => ['_score' => SORT_DESC]
				]
			],
			'orderBy' => '_score'
		];
		$this->model->setType('_search');
		$this->model->text = \Yii::$app->request->get('q');
		$parts = $this->parseQuery(\Yii::$app->request->get('q'));
		$body = [
			'query' => isset($parts['boost']) ? $parts['boost'] : null,
			'sort' => [
				['_score' => ['order' => 'desc']],
				['created_at' => ['order' => 'desc', 'ignore_unmapped' => true]]
			]
		];
		$models = [];
		if(sizeof($parts) >= 1 && !empty($this->model->text))
		{
			try {
				$results = $this->model->find()->createCommand()->db->get($parts['route'], [], json_encode($body));
				$success = true;
			} catch (\Exception $e) {
				$success = false;
				if(defined('YII_DEBUG') && YII_DEBUG === true)
				{
					throw $e;
				}
			}
			if($success)
			{
				foreach($results['hits']['hits'] as $attributes)
				{
					$properName = \nitm\models\Data::properClassName($attributes['_type']);
					$class = $this->namespace.'search\\'.$properName;
					if(!class_exists($class))
						$class = '\nitm\models\search\\'.$properName;
					$model = new $class($attributes);
					$this->model->setType($attributes['_type']);
					$model->setAttributes($attributes['_source'], false);
					$models[] = $model;
				}
			}
		}
		else
		{
			$this->model->addError('info', "Empty string provided!!");
		}
        $dataProvider = new \yii\data\ArrayDataProvider([
			'allModels' => $models
		]);
		$dataProvider->pagination->route = \Yii::$app->urlManager->createUrl([
			'/search/'.$this->model->indexName(), 
			'q' => $this->model->text,
		]);
        $ret_val = [
			'success' => true,
			'query' => $this->model->text,
			'data' => $this->renderAjax('index', array_merge([
				'dataProvider' => $dataProvider,
				'model' => $this->model,
				'stats' => [
					'duration' => @$results['took'].'ms',
					'total' => @$results['hits']['total'],
					'max_score' => @$results['hits']['max_score']
				]
			], $options['viewOptions']))
		];
		Response::$viewOptions['args']['content'] = $ret_val['data'];
		return $this->renderResponse($ret_val, Response::$viewOptions, \Yii::$app->request->isAjax);
    }
	
	protected function parseQuery($string=null)
	{
		if(is_null($string))
			return [];
		$query = $this->filter(explode(' ', $string), ['_type']);
		
		//If types were specified then get them
		if(isset($query[$this->nestedPrefix.'_type']) && sizeof($query[$this->nestedPrefix.'_type']) >= 1)
			$userSpecifiedTypes = $query[$this->nestedPrefix.'_type'];
		$types = $this->getTypes($query['parts'], isset($userSpecifiedTypes) ? $userSpecifiedTypes : false);
		
		//We don't want these in our query anymore
		unset($query[$this->nestedPrefix.'_type'], $query[$this->nestedPrefix.'_index']);
		
		//If the user specified anything then we must match it otherwise we're doing a loose search
		$mustMatch = (sizeof($query) >= 2) ? true : false;
		$ret_val['route'] = $this->model->indexName().'/'.implode(',', $types).'/_search/';
		$ret_val['boost'] = $this->getTypeBoost($types, $mustMatch, $query);
		return $ret_val;
	}
	
	protected function getTypes($string=null, $types=null)
	{
		$ret_val = [];
		$types = array_filter(array_merge((array)$string, (array)$types));
		if(sizeof($types) >= 1)
		{
			foreach($types as $idx=>$type)
			{
				$type = strtolower($type);
				//echo "Checking for $type<br>";
				switch (1)
				{
					//If this exact type is already predefined then get all the matches
					case sizeof($matching = @preg_grep("/^$type*/", array_keys(\Yii::$app->params['components.search.'.$this->engine]['boost']))) >= 1:
					//Otherwise if it is close to a certain type then get them
					case sizeof(($matching = array_filter(array_keys(\Yii::$app->params['components.search.'.$this->engine]['boost']), function ($correctType) use($type) {
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
		{
			$ret_val =  [
				'updates', 'meetings', 'requests', 
				'refunds', 'routing', 'ip-hosts', 
				'carrier-contact', 'carrier-contact-level',
				'prefixes', 'replies', 'link-pass'
			];
		}
		//exit;
		return $ret_val;
	}
	
	protected function filter($array, $filters=null)
	{
		$ret_val = [];
		$string = $array;
		while(list($idx, $part) = each($array))
		{
			$parts = explode(':', $part);
			switch((sizeof($parts) == 1) || ($filters === true))
			{
				case true;
				$next = isset($array[$idx+1]) ? $array[$idx+1] : '';
				$prev = (isset($array[$idx-1]) && strpos($array[$idx-1], ':') !== false) ? $array[$idx-1] : false;
				$prevKey = $prev !== false ? array_shift(explode(':', $prev)) : false;
				/** 
				 * If this is a string and the next part in the $array is a filter 
				 * then combine this with the previous string value
				 */
				if(($prevKey !== false) && (((strpos($next, ':') !== false) && sizeof($parts) == 1) || !$next) && ($prevKey && is_array($filters) && !in_array($prevKey, $filters)))
				{
					$ret_val[$this->nestedPrefix.$prevKey][0] .= ' '.$parts[0];
					unset($string[$idx]);
				}
				break;
				
				default:
				$ret_val[$this->nestedPrefix.$parts[0]] = explode(',', $parts[1]);
				unset($string[$idx]);
				break;
			}
		}
		$ret_val['parts'] = $string;
		//print_r($ret_val);
		//exit;
		return $ret_val;
		
	}
	
	protected function getTypeBoost($types, $must=false, $filters=null)
	{
		$ret_val = [];
		$key = 'functions';
		$boostMethod = 'function_score';
		switch(is_array($filters))
		{
			case true:
			$boolMethod = $must === false ? 'should' : 'must';
			$string = implode(' ', $filters['parts']);
			unset($filters['parts']);
			$query = [
				'bool' => [
					$boolMethod => [
					]
				]
			];
			if($string)
				$query['bool'][$boolMethod] = [
					[
						'multi_match' => [
							'fields' => ['_all'],
							'type' => 'cross_fields',
							'query' => $string,
							'operator' => $boolMethod == 'must' ? 'and' : 'or'
						]
					]
				];
			/*else
				$query['bool'][$boolMethod] = [
					['match' => ['_all' => ['query' => '*']]]
				];*/
			$nested = [];
			foreach($filters as $match=>$values)
			{
				switch(1)
				{
					case ($matchWith = substr($match, 0, strlen($this->nestedPrefix))) == $this->nestedPrefix:
					$match = substr($match, strlen($matchWith));
					foreach($values as $value)
					{
						$nested[] = [
							'nested' => [
								'path' => $match,
								'score_mode' => 'max',
								'query' => [
									'wildcard' => [
										'_all' => $this->translateValue($value),
									]
								]
							]
						];
					}
					break;
					
					default:
					foreach($values as $value)
					{
						list($match, $value) = $this->getFilterSynonym($match, $value);
						$nested[] = [
							'wildcard' => [
								$match => $value,
							]
						];
					}
					break;
				}
			}
			if(sizeof($nested) >= 1)
				$query['bool'][$boolMethod] = array_merge($query['bool'][$boolMethod], $nested);
			$query['bool'] = array_filter($query['bool']);
			$query = array_filter($query);
			break;
			
			default:
			$query = [
				'query_string' => [
					'query' => $filters,
				]
			];
			break;
		}
		$ret_val[$boostMethod]['query'] = $query;
		foreach((array)$types as $type)
		{
			if(!isset(\Yii::$app->params['components.search.'.$this->engine]['boost'][$type]))
				continue;
			$boost = \Yii::$app->params['components.search.'.$this->engine]['boost'][$type];
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
		print_r($ret_val);
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
