<?php
namespace nitm\traits;

use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Traits defined for expanding active relation scopes until yii2 resolves traits issue
 */

trait BaseWidget {
	
	public $_value;
	public $constrain;
	public $constraints = [];
	public $initSearchClass = true;
	
	public static $usePercentages;
	public static $allowMultiple;
	public static $individualCounts;
	public static $statuses = [
		'normal' => 'default',
		'important' => 'info',
		'critical' => 'error'
	];
	
	protected $link = [
		'parent_type' => 'parent_type',
		'parent_id' => 'parent_id'
	];
	protected $_new; 
	protected static $userLastActive;
	protected static $currentUser;
	protected $_supportedConstraints =  [
		'parent_id' => [0, 'id', 'parent_id'],
		'parent_type' => [1, 'type', 'parent_type'],
	];
	
	private static $_dateFormat = "D M d Y h:iA";
	
	public function init()
	{
		$this->setConstraints($this->constrain);
		parent::init();
		$this->addWith(['author']);
		if($this->initSearchClass)
			//static::initCache($this->constrain, self::cacheKey($this->getId()));
		static::$currentUser =  isset(\Yii::$app->user) ? \Yii::$app->user->identity : new \nitm\models\User(['id' => 1]);
		static::$userLastActive = is_null(static::$userLastActive) ? static::$currentUser->lastActive() : static::$userLastActive;
		$this->initEvents();
	}
	
	protected function initEvents()
	{
		Event::on(ActiveRecord::className(), ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'beforeSaveEvent']);
		Event::on(ActiveRecord::className(), ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'beforeSaveEvent']);
		Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_INSERT, [$this, 'afterSaveEvent']);
		Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_UPDATE, [$this, 'afterSaveEvent']);
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
			foreach($this->_supportedConstraints as $attribute=>$supported)
			{
				if($this->hasProperty($attribute))
				{
					$this->constraints[$attribute] = $this->$attribute;
				}
			}
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
		foreach($this->_supportedConstraints as $attribute=>$supported)
		{
			foreach($supported as $attr)
			{
				switch(isset($using[$attr]))
				{
					case true:
					switch($attribute)
					{
						case 'parent_type':
						$using[$attr] = strtolower(array_pop(explode('\\', $using[$attr])));
						break;
					}
					$this->constraints[$attribute] = $using[$attr];
					$this->$attribute = $using[$attr];
					break;
				}
			}
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
		$ret_val = parent::getCount($this->link);
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
		$ret_val = $this->hasOne(static::className(), $this->link);
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
		return $this->newCount instanceof static ? $this->newCount->_new : 0;
	}
	
	protected function getNewCount()
	{
		$primaryKey = $this->primaryKey()[0];
		$ret_val = $this->hasOne(static::className(), $this->link);
		$andWhere = ['or', 'UNIX_TIMESTAMP(created_at)>='.static::$currentUser->lastActive()];
		$ret_val->select([
				'_new' => 'COUNT('.$primaryKey.')'
			])
			->andWhere($andWhere)
			->andWhere($this->getConstraints());
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
		$ret_val = $this->hasOne(static::className(), $this->link)
			->orderBy([array_shift($this->primaryKey()) => SORT_DESC])
			->with('author');
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
		if(!\nitm\helpers\Cache::exists($key))
		{
			$class = static::className();
			$model = new $class(['initSearchClass' => false]);
			$model->setConstraints($constrain);
			\nitm\helpers\Cache::setModel($key, [$model->className(), \yii\helpers\ArrayHelper::toArray($model)]);
		}
		else {
			$array = \nitm\helpers\Cache::getModel($key);
			$model = new $array[0]($array[1]);
		}
		return $model;
	}
}
?>
