<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use nitm\helpers\Icon;
use \yii\bootstrap\Tabs;

$model->replies = \nitm\models\Replies::findModel([$model->getId(), $model->isWhat(), $model->created_at]);
$model->issues = \nitm\models\Issues::findModel([$model->getId(), $model->isWhat()]);
$model->revisions = \nitm\models\Revisions::findModel([$model->getId(), $model->isWhat()]);
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
				'label' => 'Issues '.Html::tag('span', (int)$model->issues->getCount(), ['class' => 'badge']),
				'content' =>Html::tag('div', '',
					[
						'class' => "row ".\nitm\helpers\Statuses::getIndicator($model->getStatus()),
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
					'data-url' =>  \Yii::$app->urlManager->createUrl(['/issue/index/'.$model->isWhat().'/'.$model->getId(), '__format' => 'html'])
				]
			],
			[
				'label' => 'Comments '.Html::tag('span', (int)$model->replies->getCount(), ['class' => 'badge']),
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
				'label' => 'Revisions '.Html::tag('span', (int)$model->revisions->getCount(), ['class' => 'badge']),
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
<?php
	switch(\Yii::$app->request->isAjax)
	{
		case false:
		echo $this->context->issueModalWidget();
		echo $this->context->issueModalWidget([
			'options' => [
				'id' => 'issue-tracker-modal-form',
				'style' => 'z-index: 100002',
			]
		]);
		break;
	}
?>