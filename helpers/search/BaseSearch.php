<?php
namespace nitm\helpers\search;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseSearch extends \yii\elasticsearch\ActiveRecord
{
	public $mock;
	public $verbose = 0;
	public $offset = 0;
	public $limit = 500;
	public $model;
	public $score;
	public $info = [];
	public $string = "";
	public $minLength = 3;
	public $threshold = [
		"name" => 'relevance', 
		'nameMax' => 'best_match', 
		"min" => 0.6, 
		'max' => 0
	];
	
	protected static $_database;
	protected static $_table;
	
	public static function setIndex($index)
	{
		static::$_database = $index;
	}
	
	public static function setType($type)
	{
		static::$_table = $type;
	}
	
	public static function indexName()
	{
		return isset(\Yii::$app->params['components.search']['index']) ? \Yii::$app->params['components.search']['index'] : static::index();
	}
	
	public static function index()
	{
		return isset(static::$_database) ? static::$_database : parent::index();
	}
	
	public static function type()
	{
		return isset(static::$_table) ? static::$_table : parent::type();
	}
	
	public function search()
	{
		throw new \yii\base\Exception("Search needs to be configured by classes extending from this one");
	}
}
?>