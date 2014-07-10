<?php
namespace nitm\models\security;

use yii\db\ActiveRecord;
use yii\helpers\Security;
use yii\web\IdentityInterface;
use dektrium\user\models\Profile;

/**
 * Class User
 * @package nitm\models
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
class User extends \yii\base\Model
{

	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_INACTIVE = 2;
	const STATUS_BANNED = 3;

	const ROLE_USER = 10;
	const ROLE_API_USER = 11;
	const ROLE_ADMIN = 12;
	
	/**
	 * Get the status value for a user
	 * @param User $user object
	 * @return string
     */
	public static function getStatus($user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		$statuses = self::getStatuses();
		return $statuses[$user->status ? $user->status : self::STATUS_INACTIVE];
	}
	
	public static function getIndicator($user)
	{
		$ret_val = 'default';
		switch(self::getStatus($user))
		{
			case self::STATUS_BANNED:
			$ret_val = 'error';
			break;
			
			case self::STATUS_INACTIVE:
			case self::STATUS_DELETED:
			$ret_val = 'disabled';
			break;
			
			default:
			$ret_val = 'success';
			break;
		}
		switch(self::getRole($user))
		{
			case self::ROLE_ADMIN:
			$ret_val = 'info';
			break;
			
			case self::ROLE_API_USER:
			$ret_val = 'warning';
			break;
		}
		return $ret_val;
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
	public static function getRole($user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		$roles = self::getRoles();
		return @$roles[$user->role ? $user->role : self::ROLE_USER];
	}
 
	/**
     * Get the supported roles
	 * @return array statuses
     */
	public function getRoles()
	{
		$roles = [
			self::ROLE_USER => 'User', 
			self::ROLE_API_USER => 'Api User', 
			self::ROLE_ADMIN => 'Admin', 
		];
		return $roles;
	}
	
	/**
	 *
	 */
	public function getIsAdmin($user=null)
	{
		$user = !$user ? \Yii::$app->user->getIdentity() : $user;
		switch(!$user)
		{
			case true:
			$ret_val = false;
			break;
			
			default:
			$ret_val = ($user->role == self::ROLE_ADMIN);
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Does this user have tokens?
	 * @param User $user object
	 * @return string
	 */
	public static function hasApiTokens($user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		return (bool) ($user->hasMany(\nitm\models\api\Token::className(), ['userid' => 'id'])->count() >= 1);
	}
	
	/**
	 * Does this user have tokens?
	 * @param User $user object
	 * @return string
	 */
	public static function getApiTokens($user=null)
	{
		$user = is_null($user) ? \Yii::$app->user->getIdentity() : $user;
		return $user->hasMany(\nitm\models\api\Token::className(), ['userid' => 'id'])->all();
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
