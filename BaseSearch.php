<?php

namespace nitm\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * BaseSearch represents the model behind the search form about `nitm\search\BaseSearch`.
 */
class BaseSearch extends \nitm\models\Data implements SearchInterface
{	
	use traits\SearchTrait;
	
	public function init()
	{
		$this->setPrimaryModelClass(static::formName());
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
		if($model->hasProperty('namespace') && !empty($class::$namespace))
			$namespace = $class::$namespace;
		else
		{
			$reflectedModel = new \ReflectionClass($class);
			$namespace = explode('\\', $reflectedModel->getNamespaceName());
			$namespace = implode('\\', ($namespace[sizeof($namespace)-1] == 'search' ? array_slice($namespace, 0, sizeof($namespace)-1) : $namespace));
		}
		$modelClass = (new $class())->getModelClass(static::className());
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
	
	public static function type()
	{
		return static::isWhat();
	}
	
	public static function setIndexType()
	{
		return true;
	}
	
	public function columns() {
		return static::getTableSchema()->columns;
	}
	
	/**
	 * Overriding default find function
	 */
	public static function find($model=null, $options=null)
	{
		return static::findInternal(\yii\db\ActiveQuery::className(), $model, $options);
	}
	
	public function getDataProvider($query, $parts, $options)
	{
		$query->offset((int) \Yii::$app->request->get('page')*$options['limit']);
		$query->orderBy(ArrayHelper::getValue($options, 'sort', [
			'id' => SORT_DESC
		]));
		$parts['filter'] = ArrayHelper::getValue($parts, 'filter', []);
		$query->where(array_merge((array)$query->where, (array)$parts['filter'], ArrayHelper::getValue($options, 'where', [])));
		
		//Setup data provider. Manually set the totalcount and models to enable proper pagination
        $dataProvider = new \yii\data\ActiveDataProvider([
			'query' => $query,
		]);
		
		return [[], $dataProvider];
	}
}