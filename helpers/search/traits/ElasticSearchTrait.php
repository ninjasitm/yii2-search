<?php

namespace nitm\helpers\search\traits;

use Yii;

/**
 * trait Alerts
 * May merge into nitm Module
 * @package nitm\module
 */

trait ElasticSearchTrait
{
	public static $noSanitizeType = false; 
	
	protected static $_database;
	protected static $_table;
	protected static $_type;
	protected static $_columns;
	protected $_tableSchema;
	
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
		if(class_exists($class))
			static::$tableName = $class::tableName();
		else {
			$class = get_called_class();
			static::$tableName = $class::tableName();
		}
	}
	
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
	
	public static function setType($type, $table=null)
	{
		static::$_type = $type;
		static::$_table = is_null($table) ? $type : $table;
	}
	
	public static function indexName()
	{
		return isset(\Yii::$app->params['components.search']['index']) ? \Yii::$app->params['components.search']['index'] : static::index();
	}
	
	public static function index()
	{
		return isset(static::$_database) ? static::$_database : static::indexName();
	}
	
	public static function type()
	{
		return static::$_type;
	}
	
	public static function tableName()
	{
		return static::$_table;
	}
	
	public function columns()
	{
		if(!$this->type())
			return null;
		if(!isset(static::$_columns[$this->type()]))
		{
			$columns = $this->getDb()->get([$this->indexName(), $this->type(), '_mapping']);
			$properties = isset($columns[$this->indexName()]['mappings'][$this->tableName()]) ? $columns[$this->indexName()]['mappings'][$this->type()]['properties'] : [];
			static::$_columns[$this->type()] = !empty($properties) ? array_combine(array_keys($properties), array_map(function ($name, $col){
				$type = isset($col['type']) ? $col['type'] : 'string';
				return new\yii\db\ColumnSchema([
					'name' => $name,
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