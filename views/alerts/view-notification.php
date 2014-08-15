<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use nitm\models\Notification;

/* @var $this yii\web\View */
/* @var $searchModel nitm\models\search\Notification */
/* @var $dataProvider yii\data\ActiveDataProvider */
$itemOptions = [
	'id' => 'notification'.$model->getId(),
	'class' => 'list-group-item '.\nitm\helpers\Statuses::getListIndicator($model->getPriority())
];
echo Html::tag('div', 
	((isset($isNew) && ($isNew === true) || $model->isNew()) ? \nitm\widgets\activityIndicator\ActivityIndicator::widget() : '').$model->message.
	Html::button(
		Html::tag('span', '&times;', ['aria-hidden' => true]), 
		[
			'class' => 'close',
			'onclick' => '$.post("/alerts/mark-notification-read/'.$model->getId().'", function () {$("#'.$itemOptions['id'].'").remove()});',
			'data-parent' => '#notification'.$model->getId()
		]
	), 
	$itemOptions
);
