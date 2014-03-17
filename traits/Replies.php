<?php

namespace nitm\module\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;

/**
 * Class Replies
 * @package nitm\module\models
 */

trait Replies
{	
	public function replyWidget($constrain)
	{
		return \nitm\widgets\replies\widget\Replies::widget([
			'constrain' => $constrain
		]);
	}
	
	public function replyFormWidget($constrain)
	{
		return \nitm\widgets\replies\widget\RepliesForm::widget([
			'constrain' => $constrain
		]);
	}
}
?>