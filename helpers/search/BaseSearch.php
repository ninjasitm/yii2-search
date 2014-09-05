<?php
namespace nitm\helpers\search;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseSearch extends \yii\elasticsearch\ActiveRecord
{
	public $verbose;
	public $limit = 500;
	public $model;
	public $stats = [];
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
	
	private $_database;
	private $_table;
	
    /**
	 * Get the duration of the seach query
     */
    public function duration()
    {
		return $this->stats['end'] - $this->stats['start'];
    }
	
	public function start()
	{
		$this->stats['start'] = microtime(1);
	}
	
	public function finish()
	{
		$this->stats['end'] = microtime(true);
	}
	
	public function setIndex($index)
	{
		$this->_database = $index;
	}
	
	public function setType($type)
	{
		$this->_table = $type;
	}
	
	public static function index()
	{
		return isset($this->_database) ? $this->_database : parent::index();
	}
	
	public static function type()
	{
		return isset($this->_table) ? $this->_table : parent::type();
	}
	
    /**
	 * Function to initialize solf configuration
     * @param mixed $config Array for solr configuration      
     */
    public function init()
    {
		$this->start();
    }
	
	public function search()
	{
		throw new \yii\base\Exception("Search needs to be configured by classes extending from this one");
	}
}
?>