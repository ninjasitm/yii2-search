<?php

namespace nitm\helpers\alerts;

use Yii;
use yii\helpers\Html;
use nitm\helpers\Cache;
use nitm\models\Alerts;

/**
 * This is the alert dispatcher class.
 */
class Dispatcher extends \yii\base\Component
{
	public $mode;
	public $useFullnames = true;
	public $reportedAction;
	public static $usersWhere = [];
	
	protected static $is = 'alerts';
	protected static $_subject;
	protected static $_body;
	
	protected $_criteria = [];
	protected $_originUserId;
	protected $_message;
	protected $_notifications = [];
	
	private $_prepared = false;
	private $_variables = [];
	private $_alerts;
	
	const BATCH = 'batch';
	const SINGLE = 'single';
	const UNDEFINED = '__undefined__';
	
	public static function supportedMethods()
	{
		return [
			'any' => 'Any Method',
			'email' => 'Email',
			'mobile' => 'Mobile/SMS'
		];
	}
	
	public function reset()
	{
		$this->_variables = [];
		$this->_criteria = [];
		$this->reportedAction = '';
		$this->_prepared = false;
	}
	
	public function addVariables(array $variables)
	{
		$this->_variables = array_merge($variables, $this->_variables);
	}
	
	public function resetVariables()
	{
		$this->_variables = [];
	}
	
	public function prepare($isNew, $basedOn)
	{
		$basedOn['action'] = $isNew === true ? 'create' : 'update';
		$this->reportedAction = $basedOn['action'].'d';
		$this->_criteria = $basedOn;
		$this->_prepared = true;
	}
	
	public function usersWhere($where=[])
	{
		Users::$usersWhere = $where;
	}
	
	public function isPrepared()
	{
		return $this->_prepared === true;
	}
	
	public function criteria($_key, $_value='__undefined__')
	{
		$ret_val = [];
		$key = is_array($_key) ? $_key : [$_key];
		$value = is_array($_value) ? $_value : [$_value];
		foreach($key as $idx=>$k)
		{
			switch($value[$idx])
			{
				case self::UNDEFINED:
				$ret_val[$k] = isset($this->_criteria[$k]) ? $this->_criteria[$k] : self::UNDEFINED;
				break;
				
				default:
				$this->_criteria[$k] = $value[$idx];
				break;
			}
		}
		return (is_array($ret_val) && sizeof($ret_val) == 1) ? array_pop($ret_val) : $ret_val;
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
			->indexBy('user_id')
			->with('user')->all();
	}
	
	/**
	 * Find alerts for the specific criteia originally provided
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public static function findSpecific(array $criteria)
	{
		unset($criteria['user_id']);
		return Alerts::find()->select('*')
			->where($criteria)
			->andWhere([
				'user_id' => \Yii::$app->user->getId()
			])
			->indexBy('user_id')
			->with('user');
	}
	
	/**
	 * Find alerts for the specific criteia originally provided
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public static function findOwner($author_id, array $criteria)
	{
		$criteria['user_id'] = $author_id;
		$criteria['action'] .= '_my';
		$criteria['remote_type'] = [$criteria['remote_type'], 'any'];
		$criteria['remote_for'] = [$criteria['remote_for'], 'any'];
		$criteria['priority'] = [$criteria['priority'], 'any'];
		$remoteWhere = [];
		if(isset($criteria['remote_id']))
		{
			$remoteWhere = ['or', '`remote_id`='.$criteria['remote_id'], ' `remote_id` IS NULL'];
			unset($criteria['remote_id']);
		}
		return Alerts::find()->select('*')
			->where($criteria)
			->andWhere($remoteWhere)
			->indexBy('user_id')
			->with('user');
	}
	
	/**
	 * This searches for users who are listening for activity 
	 * Based on the remote_type, action and priority
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public static function findListeners(array $criteria)
	{
		unset($criteria['user_id']);
		$criteria['remote_type'] = [$criteria['remote_type'], 'any'];
		$criteria['remote_for'] = [$criteria['remote_for'], 'any'];
		$criteria['action'] = [$criteria['action'], 'any'];
		$criteria['priority'] = [$criteria['priority'], 'any'];
		$remoteWhere = [];
		if(isset($criteria['remote_id']))
		{
			$remoteWhere = ['or', '`remote_id`='.$criteria['remote_id'], ' `remote_id` IS NULL'];
			unset($criteria['remote_id']);
		}
		return Alerts::find()->select('*')
			->orWhere($criteria)
			->andWhere($remoteWhere)
			->andWhere([
				'not', ['user_id' => \Yii::$app->user->getId()]
			])
			->indexBy('user_id')
			->with('user');
	}
	
	/**
	 * Find global listeners for this criteria 
	 * @param array $criteria
	 * @return \yii\db\Query
	 */
	public static function findGlobal(array $criteria)
	{
		$criteria['global'] = 1;
		$criteria['user_id'] = null;
		$criteria['remote_type'] = [$criteria['remote_type'], 'any'];
		$criteria['action'] = [$criteria['action'], 'any'];
		$criteria['priority'] = [$criteria['priority'], 'any'];
		return Alerts::find()->select('*')
			->orWhere($criteria)
			->indexBy('user_id')
			->with('user');
	}
	
	public function sendAlerts($compose, $ownerId)
	{
		$this->_alerts = $this->findAlerts($ownerId);
		$to = [
			'global' => [],
			'individual'=> [],
			'owner' => []
		];
		//Build the addresses
		switch(is_array($this->_alerts ) && !empty($this->_alerts ))
		{
			case true:
			//Organize by global and individual alerts
			foreach($this->_alerts  as $idx=>$alert)
			{
				switch(1)
				{
					case $alert->global == 1:
					/**
					 * Only send global emails based on what the user preferrs in their profile. 
					 * For specific alerts those are based ont he alert settings
					 */
					$to['global'] = array_merge_recursive($to['global'], $this->getAddresses($alert->methods, $this->getUsers(), true));
					break;
					
					case $alert->user->getId() == $this->_originUserId:
					$to['owner'] = array_merge_recursive($to['owner'], $this->getAddresses($alert->methods, [$alert->user]));
					break;
					
					default:
					$to['individual'] = array_merge_recursive($to['individual'], $this->getAddresses($alert->methods, [$alert->user]));
					break;
				}
			}
			foreach($to as $scope=>$types)
			{
				if(!empty($types))
				{
					switch($this->mode)
					{
						case self::SINGLE;
						$this->sendAsSingle($scope, $types, $compose);
						break;
						
						default:
						$this->sendAsBatch($scope, $types, $compose);
						break;
					}
				}
			}
			$this->sendNotifications();
			break;
		}
		$this->reset();
		return true;
	}
	
	/**
	 * Send emails using BCC
	 * @param string $scope Individual, Owner, Global...etc.
	 * @param array $types the types of emails that are being sent out
	 * @param array $compose
	 * @return boolean
	 */
	protected function sendAsBatch($scope, $types, $compose)
	{
		$ret_val = false;
		switch(is_array($types))
		{
			case true:
			$ret_val = true;
			//Send the emails/mobile alerts
			self::$_subject = is_array($compose['subject']) ? \Yii::$app->mailer->render($compose['subject']['view']) : $compose['subject'];
			foreach($types as $type=>$unMappedAddresses)
			{
				$addresses = $this->getAddressNameMap($unMappedAddresses);
				$params = [
					"subject" => self::$_subject,
					"content" => is_array($compose['message'][$type]) ? \Yii::$app->mailer->render($compose['message'][$type]['view']) : $compose['message'][$type]
				];
				switch($scope)
				{
					case 'owner':
					$this->_variables['%bodyDt%'] = 'your';
					$this->_variables['%subjectDt%'] = $this->_variables['%bodyDt%'];
					break;
					
					default:
					$this->_variables['%bodyDt%'] = (($this->criteria('action') == 'create') ? 'a' : 'the');
					$this->_variables['%subjectDt%'] = $this->_variables['%bodyDt%'];
					break;
				}
				$params['title'] = $params['subject'];
				switch($type)
				{
					case 'email':
					$view = ['html' => '@nitm/views/alerts/message/email'];
					$params['content'] = $this->getEmailMessage($params['content']);
					break;
					
					case 'mobile':
					//140 characters to be able to send a single SMS
					
					$params['content'] = $this->getMobileMessage($params['content']);
					$params['title'] = '';
					$view = ['text' => '@nitm/views/alerts/message/mobile'];
					break;
				}
				$params = $this->replaceCommon($params);
				$this->_message = \Yii::$app->mailer->compose($view, $params)->setTo(array_slice($addresses, 0, 1));
				switch($type)
				{
					case 'email':
					$this->_message->setSubject($params['subject']);
					break;
						
					case 'mobile':
					$this->_message->setTextBody($params['content']);
					break;
				}
				switch(sizeof($addresses) >= 1)
				{
					case true:
					$this->_message->setBcc($addresses);
					break;
				}
				$this->send();
				$this->addNotification($this->getMobileMessage($compose['message']['mobile']), $unMappedAddresses);
			}
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Send emails using BCC
	 * @param string $scope Individual, Owner, Global...etc.
	 * @param array $types the types of emails that are being sent out
	 * @param array $compose
	 * @return boolean
	 */
	protected function sendAsSingle($scope, $types, $compose)
	{
		switch(is_array($types))
		{
			case true:
			$ret_val = true;
			//Send the emails/mobile alerts
			self::$_subject = is_array($compose['subject']) ? \Yii::$app->mailer->render($compose['subject']['view']) : $compose['subject'];
			foreach($types as $type=>$unMappedAddresses)
			{
				$addresses = $this->getAddressNameMap($unMappedAddresses);
				foreach($addresses as $name=>$email)
				{
					$address = [$name => $email];
					$params = [
						"subject" => self::$_subject,
						"content" => (is_array($compose['message'][$type]) ? \Yii::$app->mailer->render($compose['message'][$type]['view']) : $compose['message'][$type])
					];
					switch($scope)
					{
						case 'owner':
						$this->_variables['%bodyDt%'] = 'your';
						$this->_variables['%subjectDt%'] = $this->_variables['%bodyDt%'];
						break;
						
						default:
						$this->_variables['%bodyDt%'] = (($this->criteria('action') == 'create') ? 'a' : 'the');
						$this->_variables['%subjectDt%'] = $this->_variables['%bodyDt%'];
						break;
					}
					$params['greeting'] = "Dear ".current($unMappedAddresses)['user']->username.", <br><br>";
					$params['title'] = $params['subject'];
					switch($type)
					{
						case 'email':
						$view = ['html' => '@nitm/views/alerts/message/email'];
						$params['content'] = $this->getEmailMessage($params['content'], current($unMappedAddresses)['user'], $scope);
						break;
						
						case 'mobile':
						//140 characters to be able to send a single SMS
						$params['content'] = $this->getMobileMessage($params['content']);
						$params['title'] = '';
						$view = ['text' => '@nitm/views/alerts/message/mobile'];
						break;
					}
					$params = $this->replaceCommon($params);
					$this->_message = \Yii::$app->mailer->compose($view, $params)->setTo($address);
					switch($type)
					{
						case 'email':
						$this->_message->setSubject($params['subject']);
						break;
						
						case 'mobile':
						$this->_message->setTextBody($params['content']);
						break;
					}
					$this->send();
				}
				$this->addNotification($this->getMobileMessage($compose['message']['mobile']), $unMappedAddresses);
			}
			break;
		}
		return $ret_val;
	}

    /**
     * @return array
     */
    protected function getUsers($options=[])
    {
		$userClass = \Yii::$app->user->identity->className();
		$key = 'alerts.users';
        switch(Cache::exists($key))
		{
			case true:
			$ret_val = Cache::getModelArray($key, $options);
			break;
			
			default:
			$ret_val = $userClass::find()->with('profile')->where(static::$usersWhere)->all();
			Cache::setModelArray($key, $ret_val);
			break;
		}
		return $ret_val;
    }
	
	
	protected function send()
	{
		if(!is_null($this->_message))
		{
			$this->_message->setFrom(\Yii::$app->params['components.alerts']['sender'])
				->send();
			$this->_message = null;
			return true;
		}
		else
			return false;
	}
	
	protected function addNotification($message, $addresses)
	{
		foreach((array)$addresses as $address)
		{
			$userId = $address['user']->getId();
			switch(isset($this->_notifications[$userId]))
			{
				case false:
				$this->_notifications[$userId] = [
					$message,
					$this->criteria('priority'),
					$userId 
				];
				break;
			}
		}
	}
	
	protected function sendNotifications()
	{
		switch(is_array($this->_notifications) && !empty($this->_notifications))
		{
			case true:
			$keys = [
				'message',
				'priority',
				'user_id'
			];
			\nitm\models\Notification::find()->createCommand()->batchInsert(
				\nitm\models\Notification::tableName(), 
				$keys, 
				array_values($this->_notifications)
			)->execute();
			break;
		}
	}
	
	protected function getAddressNameMap($addresses)
	{
		$ret_val = [];
		foreach($addresses as $address)
		{
			unset($address['user']);
			$ret_val[key($address)] = $address[key($address)];
		}
		return $ret_val;
	}
	
	protected function getAddressIdMap($addresses)
	{
		$ret_val = [];
		foreach($addresses as $address)
		{
			$user = $address['user'];
			unset($address['user']);
			$ret_val[key($address)] = $user->getId();
		}
		return $ret_val;
	}
	
	public static function filterMethods($value)
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
	
	protected function replaceCommon($string)
	{
		$string = is_array($string) ? $string : [$string];
		$stringPlaceholders = array_map(function ($value) {
			preg_match_all("/%([\w+\:]+)%/", $value, $matches);
			if(sizeof($matches[1]) >= 1)
				return $matches[1];
			else
				return false;
		}, $string);
		$variables = array_merge($this->defaultVariables(), $this->_variables);
		array_walk($stringPlaceholders, function ($value, $key) use (&$variables) {
			if(!$value)
				return;
			array_walk($value, function ($v) use(&$variables) {
				$v = explode(':', $v);
				$k = $v[0];
				$f = sizeof($v) == 1 ? null : $v[1];
				$realValue = $variables['%'.$k.'%'];
				$variables['%'.implode(':', $v).'%'] = is_null($f) == 1 ? $realValue : $f($realValue);
			});
		});
		$ret_val = str_replace(array_keys($variables), array_values($variables), $string);
		return (is_array($ret_val) && sizeof($ret_val) == 1) ? array_pop($ret_val) : $ret_val;
	}
	
	protected function getMobileMessage($original)
	{
		switch(is_array($original))
		{
			case true:
			$original = \Yii::$app->mailer->render($original['view']);
			break;
		}
		$original = $this->replaceCommon($original);
		//140 characters to be able to send a single SMS
		return strip_tags(strlen($original) <= 140 ? $original : substr($original, 0, 136).'...');
	}
	
	protected function getEmailMessage($original, $user, $scope)
	{
		//140 characters to be able to send a single SMS
		return nl2br($original.$this->getFooter($scope, isset($this->_alerts[$user->getId()]) ? $this->_alerts[$user->getId()]->getAttributes() : null));
	}
	
	private function defaultVariables()
	{
		return [ 
			'%who%' => '@'.\Yii::$app->user->identity->username,
			'%when%' => date('D M jS Y @ h:iA'), 
			'%today%' => date('D M jS Y'),
			'%priority%' => ($this->criteria('priority') == 'any') ? 'Normal' : ucfirst($this->criteria('priority')),
			'%action%' => $this->reportedAction,
			'%remoteFor%' => ucfirst($this->criteria('remote_for')),
			'%remoteType%' => ucfirst($this->criteria('remote_type')),
			'%remoteId%' => $this->criteria('remote_id'),
			'%id%' => $this->criteria('remote_id')
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
		if(in_array('any', $methods))
			unset($methods[array_search('any', $methods)]);
		foreach($users as $user)
		{
			foreach($methods as $method)
			{
				if($user->getId() == \Yii::$app->user->getId())
					continue;
				switch($method)
				{
					case 'email':
					switch(1)
					{
						case ($uri = (is_object($user->profile) ? $user->profile->getAttribute('public_email') : $user->email)) != '':
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
	
	private function getFooter($scope, $alert=null)
	{	
		$alert = is_array($alert) ? $alert : $this->_criteria;
		switch($scope)
		{
			case 'global':
			$footer = "\n\nYou are receiving this becuase of a global alert matching: ";
			break;
			
			default:
			$footer = "\n\nYou are receiving this bcause your alert settings matched: ";
			break;
		}
		if(isset($alert['priority']))
		$footer .= "Priority: <b>".ucfirst($alert['priority'])."</b>, ";
		if(isset($alert['remote_type']))
		$footer .= "Type: <b>".ucfirst($alert['remote_type'])."</b>, ";
		if(isset($alert['remote_id']))
		$footer .= "Id: <b>".$alert['remote_id']."</b>, ";
		if(isset($alert['remote_for']))
		$footer .= "For: <b>".ucfirst($alert['remote_for'])."</b>, ";
		if(isset($alert['action']) || !empty($this->reportedAction))
		$footer .= "and Action <b>".Alerts::properName($this->reportedAction)."</b>";
		$footer .= ". Go ".Html::a("here", \Yii::$app->urlManager->createAbsoluteUrl("/alerts/index"))." to change your alerts";
		$footer .= "\n\nSite: ".Html::a(\Yii::$app->urlManager->createAbsoluteUrl('/'), \Yii::$app->urlManager->createAbsoluteUrl('/index'));
			
		return Html::tag('small', $footer);
	}
}
