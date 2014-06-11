<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\markdown\MarkdownEditor;
use nitm\models\Issues;
use yii\redactor\widgets\Redactor;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 * @var yii\widgets\ActiveForm $form
 */

$action = ($model->getIsNewRecord()) ? "create" : "update";

$customToolbar = [
	[
		'buttons' => [
		MarkdownEditor::BTN_BOLD => ['icon'=>'bold', 'title' => 'Bold'],
		MarkdownEditor::BTN_ITALIC => ['icon'=>'italic', 'title' => 'Italic'],
		]
	],
	[
		'buttons' => [
			MarkdownEditor::BTN_LINK => [
				'icon' => 'link',
				'encodeLabel'=>false,
				'title' => 'URL/Link'
			],
			MarkdownEditor::BTN_PREVIEW => [
				'icon' => 'eye-open',
				'encodeLabel'=>false,
				'title' => 'Preview'
			]
		],
	],
	[
		'buttons' => [
			MarkdownEditor::BTN_LINK => [
				'icon' => 'save',
				'encodeLabel'=>false,
				'title' => 'URL/Link'
			],
			MarkdownEditor::BTN_PREVIEW => [
				'icon' => 'eye-open',
				'encodeLabel'=>false,
				'title' => 'Preview'
			]
		],
	],
];
$options =  [
	'attribute' => 'message',
	'model' => $model,
	'footer' => $widget->getActions($useModal || !$inline),
	'toolbar' => $customToolbar,
	'showPreview' => false,
	'showExport' => false,
	'height' => 16,
	'encodeLabels' => true,
	'buttonOptions' => [
		'class' => 'btn btn-sm chat-form-btn'
	],
	'options' => [
		'class' => 'form-control'
	],
	'footerOptions' => [
		'class' => 'chat-form-footer'
	],
	'editorOptions' => [
		'class' => 'chat-form-body'
	],
	'headerOptions' => [
		'class' => 'chat-form-header'
	]
]
?>

<div class="chat-form chat-form-container" id='chatForm<?= $parentId ?>'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<div id="alert"></div>

	<?php $form = ActiveForm::begin([
			'id' => 'chat_form'.$parentId,
			"action" => "/reply/new/".$parentType."/0/".urlencode($parentKey),
			"options" => [
				'data-parent' => 'chat'.$parentId,
				"role" => "chatForm",
			],
			"fieldConfig" => [
				"inputOptions" => ["class" => "form-control"]
			],
			"enableAjaxValidation" => true
		]); ?>
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
					'class' => ''
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
			echo MarkdownEditor::widget($options);
			Modal::end();
			break;
			
			default:
			// usage with model and a custom toolbar
			echo MarkdownEditor::widget($options);
			break;
		}
	?>
	<?= Html::activeHiddenInput($model, "reply_to", ['value' =>  null]); ?>
    <?php ActiveForm::end(); ?>

</div>

<script type='text/javascript'>
$nitm.addOnLoadEvent(function () {
	$nitm.replies.initCreating('chatForm<?= $parentId ?>');
});
</script>
