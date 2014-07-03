<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use yii\bootstrap\Modal;
use yii\bootstrap\Tabs;
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
$baseModel = new Issues;
?>	
<?php
	$viewOptions = [
		'enableComments' => $enableComments
	];
	$issuesOpen = Html::tag('div', '', ['id' => 'alert'.$parentId]).getIssues($dataProviderOpen, $viewOptions);
	$issuesClosed = Html::tag('div', '', ['id' => 'alert'.$parentId]).Html::tag('div', '', ['class' => 'issues']);
	$issuesForm =  $this->render('create', [
		'model' => new Issues,
		'parentId' => $parentId,
		'parentType' => $parentType,
		'enableComments' => $enableComments
	]);
?>
<div class="issues-index wrapper" id="issue-tracker<?=$parentId?>">
	<h3><?= Html::encode($title) ?></h3>
	<?=
		Tabs::widget([
			'encodeLabels' => false,
			'items' => [
				[
					'label' => 'Open '.Html::tag('span', $dataProviderOpen->getCount(), ['class' => 'badge']),
					'content' =>Html::tag('div', $issuesOpen,
						[
							'id' => 'open-issues-content'.$parentId,
						]
					),
					'options' => [
						'id' => 'open-issues'.$parentId
					],
					'headerOptions' => [
						'id' => 'open-issues-tab'.$parentId
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#open-issues'.$parentId,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId, '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'open-issues-link'.$parentId
					]
				],
				[
					'label' => 'Closed '.Html::tag('span', $dataProviderClosed->getCount(), ['class' => 'badge']),
					'content' => Html::tag('div', $issuesClosed,
						[
							'id' => 'closed-issues-content'.$parentId,
						]
					),
					'options' => [
						'id' => 'closed-issues'.$parentId
					],
					'headerOptions' => [
						'id' => 'closed-issues-tab'.$parentId
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#closed-issues'.$parentId,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId.'/closed', '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'closed-issues-link'.$parentId
					]
				],
				[
					'label' => 'Create Issue ',
					'content' => Html::tag('div', $issuesForm,
						[
							'id' => 'create-issue'.$parentId,
						]
					),
					'options' => [
						'id' => 'issues-form'.$parentId
					],
					'headerOptions' => [
						'id' => 'issues-form-tab'.$parentId,
						'class' => 'bg-success'
					],
					'linkOptions' => [ 
						'id' => 'issues-form-link'.$parentId,
					]
				],
				[
					'label' => 'Update Issue ',
					'content' => Html::tag('div', '',
						[
							'id' => 'update-issue'.$parentId,
							'style' => 'display:none'
						]
					),
					'options' => [
						'id' => 'issues-update-form'.$parentId
					],
					'headerOptions' => [
						'id' => 'issues-update-form-tab'.$parentId,
						'class' => 'hidden'
					],
					'linkOptions' => [
						'id' => 'issues-update-form-link'.$parentId, 
					]
				],
				[
					'label' => Html::tag('div', '',
						[
							'id' => 'issues-alerts-message'.$parentId,
						]
					),
					'content' => '',
					'headerOptions' => [
						'id' => 'issues-alerts-tab'.$parentId,
					],
					'linkOptions' => [
						'id' => 'issues-alerts-link'.$parentId, 
					]
				]
			]
		]);
	?>
</div>

<script type="text/javascript">
<?php if(\Yii::$app->request->isAjax): ?>
$nitm.onModuleLoad('issueTracker', function () {
	$nitm.module('issueTracker').init("issue-tracker<?=$parentId?>");
}, 'issueTrackerIndex');
$nitm.onModuleLoad('tools', function () {
	$nitm.module('tools').initVisibility("issue-tracker<?=$parentId?>");
	$nitm.module('tools').initDynamicValue("issue-tracker<?=$parentId?>");
}, 'issueTrackerIndex');
<?php endif ?>
</script>

<?php 
	function getIssues($dataProvider, $options=[])
	{
		global $parentId;
		return ListView::widget([
			'options' => [
				'id' => 'issues'.$parentId,
				'class' => 'col-md-12 col-lg-12'
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
