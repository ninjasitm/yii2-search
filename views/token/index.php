<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Token $searchModel
 */

$this->title = 'Edit: ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="token-index">

	<h1><?= Html::encode($this->title) ?></h1>

	<?php // echo $this->render('_search', ['model' => $searchModel]); ?>

	<p>
		<?= Html::tag("You are editing: ".$this->title) ?>
	</p>
	
	<?= \backend\widgets\Legend::widget([
		"legend" => $this->context->legend
	]); ?>

	<?php
		echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => [
			'id',
			[
				'label' => 'User',
				'attribute' => 'userid',
				'value' => function ($user) {
					return \nitm\module\models\User::getFullName($user);
				},
			],
			'token:ntext',
			'added',
			'active:boolean',
			'level',
			'revoked:boolean',
			'revoked_on',

			['class' => 'yii\grid\ActionColumn'],
		],
		'rowOptions' => function ($model, $key, $index, $grid)
		{
			return [
						"class" => $this->context->getStatusIndicator($model)
					];
		},
		"tableOptions" => [
			'class' => 'table table-bordered'
		],
	]); ?>

</div>
