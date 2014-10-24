<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseElasticSearch extends \yii\elasticsearch\ActiveRecord implements SearchInterface
{
	use traits\ElasticSearchTrait, traits\SearchTrait, \nitm\traits\Data, \nitm\traits\Query;
	
	public $score;
	
	public function init()
	{
		if(!isset($this->primaryModelClass))
		{
			$class = $this->getModelClass(static::formName());
			$this->primaryModelClass = $class;
		}
		else
		{
			$class = $this->primaryModelClass;
		}
		if(!class_exists($class))
			$class = get_called_class();
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
	
	public function reset()
	{
		if(class_exists($this->primaryModelClass))
			$this->primaryModel = new $this->primaryModelClass;
		else
			$this->primaryModel = new static;
        $query = static::find($this);
        $this->dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
        ]);
		$this->conditions = [];
		return $this;
	}
	
	/**
	 * Overriding default find function
	 */
	public static function find($model=null, $options=null)
	{
		$query = \Yii::createObject(\nitm\search\query\ActiveElasticQuery::className(), [get_called_class()]);
		if(is_object($model))
		{
			if(!empty($model->withThese))
				$query->with($model->withThese);
			foreach($model->queryFilters as $filter=>$value)
			{
				switch(strtolower($filter))
				{
					case 'select':
					case 'indexby':
					case 'orderby':
					if(is_string($value) && ($value == 'primaryKey'))
					{
						unset($model->queryFilters[$filter]);
						$query->$filter(static::primaryKey()[0]);
					}
					break;
				}
			}
			static::applyFilters($query, $model->queryFilters);
		}
		return $query;
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
		switch(1)
		{
			case is_numeric($value) && !$partialMatch:
			case is_bool($value) && !$partialMatch:
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
}
?>