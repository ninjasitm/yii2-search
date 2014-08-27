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
		$this->_supportedConstraints['key'] = [3, 'key'];
		parent::init();
		//constrain for admin user
		switch(\Yii::$app->user->identity->isAdmin())
		{
			case false:
			$this->queryFilters['hidden'] = 0;
			break;
		}
	}
	
	public function behaviors()
	{
		$behaviors = [
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
			$ret_val = ($this->remote_type == 'chat') ? strlen(strip_tags($this->message)) > $this->maxLength : false;
			break;
		}
		return $ret_val;
	}
	
	public function afterSaveEvent($event)
	{
		if($event->sender->className() != static::className())
			return;
		$message = [];
		$this->_alerts->addVariables([
			'%id%' => $event->sender->getId(),
			"%viewLink%" => \yii\helpers\Html::a(\Yii::$app->urlManager->createAbsoluteUrl($event->sender->parent_type."/view/".$event->sender->parent_id), \Yii::$app->urlManager->createAbsoluteUrl($event->sender->parent_type."/view/".$event->sender->parent_id))
		]);
		$type = $event->sender->isWhat();;
		switch($event->sender->getScenario())
		{
			case 'create':
			switch($event->sender->parent_type)
			{
				case 'chat':
				$type = 'chat';
				switch(empty($event->sender->reply_to))
				{
					case false:
					$text = " %who% to @".$event->sender->getReplyTo()->one()->author()->username.": ".$event->sender->message;
					break;
					
					default:
					$text = $event->sender->message;
					break;
				}
				$message = [
					'subject' => "%who% posted a %priority% chat message",
					'message' => [
						'email' => $text,
						'mobile' => "(%priority%)".$text,
					]
				];
				break;
				
				default:
				$message = [
					'subject' => "%who% replied to %subjectDt% %priority% %remoteFor%, with id: %id%, on %when%",
					'message' => [
						'email' => "%who% replied to %subjectDt% %priority% %remoteFor%, with id: %id%, was %action% to by %who%. %who% said:\n\t".$event->sender->message,
						'mobile' => "%who% %action% to %subjectDt% %remoteFor% with id %id%: ".$event->sender->message,
					]
				];
				break;
			}
			break;
		}
		if(!empty($message) && $event->sender->getId())
		{
			$this->_alerts->criteria([
				'remote_type',
				'remote_for',
				'remote_id',
				'action',
				'priority'
			], [
				$type,
				$event->sender->parent_type,
				$event->sender->parent_id,
				($event->sender->reply_to != null ? 'reply' : 'create'),
				$event->sender->priority
			]);
			switch($event->sender->reply_to != null)
			{
				case true:
				$this->_alerts->reportedAction = 'replied';
				break;
				
				default:
				$this->_alerts->reportedAction = 'create';
				break;
			}
			$message['owner_id'] = $event->sender->hasAttribute('author_id') ? $event->sender->author_id : null;
			if(!$event->handled) static::processAlerts($event, $message);
		}
		return $message;
	}
}
?>