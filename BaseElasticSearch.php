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
<<<<<<< HEAD
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
				if(is_array($v)) {
					$item[$f] = static::normalize($v, $decode);
				}
				/*else {
					$args = [$v, ENT_COMPAT|ENT_HTML5, ini_get("default_charset")];
					if($decode)
						$func = 'html_entity_decode';
					else
					{
						$func = 'htmlentities';
						$args[] = false;
					}
					$item[$f] = call_user_func_array($func, $args);
				}*/
				break;
			}
		}
		return $item;
=======
		$model = static::instantiateInternal($attributes['_source'], $attributes['_type']);
		static::setIndexType($model->isWhat());
		return $model;
>>>>>>> 7874bd28874ec7ab6524ea09dd05fd285e3e9a6d
	}
}
?>