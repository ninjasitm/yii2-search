<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Issues $searchModel
 */

$this->title = Yii::t('app', 'Issues: '.ucfirst($parentType).": ".$parentId);
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
<?php
	$issuesTabs = Html::tag('ul', 
		Html::tag('li', Html::a('Open', '#open-issues', ['data-toggle' => 'tab']), ['class' => 'tab-pane active']). 
		Html::tag('li', Html::a('Closed', '#closed-issues', ['data-toggle' => 'tab']), ['class' => 'tab-pane']),
		[
			'class' => 'nav nav-tabs'
		]
	);
	$issuesOpen = getIssues($dataProviderOpen);
	$issuesClosed = getIssues($dataProviderClosed);
	$issues = Html::tag('div',
		Html::tag('div', $issuesOpen, ['class' => 'tab-pane fade in active', 'id' => 'open-issues']).
		Html::tag('div', $issuesClosed, ['class' => 'tab-pane fade in', 'id' => 'closed-issues']),
		['class' => 'tab-content']
	);
	echo $issuesTabs.$issues;
?>
	<?php
		if(isset($modal))
		{
			echo $modal;
		}
	?>

</div>

<?php 
	function getIssues($dataProvider)
	{
		return ListView::widget([
			'options' => [
				'id' => 'issues'
			],
			'dataProvider' => $dataProvider,
			'itemOptions' => ['class' => 'item'],
			'itemView' => function ($model, $key, $index, $widget) {
					return $widget->render('@nitm/views/issue/view',['model' => $model]);
			},
		
		]);
	}
?>
