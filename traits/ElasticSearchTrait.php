<?php

namespace nitm\search\traits;

use Yii;

/**
 * trait Alerts
 * May merge into nitm Module
 * @package nitm\module
 */

trait ElasticSearchTrait
{	
	protected static $_type;
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
		static::$_type = $type;
		static::$_table = is_null($table) ? $type : $table;
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
		return static::$_type;
	}
	
	public static function tableName()
	{
		return static::$_table;
	}
	
	public function getMapping()
	{
		return $this->getDb()->get([$this->index(), $this->type(), '_mapping']);
	}
	
	public function columns()
	{
		if(!$this->type())
			return ['_id', '_type', '_index'];
		if(!array_key_exists($this->type(), static::$_columns))
		{
			$columns = $this->getMapping();
			$properties = isset($columns[$this->index()]['mappings'][$this->type()]) ? $columns[$this->index()]['mappings'][$this->type()]['properties'] : [];
			static::$_columns[$this->type()] = !empty($properties) ? array_combine(array_keys($properties), array_map(function ($name, $col){
				$type = isset($col['type']) ? $col['type'] : 'string';
				return new\yii\db\ColumnSchema([
					'name' => $col,
					'type' => $type,
					'phpType' => $type,
					'dbType' => $type
				]);
			}, $properties, array_keys($properties))) : [];
		}
		return static::$_columns[$this->type()];
	}
	
	public function attributes()
	{
		return array_combine(array_keys((array)$this->columns()), array_keys((array)$this->columns()));
	}
}
?>