<?php

namespace nitm\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use nitm\helpers\Cache;

/**
 * This is the model class for table "alerts".
 *
 * @property integer $id
 * @property integer $remote_id
 * @property string $remote_type
 * @property string $remote_for
 * @property integer $user_id
 * @property string $action
 * @property integer $global
 * @property integer $disabled
 * @property string $created_at
 *
 * @property User $user
 */
class Alerts extends Data
{
	public $useFullnames = true;
	public $reportedAction;
	public $usersWhere;
	
	protected static $is = 'alerts';
	protected $_criteria = [];
	protected $_originUserId;
	
	private $_prepared = false;
	private $_variables = [];
	private $_mailer;
	
	public function init()
	{
		parent::init();
		$this->initConfig(static::isWhat());
		$this->_mailer = \Yii::$app->mailer;
	}
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'alerts';
    }
	
    public function behaviors()
    {
		$behaviors = [
			'blamable' => [
				'class' => \yii\behaviors\BlameableBehavior::className(),
					'attributes' => [
						ActiveRecord::EVENT_BEFORE_INSERT => 'user_id',
					],
			]
		];
		return array_merge(parent::behaviors(), $behaviors);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['remote_type', 'action', 'remote_for'], 'required', 'on' => ['create']],
            [['remote_id', 'user_id', 'global', 'disabled'], 'integer'],
            [['created_at', 'remote_for'], 'safe'],
            [['remote_type', 'action'], 'string', 'max' => 64],
            [['remote_id', 'remote_type', 'user_id', 'action'], 'unique', 'targetAttribute' => ['remote_id', 'remote_type', 'user_id', 'action'], 'message' => 'The combination of Remote ID, Remote Type, User ID and Action has already been taken.'],
			[['remote_for'], 'validateRemoteFor'],
			[['methods'], 'filter', 'filter' => [$this, 'filterMethods']]
        ];
    }
	
	public function scenarios()
	{
		$scenarios = [
			'create' => ['remote_id', 'remote_type', 'remote_for', 'action', 'priority', 'methods']
		];
		return array_merge(parent::scenarios(), $scenarios);
	}
	
	public function validateRemoteFor($attribute, $params)
	{
		$ret_val = '';
		switch($this->remote_type)
		{
			case 'issues':
			switch($this->$attribute)
			{
				case 'guides':
				case 'requests':
				break;
				
				default:
				$ret_val = [];
				$ret_val['message'] = "Option requred for ".$this->remote_type;
				$ret_val['attribute'] = $this->getAttributeLabel($attribute);
				$ret_val = 'yii.validation.required(value, messages, ' . json_encode($ret_val) . ');';
				break;
			}
			break;
		}
		return $ret_val;
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'remote_id' => Yii::t('app', 'Remote ID'),
            'remote_type' => Yii::t('app', 'Remote Type'),
            'user_id' => Yii::t('app', 'User ID'),
            'action' => Yii::t('app', 'Action'),
            'global' => Yii::t('app', 'Global'),
            'disabled' => Yii::t('app', 'Disabled'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id'])->where($this->usersWhere)->with('profile');
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        switch(Cache::exists('alerts.users'))
		{
			case true:
			$ret_val = Cache::getModelArray($key, $options);
			break;
			
			default:
			$ret_val = User::find()->with('profile')->where($this->usersWhere)->all();
			Cache::setModelArray('alerts.users', $ret_val);
			break;
		}
		return $ret_val;
    }
	
	public function getPriority()
	{
		switch($this->priority)
		{
			case 'critical':
			$ret_val = 'error';
			break;
			
			case 'important':
			$ret_val = 'info';
			break;
			
			default:
			$ret_val = 'default';
			break;
		}
		return $ret_val;
	}
	
	public static function supportedMethods()
	{
		return [
			'any' => 'Any',
			'email' => 'Email',
			'mobile' => 'Mobile'
		];
	}
	
	public function prepare($isNew, $basedOn)
	{
		$basedOn['action'] = $isNew === true ? 'create' : 'update';
		$this->reportedAction = $basedOn['action'].'d';
		$this->_criteria = $basedOn;
		$this->_prepared = true;
	}
	
	public function isPrepared()
	{
		return $this->_prepared === true;
	}
	
	public function criteria($key, $value='undefined')
	{
		$ret_val = true;
		switch($value)
		{
			case 'undefined':
			$ret_val = isset($this->_criteria[$key]) ? $this->_criteria[$key] : false;
			break;
			
			default:
			$this->_criteria[$key] = $value;
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Find alerts for the specific criteia originally provided
	 * @param int $originUserId Is the ID of the user for the object which triggered this alert sequence
	 * @return \yii\db\Query
	 */
	public function findAlerts($originUserId)
	{
		$this->_originUserId = $originUserId;
		return $this->findSpecific($this->_criteria)
			->union($this->findOwner($this->_originUserId, $this->_criteria))
			->union($this->findListeners($this->_criteria))
			->union($this->findGlobal($this->_criteria))
			->with('user')->all();
	}
	
	/**
	 * Find alerts for the specific criteia originally provided
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public static function findSpecific(array $criteria)
	{
		return self::find()->select('*')
			->where($criteria)
			->andWhere([
				'user_id' => \Yii::$app->user->getId()
			])
			->with('user');
	}
	
	/**
	 * Find alerts for the specific criteia originally provided
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public function findOwner($author_id, array $criteria)
	{
		$criteria['user_id'] = $author_id;
		$criteria['action'] .= '_my';
		return self::find()->select('*')
			->where($criteria)
			->with('user');
	}
	
	/**
	 * This searches for users who are listening for activity 
	 * Based on the remote_type, action and priority
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public function findListeners(array $criteria)
	{
		unset($criteria['user_id']);
		$listenerCriteria = array_intersect_key($criteria, [
			'remote_type' => null,
			'action' => null
		]);
		$anyRemoteFor = array_merge($listenerCriteria, [
			'remote_for' => 'any'
		]);
		$anyPriority = array_merge($listenerCriteria, [
			'priority' => 'any'
		]);
		return self::find()->select('*')
			->orWhere($anyRemoteFor)
			->orWhere($anyPriority)
			->orWhere($criteria)
			->andWhere([
				'not', ['user_id' => \Yii::$app->user->getId()]
			])
			->with('user');
	}
	
	/**
	 * Find global listeners for this criteria 
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public function findGlobal(array $criteria)
	{
		$criteria = array_intersect_key($this->_criteria, [
			'remote_type' => null,
			'action' => null
		]);
		$criteria['global'] = 1;
		$criteria['user_id'] = null;
		$anyRemoteFor = array_replace($criteria, [
			'remote_for' => 'any'
		]);
		$anyPriority = array_replace($criteria, [
			'priority' => 'any'
		]);
		return self::find()->select('*')
			->orWhere($criteria)
			->orWhere($anyRemoteFor)
			->orWhere($anyPriority)
			->with('user');
	}
	
	public function sendAlerts($compose, $ownerId)
	{
		$alerts = $this->findAlerts($ownerId);
		$to = [
			'global' => [],
			'individual'=> [],
			'owner' => []
		];
		//Build the addresses
		switch(is_array($alerts) && !empty($alerts))
		{
			case true:
			//Organize by global and individual alerts
			foreach($alerts as $idx=>$alert)
			{
				switch(1)
				{
					case $alert->global == 1:
					/**
					 * Only send global emails based on what the user preferrs in their profile. 
					 * For specific alerts those are based ont he alert settings
					 */
					$to['global'] = array_merge_recursive($to['global'], $this->getAddresses($alert->user->profile->contact_methods, $this->getUsers(), true));
					break;
					
					case $alert->user->getId() == $this->_originUserId:
					$to['owner'] = array_merge_recursive($to['owner'], $this->getAddresses($alert->methods, [$alert->user]));
					break;
					
					default:
					$to['individual'] = array_merge_recursive($to['individual'], $this->getAddresses($alert->methods, [$alert->user]));
					break;
				}
			}
			//Send the emails/mobile alerts
			$originalSubject = $this->replaceCommon(is_array($compose['subject']) ? $this->_mailer->render($compose['subject']['view']) : $compose['subject']);;
			foreach($to as $scope=>$types)
			{
				foreach($types as $type=>$addresses)
				{
					$body = $this->replaceCommon(is_array($compose['message'][$type]) ? $this->_mailer->render($compose['message'][$type]['view']) : $compose['message'][$type]);
					$params = [
						"content" => $body
					];
					switch($scope)
					{
						case 'owner':
						$subject = 'Your '.$originalSubject;
						$params['content'] = (($this->criteria('action') == 'create') ? '' : 'Your ').$params['content'];
						$params['greeting'] = "Dear ".current($addresses)['user']->username.", <br><br>";
						break;
						
						default:
						$subject = (($this->criteria('action') == 'create') ? 'A' : 'The').' '.$originalSubject;
						$params['content'] = (($this->criteria('action') == 'create') ? '' : 'The ').$params['content'];
						$params['greeting'] = "Dear user, <br><br>";
						break;
					}
					$params['title'] = $subject;
					switch($type)
					{
						case 'email':
						$view = ['html' => '@nitm/views/alerts/message/email'];
						$params['content'] = nl2br($params['content'].$this->getFooter($scope));
						break;
						
						case 'mobile':
						//140 characters to be able to send a single SMS
						$params['content'] = substr($body, 0, 140);
						$params['title'] = '';
						$view = ['text' => '@nitm/views/alerts/message/mobile'];
						break;
					}
					$addresses = $this->filterAddresses($addresses);
					$email = $this->_mailer->compose($view, $params)->setTo(array_slice($addresses, 0, 1));
					switch($type)
					{
						case 'email':
						$email->setSubject($subject);
						break;
					}
					switch(sizeof($addresses) >= 1)
					{
						case true:
						$email->setBcc($addresses);
						break;
					}
					$email->setFrom(\Yii::$app->params['components.alerts']['sender'])
						->send();
				}
			}
			break;
		}
		return true;
	}
	
	protected function filterAddresses($addresses)
	{
		$ret_val = [];
		foreach($addresses as $address)
		{
			unset($address['user']);
			$ret_val[key($address)] = $address[key($address)];
		}
		return $ret_val;
	}
	
	public function filterMethods($value)
	{
		$ret_val = [];
		$value = is_array($value) ? $value : [$value];
		foreach($value as $method)
		{
			switch(array_key_exists($method, static::supportedMethods()))
			{
				case true:
				$ret_val[] = $method;
				break;
			}
		}
		return implode(',', (empty($ret_val) ? ['email'] : $ret_val));
	}
	
	public function addVariables(array $variables)
	{
		$this->_variables = array_merge($variables, $this->_variables);
	}
	
	public function resetVariables()
	{
		$this->_variables = [];
	}
	
	protected function replaceCommon($string)
	{
		$variables = array_merge($this->defaultVariables(), $this->_variables);
		return str_replace(array_keys($variables), array_values($variables), $string);
	}
	
	private function defaultVariables()
	{
		return [ 
			'%who%' => \Yii::$app->user->identity->username,
			'%when%' => date('D M jS Y @ h:iA'), 
			'%today%' => date('D M jS Y'),
			'%priority%' => ucfirst($this->_criteria['priority']),
			'%action%' => $this->reportedAction,
			'%remoteFor%' => ucfirst($this->_criteria['remote_for']),
			'%remoteType%' => ucfirst($this->_criteria['remote_type']),
			'%remoteId%' => $this->_criteria['remote_id']
		];
	}
	
	private function getAddresses($method=null, $users=[], $global=false)
	{
		$method = (string)$method;
		$ret_val = [];
		switch($global)
		{
			case true:
			$users = $this->getUsers();
			break;
		}
		$methods = ($method == 'any' || is_null($method)) ? array_keys(static::supportedMethods()) : explode(',', $method);
		unset($methods[array_search('any', $methods)]);
		foreach($users as $user)
		{
			foreach($methods as $method)
			{
				if($user->getId() == $this->_originUserId)
					continue;
				switch($method)
				{
					case 'email':
					switch(1)
					{
						case !empty($uri = (is_object($user->profile) ? $user->profile->getAttribute('public_email') : $user->email)):
						break;
						
						default:
						$uri = $user->email;
						break;
					}
					break;
					
					default:
					$uri = is_object($user->profile) ? $user->profile->getAttribute($method.'_email') : null;
					break;
				}
				if(!empty($uri))
				{
					$name = $user->fullName();
					$ret_val[$method][$user->getId()] = [$uri => (!$name ? $uri : $name), 'user' => $user];
				}
			}
		}
		return $ret_val;
	}
	
	private function getFooter($scope)
	{	
		switch($scope)
		{
			case 'global':
			$footer = "\n\nYou are receiving this becuase of a global alert matching: ";
			break;
			
			default:
			$footer = "\n\nYou are receiving this bcause your alert settings matched: ";
			break;
		}
		if(($priority = $this->criteria('priority')) != false)
		$footer .= "Priority: <b>".ucfirst($priority)."</b>, ";
		if(($type = $this->criteria('remote_type')) != false)
		$footer .= "Type: <b>".ucfirst($type)."</b>, ";
		if(($id = $this->criteria('priority')) != false)
		$footer .= "Id: <b>".ucfirst($id)."</b>, ";
		if(($for = $this->criteria('priority')) != false)
		$footer .= "For: <b>".ucfirst($for)."</b>, ";
		if(($action = $this->criteria('action')) != false)
		$footer .= "and Action <b>".$this->properName($action)."</b>";
		$footer .= ". Go ".Html::a("here", \Yii::$app->urlManager->createAbsoluteUrl("/alerts/index"))." to change your alerts";
		$footer .= "\nSite: ".Html::a(\Yii::$app->urlManager->createAbsoluteUrl('/'), \Yii::$app->urlManager->createAbsoluteUrl('/index'));
			
		return Html::tag('small', $footer);
	}
}
