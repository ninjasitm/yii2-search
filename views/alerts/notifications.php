<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use nitm\models\Notification;

/* @var $this yii\web\View */
/* @var $searchModel nitm\models\search\Notification */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Alerts');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notification-index" role="notificationListForm">
<div class="wrapper">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
		'itemView' => function ($model, $key, $index, $widget) {
			return $this->render('view-notification', [
				'model' => $model
			]);
		},
		'summary' => false,
		"layout" => "{summary}\n{items}{pager}",
		'itemOptions' => [
			'tag' => false,
		],
		'options' => [
			'id' => 'notification-list-container',
			'tag' => 'div',
			'class' => 'list-group absolute full-height',
			'style' => 'margin-top: 70px; padding-bottom: 120px',
			'role' => 'notificationList'
		],
		'pager' => [
			'class' => \kop\y2sp\ScrollPager::className(),
			'container' => '#notification-list-container',
			'eventOnScroll' => "function (){console.log('scroll');}",
			'item' => ".list-group-item",
			'negativeMargin' => 250,
			'delay' => 1000,
			'triggerText' => 'More notifications',
			'noneLeftText' => 'No More notifications'
		]
    ]); ?>
</div>
</div>
