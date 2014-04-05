<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use kartik\icons\Icon;

/**
 * @var yii\web\View $this
 * @var nitm\module\models\Revisions $model
 */

$this->title = "Revision for ".$model->parent_type." by ".\Yii::$app->userMeta->getFullName(true, $model->author);
$this->params['breadcrumbs'][] = ['label' => 'Revisions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="revisions-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Icon::show('reply'), ['restore', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Icon::show('trash-o'), ['delete', 'author' => $model->author, 'parent_type' => $model->parent_type, 'parent_id' => $model->parent_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => new ArrayDataProvider([
			'allModels' => [$model],
			'pagination' => false,
		]),
        'columns' => [
            'created_at:datetime',
            'parent_type',
            'parent_id',
        ],
		'afterRow' => function ($model, $key, $index, $grid) {
			switch($model->getAttribute('data') != null)
			{
				case true:
				$data = json_decode($model->getAttribute('data'), true);
				switch(is_array($data))
				{
					case true:
					foreach($data as $title => $value)
					{
						$ret_val = Html::tag('div',
							Html::tag('h3', ucfirst($title)).
							Html::tag('div', urldecode($value)),
							[
								'class' => 'well'
							]
						);
					}
					break;
				}
				break;
				
				default:
				$ret_val = $model->getAttribute('data');
				break;
			}
			return Html::tag('tr', 
				Html::tag(
					'td', 
					$ret_val, 
					[
						'colspan' => 6, 
						'rowspan' => 1
					]
				)
			);
		}
    ]) ?>

</div>
