<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use nitm\helpers\Icon;
use \yii\bootstrap\Tabs;
?>

<?php
	$uniqid = uniqid();
	echo Tabs::widget([
		'encodeLabels' => false,
		'items' => [
			[
				'label' => 'General Info',
				'content' =>Html::tag('div', 
					$this->render('../general_info', [
						"model" => $model,
					]),
					[
						'class' => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
						'role' => 'statusIndicator'.$uniqid,
						'id' => 'request-general-info'.$uniqid
					]
				)
			],
			[
				'label' => 'Issues '.Html::tag('span', (int)$model->issueModel()->count(), ['class' => 'badge']),
				'content' =>Html::tag('div', '',
					[
						'class' => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
						'role' => 'statusIndicator'.$uniqid,
						'id' => 'request-issues'.$uniqid,
						'style' => 'display:none'
					]
				),
				'linkOptions' => [
					'role' => 'visibility',
					'data-run-once' => 1,
					'data-type' => 'html',
					'data-id' => 'request-issues'.$uniqid,
					'data-url' =>  \Yii::$app->urlManager->createUrl(['/issue/index/'.$model->isWhat().'/'.$model->getId(), '__format' => 'html', \nitm\models\Issues::COMMENT_PARAM => true])
				]
			],
			[
				'label' => 'Comments '.Html::tag('span', (int)$model->replyModel()->count(), ['class' => 'badge']),
				'content' => Html::tag('div', '',
					[
						'class' => "col-lg-12 col-md-12 ".\nitm\helpers\Statuses::getIndicator($model->getStatus()),
						'role' => 'statusIndicator'.$uniqid,
						'id' => 'request-comments'.$uniqid,
						'style' => 'display:none'
					]
				),
				'linkOptions' => [
					'role' => 'visibility',
					'data-run-once' => 1,
					'data-type' => 'html',
					'data-id' => 'request-comments'.$uniqid,
					'data-url' =>  \Yii::$app->urlManager->createUrl(['/reply/index/'.$model->isWhat().'/'.$model->getId(), '__format' => 'html'])
				]
			],
			[
				'label' => 'Revisions '.Html::tag('span', (int)$model->revisionModel()->count(), ['class' => 'badge']),
				'content' => Html::tag('div', '',
					[
						'class' => "col-lg-12 col-md-12 ".\nitm\helpers\Statuses::getIndicator($model->getStatus()),
						'role' => 'statusIndicator'.$uniqid,
						'id' => 'request-revisions'.$uniqid,
						'style' => 'display:none'
					]
				),
				'linkOptions' => [
					'role' => 'visibility',
					'data-run-once' => 1,
					'data-type' => 'html',
					'data-id' => 'request-revisions'.$uniqid,
					'data-url' =>  \Yii::$app->urlManager->createUrl(['/revisions/index/'.$model->isWhat().'/'.$model->getId(), '__format' => 'html'])
				]
			]
		]
	]);
?>
<script type='text/javascript'>
$nitm.onModuleLoad('entity:requests', function () {
	$nitm.module('entity').initMetaActions('#<?= $model->isWhat();?>_form_container', 'entity:requests');
	<?php if(\Yii::$app->request->isAjax): ?>
	$nitm.module('tools').initVisibility('#<?= $model->isWhat();?>_form_container');
	<?php endif; ?>
});
</script>