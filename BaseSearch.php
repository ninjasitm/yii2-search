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
	
	public $engine = 'db';
	
	public function init()
	{
		//$this->setPrimaryModelClass(static::formName());
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
		if(!isset(static::$tableNames[$class])) {
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
			if(class_exists($modelClass)) {
				self::$tableNames[$class] = (new $modelClass)->tableName();
			}
			else {
				self::$tableNames[$class] = '';
			}
		}
		return self::$tableNames[$class];
	}
	
	public function type()
	{
		if(isset($this))
			return $this->isWhat(null, true);
		else
			return static::isWhat(null, true);
	}
	
	public function setIndexType()
	{
		return true;
	}
	
	public function columns() {
		if(static::tableName())
			return static::getTableSchema()->columns;
		else 
			return [];
	}
	
	/**
	 * Overriding default find function
	 */
	public static function find($model=null, $options=null)
	{
		return static::findInternal(\yii\db\ActiveQuery::className(), $model, $options);
	}
	
	
	public function getDataProvider($params, $options=[])
	{
		/**
		 * Setup data parts
		 */
		$dataProvider = $this->search($params);
		$query = $dataProvider->query;
		
		//Parse the query and extract the parts
		$parts = $this->parseQuery($this->text);
		
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
		
		return [[
			'total' => $dataProvider->getTotalCount()
		], $dataProvider];
	}
	
	/**
	 * Custom record population for related records
	 */
	public static function populateRecord($record, $row)
	{
		if(isset($row['_source']))
			$row = $row['_source'];
		parent::populateRecord($record, $row);
		static::populateRelations($record, $row);
	}
	
	public function attributes()
	{
		if(static::tableName())
			return parent::attributes();
		else
			return [];
	}
	
	
	/**
	 * Parse the query and extract speciial named parameters
	 * @param array $options
	 * @return array
	 */
	public function parseQuery($string=null)
	{
		if(is_null($string))
			return [];
		$this->text = $string;
		$ret_val = $this->filter(explode(' ', $string), ['_type']);
		return $ret_val;
	}
	
	/**
	 * Fitler an array based on colon (:) separated parameters
	 * @param array $array
	 * @param array $filters
	 * @return array
	 */
	protected function filter($array, $filters=null)
	{
		$ret_val = [];
		$string = $array;
		while(list($idx, $part) = each($array))
		{
			$parts = explode(':', $part);
			switch(1)
			{
				case (sizeof($parts = explode(':', $part)) == 2) || ($filters === true):
				$ret_val['filter'][$parts[0]] = explode(',', $parts[1]);
				unset($string[$idx]);
				break;
				
				//If this is an equal query: name=value then split it accordingly
				case((sizeof($parts = explode('=', $part)) == 2)):
				$ret_val['filter'][$parts[0]] = $parts[1];
				unset($string[$idx]);
				break;
				
				default:
				$next = isset($array[$idx+1]) ? $array[$idx+1] : '';
				$prev = (isset($array[$idx-1]) && strpos($array[$idx-1], ':') !== false) ? $array[$idx-1] : false;
				$prevKey = $prev !== false ? array_shift(explode(':', $prev)) : false;
				/** 
				 * If this is a string and the next part in the $array is a filter 
				 * then combine this with the previous string value
				 */
				if(($prevKey !== false) && (((strpos($next, ':') !== false) && sizeof($parts) == 1) || !$next) && ($prevKey && is_array($filters) && !in_array($prevKey, $filters)))
				{
					$isNested = strpos($prev, ':') !== false;
					$ret_val['filter'][$prevKey][0] .= ' '.$parts[0];
					unset($string[$idx]);
				}
				break;
			}
		}
		
		$ret_val['parts'] = $string;
		return $ret_val;
		
	}
}