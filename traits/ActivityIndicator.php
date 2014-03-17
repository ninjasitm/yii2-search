<?php

namespace nitm\module\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;

/**
 * Trait ActivityIndicator
 * @package nitm\module\models
 */

trait ActivityIndicator
{	
	public function activityWidget($options)
	{
		return \nitm\widgets\activityIndicator\widget\ActivityIndicator::widget($options);
	}
}
?>