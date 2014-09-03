<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use nitm\models\Notification;
use nitm\helpers\Icon;

/* @var $this yii\web\View */
/* @var $searchModel nitm\models\search\Notification */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Alerts');
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if(isset($contentOnly) && $contentOnly === false || !isset($contentOnly)): ?>
<div id='notification-index' class="absolute full full-width full-height" role="notificationListForm">
<div class="wrapper">
    <h1>
		<?= 
			Html::encode($this->title).'&nbsp'.
			Html::a(
				Icon::show('refresh'), 
				\Yii::$app->urlManager->createUrl(['/alerts/notifications', '__format' => 'html', '__contentOnly' => true]),
				[
					'role' => 'dynamicValue',
					'data-id' => 'notification-list-container',
					'data-type' => 'html',
					'class' => 'pull-right'
				]); 
		?>
	</h1>
<?php endif; ?>
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
		'itemView' => function ($model, $key, $index, $widget) {
			return $this->render('view-notification', [
				'model' => $model
			]);
		},
		"layout" => "{summary}\n{items}{pager}",
		'itemOptions' => [
			'class' => 'item',
		],
		'options' => [
			'id' => 'notification-list-container',
			'tag' => 'div',
			'class' => 'list-group',
			'role' => 'notificationList'
		],
		'pager' => [
			'class' => \nitm\widgets\ias\ScrollPager::className(),
			'overflowContainer' => '#notification-index',
			'container' => '#notification-list-container',
			'item' => ".item",
			'negativeMargin' => 75,
			'noneLeftText' => 'No more notifications',
			'triggerText' => 'More notifications',
		]
    ]); ?>
<?php if(isset($contentOnly) && $contentOnly === false): ?>
</div>
</div>
<?php endif; ?>
