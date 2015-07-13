<?php

namespace nitm\search\traits;

use Yii;

/**
 * trait Alerts
 * May merge into nitm Module
 * @package nitm\module
 */

trait MongoTrait
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
			return ucfirst(static::$_localType);
		else
			return parent::formName();
	}
	
	public static function dbName()
	{
		return isset(static::$_database) ? static::$_database : \nitm\models\DB::getDbName();
	}
	
	public static function index()
	{
		return isset(\Yii::$app->getModule('nitm-search')->index) ? \Yii::$app->getModule('nitm-search')->index : static::dbName();
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
		return (array)static::getCollection()->mongoCollection->getIndexInfo();
	}
	
	public function columns()
	{
		if(!static::type())
			return [
				new\yii\db\ColumnSchema([
					'name' => 'id',
					'type' => 'int',
					'phpType' => 'integer',
					'dbType' => 'int'
				])
			];
		if(!array_key_exists(static::type(), static::$_columns))
		{
			foreach(static::getMapping() as $key) {
				static::$_columns[static::type()][key($key['key'])] = new\yii\db\ColumnSchema([
					'name' => key($key['key']),
					'type' => 'string',
					'phpType' => 'string',
					'dbType' => 'string'
				]);
			}
			static::$_columns[static::type()]['message'] = new\yii\db\ColumnSchema([
				'name' => 'message',
				'type' => 'string',
				'phpType' => 'string',
				'dbType' => 'string'
			]);
		}
		return \yii\helpers\ArrayHelper::getValue(static::$_columns, static::type(), []);
	}
	
	public function attributes()
	{
		return array_combine(array_keys((array)$this->columns()), array_keys((array)$this->columns()));
	}
}
?>