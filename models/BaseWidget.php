<?php

namespace nitm\module\models;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\base\Event;
use nitm\module\models\Data;
use nitm\module\models\User;
use nitm\module\models\security\Fingerprint;
use nitm\module\interfaces\DataInterface;

/**
 * Class Replies
 * @package nitm\models
 *
 */

class BaseWidget extends Data implements DataInterface
{
	public $count;
	public $hasAny;
	public $hasNew;
	public $constrain;
	public $constraints = [];
	
	protected $authorIdKey = 'author';
	protected $editorIdKey = 'editor';
	
	private $_dateFormat = "D M d Y h:iA";
	
	public function __construct($constrain=null)
	{
		$this->constrain($constrain);
		$this->init();
	}
	
	public function init()
	{
		parent::init();
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
	public function constrain($using)
	{
		$this->setConstraints($using);
		$this->queryFilters = array_merge($this->queryFilters, $this->constraints);
	}
	
	/*
	 * Set the constrining parameters
	 * @param mixed $using
	 */
	public function setConstraints($using)
	{
		if(!empty($using[0]))
		{
			$this->constraints['parent_id'] = $using[0];
			$this->parent_id = $this->constraints['parent_id'];
		}
		if(!empty($using[1]))
		{
			$this->constraints['parent_type'] = strtolower(array_pop(explode('\\', $using[1])));
			$this->parent_type = $this->constraints['parent_type'];
		}
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
			 throw new Exception("Error validating for count.".var_export($this->getErrors(), true));
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
			 $ret_val = $this->find()->select("SUM(value) AS value")->where($this->queryFilters)->andWhere($andWhere)->asArray()->all();
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
	 * Get the count for the current parameters
	 * @return int count
	 */
	 public static function findModel($constrain)
	 {
		$class = static::className();
		$model = new $class;
		$model->constrain($constrain);
		$ret_val = $model->find()
		 	->with([
				'last' => function ($query) use ($model) {
					$query->andWhere($model->queryFilters);
				}
			])
			->where($model->queryFilters)
			->one();
		switch(is_a($ret_val, static::className()))
		{
			case true:
			$ret_val->count = $model->getCount();
			$ret_val->hasNew = $model->hasNew();
			$ret_val->hasAny = $model->hasAny();
			break;
			
			default:
			$ret_val = $model;
			break;
		}
		return $ret_val;
	 }
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function hasNew()
	{
		$queryFilters = is_array($this->queryFilters) ? $this->queryFilters : null;
		$andWhere = ['and', 'TIMESTAMP(FROM_UNIXTIME(created_at))>='.\Yii::$app->userMeta->lastActive()];
		$ret_val = $this->find()
			->where($queryFilters)
			->orderBy([array_shift($this->primaryKey()) => SORT_DESC])
			->andWhere($andWhere)
			->count();
		return $ret_val;
	}
	
	/*
	 * Get the author for this object
	 * @return boolean
	 */
	public function hasAny()
	{
		return $this->find()->count() >= 1;
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
			->with('authorUser');
		return $ret_val;
	}
	
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function getAuthorUser()
	{
		 return $this->hasOne(User::className(), ['id' => $this->authorIdKey]);
	}
	
	
	/*
	 * Get the author for this object
	 * @return mixed user array
	 */
	public function getEditorUser()
	{
		return $this->hasOne(User::className(), ['id' => $this->editorIdKey]);
	}
}
?>