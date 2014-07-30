<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use nitm\models\Alerts;

/* @var $this yii\web\View */
/* @var $searchModel nitm\models\search\Alerts */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Alerts');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="alerts-index" role="alertsListForm">
<div class="wrapper">

    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render("_form", ["model" => new Alerts]);  ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
		'itemView' => function ($model, $key, $index, $widget) {
			$widget->itemOptions['id'] = 'alert'.$model->getId();
			$widget->itemOptions['class'] =  \nitm\helpers\Statuses::getListIndicator($model->getPriority());
			return $this->render('view', ['model' => $model, 'notAsListItem' => true]);
		},
		'summary' => false,
		"layout" => "{summary}\n{items}",
		'itemOptions' => [
			'tag' => 'li'
		],
		'options' => [
			'id' => 'alerts-list-container',
			'tag' => 'ul',
			'class' => 'list-group',
			'role' => 'alertsList'
		]
    ]); ?>
</div>
</div>
