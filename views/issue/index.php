<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use yii\bootstrap\Modal;
use nitm\models\Issues;

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
?>
<div class="issues-index" id="wrapper issue-tracker<?=$parentId?>">
	<h3><?= Html::encode($title) ?></h3>
	
<?php
	$issuesTabs = Html::tag('ul', 
		Html::tag('li', 
			Html::a('Open '.Html::tag('span', $dataProviderOpen->getCount(), ['class' => 'badge']), '#open-issues', ['data-toggle' => 'tab']), [
				'class' => 'tab-pane active', 
				'id' => 'open-issues-tab'
			]). 
		Html::tag('li', 
			Html::a('Closed '.Html::tag('span', $dataProviderClosed->getCount(), ['class' => 'badge']), '#closed-issues', ['data-toggle' => 'tab']), [
				'class' => 'tab-pane', 
				'id' => 'closed-issues-tab'
			]). 
		Html::tag('li', 
			Html::a('Create Issue ', '#issues-form', [
				'data-toggle' => 'tab', 
				'class' => 'btn btn-success'
			]), ['class' => 'tab-pane']). 
		Html::tag('li', 
			Html::a('Update Issue ', '#issues-update-form', [
				'data-toggle' => 'tab', 
				'id' => 'issues-update-form-tab', 
				'class' => 'hidden'
			]), ['class' => 'tab-pane']). 
		Html::tag('li', 
			Html::a('', '#', [
				'data-toggle' => 'tab',
				'id' => 'issues-alerts'
			]), [
				'class' => 'tab-pane'
			]),
		[
			'class' => 'nav nav-tabs'
		]
	);
	$viewOptions = [
		'enableComments' => $enableComments
	];
	$issuesOpen = Html::tag('div', '', ['id' => 'alert']).getIssues($dataProviderOpen, $viewOptions);
	$issuesClosed = Html::tag('div', '', ['id' => 'alert']).getIssues($dataProviderClosed, $viewOptions);
	$issuesForm =  $this->render('create', [
		'model' => new Issues,
		'parentId' => $parentId,
		'parentType' => $parentType,
		'enableComments' => $enableComments
	]);
	$issues = Html::tag('div',
		Html::tag('div', $issuesOpen, ['class' => 'tab-pane fade in active', 'id' => 'open-issues']).
		Html::tag('div', $issuesClosed, ['class' => 'tab-pane fade in', 'id' => 'closed-issues']).
		Html::tag('div', $issuesForm, ['class' => 'tab-pane fade in', 'id' => 'issues-form']).
		Html::tag('div', '', ['class' => 'tab-pane fade in', 'id' => 'issues-update-form']),
		['class' => 'tab-content']
	);
	echo $issuesTabs.$issues;
?>
</div>

<script type="text/javascript">
$nitm.addOnLoadEvent(function () {
	$nitm.issueTracker.init("issue-tracker<?=$parentId?>");
	<?php if(\Yii::$app->request->isAjax): ?>
	$nitm.tools.initVisibility("issue-tracker<?=$parentId?>");
	<?php endif ?>
});
</script>

<?php 
	function getIssues($dataProvider, $options=[])
	{
		global $enableComments, $repliesModel;
		return ListView::widget([
			'options' => [
				'id' => 'issues'
			],
			'dataProvider' => $dataProvider,
			'itemOptions' => ['class' => 'item'],
			'itemView' => function ($model, $key, $index, $widget) use($options){
				$viewOptions = array_merge(['model' => $model], $options);
				return $widget->render('@nitm/views/issue/view', $viewOptions);
			},
			'pager' => ['class' => \kop\y2sp\ScrollPager::className()]
		
		]);
	}
?>
