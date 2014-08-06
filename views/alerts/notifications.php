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
			$widget->itemOptions['id'] = 'notification'.$model->getId();
			$widget->itemOptions['class'] =  'alert '.\nitm\helpers\Statuses::getListIndicator($model->getPriority());
			return Html::tag('div', 
				$model->message.
				Html::button(
				Html::tag('span', '&times;', ['aria-hidden' => true]), [
					'class' => 'close',
					'onclick' => '$.post("/alerts/mark-notification-read/'.$model->getId().'");',
					'role' => 'removeParent',
					'data-parent' => 'li'
				])
			);
		},
		'summary' => false,
		"layout" => "{summary}\n{items}",
		'itemOptions' => [
			'tag' => 'li'
		],
		'options' => [
			'id' => 'notification-list-container',
			'tag' => 'ul',
			'class' => 'list-group',
			'role' => 'notificationList'
		]
    ]); ?>
</div>
</div>
