<?php

namespace nitm\models;

use Yii;
use yii\db\ActiveRecord;
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
	
	protected static $is = 'alerts';
	protected $_criteria = [];
	
	private $_prepared = false;
	private $_variables = [];
	
	public function init()
	{
		parent::init();
		$this->initConfig(static::isWhat());
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
        return $this->hasOne(User::className(), ['id' => 'user_id'])->with('profile');
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
	
	public function prepare($basedOn)
	{
		$this->_criteria = $basedOn;
		$this->_prepared = true;
	}
	
	public function isPrepared()
	{
		return $this->_prepared === true;
	}
	
	public function findSpecific()
	{
		return self::find()->select('id')
		->where($this->_criteria)
		->andWhere([
			'user_id' => \Yii::$app->user->getId()
		])
		->with('user')->all();
	}
	
	public function findOwner($author_id)
	{
		$criteria = $this->_criteria;
		$criteria['user_id'] = $author_id;
		$criteria['action'] .= '_my';
		return self::find()->select('id')
		->where($criteria)
		->with('user')->all();
	}
	
	/**
	 * This searches for users who are listening for activity based on the remote_type, action and priority
	 */
	public function findListeners()
	{
		$criteria = array_intersect_key($this->_criteria, [
			'remote_type' => null,
			'action' => null
		]);
		$anyRemoteFor = array_merge($criteria, [
			'remote_for' => 'any'
		]);
		$anyPriority = array_merge($criteria, [
			'priority' => 'any'
		]);
		$users = self::find()->select('id')
		->orWhere($anyRemoteFor)
		->orWhere($anyPriority)
		->orWhere($this->_criteria)
		->andWhere([
			'not', 'user_id' => \Yii::$app->user->getId()
		])->with('user')->all();
	}
	
	public function findGlobal()
	{
		$criteria = array_intersect_key($this->_criteria, [
			'remote_type' => null,
			'action' => null
		]);
		$criteria['global'] = 1;
		$criteria['user_id'] = null;
		$anyRemoteFor = array_merge($criteria, [
			'remote_for' => 'any'
		]);
		$anyPriority = array_merge($criteria, [
			'priority' => 'any'
		]);
		return self::find()->select('id')
		->orWhere($criteria)
		->orWhere($anyRemoteFor)
		->orWhere($anyPriority)
		->with('user')->all();
	}
	
	public function send($compose, $alerts, $global=false)
	{
		$to = [];
		//Build the addresses
		switch(is_array($alerts) && !empty($alerts))
		{
			case true:
			switch($global)
			{
				case false:
				foreach($alerts as $alert)
				{
					$user = $alert->user;
					$methods = ($alert->methods == 'any') ? array_keys(static::supportedMethods()) : explode(',', $alert->methods);
					foreach($methods as $method)
					{
						if(isset($user->profile) && !empty($user->profile->getAttribute($method.'_email')))
						{
							$to[$method][] = $user->name."<".$user->profile->getAttribute($method.'_email').">";
						}
					}
				}
				break;
				
				default:
				$users = User::find()->with('profile')->all();
				foreach($users as $user)
				{
					/**
					 * Only send global emails based on what the user preferrs in their profile. 
					 * For specific alerts those are based ont he alert settings
					 */
					$preferredMethods = ($user->profile->contact_methods == 'any') ? array_keys(static::supportedMethods()) : explode(',', $user->profile->contact_methods);
					foreach($preferredMethods as $method)
					{
						if(isset($user->profile) && !empty($user->profile->getAttribute($method.'_email')))
						{
							$to[$method][] = $user->name."<".$user->profile->getAttribute($method.'_email').">";
						}
					}
				}
				break;
			}
			//Send the emails/mobile alerts
			foreach($to as $type=>$addresses)
			{
				//140 characters to be able to send a single SMS
				$subject = $this->replaceCommon($compose['subject']);
				$body = $this->replaceCommon($compose['message'][$type]);
				$mail = \Yii::$app->mailer->compose()
				->setTo(implode(',', $addresses))
				->setFrom(\Yii::$app->params['alerts.sender']);
				switch($type)
				{
					case 'email':
					$message->setSubject($subject);
					$body = str_replace(["%title%", "%content%"], [$subject, $body], 
					"<html>
						<head>
							<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
							<title>%title%</title>
						</head>
						<body>%content%</body>
					</html>");
					break;
					
					case 'mobile':
					$body = substr($body, 0, 140);
					break;
				}
				$mail->setTextBody($body)
				->send();
			}
			break;
		}
		return true;
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
		return str_replace(array_keys($variables), aray_values($variables), $string);
	}
	
	private function defaultVariables()
	{
		return [
			'%currentUser%' => \Yii::$app->user->identity->fullName(), 
			'%when%' => date('D M jS Y @ h:i'), 
			'%today%' => date('D M jS Y'),
			'%priority%' => ucfirst($this->_criteria['priority']),
			'%action%' => ucfirst($this->_criteria['action']),
			'%remoteFor%' => ucfirst($this->_criteria['remote_for']),
			'%remoteType%' => ucfirst($this->_criteria['remote_type']),
			'%remoteId%' => ucfirst($this->_criteria['remote_id'])
		];
	}
}
