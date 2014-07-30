<?php

namespace nitm\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;
use nitm\models\Alerts as AlertModel;

/**
 * trait Alerts
 * May merge into nitm Module
 * @package nitm\module
 */

trait Alerts
{
	protected $_alerts;
	
	protected function prepareAlerts()
	{
		$this->_alerts = new AlertModel;
		$alerts = [];
		$alerts['remote_id'] = null;
		$alerts['remote_type'] = $this->isWhat();
		$alerts['remote_for'] = 'any';
		$alerts['action'] = $this->getIsNewRecord() ? 'create' : 'update';
		$alerts['priority'] = 'any';
		$this->_alerts->prepare($alerts);
	}
	
	/**
	 * Process the alerts according to $message and $parameters
	 * @param array $message = the subject and mobile/email messages:
	 * [
	 *		'subject' => String
	 *		'message' => [
	 *			'email' => The email message
	 *			'mobile' => The mobile/text message
	 *		]
	 * ]
	 * @param array $options = an array of parameters to be used during alert creation
	 */
	protected function processAlerts($options=[])
	{
		$this->_alerts->criteria('action', isset($options['action']) ? $options['action'] : $this->_alerts->criteria('action'));
		switch(!$this->_alerts->criteria('action'))
		{
			case false:
			$this->_alerts->criteria('remote_for', isset($options['for']) ? $options['for'] : 'any');
			$this->_alerts->criteria('remote_id', isset($options['id']) ? $options['id'] : null);
			$this->_alerts->criteria('priority', isset($options['priority']) ? $options['priority'] : 'any');
			switch($this->_alerts->isPrepared())
			{
				case true:
				//First check to see if this specific alert exits
				$this->_alerts->sendAlerts($options, $this->_alerts->findAlerts($options['owner_id']));
				break;
				
				default:
				throw new \yii\base\Exception("You need to call \$this->prepareAlerts() before calling \$this->processAlerts");
				break;
			}
			break;
			
			default:
			throw new \yii\base\Exception("Need an action to process the alert");
			break;
		}
	}
}
?>