<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use nitm\models\Issues;
use yii\imperavi\Widget as Redactor;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 * @var yii\widgets\ActiveForm $form
 */

$action = ($model->getIsNewRecord()) ? "create" : "update";
$model->setScenario($action);

$options =  [
	'air'=> true,
	'airButtons' => [
		'bold', 'italic', 'deleted', 'link'
	],
	'height' => 'auto',
	'buttonOptions' => [
		'class' => 'btn btn-sm chat-form-btn'
	]
];

$htmlOptions = [
	'style' => 'z-index: 99999',
	'class' => 'form-control',
	'rows' => 3,
	'required' => true,
	"role" => "message"
];
?>

<div class="chat-form chat-form-container" id='chat-form<?= $parentId ?>'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<div id="alert"></div>

	<?php $form = ActiveForm::begin([
			'id' => 'reply_form0',
			"action" => "/reply/new/".$parentType."/0/".urlencode($parentKey),
			"options" => [
				'data-parent' => 'chat'.$parentId,
				"role" => "chatForm",
			],
			"fieldConfig" => [
				"inputOptions" => ["class" => "form-control"]
			],
			"enableAjaxValidation" => true
		]); 
	?>
	<?php
		echo $form->field($model, 'title', [
				'addon' => [
					'prepend' => [
						'content' => \nitm\widgets\priority\Priority::widget([
							'type' => 'addon',
							'inputsInline' => true,
							'addonType' => 'radiolist',
							'fieldName' => 'priority',
							'model' => $model,
							'form' => $form
						]),
						'asButton' => true
					],
					'groupOptions' => [
					]
				],
				'options' => [
					'class' => 'chat-message-title',
				]
			])->textInput([
			'placeholder' => "Optional title",
			'tag' => 'span'
		])->label("Title", ['class' => 'sr-only']);
		switch(isset($inline) && ($inline == true)) 
		{
			case false:
			Modal::begin([
				'id' => 'markdown-modal',
				'toggleButton' => ['label' => 'Click to Reply', 'class' => 'btn btn-lg btn-primary'],
				'header' => '<h4 style="margin:0; padding:0">Markdown Editor Inside Modal</h4>',
				'clientOptions' => [
				'modal' => true,
				'autoOpen' => false,
				],
			]);
			$id = 'message'.$parentId;
			$ta = @Redactor::begin([
				'options' => $options,
				'htmlOptions' => $htmlOptions,
				'model' => $model,
				'attribute' => 'message'
			]);
			Redactor::end();
			Modal::end();
			break;
			
			default:
			// usage with model and a custom toolbar
			$id = 'message'.$parentId;
			$ta = @Redactor::begin([
				'options' => $options,
				'htmlOptions' => $htmlOptions,
				'model' => $model,
				'attribute' => 'message',
			]);
			Redactor::end();
			break;
		}
		echo $widget->getActions($useModal || !$inline);
	?>
	<?= Html::activeHiddenInput($model, "reply_to", ['value' =>  null, "role" => "replyFo"]); ?>
	<div id="alert"></div>
    <?php ActiveForm::end(); ?>

<script type='text/javascript'>
$nitm.addOnLoadEvent(function () {
	$nitm.replies.initCreating('chatForm<?= $parentId ?>');
});
</script>
</div>
