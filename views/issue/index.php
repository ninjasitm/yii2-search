<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Issues $searchModel
 */

$this->title = Yii::t('app', 'Issues');
$this->params['breadcrumbs'][] = $this->title;
if($useModal == true) {
	$modalOptions =[
		'class' => 'btn btn-success',
		'data-toggle' => 'modal',
		'data-target' => '#issue-tracker-modal'
	];
} else {
	$modalOptions = ['class' => 'btn btn-success'];
}
?>
<div class="issues-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
	<?= Html::a(Yii::t('app', 'Create {modelClass}', [
			'modelClass' => 'Issue',
		]), 
		['/issue/form/'.$parentType."/".$parentId, '__format' => 'modal'],
		$modalOptions 
	) ?>
    </p>

	<?php echo ListView::widget([
		'dataProvider' => $dataProvider,
		'itemOptions' => ['class' => 'item'],
		'itemView' => function ($model, $key, $index, $widget) {
				return $this->render('view',['model' => $model]);
		},
	
	]); ?>



    <?php /*GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
		'rowOptions' => function ($model, $key, $index, $grid)
		{
			return [
				"class" => $this->context->getStatusIndicator($model),
				'id' => 'refund'.$model->id
			];
		},
		"tableOptions" => [
				'class' => 'table'
		],
        'columns' => [
            //'id',
            [
				'attribute' => 'parent_type',
				'label' => 'Type',
				'value' => function ($model)
				{
					return ucfirst($model->parent_type);
				}
			],
            'resolved:boolean',
            // 'created_at',
            // 'author',
            // 'closed_by',
            // 'resolved_by',
            // 'resolved_on',
            'closed:boolean',
            // 'closed_on',
            'duplicate:boolean',
            // 'duplicate_id',
			[
				'class' => 'yii\grid\ActionColumn',
				'buttons' => [
					'form/edit' => function ($url, $model) {
						return Html::a(Icon::show('pencil'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']), [
							'title' => Yii::t('yii', 'Edit '),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-toggle' => 'modal',
							'data-target' => '#form'
						]);
					},
					'close' => function ($url, $model) {
						return Html::a(Icon::show($model->closed ? 'unlock-alt' : 'lock'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']), [
							'title' => Yii::t('yii', ($model->closed ? 'Open' : 'Close').' '),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					},
					'duplicate' => function ($url, $model) {
						return Html::a(Icon::show($model->duplicate ? 'unlock-alt' : 'lock'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']), [
							'title' => Yii::t('yii', ($model->duplicate ? 'Flag as not duplicate' : 'flag as duplicate').' '),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					},
					'resolve' => function ($url, $model) {
						return Html::a(Icon::show($model->resolved ? 'circle' : 'check-circle'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']), [
							'title' => Yii::t('yii', ($model->resolved ? 'Unresolve' : 'Resolve').' '),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					}
				],
				'template' => "{form/edit} {resolve} {duplicate} {close}",
				"urlCreator" => function ($action, $model) {
						$params = [
								"/issue/".$action."/".$model->id
						];
						return \yii\helpers\Url::toRoute($params);
				},
				'options' => [
					'rowspan' => 2
				]
			],
        ],
		'rowOptions' => function ($model, $key, $index, $grid)
		{
			return [
				"class" => $this->context->getStatusIndicator($model),
			];
		},
		'afterRow' => function ($model, $key, $index, $grid)
		{
			return Html::tag('tr', 
				Html::tag('td',
					$model->notes,
					[
						'colspan' => 5
					]
					),
				[
					"class" => $this->context->getStatusIndicator($model)
				]);
		}
    ]);*/ ?>

</div>
