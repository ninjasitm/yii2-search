<?php

use yii\helpers\Html;
use yii\grid\GridView;
use nitm\helpers\Icon;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var frontend\models\search\Requests $searchModel
 */

$this->title = 'Requests';
$this->params['breadcrumbs'][] = $this->title;
?>

<?= GridView::widget([
		'options' => [
			'id' => 'requests'
		],
		'dataProvider' => $dataProvider,
		//'filterModel' => $searchModel,
		'columns' => [
			[
				'label' => '',
				'attribute' => 'id',
				'format' => 'html',
				'value' => function ($model) {
					return Html::tag('h1', $model->getId());
				},
				'contentOptions' => [
					'rowspan' => 2,
					'style' => 'vertical-align: middle'
				]
			],
			[
				'attribute' => 'rating',
				'label' => '%',
				'format' => 'html',
				'value' => function ($model, $index, $widget) {
					$rating = Html::tag('div',
						$this->context->voteWidget([
							'parentType' => $model->isWhat(), 
							'parentId' => $model->getId(),
						])
					);
					return $rating;
				},
				'options' => [
					'rowspan' => 3
				]
			],
			[
				'format'  => 'html',
				'attribute' => 'type_id',
				'label' => 'Type',
				'value' => function ($model) {
					return $model->url('type_id', [$model->type(), 'name']);
				}
			],
			[
				'format'  => 'html',
				'attribute' => 'request_for_id',
				'label' => 'Request For',
				'value' => function ($model) {
					return $model->url('request_for_id', [$model->requestFor(), 'name']);
				}
			],
			[
				'format' => 'html',
				'attribute' => 'status',
				'label' => 'Urgency',
				'value' => function ($model, $index, $widget) {
					return $model->url('status', $model->getUrgency());
				}
			],
			'closed:boolean',
			'completed:boolean',
			// 'author',
			// 'edited',
			// 'editor',
			// 'edits',
			// 'request:ntext',
			// 'type:ntext',
			// 'request_for:ntext',
			// 'status',
			// 'completed',
			// 'completed_on',
			// 'closed',
			// 'closed_on',
			// 'rating',
			// 'rated_on',
			[
				'attribute' => 'author',
				'label' => 'Author',
				'format' => 'html',
				'value' => function ($model, $index, $widget) {
					return $model->author()->url(\Yii::$app->getModule('lab1')->fullUsernames, \Yii::$app->request->url, [$model->formname().'[author]' => $model->author()->getId()]);
				}
			],
			'created_at:date',
	
			[
				'class' => 'yii\grid\ActionColumn',
				'buttons' => [
					'form/update' => function ($url, $model) {
						return Html::a(Icon::forAction('update'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']), [
							'title' => Yii::t('yii', 'Edit '.$model->title),
							'role' => 'dynamicAction disabledOnClose',
							'class' => 'fa-2x',
							'data-pjax' => '0',
							'data-toggle' => 'modal',
							'data-target' => '#form'
						]);
					},
					'close' => function ($url, $model) {
						return Html::a(Icon::forAction('close', 'closed', $model), \Yii::$app->urlManager->createUrl([$url]), [
							'title' => Yii::t('yii', ($model->closed ? 'Open' : 'Close').' '.$model->title),
							'role' => 'metaAction closeAction',
							'class' => 'fa-2x',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					},
					'complete' => function ($url, $model) {
						return Html::a(Icon::forAction('thumbs-up', 'completed', $model), \Yii::$app->urlManager->createUrl([$url]), [
							'title' => Yii::t('yii', ($model->completed ? 'Incomplete' : 'Complete').' '.$model->title),
							'role' => 'metaAction resolveAction disabledOnClose',
							'class' => 'fa-2x',
							'data-parent' => 'tr',
							'data-pjax' => '0',
						]);
					}
				],
				'template' => "{form/update} {complete} {close}",
				'urlCreator' => function($action, $model, $key, $index) {
					return $this->context->id.'/'.$action.'/'.$model->getId();
				},
				'options' => [
					'rowspan' => 3
				]
			],
		],
		'rowOptions' => function ($model, $key, $index, $grid)
		{
			return [
				"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
				'id' => 'request'.$model->getId(),
				'role' => 'statusIndicator'.$model->getId()
			];
		},
		"tableOptions" => [
				'class' => 'table table-bordered'
		],
		'afterRow' => function ($model, $key, $index, $grid){
			//Extra information section
			$replies = $this->context->replyCountWidget([
				"model" => $model->replyModel(),
				'fullDetails' => false,
				'widgetOptions' => ['class' => 'list-group'],
				'itemOptions' => [
					'class' => "list-group-item list-group-item-transparent",
				]
			]);
			$revisions = $this->context->revisionsCountWidget([
				'model' => $model->revisionModel(),
				"parentId" => $model->getId(), 
				"parentType" => $model->isWhat(),
				'fullDetails' => false ,
				'widgetOptions' => ['class' => 'list-group'],
				'itemOptions' => [
					'class' => "list-group-item list-group-item-transparent",
				]
			]);
			$issues = $this->context->issueCountWidget([
				'model' => $model->issueModel(),
				'enableComments' => true,
				"parentId" => $model->getId(), 
				"parentType" => $model->isWhat(),
				'fullDetails' => false,
				'widgetOptions' => ['class' => 'list-group'],
				'itemOptions' => [
					'class' => "list-group-item list-group-item-transparent",
				]
			]);
			$title = Html::tag('div',
				Html::tag(
					'h4', 
					$model->title
				),
				['class' => 'col-md-6 col-lg-6']
			);
			
			$activityInfo = Html::tag('div',
				Html::tag('div', $replies, ['class' => 'col-md-4 col-lg-4']).
				Html::tag('div', $revisions, ['class' => 'col-md-4 col-lg-4']).
				Html::tag('div', $issues, ['class' => 'col-md-4 col-lg-4']),
				[
					'class' => 'col-md-6 col-lg-6'
				]
			);
			$shortLink = \lab1\widgets\ShortLink::widget([
				'url' => \Yii::$app->urlManager->createAbsoluteUrl([$model->isWhat().'/view/'.$model->getId()]),
				'header' => $model->title,
				'type' => 'modal',
				'size' => 'large'
			]);
			$metaInfo = Html::tag('div', 
				Html::tag('div', 
					$title.$activityInfo."<br>".$shortLink
				),
				[
					'class' => 'clearfix'
				]
			)."<br>";
			return Html::tag('tr', 
				Html::tag(
					'td', 
					$metaInfo, 
					[
						'colspan' => 9, 
						'rowspan' => 1,
					]
				),
				[
					"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
					'role' => 'statusIndicator'.$model->getId()
				]
			);
		},
		'pager' => ['class' => \kop\y2sp\ScrollPager::className([
			'container' => '#requests',
			'item' => "#refunds [id^='request']"
		])]
	]); ?>
