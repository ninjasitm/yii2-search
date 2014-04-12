<?php

namespace nitm\models\api;
use nitm\models\User;

/**
 * Token is the token class used for handling api tokens
 * Package nitm\models\api
 *
 * @property int $user_id The user_id of the owner
 * @property string $identity The string identity of the user
 * @property string $token The token
 * @property boolean $active Is this token active?
 * @property int $level The access level of the token
 * @property boolean $revoked Has the token been revoked? Thhis is bad since it means it was forcefully disabled
 * @property Timestamp $revoked_on The date the token was revoked on
 */
class Token extends \nitm\models\Data
{
	public $message;
	public $name;

	private $_create;		                                           //Tokens which need to be createed
	private $_update;		                                          //Tokens which need to be updateed
	private $_toggle;	                                         //Tokens which need to be toggled
	private $_count;	                                          //The number of tokens for this user
	private $_tokenKey;

	protected static $is = 'token';
	
	const LEVEL_READ = 0;
	const LEVEL_WRITE = 1;

	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_BANNED = 2;
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'access_tokens';
	}
	
	public function rules()
	{
		$rules = [
			[['user_id'], 'required', 'on' => ['update', 'delete', 'create']],
			[['user_id'], 'required', 'on' => ['select']],
			[['token'], 'required', 'on' => ['revoke']],
			['revoked_on', 'date', 'on' => ['revoke']],
			[['level', 'user_id'], 'required', 'on' => ['create']],
			[['user_id', 'createed'], 'required'],
			[['user_id'], 'integer'],
			[['token'], 'string'],
			[['createed', 'revoked_on'], 'safe'],
			[['active', 'revoked'], 'boolean'],
		];
		return array_merge(parent::rules(), $rules);
	}
	
	public function scenarios()
	{
		$scenarios = [
			'select' => ['user_id'],
			'update' => ['token', 'active', 'revoked', 'revoked_on', 'level'],
			'delete' => ['active'],
			'create' => ['token', 'user_id', 'active', 'level']
		];
		return array_merge(parent::scenarios(), $scenarios);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'Token Id',
			'user_id' => 'User Id',
			'token' => 'Token',
			'createed' => 'Created',
			'active' => 'Active',
			'level' => 'Level',
			'revoked' => 'Revoked',
			'revoked_on' => 'Revoked On',
		];
	}
	
	/**
     * Get the token levels
	 * @param Token $token object
	 * @return string name of level
     */
	public static function getLevel($token)
	{
		$levels = static::getLevels();
		return $levels[$token->level];
	}
	
	/**
     * Get the supported token levels
     */
	public static function getLevels()
	{
		$levels = [
			static::LEVEL_READ => 'Read', 
			static::LEVEL_WRITE => 'Write', 
		];
		return $levels;
	}
	
	/**
	 * Get the status value for a user
	 * @param Token $token object
	 * @return string status
     */
	public static function getStatus(Token $token=null)
	{
		$status = 'Inactive';
		switch($token->active)
		{
			case 1:
			$status = 'Active';
			break;
		}
		switch($token->revoked)
		{
			case 1:
			$status = 'Revoked';
			break;
		}
		return $status;
	}
	
	/*
	 * Parse an array for token information
	 * @param mixed $array
	 */
	public function parse($array)
	{
		for($idx = 0; $idx < $this->settings[static::isWhat()]['globals']['tokens_max']; $idx++)
		{
			switch(empty($array))
			{
				case false:
				switch(($array['toggle'][$idx] == 0) && ($array['revoke'][$idx] != 'on') && ($array['toggle'][$idx] != $array['token_is_active'][$idx]))
				{
					case true:
					//we're revoking this token;
					$this->_revoke[$idx]['unique'] = $array['token'][$idx];
					break;
				}
				continue;
				break;
				
				default:
				switch(($array['toggle'][$idx] == 1) && ($array['revoke'][$idx] != 'on') && ($max === false) && ($array['token_is_active'][$idx] == ''))
				{
					case true:
					$this->_create[$idx]['level'] = $array['token_level'][$idx];
					$this->_create[$idx]['active'] = 1;
					break;
				}
				continue;
				break;
			}
			switch($array['update'][$idx])
			{
				case 'on':
				$this->_update[$idx]['unique'] = $array['token'][$idx];
				$this->_update[$idx]['data'] = ['level' => $array['token_level'][$idx]];
				continue;
				break;
			}
			switch(empty($array['token'][$idx]))
			{
				case false:
				switch($array['revoke'][$idx])
				{
					case 'on':
					$this->_revoke[$idx]['unique'] = $array['token'][$idx];
					break;
				}
				break;
			}
		}
	}
	
	public function create()
	{
		$ret_val = false;
		switch(is_array($this->_create))
		{
			case true:
			$this->logText = "";
			foreach($this->_create as $data)
			{
				$model = new Token();
				$model->setScenario('create');
				$model->load($data);
				$model->user_id = $this->user_id;
				switch($model->validate())
				{
					case true:
					$model->save();
					$this->logText .= \nitm\models\DB::split_c(array_keys($data['data']), array_values($data['data']), '=', ',', false, false, false)."\n";
					break;
				}
			}
			$this->messages .= "Created ".sizeof($this->_update)." tokens for user with ID ".$this->user_id;
			$this->log(static::tableName(), 'Create Tokens', $this->message.". Tokens:\n".$this->logText);
			$ret_val = true;
			break;
		}
		return $ret_val;
	}
	
	public function update($validate=true, $attributes=null)
	{
		$ret_val = false;
		switch(is_array($this->_update))
		{
			case true:
			$this->logText = "";
			foreach($this->_update as $data)
			{
				$model = Token::find($data['unique']);
				switch($model instanceof Token)
				{
					case true:
					$model->setScenario('update');
					$model->load($data['data']);
					switch($model->validate())
					{
						case true:
						$model->save();
						$this->logText .= \nitm\models\DB::split_c(array_keys($data['data']), array_values($data['data']), '=', ',', false, false, false)."\n";
						break;
					}
					break;
				}
			}
			$this->messages .= "Updated ".sizeof($this->_update)." tokens for user with ID ".$this->user_id;
			$this->log(static::tableName(), 'Update Tokens', $this->message.". Tokens:\n".$this->logText);
			$ret_val = true;
			break;
		}
		return $ret_val;
	}
	
	public function revoke()
	{
		$ret_val = false;
		$this->_update = $this->_revoke;
		switch($this->update())
		{
			case true:
			$ret_val = true;
			$this->messagse .= "Revoked ".sizeof($this->_update)." tokens for user with ID ".$this->user_id;
			$this->log(static::tableName(), 'Revoke Tokens', $this->message.". Tokens:\n".$this->logText);
			break;
		}
		return $ret_val;
		foreach($token_data as $data)
		{
			$fields = array('revoked' => 1, 'revoked_on' => date($this->getval('settings.globals.today_mysql'), strtotime('now')), 'active' => 0);
			$cond = array("key" => array_keys($data), "data" => array_values($data));
		}
	}
	
	public function toggle()
	{
		$ret_val = false;
		$this->_update = $this->_toggle;
		switch($this->update())
		{
			case true:
			$ret_val = true;
			$this->messagse .= "Toggled ".sizeof($this->_update)." tokens for user with ID ".$this->user_id;
			$this->log(static::tableName(), 'Toggle Tokens', $this->message.". Tokens:\n".$this->logText);
			break;
		}
		return $ret_val;
		foreach($this->_toggle as $data)
		{
			$fields = array(DB::FLAG_ASIS.'`active`= NOT `active`' => DB::FLAG_NULL);
			$cond = array("key" => array_keys($data), "data" => array_values($data));
		}
	}
	
	/*
	 * Get a unique token for a specific user
	 */
	public function getUniqueToken($id=null)
	{
		$id = is_null($id) ? $this->user_id : $id;
		switch(($user = User::find((int) $id)) == null)
		{
			case true:
			throw new NotFoundHttpException('The requested user does not exist.');
			break;
		}
		switch($user->api_key == null)
		{
			case true:
			$user->generateApiToken();
			break;
		}
		return \yii\helpers\Security::hashData(uniqid(), $user->api_key, 'fnv164');
	}
	
	/*
	 * Get tokens for a specific user
	 */
	private function getTokens($active=true, $revoked=false)
	{
		$ret_val = null;
		//First of all does this user exist?
		$user = $this->hasOne(User::className(), ['user_id' => 'user_id']);
		switch($user instanceof User)
		{
			case true:
			$cond = ['active' => 1, 'revoked' => 0];
			switch($active)
			{
				case false:
				$cond['active'] = 0;
				break;
			}
			switch($revoked)
			{
				case true:
				$cond['revoked'] = 1;
				break;
			}
			//Ok let's get their tokens
			$ret_val = $this->hasMany(Tokens::className(), ['user_id' => 'user_id'])->where($cond)->asArray()->all();
			break;
		}
		return $ret_val;
	}
	
	/*
	 * Get the token count for the current user
	 *
	 */
	private function tokenCount()
	{
		$this->setScenario('count');
		switch($this->validate())
		{
			case true:
			$this->_count = $this->find()->where([
				'user_id' => $this->user_id,
				'active' => 1,
				'revoked' => 0
			])->count();
			$this->_max = ($this->_count >= $this->settings[static::isWhat()]['globals']['max']) ? true : false;
			break;
			
			default:
			$this->_max = false;
			break;
		}
	}
}

?>
