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
	use \nitm\traits\Nitm, \nitm\traits\Relations;
	
	public $count;
	public $hasAny;
	public $hasNew;
	public $constrain;
	public $constraints = [];
	public $queryOptions = [];
	
	public static $statuses = [
		'normal' => 'default',
		'important' => 'info',
		'critical' => 'error'
	];
	public $initSearchClass = true;
	
	protected static $userLastActive;
	
	private static $_dateFormat = "D M d Y h:iA";
	
	public function init()
	{
		$this->setConstraints($this->constrain);
		parent::init();
		$this->addWith(['author']);
		if($this->initSearchClass)
			static::initCache($this->constrain, $this->cacheKey());
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
	 * Get the count for the current parameters
	 * @return int count
	 */
	 public function getCount()
	 {
		 $ret_val = 0;
		 $this->setScenario('count');
		 switch($this->validate())
		 {
			 case true:
			 switch(isset($this->queryFilters['value']))
			 {
				 case true:
				 $andWhere = ['and'];
				 switch($this->queryFilters['value'])
				 {
					 case -1:
					 $andWhere[] = '`value`<=0';
					 break; 
					 
					 case 1:
					 $andWhere[] = '`value`>=1';
					 break;
				 }
				 unset($this->queryFilters['value']);
				 break;
				 
				 default:
				 $andWhere = [];
				 break;
			 }
			 $ret_val = $this->find()->where($andWhere)->andWhere($this->queryFilters)->count();
			 break;
			 
			 default:
			 throw new Exception("Error validating for count.\n".var_export($this->getErrors(), true));
			 break;
		 }
		 return $ret_val;
	 }
	
	/**
	 * Get the count for the current parameters
	 * @return int count
	 */
	 public function getValue()
	 {
		 $ret_val = 0;
		 $this->setScenario('count');
		 switch($this->validate())
		 {
			 case true:
			 $valueFilter = @$this->queryFilters['value'];
			 unset($this->queryFilters['value']);
			 switch(1)
			 {
				 case $valueFilter == -1:
				 $andWhere = ['and', 'value<=0'];
				 break; 
				 
				 default:
				 $andWhere = ['and', 'value>=1'];
				 break;
			 }
			 $ret_val = $this->find($this)->select("SUM(value) AS value")->andWhere($andWhere)->asArray()->all();
			 $ret_val = $ret_val[0]['value'];
			 switch(is_null($valueFilter))
			 {
				case true:
			 	$this->queryFilters['value'] = $valueFilter;
				break;
			 }
			 break;
		 }
		 return $ret_val;
	}
	
	/**
	 * Find a model
	 */
	 public function findModel($constrain=null)
	 {
		$this->initCache($constrain, $this->cacheKey());
		$model = clone Cache::getModel($this->cacheKey());
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
			$ret_val->queryFilters = $ret_val->queryFilters;
			$ret_val->constraints = $model->constraints;
			$ret_val->count = $this->getCount();
			$ret_val->hasNew = $this->hasNew();
			$ret_val->hasAny = $this->hasAny();
			break;
			
			default:
			$ret_val = $model;
			break;
		}
		return $ret_val;
	 }
	
	/*
	 * Check for new data by last activity of logged in user
	 * @return mixed user array
	 */
	public function hasNew()
	{
		$model = clone Cache::getModel($this->cacheKey());
		$andWhere = ['and', 'UNIX_TIMESTAMP(created_at)>='.\Yii::$app->user->identity->lastActive()];
		$ret_val = $model->find()->orderBy(['id' => SORT_DESC])
			->andWhere($andWhere)
			->count();
		\Yii::$app->user->identity->updateActivity();
		return $ret_val >= 1;
	}
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function isNew()
	{
		static::$userLastActive = is_null(static::$userLastActive) ? \Yii::$app->user->identity->lastActive() : static::$userLastActive;
		return strtotime($this->created_at) > static::$userLastActive;
	}
	
	/*
	 * Get the author for this object
	 * @return boolean
	 */
	public function hasAny()
	{
		return (!isset($this->count) ? $this->find()->count() : $this->count) >= 1;
	}
	
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function getLast()
	{
		$ret_val = $this->hasOne(static::className(), [
				'id' => 'id',
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
	
	protected static function initCache($constrain, $key)
	{
		if(!Cache::exists($key))
		{
			$class = static::className();
			$model = new $class(['initSearchClass' => false]);
			$model->setConstraints($constrain);
			Cache::setModel($key, $model);
		}
	}
	
	public function cacheKey()
	{
		return 'base-widget-model.'.static::isWhat().'.'.$this->getId();
	}
}
?>