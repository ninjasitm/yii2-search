<?php
namespace nitm\module\models;

use yii\db\ActiveRecord;
use yii\helpers\Security;
use yii\web\IdentityInterface;

/**
 * Class User
 * @package common\models
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $token
 * @property string $email
 * @property string $auth_key
 * @property integer $role
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * @property string $password write-only password
 */
class User extends Data
{
	/**
	 * @var string the raw password. Used to collect password input and isn't saved in database
	 */
	public $name;
	public $password;
	public $password2;
	
	protected $token_opts = array('omit' => -32, 'db' => 'ccsup', 'table' => 'auth_tokens');
	protected $auth = array('three_step' => array('url' => 							'https://admin.callcentric.com/api/yubiaccess.php', 
	'params' => array('key' => ''), 
	'auth' => array('enabled' => false, 'type' => 'basic', 'user' => 'fogDqFD0fF', 'password' => 'lUev11rgHutrkETivunFdih'), 
	'enabled' => false));
	
	protected $use_token = false;
	
	private $_lastActivity = '__lastActivity';

	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_INACTIVE = 2;
	const STATUS_BANNED = 3;

	const ROLE_USER = 10;
	const ROLE_API_USER = 11;
	const ROLE_ADMIN = 12;
	
	public static function tableName()
	{
		return \dektrium\user\models\User::tableName();
	}
	
	public static function dbName()
	{
		return null;
	}
	
	public function behaviors()
	{
		$behaviors = array(
				//"User" => array(
				//	"class" => \dektrium\user\models\User::className(),
				//),
			);
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public static function has()
	{
		return [
			'updated_at' => null,
			'created_at' => null
		];
	}
	
	/**
	 * Get the status value for a user
	 * @param User $user object
	 * @return string
     */
	public static function getStatus(User $user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		$statuses = static::getStatuses();
		return $statuses[$user->status ? $user->status : self::STATUS_INACTIVE];
	}
	
	/**
     * Get the supported statuses
	 * @return array statuses
     */
	public static function getStatuses()
	{
		$statuses = [
			self::STATUS_ACTIVE => 'Active', 
			self::STATUS_INACTIVE => 'Inactive', 
			self::STATUS_DELETED => 'Deleted', 
			self::STATUS_BANNED => 'Banned', 
		];
		return $statuses;
	}
	
	/**
     * Get the role value for a user
	 * @param User $user object
	 * @return string name of role
     */
	public static function getRole(User $user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		$roles = static::getRoles();
		return @$roles[$user->role ? $user->role : self::ROLE_USER];
	}
 
	/**
     * Get the supported roles
	 * @return array statuses
     */
	public static function getRoles()
	{
		$roles = [
			self::ROLE_USER => 'User', 
			self::ROLE_API_USER => 'Api User', 
			self::ROLE_ADMIN => 'Admin', 
		];
		return $roles;
	}

	/**
	 * Get the records for this provisioning template
	 * @param boolean $idsOnly
	 * @return mixed Users array
	 */
	public function getAll($idsOnly=true)
	{
		$ret_val = array();
		$users = $this->find()->asArray()->all();
		switch($idsOnly)
		{
			case true:
			foreach($users as $user)
			{
				$ret_val[$user['id']] = $user['f_name'].' '.$user['l_name'];
			}
			break;
			
			default:
			$ret_val = $users;
			break;
		}
		return $ret_val;
	}
	
	/**
	 * @inheritdoc
	 */
	public static function findIdentity($id)
	{
		return static::find($id);
	}

	/**
	 * @inheritdoc
	 */
	public function getId()
	{
		return $this->getAttribute('id');
	}
	
	/**
	 *
	 */
	public function isAdmin()
	{
		$user = \Yii::$app->user->getIdentity();
		switch(!$user)
		{
			case true:
			$user = new User;
			break;
		}
		return $user->role == self::ROLE_ADMIN;
	}
	
	/**
	 * Get the last time this user was active
	 * @return boolean
	 */
	public function lastActive()
	{
		$ret_val = strtotime('now');
		$sessionActivity = \Yii::$app->getSession()->get($this->_lastActivity);
		switch(is_null($sessionActivity))
		{
			case true:
			$ret_val = \Yii::$app->user->getIdentity()->logged_in_at;
			break;
			
			default:
			$ret_val = $sessionActivity;
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Should we use token authentication for this user?
	 * @param boolean $use
	 */
	public function useToken($use=false)
	{
		$this->use_token = ($use === true) ? true : false;
	}
	
	/**
	 * Get the avatar
	 * @param mixed $options
	 * @return Html string
	 */
	public function getAvatar($options=[]) 
	{
		switch($this->hasProperty('avatar') && !empty($this->avatar))
		{
			case true:
			$url = $this->avatar;
			break;
			
			//Fallback to dektriuk\User gravatar info
			default:
			$id = empty($this->id) ? \Yii::$app->user->identity->id : $this->id;
			$profile = \dektrium\user\models\Profile::find()->where(['user_id' => $id])->one();
			switch(1)
			{
				case !empty($profile->gravatar_id):
				$key = $profile->gravatar_id;
				break;
				
				case !empty($profile->gravatar_email):
				$key = $profile->gravatar_email;
				break;
				
				default:
				$key = $profile->public_email;
				break;
			}
			$url = "http://gravatar.com/avatar/$key";
			break;
		}
		return \yii\helpers\Html::img($url, array_merge(['class' => 'avatar'], $options));
	}
	
	/**
	 * Does this user have tokens?
	 * @param User $user object
	 * @return string
	 */
	public static function hasApiTokens(User $user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		return (bool) ($user->hasMany(\nitm\module\models\api\Token::className(), ['userid' => 'id'])->count() >= 1);
	}
	
	/**
	 * Does this user have tokens?
	 * @param User $user object
	 * @return string
	 */
	public static function getApiTokens(User $user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		return $user->hasMany(\nitm\module\models\api\Token::className(), ['userid' => 'id'])->all();
	}
	
	/**
	 * Get the fullname of a user
	 * @param int $id
	 * @param string $idKey The key where the userid is stored
	 * @return string
	 */
	public static function getFullName($withUsername=false, $user=null, $idKey='id')
	{
		$ret_val = '';
		switch($user instanceof User)
		{
			case false:
			switch(@parent::$active['db']['name'] == static::dbName())
			{
				case false:
				\Yii::$app->user->getIdentity()->changeDb(static::dbName());
				break;
			}
			$user = is_null($user) ? \Yii::$app->user->getIdentity() : User::find($user);
			switch(parent::$old['db']['name'] == static::dbName())
			{
				case false:
				\Yii::$app->user->getIdentity()->revertDb();
				break;
			}
			break;
		}
		switch($user instanceof User)
		{
			case true:
			$profile = \dektrium\user\models\Profile::find()->where(['user_id' => $user->id])->one();
			switch($profile instanceof \dektrium\user\models\Profile)
			{
				case true:
				$ret_val = $profile->name.($withUsername ? '('.$user->username.')' : '');
				break;
				
				default:
				$ret_val = $user->username;
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Get the username of a user
	 * @param int $id
	 * @param string $idKey The key where the userid is stored
	 * @return string
	 */
	public static function getUsername($id=null, $idKey='id')
	{
		$ret_val = '';
		$id = is_object($id) ? $id->$idKey : $id;
		switch(@parent::$active['db']['name'] == static::dbName())
		{
			case false:
			\Yii::$app->user->getIdentity()->changeDb(static::dbName());
			break;
		}
		$user = is_null($id) ? \Yii::$app->user->getIdentity() : static::findIdentity($id);
		switch($user instanceof User)
		{
			case true:
			$ret_val = $user->username;
			break;
		}
		switch(parent::$old['db']['name'] == static::dbName())
		{
			case false:
			\Yii::$app->user->getIdentity()->revertDb();
			break;
		}
		return $ret_val;
	}
	
	/**
	 * @param string $property
	 * @return string|int property value
	 */
	 public function getProperty($prop)
	 {
		$ret_val = false;
		switch(is_null($prop))
		{
			case false:
			switch(ReflectionClass($this)->hasProperty($prop))
			{
				case true:
				$ret_val = $this->$prop;
				break;
			}
			break;
		}
		return $ret_val;
	 }
	 
	 /**
	  *
	  */
	public function generateApiToken()
	{
		$this->setScenario('createToken');
		$this->api_key = $this->getApiToken();
		$this->save();
	}
	 
	/**
	  * Get a unique identify token for this user
	  *
	  */
	private function getApiToken()
	{
		return \yii\helpers\Security::generateRandomKey();
	}

	/**
	 * @return string current user auth key
	 */
	public function getAuthKey()
	{
		return $this->auth_key;
	}

	/**
	 * @return string current user auth key
	 */
	public function getToken()
	{
		return $this->token;
	}
	
	/*
	 * Do second step of authentication here
	 * At this time authenticate token against Sergey's Auth DB
	 * @param strign $token
	 * @return boolean
	 */
	private function validateToken($token)
	{
		$ret_val = false;
		switch(isset($id) || !is_null($id))
		{
			case true:
			$token = substr($this->getState(AUTH_DOMAIN.".".self::$non_persistent['token']), 0, $this->token_opts['omit']);
			switch(!empty($token))
			{
				case true:
				$this->obj->set_db($this->token_opts['db'], $this->token_opts['table']);
				$this->obj->select('token_cc', true, array("key" => array('userid', 'token'), "data" => array($id, $token)), null, null, 1);
				switch($this->obj->rows())
				{
					case true:
					/*
					Three step auth here
					 */
					error_reporting(E_ALL);
					$cur_token = $this->getState(AUTH_DOMAIN.".".self::$non_persistent['token']);
					switch(!self::$auth['three_step']['enabled'])
					{
						case true:
						case 'true':
						case 1:
						self::$auth['three_step']['params']['key'] = $cur_token;
						$options = array(CURLOPT_URL, self::$auth['three_step']['url']);
						switch(!self::$auth['three_step']['auth']['enabled'])
						{
							case true:
							case 'true':
							case 1:
							switch(self::$auth['three_step']['auth']['type'])
							{
								case 'basic';
								$options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
								break;
							}
							$options[CURLOPT_USERPWD] = self::$auth['three_step']['auth']['user'].':'.self::$auth['three_step']['auth']['password'];
							break;
						}
						$options[CURLOPT_POST] = true;
						$options[CURLOPT_POSTFIELDS] = self::$auth['three_step']['params'];
						$options[CURLOPT_RETURNTRANSFER] = true;
						$session = curl_init(self::$auth['three_step']['url']);
						curl_setopt_array($session, $options);
						$response = curl_exec($session);
						var_dump($response);
						switch(1)
						{
							case strpos($response, 'OK') !== false:
							$ret_val = true;
							break;
							
							default:
							$ret_val = false;
							break;
						}
						break;
						
						default:
						$ret_val = true;
						break;
					}
					break;
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
}
