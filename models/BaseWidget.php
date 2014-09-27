<?php

namespace nitm\models;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\base\Event;
use yii\db\ActiveRecord;
use nitm\models\Data;
use nitm\models\User;
use nitm\models\security\Fingerprint;
use nitm\interfaces\DataInterface;
use nitm\helpers\Cache;

/**
 * Class BaseWidget
 * @package nitm\models
 *
 */

class BaseWidget extends Data implements DataInterface
{
	use \nitm\traits\Nitm, \nitm\traits\Alerts, \nitm\traits\BaseWidget;
	
	public function beforeSaveEvent($event)
	{
		static::prepareAlerts($event);
	}
	
	public function afterSaveEvent($event)
	{
	}
}
?>