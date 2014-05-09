<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use nitm\widgets\issueTracker\IssueTrackerModal;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Issues $searchModel
 */

$title = Yii::t('app', 'Issues: '.ucfirst($parentType).": ".$parentId);
switch(\Yii::$app->request->isAjax)
{
	case true:
	$this->title = $title;
	break;
}
$this->params['breadcrumbs'][] = $title;
if($useModal == true) {
	$modalOptions =[
		'class' => 'btn btn-success',
		'data-toggle' => 'modal',
		'data-target' => '#issue-tracker-modal-form'
	];
} else {
	$modalOptions = ['class' => 'btn btn-success'];
}
?>
<div class="issues-index" id="issue-tracker<?=$parentId?>">

    <h1><?= Html::encode($title) ?></h1>

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
		Html::tag('li', Html::a('Open '.Html::tag('span', $dataProviderOpen->getCount(), ['class' => 'badge']), '#open-issues', ['data-toggle' => 'tab']), ['class' => 'tab-pane active']). 
		Html::tag('li', Html::a('Closed '.Html::tag('span', $dataProviderClosed->getCount(), ['class' => 'badge']), '#closed-issues', ['data-toggle' => 'tab']), ['class' => 'tab-pane']),
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

<script type="text/javascript">
$nitm.addOnLoadEvent(function () {
	$nitm.issueTracker.init("issue-tracker<?=$parentId?>");
});
</script>

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
