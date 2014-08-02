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
	
	protected static $is = 'alerts';
	protected static $_subject;
	protected static $_body;
	
	protected $_criteria = [];
	protected $_originUserId;
	protected $_message;
	
	private $_prepared = false;
	private $_variables = [];
	private $_alerts;
	
	const BATCH = 'batch';
	const SINGLE = 'single';
	
	public static function supportedMethods()
	{
		return [
			'any' => 'Any',
			'email' => 'Email',
			'mobile' => 'Mobile'
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
		return Alerts::find()->select('*')
			->where($criteria)
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
		return Alerts::find()->select('*')
			->orWhere($anyRemoteFor)
			->orWhere($anyPriority)
			->orWhere($criteria)
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
		$criteria = array_intersect_key($criteria, [
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
		return Alerts::find()->select('*')
			->orWhere($criteria)
			->orWhere($anyRemoteFor)
			->orWhere($anyPriority)
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
			self::$_subject = $this->replaceCommon(is_array($compose['subject']) ? \Yii::$app->mailer->render($compose['subject']['view']) : $compose['subject']);
			foreach($types as $type=>$addresses)
			{
				$params = [
					"content" => $this->replaceCommon(is_array($compose['message'][$type]) ? \Yii::$app->mailer->render($compose['message'][$type]['view']) : $compose['message'][$type])
				];
				switch($scope)
				{
					case 'owner':
					$subject = 'Your '.self::$_subject;
					$params['content'] = (($this->criteria('action') == 'create') ? '' : 'Your ').$params['content'];
					$params['greeting'] = "Dear ".current($addresses)['user']->username.", <br><br>";
					break;
					
					default:
					$subject = (($this->criteria('action') == 'create') ? 'A' : 'The').' '.self::$_subject;
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
					$params['content'] = substr(self::$_body, 0, 140);
					$params['title'] = '';
					$view = ['text' => '@nitm/views/alerts/message/mobile'];
					break;
				}
				$addresses = $this->getAddressNameMap($addresses);
				$this->_message = \Yii::$app->mailer->compose($view, $params)->setTo(array_slice($addresses, 0, 1));
				switch($type)
				{
					case 'email':
					$this->_message->setSubject($subject);
					break;
				}
				switch(sizeof($addresses) >= 1)
				{
					case true:
					$this->_message->setBcc($addresses);
					break;
				}
				$this->send();
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
			self::$_subject = $this->replaceCommon(is_array($compose['subject']) ? \Yii::$app->mailer->render($compose['subject']['view']) : $compose['subject']);
			foreach($types as $type=>$unMappedAddresses)
			{
				$addresses = $this->getAddressNameMap($unMappedAddresses);
				foreach($addresses as $name=>$email)
				{
					$address = [$name => $email];
					$params = [
						"content" => $this->replaceCommon(is_array($compose['message'][$type]) ? \Yii::$app->mailer->render($compose['message'][$type]['view']) : $compose['message'][$type])
					];
					switch($scope)
					{
						case 'owner':
						$subject = 'Your '.self::$_subject;
						$params['content'] = (($this->criteria('action') == 'create') ? '' : 'Your ').$params['content'];
						break;
						
						default:
						$subject = (($this->criteria('action') == 'create') ? 'A' : 'The').' '.self::$_subject;
						$params['content'] = (($this->criteria('action') == 'create') ? '' : 'The ').$params['content'];
						break;
					}
					$params['greeting'] = "Dear ".current($unMappedAddresses)['user']->username.", <br><br>";
					$params['title'] = $subject;
					switch($type)
					{
						case 'email':
						$view = ['html' => '@nitm/views/alerts/message/email'];
						$params['content'] = nl2br($params['content'].$this->getFooter($scope, $this->_alerts[current($unMappedAddresses)['user']->getId()]->getAttributes()));
						break;
						
						case 'mobile':
						//140 characters to be able to send a single SMS
						$params['content'] = substr(self::$_body, 0, 140);
						$params['title'] = '';
						$view = ['text' => '@nitm/views/alerts/message/mobile'];
						break;
					}
					$this->_message = \Yii::$app->mailer->compose($view, $params)->setTo($address);
					switch($type)
					{
						case 'email':
						$this->_message->setSubject($subject);
						break;
					}
					$this->send();
				}
			}
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
			'%remoteId%' => $this->_criteria['remote_id'],
			'%id%' => $this->_criteria['remote_id']
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
