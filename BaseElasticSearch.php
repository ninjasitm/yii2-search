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
	
	public function init()
	{
		if(!isset($this->primaryModelClass))
		{
			$class = $this->getModelClass(static::formName());
			$this->primaryModelClass = $class;
		}
		else
			$class = $this->primaryModelClass;
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
	
	/**
	 * This function properly maps the object to the correct class
	 */
	public static function instantiate($attributes)
	{
		$properName = \nitm\models\Data::properClassName($attributes['_type']);
		$class = static::$namespace.'search\\'.$properName;
		if(!class_exists($class))
			$class = '\nitm\models\search\\'.$properName;
		//$model = new static();
		//$model->setAttributes($attributes['_source'], false);
		$model = new $class();
		$model->load($attributes['_source'], false);
		$model->_type = $attributes['_type'];
		$model->_index = $attributes['_index'];
		static::setIndexType($model->_type);
		static::normalize($model, true);
		return $model;
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
	
	/**
	 * Convert some common properties
	 * @param array $item
	 * @param boolean decode the item
	 * @return array
	 */
	public static function normalize(&$item, $decode=false)
	{
		foreach((array)$item as $f=>$v)
		{
			if(!isset(static::columns()[$f]))
				continue;
			$info = \yii\helpers\ArrayHelper::toArray(static::columns()[$f]);
			switch(array_shift(explode('(', $info['dbType'])))
			{
				case 'tinyint':
				$item[$f] = $info['dbType'] == 'tinyint(1)' ? (boolean)$v : $v;
				break;
				
				case 'text':
				case 'varchar':
				case 'string':
				$args = [$v, ENT_COMPAT|ENT_HTML5, ini_get("default_charset")];
				if($decode)
					$func = 'html_entity_decode';
				else
				{
					$func = 'htmlentities';
					$args[] = false;
				}
				$item[$f] = call_user_func_array($func, $args);
				break;
			}
		}
		return $item;
	}
}
?>