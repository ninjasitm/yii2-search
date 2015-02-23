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
	
	public static function setIndex($index)
	{
		static::$_database = $index;
	}
	
	public static function setIndexType($type, $table=null)
	{
		static::$_localType = $type;
		static::$_table = is_null($table) ? $type : $table;
	}
	
	public function formName()
	{
		if(isset(static::$_localType))
			return \nitm\traits\Data::properClassName(static::$_localType);
		else
			return parent::formName();
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
			return static::defaultColumns();
			
		if(!array_key_exists(static::type(), static::$_columns))
		{
			$columns = ArrayHelper::getValue(static::getMapping(), static::index().'.mappings.'.static::type().'.properties', []);
			//if(is_null($columns))
				//$columns = static::defaultColumns();
			//else
				//$columns = array_merge(static::defaultColumns(), $columns);
			
			static::$_columns[static::type()] = !empty($columns) ? array_combine(array_keys($columns), array_map(function ($name, $col){
				$type = isset($col['type']) ? $col['type'] : 'string';
				return new\yii\db\ColumnSchema([
					'name' => $col,
					'type' => $type,
					'phpType' => $type,
					'dbType' => $type
				]);
			}, $columns, array_keys($columns))) : [];
		}
		return static::$_columns[static::type()];
	}
	
	protected function defaultColumns() 
	{
		$defaultColumns = ['_id' => 'int', '_type' => 'string', '_index' => 'string'];
		foreach($defaultColumns as $name=>$type) 
		{
			$defaultColumns[$name] = new \yii\db\ColumnSchema([
				'name' => $type, 
				'type' => $type, 
				'phpType' => $type, 
				'dbType' => $type
			]);
		}
		return $defaultColumns;
	}
	
	public function attributes()
	{
		return array_combine(array_keys((array)$this->columns()), array_keys((array)$this->columns()));
	}
}
?>