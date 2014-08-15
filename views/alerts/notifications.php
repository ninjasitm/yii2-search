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
		"layout" => "{summary}\n{items}",
		'itemOptions' => [
			'tag' => false,
		],
		'options' => [
			'id' => 'notification-list-container',
			'tag' => 'div',
			'class' => 'list-group',
			'role' => 'notificationList'
		]
    ]); ?>
</div>
</div>
