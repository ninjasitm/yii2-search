<?php

namespace nitm\models;

use Yii;
use yii\base\Model;
use yii\base\Event;
use nitm\models\Data;
use nitm\models\User;
use nitm\models\security\Fingerprint;
use nitm\interfaces\DataInterface;

/**
 * Class Replies
 * @package nitm\models
 *
 */

class Replies extends BaseWidget
{
	public $constrain;
	public $constraints = [];
	public static $statuses = [
		'normal' => 'default',
		'important' => 'info',
		'critical' => 'error'
	];
	
	protected $authorIdKey = 'author';
	protected static $is = 'replies';
	
	private $_lastActivity = '___lastActivity';
	private $_dateFormat = "D M d Y g:iA";
	
	public function init()
	{
		parent::init();
	}
	
	public function behaviors()
	{
		$behaviors = [
			//setup special attribute behavior
			'replyToId' => [
				'class' => \yii\behaviors\AttributeBehavior::className(),
				'attributes' => [
					\yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['reply_to'],
				],
				'value' => function ($event) {
					return $event->sender->getReplyToAuthorId();
				},
			],
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public static function tableName()
	{
		return 'comments';
	}
	
	public static function has()
	{
		$has = [
			'created_at' => null, 
			'updated_at' => null,
			'updates' => null,
			'hidden' => null,
			'deleted' => null,
			'author' => null,
			'editor' => null,
		];
		return array_merge(parent::has(), $has);
	}
	
	public function rules()
	{
		return [
			[
				[
					'parent_id', 
					'parent_key', 
					'parent_type', 
					'author', 
					'message',
				], 
				'required', 
				'on' => [
					'create'
				]
			],
			[
				[
					'message',
					'parent_type'
				],
				'required',
				'on' => [
					'validateNew'
				]
			],
			[
				[
					'ip_addr',
					'cookie_hash'
				],
				'required', 
				'on' => [
					'create',
				]
			],
		];
	}
	
	public function scenarios()
	{
		$scenarios =  [
			'create' => [
				'parent_id', 
				'parent_key', 
				'parent_type',
				'message',
				'priorty',
				'ip_addr',
				'ip_host',
				'cookie_hash',
				'reply_to', 
				'reply_to_author',
				'title',
			],
			'update' => [
				'parent_id', 
				'parent_key', 
				'parent_type', 
				'message', 
				'priority',
				'public', 
				'disabled', 
				'hidden',
				'title'
			],
			'hide' => [
				'hidden'
			],
			'validateNew' => [
				'message', 
				'reply_to'
			],
			'default' => []
		];
		return array_merge(parent::scenarios(), $scenarios);
	}
	
	/*
	 * Set the constrining parameters
	 * @param mixed $using
	 */
	public function setConstraints($using)
	{
		parent::setConstraints($using);
		switch(1)
		{
			case !empty($using[2]):
			case !empty($using['key']):
			$this->constraints['parent_key'] = date($this->_dateFormat, strtotime(isset($using['key']) ? $using['key'] : $using[2]));
			$this->queryFilters['parent_key'] = $this->constraints['parent_key'];
			$this->parent_key = $this->constraints['parent_key'];
			break;
		}
		//constrain for admin user
		switch(\Yii::$app->userMeta->isAdmin())
		{
			case false:
			$this->queryFilters['hidden'] = 0;
			break;
		}
	}
	
	/*
	 * Reply to a post/user
	 * @param mixed $message A maessage containing the necessary fileds
	 * @return mixed user array
	 */
	public function reply($message=null)
	{
		$ret_val = false;
		$this->setScenario('create');
		if(is_array($message))
		{
			$this->load($message);
		}
		$this->ip_addr = \Yii::$app->request->getUserIp();
		$this->ip_host = \Yii::$app->request->getUserHost();
		$cookie = Fingerprint::getSessionCookie();
		switch($cookie instanceof Cookie)
		{
			case true:
			$this->cookie_hash = $cookie->value;
			break;
			
			default:
			$this->cookie_hash = Fingerprint::getBrowserFingerPrint();
			break;
		}
		switch($this->validate())
		{
			case true:
			$ret_val = $this->save();
			$this->created_at = \nitm\helpers\DateFormatter::formatDate();
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Return the reply author information
	 * @param string $what The property to return
	 */
	public function getReplyTo()
	{
		return $this->hasOne(Replies::className(), ['id' => 'reply_to'])->with(['authorUser']);
	}
	
	public function getStatus()
	{
		$ret_val = isset(self::$statuses[$this->priority]) ? self::$statuses[$this->priority] : 'default';
		return $ret_val;
	}
}
?>