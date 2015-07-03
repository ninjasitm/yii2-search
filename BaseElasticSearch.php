<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseElasticSearch extends \yii\elasticsearch\ActiveRecord implements SearchInterface
{
	use traits\ElasticSearchTrait, traits\SearchTrait, \nitm\traits\Data, \nitm\traits\Query, \nitm\traits\Relations;
	public $engine = 'elasticsearch';
	
	public function init()
	{
		$class = $this->getPrimaryModelClass();
		static::setIndexType($class::isWhat(), $class::tableName());
	}
	
	public function behaviors()
	{
		$behaviors = [
			'behavior' => [
				'class' => \yii\base\Behavior::className()
			]
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public function getId()
	{
		return parent::getPrimaryKey();
	}
	
	public function offsetGet($name)
	{
		/**
		 * For some reason needed to implement this to allow population of relations
		 */
		return self::__get($name);
	}
	
	/**
	 * Overriding default find function
	 */
	public static function find($model=null, $options=null)
	{
		return static::findInternal(\nitm\search\query\ActiveElasticQuery::className(), $model, $options);
	}
	
	public static function getTableSchema()
	{
		return new \yii\db\TableSchema([
			'schemaName' => static::index(),
			'columns' => (new static)->columns(),
			'primaryKey' => '_id',
			'name' => static::type(),
			'fullName' => static::index().'.'.static::type()
		]);
	}
	
	public function get_Id()
	{
		return $this->id;
	}

    protected function addCondition($attribute, $value, $partialMatch=false)
    {
        if (($pos = strrpos($attribute, '.')) !== false) {
            $modelAttribute = substr($attribute, $pos + 1);
        } else {
            $modelAttribute = $attribute;
        }
        if (is_string($value) && trim($value) === '') {
            return;
        }
		
		$value = (is_array($value) && count($value) == 1) ? current($value) : $value;
		
		switch(1)
		{
			case is_numeric($value):
			case \nitm\helpers\Helper::boolval($value):
			case is_array($value) && !$partialMatch:
            switch($this->inclusiveSearch && !$this->exclusiveSearch)
			{
				case true:
				$this->conditions['or'][] = [$attribute => $value];
				break;
				
				default:
				$this->conditions['and'][] = [$attribute => $value];
				break;
			}
			break;
			
			default:
			switch($partialMatch) 
			{
				case true:
				switch($this->inclusiveSearch)
				{
					case true:
					$this->conditions['or'][] = [$attribute, $value, false];
					break;
					
					default:
					$this->conditions['and'][] = [$attribute, $value, false];
					break;
				}
				break;
				
				default:
            	$this->conditions['and'][] = [$attribute => $value];
				break;
			}
			break;
		}
	}
	
	protected function getTextParam($value)
	{
		//$this->dataProvider->query->query = ['query_string' => $value];
		$this->useEmptyParams = true;
		return ['q' => $value];
	}
	
	public static function instantiate($attributes)
	{
		if(!isset($attributes['_source']))
			$attributes['_source'] = [];
		$model = static::instantiateInternal($attributes['_source'], $attributes['_type']);
		static::setIndexType($model->isWhat());
		return $model;
	}
	
	public function getDataProvider($query, $parts, $options)
	{
		$command = $query->createCommand();	
		$query->offset((int) \Yii::$app->request->get('page')*$options['limit']);
		//$query->highlight(true);
		$query->query(isset($parts['query']) ? $parts['query'] : ArrayHelper::getValue($command, 'queryParts.query', []));
		$query->orderBy(ArrayHelper::getValue($options, 'sort', [
			'_id' => 'desc',
		]));
		$parts['filter'] = ArrayHelper::getValue($parts, 'filter', []);
		$query->where(array_merge((array)$query->where, (array)$parts['filter'], ArrayHelper::getValue($options, 'where', [])));
		
		if($this->forceType === true)
			$query->type = $options['types'];
		else
			$query->type = (isset($parts['types']) ? $parts['types'] : $options['types']);

		$models = $results = [];
		
		//Setup data provider. Manually set the totalcount and models to enable proper pagination
        $dataProvider = new \yii\data\ArrayDataProvider;
		
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
				$dataProvider->setTotalCount($results['hits']['total']);
				//Must happen after setting the total count
				$dataProvider->setModels($models);
				$dataProvider->pagination->totalCount = $dataProvider->getTotalCount();
			}
		}
		
		return [$results, $dataProvider];
	}
	
	/**
	 * Custom record population for related records
	 */
	public static function populateRecord($record, $row)
	{
		if(!isset($row['_source']))
			return;
			
		parent::populateRecord($record, $row);
		
		static::populateRelations($record, $row);
	}
}
?>