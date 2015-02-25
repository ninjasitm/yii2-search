<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseMongo extends \yii\mongodb\ActiveRecord implements SearchInterface
{
	use traits\MongoTrait, traits\SearchTrait, \nitm\traits\Data, \nitm\traits\Query, \nitm\traits\Relations;
	
	public function init()
	{
		$class = $this->getPrimaryModelClass();
		static::setIndexType($this->isWhat(), (is_array($class::collectionName()) ? array_pop($class::collectionName()) : $class::collectionName()));
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
		return static::findInternal(\nitm\search\query\ActiveMongoQuery::className(), $model, $options);
	}
	
	public static function getTableSchema()
	{
		return new \yii\db\TableSchema([
			'schemaName' => static::index(),
			'primaryKey' => '_id',
			'name' => static::type(),
			'fullName' => static::index().'.'.static::type(),
			'columns' => static::columns()
		]);
	}
	
	public static function collectionName()
	{
		try {
			return static::$collectionName;
		} catch (\Exception $e) {
			return parent::collectionName();
		}
	}
	
	public function get_Id()
	{
		return $this->id;
	}
	
	protected function getTextParam($value)
	{
		//$this->dataProvider->query->query = ['query_string' => $value];
		$this->useEmptyParams = true;
		return ['q' => $value];
	}
	
	public static function instantiate($attributes)
	{
		$model = static::instantiateInternal($attributes);
		return $model;
	}
	
	public function beforeInsert() {
		echo __FUNCTION__;
		exit;
	}
}
?>