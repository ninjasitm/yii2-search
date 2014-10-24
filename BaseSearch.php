<?php

namespace nitm\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * BaseSearch represents the model behind the search form about `nitm\search\BaseSearch`.
 */
class BaseSearch extends \nitm\models\Data implements SearchInterface
{	
	use traits\SearchTrait;
	
	public function init()
	{
		$class = $this->getModelClass(static::formName());
		$this->primaryModelClass =  $class;
	}
	
	public function behaviors()
	{
		$behaviors = [
			'behaviors' => [
				'class' => \yii\base\Behavior::className()
			]
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public static function tableName()
	{
		$class = get_called_class();
		$model = new $class;
		if($model->hasProperty('namespace') && !empty($model->namespace))
			$namespace = $model->namespace;
		else
		{
			$reflectedModel = new \ReflectionClass($class);
			$namespace = explode('\\', $reflectedModel->getNamespaceName());
			$namespace = implode('\\', ($namespace[sizeof($namespace)-1] == 'search' ? array_slice($namespace, 0, sizeof($namespace)-1) : $namespace));
		}
		$modelClass = (new $class([
			'namespace' => $namespace
		]))->getModelClass(static::className());
		if(class_exists($modelClass))
		{
			static::$tableName = (new $modelClass)->tableName();
		}
		else
		{
			static::$tableName = '';
		}
		return static::$tableName;
	}
}