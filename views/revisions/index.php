<?php

use yii\helpers\Html;
use yii\grid\GridView;
use kartik\icons\Icon;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var nitm\module\models\search\Revisions $searchModel
 */

$this->title = 'Revisions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="revisions-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?php //echo Html::a('Create Revisions', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
				'attribute' => 'author',
				'label' => 'Author',
				'format' => 'html',
				'value' => function ($model, $index, $widget) {
					return Html::a($model->authorUser->getFullName(true, $model->authorUser), \Yii::$app->urlManager->createUrl(['', 'Revisions[author]' => $model->authorUser->id]));
				}
			],
            'created_at',
            'parent_type',
            // 'parent_id',
			[
				'class' => 'yii\grid\ActionColumn',
				'buttons' => [
					'view' => function ($url, $model) {
						return Html::a(Icon::show('eye'), $url."?__format=modal", [
							'title' => Yii::t('yii', 'View Revision'),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-pjax' => '0',
							'data-toggle' => 'modal',
							'data-target' => '#revisionsViewModal'
						]);
					},
					'restore' => function ($url, $model) {
						return Html::a(Icon::show('refresh'), $url, [
							'title' => Yii::t('yii', 'Restore Revision'),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					},
					'delete' => function ($url, $model) {
						return Html::a(Icon::show('delete'), $url, [
							'title' => Yii::t('yii', 'Delete Revision'),
							'class' => 'fa-2x',
							'role' => 'dynamicAction',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					}
				],
				'template' => "{view} {restore} {delete}",
				'urlCreator' => function($action, $model, $key, $index) {
					return $this->context->id.'/'.$action.'/'.$model->getId();
				},
				'options' => [
					'rowspan' => 3
				]
			],
        ],
    ]); ?>

</div>

<div role="dialog" class="col-md-6 col-lg-6 col-sm-12 col-xs-12 col-md-offset-3 col-lg-offset-3 modal fade" id="revisionsViewModal" style="z-index: 10001">
<div class="modal-content modal-content-fixed"></div>
</div><!-- /.modal -->
