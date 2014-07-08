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
	$issuesOpen = Html::tag('div', '', ['id' => 'alert'.$parentId]).getIssues($dataProviderOpen, $viewOptions);
	$issuesClosed = Html::tag('div', '', ['id' => 'alert'.$parentId]).Html::tag('div', '', ['class' => 'issues']);
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
						'style' => 'color:black;'
					],
					'headerOptions' => [
						'id' => 'open-issues-tab'.$uniqid
					],
					'linkOptions' => [
						'role' => 'dynamicValue',
						'data-type' => 'html',
						'data-id' => '#open-issues'.$uniqid,
						'data-url' => \Yii::$app->urlManager->createUrl(['/issue/issues/'.$parentType.'/'.$parentId, '__format' => 'html', Issues::COMMENT_PARAM => $enableComments]),
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
						'style' => 'color:black;'
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
				[
					'label' => Html::tag('div', '',
						[
							'id' => 'issues-alerts-message'.$uniqid,
						]
					),
					'content' => '',
					'headerOptions' => [
						'id' => 'issues-alerts-tab'.$uniqid,
					],
					'linkOptions' => [
						'id' => 'issues-alerts-link'.$uniqid, 
					]
				]
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

<?php 
	function getIssues($dataProvider, $options=[])
	{
		global $uniqid;
		return ListView::widget([
			'options' => [
				'id' => 'issues'.$uniqid,
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
