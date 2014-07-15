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

$uniqid = uniqid();
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
	$issuesOpen = Html::tag('div', '', ['id' => 'alert'.$parentId]).$this->render('issues', [
		'searchModel' => $searchModel,
		"options" => $viewOptions, 
		'dataProvider' => $dataProviderOpen, 
		'filterType' => 'open',
		'enableComments' => $enableComments,
		'parentType' => $parentType,
		'parentId' => $parentId
	]);
	$issuesClosed = Html::tag('div', '', ['id' => 'alert'.$parentId]).Html::tag('div', '', ['class' => 'issues']);
	$issuesDuplicate = Html::tag('div', '', ['id' => 'alert'.$parentId]).Html::tag('div', '', ['class' => 'issues']);
	$issuesResolved = Html::tag('div', '', ['id' => 'alert'.$parentId]).Html::tag('div', '', ['class' => 'issues']);
	$issuesUnResolved = Html::tag('div', '', ['id' => 'alert'.$parentId]).Html::tag('div', '', ['class' => 'issues']);
	$issuesForm =  $this->render('create', [
		'model' => new Issues,
		'parentId' => $parentId,
		'parentType' => $parentType,
		'enableComments' => $enableComments
	]);
?>
<div class="issues-index wrapper" id="issue-tracker<?=$uniqid?>">
	<h3><?= Html::encode($title) ?></h3>
	<?=
		Tabs::widget([
			'options' => [
				'id' => 'issue-tracker'.uniqid()
			],
			'encodeLabels' => false,
			'items' => [
				[
					'label' => 'Open '.Html::tag('span', $dataProviderOpen->getCount(), ['class' => 'badge']),
					'content' =>Html::tag('div', $issuesOpen,
						[
							'id' => 'open-issues-content'.$uniqid,
						]
					),
					'options' => [
						'id' => 'open-issues'.$uniqid,
					],
					'headerOptions' => [
						'id' => 'open-issues-tab'.$uniqid
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#open-issues'.$uniqid,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId."/open", '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'open-issues-link'.$uniqid
					]
				],
				[
					'label' => 'Closed '.Html::tag('span', $dataProviderClosed->getCount(), ['class' => 'badge']),
					'content' => Html::tag('div', $issuesClosed,
						[
							'id' => 'closed-issues-content'.$uniqid,
						]
					),
					'options' => [
						'id' => 'closed-issues'.$uniqid,
					],
					'headerOptions' => [
						'id' => 'closed-issues-tab'.$uniqid
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#closed-issues'.$uniqid,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId.'/closed', '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'closed-issues-link'.$uniqid
					]
				],
				[
					'label' => 'Resolved '.Html::tag('span', $dataProviderResolved->getCount(), ['class' => 'badge']),
					'content' => Html::tag('div', $issuesResolved,
						[
							'id' => 'resolved-issues-content'.$uniqid,
						]
					),
					'options' => [
						'id' => 'resolved-issues'.$uniqid,
					],
					'headerOptions' => [
						'id' => 'resolved-issues-tab'.$uniqid
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#resolved-issues'.$uniqid,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId.'/resolved', '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'resolved-issues-link'.$uniqid
					]
				],
				[
					'label' => 'Un-Resolved '.Html::tag('span', $dataProviderUnresolved->getCount(), ['class' => 'badge']),
					'content' => Html::tag('div', $issuesUnResolved,
						[
							'id' => 'unresolved-issues-content'.$uniqid,
						]
					),
					'options' => [
						'id' => 'unresolved-issues'.$uniqid,
					],
					'headerOptions' => [
						'id' => 'unresolved-issues-tab'.$uniqid
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#unresolved-issues'.$uniqid,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId.'/unresolved', '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'unresolved-issues-link'.$uniqid
					]
				],
				[
					'label' => 'Duplicate '.Html::tag('span', $dataProviderDuplicate->getCount(), ['class' => 'badge']),
					'content' => Html::tag('div', $issuesDuplicate,
						[
							'id' => 'duplicate-issues-content'.$uniqid,
						]
					),
					'options' => [
						'id' => 'duplicate-issues'.$uniqid,
					],
					'headerOptions' => [
						'id' => 'duplicate-issues-tab'.$uniqid
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#duplicate-issues'.$uniqid,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId.'/duplicate', '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
						'id' => 'duplicate-issues-link'.$uniqid
					]
				],
				[
					'label' => 'Create Issue ',
					'content' => Html::tag('div', $issuesForm,
						[
							'id' => 'create-issue'.$uniqid,
						]
					),
					'options' => [
						'id' => 'issues-form'.$uniqid
					],
					'headerOptions' => [
						'id' => 'issues-form-tab'.$uniqid,
						'class' => 'bg-success'
					],
					'linkOptions' => [ 
						'id' => 'issues-form-link'.$uniqid,
					]
				],
				[
					'label' => 'Update Issue ',
					'content' => Html::tag('div', '',
						[
							'id' => 'update-issue'.$uniqid,
							'style' => 'display:none'
						]
					),
					'options' => [
						'id' => 'issues-update-form'.$uniqid
					],
					'headerOptions' => [
						'id' => 'issues-update-form-tab'.$uniqid,
						'class' => 'hidden'
					],
					'linkOptions' => [
						'id' => 'issues-update-form-link'.$uniqid, 
					]
				],
			]
		]);
	?>
</div>

<script type="text/javascript">
<?php if(\Yii::$app->request->isAjax): ?>
$nitm.onModuleLoad('issueTracker', function () {
	console.log('Waiting for issueTracker');
	$nitm.module('issueTracker').init("issue-tracker<?=$uniqid?>");
}, 'issueTrackerIndex');
$nitm.onModuleLoad('tools', function () {
	$nitm.module('tools').initVisibility("issue-tracker<?=$uniqid?>");
	$nitm.module('tools').initDynamicValue("issue-tracker<?=$uniqid?>");
}, 'issueTrackerIndex');
<?php endif ?>
</script>
