<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Issues $searchModel
 */

$this->title = Yii::t('app', 'Issues');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="issues-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
	<?= Html::a(Yii::t('app', 'Create {modelClass}', [
			'modelClass' => 'Issues',
		]), 
		['/issue/create/'.$parentType."/".$parentId], 
		[
			'class' => 'btn btn-success',
			'data-toggle' => 'modal',
			'data-target' => '#issue-tracker-modal'
		]
	) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'parent_id',
            'parent_type',
            'notes:ntext',
            'resolved',
            // 'created_at',
            // 'author',
            // 'closed_by',
            // 'resolved_by',
            // 'resolved_on',
            // 'closed',
            // 'closed_on',
            // 'duplicate',
            // 'duplicate_id',

            [
				'class' => 'yii\grid\ActionColumn',
				"urlCreator" => function ($action, $model) {
						$params = [
								"/".$model->isWhat().'/'.$action."/".$model->id
						];
						return \yii\helpers\Url::toRoute($params);
				}
			],
        ],
    ]); ?>

</div>
