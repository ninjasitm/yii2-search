<?php

use\yii\grid\GridView;
use yii\helpers\Html;
use yii\data\ArrayDataProvider;
use nitm\helpers\Icon;

echo GridView::widget([
	'showFooter' => false,
	'summary' => '',
	'dataProvider' => new ArrayDataProvider([
		'allModels' => [$model],
		'pagination' => false,
	]),
	'columns' => [
		[
			'attribute' => 'id',
			'label' => 'ID',
			'options' => [
				'rowspan' => 3
			]
		],
		[
			'attribute' => 'rating',
			'label' => '%',
			'format' => 'raw',
			'value' => function ($model, $index, $widget) {
				$rating = Html::tag('div',
					$this->context->VoteWidget([
						'size' => 'large',
						'model' => $model->voteModel(),
						'parentType' => $model->isWhat(), 
						'parentId' => $model->getId(),
					])
				);
				return $rating;
			},
			'options' => [
				'rowspan' => 3,
				'class' => 'col-md-2 col-lg-2'
			]
		],
		[
			'attribute' => 'author',
			'label' => 'Author',
			'format' => 'html',
			'value' => function ($model, $index, $widget) {
				return $model->author()->url(\Yii::$app->getModule('lab1')->fullUsernames, \Yii::$app->request->url, [$model->formname().'[author]' => $model->author()->getId()]);
			}
		],
		'closed:boolean',
		'completed:boolean',
		[
			'class' => 'yii\grid\ActionColumn',
			'buttons' => [
				'close' => function ($url, $model) {
					return Html::a(Icon::forAction('close', 'closed', $model), $url, [
						'title' => Yii::t('yii', ($model->closed ? 'Open' : 'Close').' '.$model->title),
						'class' => 'fa-2x',
						'role' => 'metaAction closeAction',
						'data-parent' => 'tr',
						'data-pjax' => '0',
					]);
				},
				'complete' => function ($url, $model) {
					return Html::a(Icon::forAction('resolve', 'completed', $model), $url, [
						'title' => Yii::t('yii', ($model->completed ? 'Incomplete' : 'Complete').' '.$model->title),
						'class' => 'fa-2x',
						'role' => 'metaAction resolveAction disabledOnClose',
						'data-parent' => 'tr',
						'data-pjax' => '0',
					]);
				}
			],
			'template' => "{complete} {close}",
			'urlCreator' => function($action, $model, $key, $index) {
				return '/'.$this->context->id.'/'.$action.'/'.$model->getId();
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
			'role' => 'statusIndicator'.$model->getId()
		];
	},
	"tableOptions" => [
		'class' => 'table table-bordered'
	],
	'afterRow' => function ($model, $key, $index, $grid) {
		
		$shortLink = \lab1\widgets\ShortLink::widget([
			'url' => \Yii::$app->urlManager->createAbsoluteUrl([$model->isWhat().'/view/'.$model->getId()]),
			'viewOptions' => [
				'data-toggle' => 'modal',
				'data-target' => '#view'
			]
		]);
		
		$statusInfo = \lab1\widgets\StatusInfo::widget([
			'items' => [
				[
					'blamable' => $model->author(),
					'date' => $model->created_at,
					'value' => $model->created_at,
					'label' => [
						'true' => "Created ",
					]
				],
				[
					'blamable' => $model->completedBy(),
					'date' => $model->completed_at,
					'value' => $model->completed,
					'label' => [
						'true' => "Completed ",
						'false' => "Not completed"
					]
				],
				[
					'blamable' => $model->closedBy(),
					'date' => $model->closed_at,
					'value' => $model->closed,
					'label' => [
						'true' => "Closed ",
						'false' => "Not closed"
					]
				],
			],
		]);
		
		$metaInfo = empty($statusInfo) ? $shortLink : $shortLink.$statusInfo;
		/*$issues = $this->context->issueCountWidget([
			"model" => $model->issues, 
		]);*/
		return Html::tag('tr', 
			Html::tag(
				'td', 
				$metaInfo, 
				[
					'colspan' => 6, 
					'rowspan' => 1,
					"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
					'role' => 'statusIndicator'.$model->getId()
				]
			)
		);
	}
]); ?>