<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var frontend\models\search\Refunds $searchModel
 */

$this->title = 'Requests';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-md-8 col-lg-8">
	<h1><?= Html::encode($this->title) ?></h1>
	<?php
		echo $this->render("data", [
				'dataProvider' => $dataProvider,
				'searchModel' => $searchModel,
			]
		); 
	?>
</div>
<div class="col-md-4 col-lg-4">
	<?php
		echo @$this->render('_search', array("data" => array(), 'model' => $model)); 
	?>
</div>

<?= $this->context->modalWidget([
	'size' => 'medium',
	'options' => [
		'id' => 'view',
		"style" => "z-index: 1043"
	],
	'contentOptions' => [
		"class" => "modal-full"
	],
	'dialogOptions' => [
		"class" => "modal-full"
	]
]); ?>

<?= $this->context->modalWidget([
	'size' => 'x-large',
	'options' => [
		'id' => 'form',
		"style" => "z-index: 1041",
	],
	'contentOptions' => [
		"class" => "modal-full"
	],
	'dialogOptions' => [
		"class" => "modal-full"
	]
]); ?>

<?= $this->context->revisionsModalWidget([
		'options' => [
			'style' => 'z-index: 1042',
		]
]); ?>

<?= $this->context->issueModalWidget([
		'options' => [
			'id' => 'issue-tracker-modal',
			'style' => 'z-index: 1045',
		]
]); ?>

<?= $this->context->replyModalWidget(); ?>