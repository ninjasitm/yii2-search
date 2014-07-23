<?php

namespace nitm\models;

use Yii;
use yii\base\Model;
use yii\base\Event;
use nitm\models\Data;
use nitm\models\User;
use nitm\helpers\security\Fingerprint;
use nitm\interfaces\DataInterface;

/**
 * Class Replies
 * @package nitm\models
 *
 */

class Replies extends BaseWidget
{
	public $maxLength;
	public $constrain;
	public $constraints = [];
	public static $statuses = [
		'normal' => 'default',
		'important' => 'info',
		'critical' => 'error'
	];
	
	protected $author_idIdKey = 'author_id';
	protected static $is = 'replies';
	
	const LAST_ACTIVITY = '___lastActivity';
	const FORM_PARAM = '__withForm';
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
					\yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['reply_to_author_id'],
				],
				'value' => function ($event) {
					return $event->sender->replyToAuthorId();
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
			'author_id' => null,
			'editor_id' => null,
		];
		return array_merge(parent::has(), $has);
	}
	
	public function rules()
	{
		return [
			[['parent_id', 'parent_type', 'author_id', 'message', 'ip_addr', 'cookie_hash'], 'required', 'on' => ['create']],
			[['message','parent_type'],'required','on' => ['validateNew']],
			['message', 'isTooLong', 'message' => 'This message is too long'],
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
				'priority',
				'ip_addr',
				'ip_host',
				'cookie_hash',
				'reply_to', 
				'reply_to_author_id',
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
			//$this->constraints['parent_key'] = date($this->_dateFormat, strtotime(isset($using['key']) ? $using['key'] : $using[2]));
			//$this->queryFilters['parent_key'] = $this->constraints['parent_key'];
			//$this->parent_key = $this->constraints['parent_key'];
			break;
		}
		//constrain for admin user
		switch(\Yii::$app->user->identity->isAdmin())
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
	 * Return the reply author_id information
	 * @param string $what The property to return
	 */
	public function getReplyTo()
	{
		return $this->hasOne(Replies::className(), ['id' => 'reply_to'])->with('author');
	}

	/**
	* Get the userID for the reply_to author_id
	*/
	public function replyToAuthorId()
	{
		switch(empty($this->reply_to))
		{
			case false:
			$this->reply_to_author_id = Replies::find()->select([$this->author_idIdKey])->where([static::primaryKey()[0] => $this->reply_to])->one()->author_id;
			break;
		}
	}
	
	public function getStatus()
	{
		$ret_val = isset(self::$statuses[$this->priority]) ? self::$statuses[$this->priority] : 'default';
		return $ret_val;
	}
	
	public function isTooLong()
	{
		switch($this->maxLength)
		{
			case 0;
			case null:
			case false:
			$ret_val = false;
			break;
			
			default:
			$ret_val = strlen(strip_tags($this->message)) > $this->maxLength;
			break;
		}
		return $ret_val;
	}
}
?>