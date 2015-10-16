<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseMongo extends \yii\mongodb\ActiveRecord implements SearchInterface
{
	use traits\MongoTrait, traits\SearchTrait, \nitm\traits\Data, \nitm\traits\Query, \nitm\traits\Relations, \nitm\traits\Nitm;
	
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
			'primaryKey' => 'id',
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
	
	public function getDataProvider($params, $options=[])
	{
		/**
		 * Setup data parts
		 */
		$dataProvider = $this->search($params);
		$query = $dataProvider->query;
		
		//Parse the query and extract the parts
		$parts = $this->parseQuery($this->text);
		
		$command = $query->createCommand();	
		$query->offset((int) \Yii::$app->request->get('page')*$options['limit']);
		//$query->highlight(true);
		$query->query(isset($parts['query']) ? $parts['query'] : ArrayHelper($command, 'queryParts.query', []));
		$query->orderBy($options['sort']);
		$parts['filter'] = ArrayHelper::getValue($parts, 'filter', []);
		$query->where(array_merge((array)$query->where, (array)$parts['filter'], ArrayHelper::getValue($options, 'where', [])));
		
		if($this->forceType === true)
			$query->type = $options['types'];
		else
			$query->type = (isset($parts['types']) ? $parts['types'] : $options['types']);

		$models = $results = [];
		
		//Setup data provider. Manually set the totalcount and models to enable proper pagination
        $dataProvider = new \yii\data\ArrayDataProvider;
		
		if(count($command->queryParts) || $this->text)
		{
			try {
				$results = $query->search();
				$success = true;
			} catch (\Exception $e) {
				$success = false;
				if(defined('YII_DEBUG') && YII_DEBUG === true) {
					throw $e;
				}
			}
			if($success)
			{
				/**
				 * The models are instantiated in Search::instantiate function
				}*/
				$dataProvider->setTotalCount($results['hits']['total']);
				//Must happen after setting the total count
				$dataProvider->setModels(ArrayHelper::remove($results['hits'], 'hits'));
				$dataProvider->pagination->totalCount = $dataProvider->getTotalCount();
			}
		}
		return [$results, $dataProvider];
	}
	
	public function getSort()
	{
		return [];
	}
}
?>