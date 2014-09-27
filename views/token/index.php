<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Token $searchModel
 */

$this->title = 'Tokens';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="token-index">
	<?= yii\widgets\Breadcrumbs::widget([
		'links' => $this->params['breadcrumbs']
	]); ?>

	<h1><?= Html::encode($this->title) ?></h1>

	<?php // echo $this->render('_search', ['model' => $searchModel]); ?>

	<p>
		<?= Html::tag("You are editing: ".$this->title) ?>
	</p>
	<p>
		<?= Html::a('Create Token', ['create'], ['class' => 'btn btn-success']) ?>
	</p>
	
	<?= $this->context->legendWidget(); ?>

	<?php
		echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => [
			'id',
			[
				'label' => 'User',
				'attribute' => 'user_id',
				'value' => function ($model) {
					return $model->user()->fullName(true);
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
				"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus())
			];
		},
		"tableOptions" => [
			'class' => 'table table-bordered'
		],
	]); ?>

</div>
