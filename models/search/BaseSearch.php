<?php

namespace nitm\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * BaseSearch represents the model behind the search form about `nitm\models\BaseSearch`.
 */
class BaseSearch extends \nitm\models\Data
{
	use \nitm\traits\Search;
	
	public function init()
	{
		$class = $this->getModelClass(static::formName());
		$this->primaryModelClass =  $class;
	}
	
	public static function tableName()
	{
		$class = get_called_class();
		$model = new \ReflectionClass($class);
		$namespace = explode('\\', $model->getNamespaceName());
		$namespace = implode('\\', ($namespace[sizeof($namespace)-1] == 'search' ? array_slice($namespace, 0, sizeof($namespace)-1) : $namespace));
		$modelClass = (new $class([
			'namespace' => $namespace
		]))->getModelClass(static::className());
		static::$tableName = (new $modelClass)->tableName();
		return static::$tableName;
	}
}