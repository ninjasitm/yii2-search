<?php

namespace nitm\search\traits;

use Yii;
use nitm\helpers\ArrayHelper;

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

	public function setIndex($index)
	{
		static::$_database = $index;
	}

	public function setIndexType($type, $table=null)
	{
		static::$_localType = $type;
		static::$_table = is_null($table) ? $type : $table;
		if(isset($this)) {
			$this->getPrimaryModelClass(true);
		}
	}

	public function formName()
	{
		$ret_val = explode('\\', get_called_class());
		return array_pop($ret_val);
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
		if(!isset(static::$_table)) {
			$class = __CLASS__;
			$modelClass = static::getModelClass(static::className());
			if(class_exists($modelClass))
				static::$_table = $modelClass::tableName();
		}
		return static::$_table;
	}

	/**
	 * Get the mapping from the Mongo server
	 * @return array
	 */
	public function getMapping()
	{
		return iterator_to_array(static::getDb()->getCollection(static::collectionName())->mongoCollection->listIndexes());
	}

	public function columns()
	{
		if(!static::tableName())
			return [
				new \yii\db\ColumnSchema([
					'name' => '_id',
					'type' => 'int',
					'phpType' => 'integer',
					'dbType' => 'int'
				])
			];
		if(!array_key_exists(static::tableName(), static::$_columns)) {
			static::$_columns[static::tableName()]['_id'] = new \yii\db\ColumnSchema([
				'name' => '_id',
				'type' => 'int',
				'phpType' => 'integer',
				'dbType' => 'int',
				'isPrimaryKey' => true
			]);
			//$mapping = static::getMapping();
			//Hacking this out here as currently Microsoft MongoDB driver doesn't support properly creating indexes
			$modelClass = static::getPrimaryModelClass(true);
			$model = new $modelClass;
			$mapping = array_merge(array_map(function ($k, $v) {
				return [
					'key' => [
						is_int($k) ? $v : $k => 1
					]
				];
			}, array_keys($model->fields()), $model->fields()), array_map(function ($k, $v) {
				return [
					'key' => [
						is_int($k) ? $v : $k => 1
					]
				];
			}, array_keys($model->extraFields()), $model->extraFields()));
			foreach($mapping as $key) {
				static::$_columns[static::tableName()][key($key['key'])] = new \yii\db\ColumnSchema([
					'name' => key($key['key']),
					'type' => 'string',
					'phpType' => 'string',
					'dbType' => 'string',
					'isPrimaryKey' => (boolean)@$key['unique']
				]);
			}
			static::$_columns[static::tableName()]['message'] = new \yii\db\ColumnSchema([
				'name' => 'message',
				'type' => 'string',
				'phpType' => 'string',
				'dbType' => 'string'
			]);
		}
		return \yii\helpers\ArrayHelper::getValue(static::$_columns, static::tableName(), []);
	}

	public function attributes()
	{
		$columnAttrs =  array_combine(array_keys((array)$this->columns()), array_keys((array)$this->columns()));
		$fieldAttrs =  array_combine(array_keys((array)$this->fields()), array_keys((array)$this->fields()));
		$extraFieldAttrs =  array_combine(array_keys((array)$this->extraFields()), array_keys((array)$this->extraFields()));
		return array_merge($columnAttrs, $fieldAttrs, $extraFieldAttrs);
	}
}
?>
