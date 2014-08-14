<?php
namespace nitm\models;

use yii\db\ActiveRecord;
use yii\helpers\Security;
use yii\web\IdentityInterface;
use dektrium\user\models\Profile;
use nitm\helpers\Cache;

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
class User extends \dektrium\user\models\User
{
	public $updateActivity;
	public $useFullnames;
	
	protected $useToken;
	
	private $_lastActivity = '__lastActivity';
	
	public function init()
	{
		if($this->updateActivity) $this->updateActivity();
	}
	
	public function isWhat() 
	{
		return 'user';
	}
	
	/**
	 * Get the status value for a user
	 * @return string
     */
	public function status()
	{
		return \nitm\models\security\User::getStatus($this);
	}
	
	public function indicator($user)
	{
		return \nitm\models\security\User::getIndicator($user);
	}
	
	/**
     * Get the role value for a user
	 * @return string name of role
     */
	public function role()
	{
		return \nitm\models\security\User::getRole($this);
	}
	
	/**
	 *
	 */
	public function isAdmin()
	{
		return \nitm\models\security\User::getIsAdmin($this);
	}

	/**
	 * Get the records for this provisioning template
	 * @param boolean $idsOnly
	 * @return mixed Users array
	 */
	public function getAll($idsOnly=true)
	{
		$ret_val = array();
		switch(Cache::cache()->exists('nitm-user-list'))
		{
			case false:
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
			Cache::cache()->set('nitm-user-list', urlencode($url), 3600);
			break;
			
			default:
			$ret_val = urldecode(Cache::cache()->get('nitm-user-list'));
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Get the actvity counter
	 * @param boolean $update Should the activity be updated
	 * @return boolean
	 */
	public function lastActive($update=false)
	{
		$ret_val = strtotime('now');
		$sessionActivity = \Yii::$app->getSession()->get($this->_lastActivity);
		switch(is_null($sessionActivity))
		{
			case true:
			$user = \Yii::$app->user->identity;
			$ret_val = !$user->getId() ? strtotime('now') : $user->logged_in_at;
			break;
			
			default:
			$ret_val = $sessionActivity;
			break;
		}
		if($update) $this->updateActivity();
		return $ret_val;
	}
	
	/**
	  * Update the user activity counter
	  */
	public function updateActivity()
	{
		return \Yii::$app->getSession()->set($this->_lastActivity, strtotime('now'));
	}
	
	/**
	 * Should we use token authentication for this user?
	 * @param boolean $use
	 */
	public function useToken($use=false)
	{
		$this->useToken = ($use === true) ? true : false;
	}
	
	public function avatarImg($options=[])
	{
		return \yii\helpers\Html::img($this->avatar(), $options);
	}
	
	public function url($fullName=false, $url=null, $options=[]) 
	{
		$url = is_null($url) ? 'user/profile/'.$this->getId() : $url;
		$urlOptions = array_merge([$url], $options);
		$text = ($fullName === false) ? $this->username : $this->fullname();
		$htmlOptions = [
			'href' => \Yii::$app->urlManager->createUrl($urlOptions), 
			'role' => 'userLink', 
			'id' => 'user'.uniqid()
		];
		return \yii\helpers\Html::tag('a', $text, $htmlOptions);
	}
	
	/**
	 * Get the avatar
	 * @param mixed $options
	 * @return string
	 */
	public function avatar() 
	{
		switch(Cache::cache()->exists('user-avatar'.$this->getId()))
		{
			case false:
			switch($this->hasAttribute('avatar') && !empty($this->avatar))
			{
				case true:
				//Support for old NITM avatar/local avatar
				$url = $this->avatar;
				break;
				
				//Fallback to dektriuk\User gravatar info
				default:
				$profile = $this->profile instanceof Profile ? $this->profile : Profile::find()->where(['user_id' => $this->getId()])->one();
				switch(!is_null($profile))
				{
					case true:
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
					break;
					
					default:
					$key = \Yii::$app->user->identity->email;
					break;
				}
				$url = "http://gravatar.com/avatar/$key";
				break;
			}
			Cache::cache()->set('user-avatar'.$this->getId(), urlencode($url), 3600);
			break;
			
			default:
			$url = urldecode(Cache::cache()->get('user-avatar'.$this->getId()));
			break;
		}
		return $url;
	}
	
	/**
	 * Does this user have tokens?
	 * @param User $user object
	 * @return string
	 */
	public function hasApiTokens()
	{
		return security\User::hasApiTokens($this);
	}
	
	/**
	 * Does this user have tokens?
	 * @param User $user object
	 * @return string
	 */
	public function getApiTokens()
	{
		return $this->hasMany(\nitm\models\api\Token::className(), ['userid' => 'id'])->all();
	}
	
	/**
	 * Get the fullname of a user
	 * @param boolean $withUsername
	 * @return string
	 */
	public function fullName($withUsername=false)
	{
		$profile = $this->profile instanceof Profile ? $this->profile : Profile::find()->where(['user_id' => $this->id])->one();
		switch(is_object($profile))
		{
			case true:
			$ret_val = $profile->name.($withUsername ? '('.$this->username.')' : '');
			break;
			
			default:
			$ret_val = '';
			break;
		}
		return $ret_val;
	}
}
