<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseElasticSearch extends \yii\elasticsearch\ActiveRecord implements SearchInterface
{
	use traits\ElasticSearchTrait, traits\SearchTrait, \nitm\traits\Data, \nitm\traits\Query, \nitm\traits\Relations;
	public $engine = 'elasticsearch';
	
	public function init()
	{
		$class = $this->getPrimaryModelClass();
		static::setIndexType($class::isWhat(), $class::tableName());
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
		return static::findInternal(\nitm\search\query\ActiveElasticQuery::className(), $model, $options);
	}
	
	public static function getTableSchema()
	{
		return new \yii\db\TableSchema([
			'schemaName' => static::index(),
			'columns' => (new static)->columns(),
			'primaryKey' => '_id',
			'name' => static::type(),
			'fullName' => static::index().'.'.static::type()
		]);
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
		if(!isset($attributes['_source']))
			$attributes['_source'] = [];
			
		$model = static::instantiateInternal($attributes['_source'], $attributes['_type']);
		static::setIndexType($model->isWhat());
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
		$query->query(isset($parts['query']) ? $parts['query'] : ArrayHelper::getValue($command, 'queryParts.query', []));
		$query->orderBy(ArrayHelper::getValue($parts, 'sort', ArrayHelper::getValue($options, 'sort', [
			'_score' => 'desc',
			'_id' => 'desc',
		])));
		
		$parts['filter'] = ArrayHelper::getValue($parts, 'filter', []);
		$where = ArrayHelper::getValue($options, 'where', false);
		
		if(count($parts['filter']))
			$query->filter($parts['filter']);
			
		if(is_array($where))
			$query->where($where);
		
		if($this->forceType === true)
			$query->type = $options['types'];
		else
			$query->type = (isset($parts['types']) ? $parts['types'] : $options['types']);

		$results = [];
		
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
				 */
				$dataProvider->setTotalCount($results['hits']['total']);
				//Must happen after setting the total count
				$dataProvider->setModels(ArrayHelper::remove($results['hits'], 'hits'));
				$dataProvider->pagination->totalCount = $dataProvider->getTotalCount();
			}
		}
		return [$results, $dataProvider];
	}
	
	/**
	 * Custom record population for related records
	 */
	public static function populateRecord($record, $row)
	{
		if(!isset($row['_source']))
			return;
			
		parent::populateRecord($record, $row);
		
		static::populateRelations($record, $row['_source']);
	}
}
?>