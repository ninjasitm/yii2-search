<?php

namespace nitm\models;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\base\Event;
use nitm\models\Data;
use nitm\models\User;
use nitm\models\security\Fingerprint;
use nitm\interfaces\DataInterface;
use nitm\helpers\Cache;

/**
 * Class BaseWidget
 * @package nitm\models
 *
 */

class BaseWidget extends Data implements DataInterface
{
	use \nitm\traits\Nitm;
	
	public $_value;
	public $_count;
	public $constrain;
	public $constraints = [];
	public $queryOptions = [];
	public $initSearchClass = true;
	
	public static $usePercentages;
	public static $allowMultiple;
	public static $individualCounts;
	public static $statuses = [
		'normal' => 'default',
		'important' => 'info',
		'critical' => 'error'
	];
	
	protected $_new; 
	protected static $userLastActive;
	protected static $currentUser;
	
	private static $_dateFormat = "D M d Y h:iA";
	
	public function init()
	{
		$this->setConstraints($this->constrain);
		parent::init();
		$this->addWith(['author']);
		if($this->initSearchClass)
			static::initCache($this->constrain, self::cacheKey($this->getId()));
		static::$currentUser =  \Yii::$app->user->identity;
		static::$userLastActive = is_null(static::$userLastActive) ? static::$currentUser->lastActive() : static::$userLastActive;
	}
	
	public function scenarios()
	{
		$scenarios = [
			'count' => ['parent_id', 'parent_type'],
		];
		return array_merge(parent::scenarios(), $scenarios);
	}
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public static function has()
	{
		$has = [
			'author' => null, 
			'editor' => null,
			'hidden' => null,
			'deleted' => null,
		];
		return array_merge(parent::has(), $has);
	}
	
	/**
	 * Get the constraints for a widget model
	 */
	public function getConstraints()
	{
		switch(empty($this->constraints))
		{
			case true:
			$this->constraints['parent_id'] = $this->parent_id;
			$this->constraints['parent_type'] = $this->parent_type;
			$this->queryFilters = array_merge($this->queryFilters, $this->constraints);
			break;
		}
		return $this->constraints;
	}
	
	/*
	 * Set the constrining parameters
	 * @param mixed $using
	 */
	public function setConstraints($using)
	{
		$this->queryFilters = [];
		switch(1)
		{
			case isset($using[0]):
			case isset($using['id']):
			case isset($using['parent_id']):
			switch(1)
			{
				case isset($using[0]):
				$id = $using[0];
				break;
				
				case isset($using['id']):
				$id = $using['id'];
				break;
				
				case isset($using['parent_id']):
				$id = $using['parent_id'];
				break;
			}
			$this->constraints['parent_id'] = $id;
			$this->parent_id = $this->constraints['parent_id'];
			case isset($using[1]):
			case isset($using['type']):
			case isset($using['parent_type']):
			switch(1)
			{
				case isset($using[1]):
				$type = $using[1];
				break;
				
				case isset($using['type']):
				$type = $using['type'];
				break;
				
				case isset($using['parent_type']):
				$type = $using['parent_type'];
				break;
			}
			$this->constraints['parent_type'] = strtolower(array_pop(explode('\\', $type)));
			$this->parent_type = $this->constraints['parent_type'];
			break;
		}
		$this->queryFilters = array_replace($this->queryFilters, $this->constraints);
	}
	
	/**
	 * Find a model
	 */
	 public static function findModel($constrain)
	 {
		$model = self::initCache($constrain, self::cacheKey($constrain[0]));
		$model->setConstraints($constrain);
		$model->addWith([
			'last' => function ($query) {
				$query->andWhere($model->queryFilters);
			}
		]);
		$ret_val = $model->find()->one();
		switch(is_a($ret_val, static::className()))
		{
			case true:
			$ret_val->queryFilters = $model->queryFilters;
			$ret_val->constraints = $model->constraints;
			//$ret_val->populateMetadata();
			break;
			
			default:
			$ret_val = $model;
			break;
		}
		return $ret_val;
	 }
	
	/**
	 * Get the count for the current parameters
	 * @return \yii\db\ActiveQuery
	 */
	 public function getCount()
	 {
		$primaryKey = $this->primaryKey()[0];
		$ret_val = parent::getCount([
			'parent_type' => 'parent_type',
			'parent_id' => 'parent_id'
		]);
		switch(isset($this->queryFilters['value']))
		{
			case true:
			switch($this->queryFilters['value'])
			{
				case -1:
				$andWhere = ['<=', 'value',  0];
				break; 
				
				case 1:
				$andWhere = ['>=', 'value', 1];
				break;
			}
			unset($this->queryFilters['value']);
			$ret_val->andWhere($andWhere);
			break;
		}
		$filters = $this->queryFilters;
		unset($filters['parent_id'], $filters['parent_type']);
		//$ret_val->andWhere($filters);
		return $ret_val;
	 }

    /**
	 * This is here to allow base classes to modify the query before finding the count
     * @return \yii\db\ActiveQuery
     */
    public function getFetchedValue()
    {
		$primaryKey = $this->primaryKey()[0];
		$ret_val = $this->hasOne(static::className(), [
			'parent_type' => 'parent_type',
			'parent_id' => 'parent_id'
		]);
		$valueFilter = @$this->queryFilters['value'];
		unset($this->queryFilters['value']);
		switch(static::$allowMultiple)
		{
			case true:
			$select = [
				"_down" => "SUM(IF(value<=0, value, 0))",
				"_up" => "SUM(IF(value>=1, value, 0))"
			];
			break;
			
			default:
			$select = [
				'_down' => "SUM(value=-1)",
				"_up" => "SUM(value=1)"
			];
			break;
		}
		$filters = $this->queryFilters;
		unset($filters['parent_id'], $filters['parent_type']);
		return $ret_val->select($select)
			->andWhere($filters);
    }
	
	public function fetchedValue()
	{
		return $this->hasProperty('fetchedValue') && isset($this->fetchedValue) ? $this->fetchedValue->_value : 0;
	}
	
	/*
	 * Check for new data by last activity of logged in user
	 * @return mixed user array
	 */
	public function hasNew()
	{
		return $this->hasAttribute('_new') ? $this->newCount->_new : 0;
	}
	
	protected function getNewCount()
	{
		$primaryKey = $this->primaryKey()[0];
		$ret_val = $this->hasOne(static::className(), [
			'parent_type' => 'parent_type',
			'parent_id' => 'parent_id'
		]);
		$andWhere = ['or', 'UNIX_TIMESTAMP(created_at)>='.static::$currentUser->lastActive()];
		$ret_val->select([
				'_new' => 'COUNT('.$primaryKey.')'
			])
			->andWhere($andWhere);
		static::$currentUser->updateActivity();
		return $ret_val;
	}
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function isNew()
	{
		static::$userLastActive = is_null(static::$userLastActive) ? static::$currentUser->lastActive() : static::$userLastActive;
		return strtotime($this->created_at) > static::$userLastActive;
	}
	
	/*
	 * Get the author for this object
	 * @return boolean
	 */
	public function hasAny()
	{
		return $this->count() >= 1;
	}
	
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function getLast()
	{
		$ret_val = $this->hasOne(static::className(), [
				'parent_id' => 'parent_id',
				'parent_type' => 'parent_type'
			])
			->orderBy([array_shift($this->primaryKey()) => SORT_DESC])
			->with('author');
		return $ret_val;
	}
	
	public function getStatus()
	{
		$ret_val = '';
		return $ret_val;
	}
	
	public static function cacheKey($id=null)
	{
		$key = isset($this) && method_exists($this, 'getId') ? static::isWhat() : static::className();
		return 'base-widget-model.'.$key.'.'.$id;
	}
	 
	protected function populateMetadata()
	{
		switch(!isset($this->count) && !isset($this->hasNew))
		{
			case true:
			$sql = static::find()->select([
				"_count" => 'COUNT(id)',
				"_hasNew" => 'SUM(IF(UNIX_TIMESTAMP(created_at)>='.static::$currentUser->lastActive().", 1, 0))"
			])
			->where($this->getConstraints());
			$metadata = $sql->createCommand()->queryAll();
			static::$currentUser->updateActivity();
			break;
		}
	}
	
	protected static function initCache($constrain, $key)
	{
		if(!Cache::exists($key))
		{
			$class = static::className();
			$model = new $class(['initSearchClass' => false]);
			$model->setConstraints($constrain);
			Cache::setModel($key, $model);
		}
		else {
			$model = Cache::getModel($key);
		}
		return $model;
	}
}
?>