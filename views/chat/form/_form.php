<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use nitm\models\Issues;
use nitm\widgets\editor\Editor;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 * @var yii\widgets\ActiveForm $form
 */

$action = ($model->getIsNewRecord()) ? "create" : "update";
$model->setScenario($action);

switch(1) 
{
	case isset($useModal) && ($useModal == true):
	\yii\bootstrap\Modal::begin([
		'id' => 'chat-message-modal',
		'options' => [
			'style' => 'z-index: 99',
		],
		'toggleButton' => ['label' => 'Click to Reply', 'class' => 'btn btn-lg btn-primary'],
		'clientOptions' => [
			'modal' => true,
			'autoOpen' => false,
		],
	]);
	break;
}
?>
<div class="chat-form chat-form-container" id='chat-form<?= $parentId ?>'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<div id="alert"></div>
	<?php 
		$form = ActiveForm::begin([
			'id' => 'reply_form0',
			"action" => "/reply/new/chat/0",
			"options" => [
				'data-editor' => $editor,
				'data-parent' => 'chat-messages',
				"role" => "chatForm",
			],
			"fieldConfig" => [
				"inputOptions" => ["class" => "form-control"]
			],
			'validateOnSubmit' => true,
			"enableAjaxValidation" => true
		]); 
	?>
	<?php
		switch(isset($inline) && ($inline == true)) 
		{
			case false:
			echo Html::button(
				'Click to Reply',
				[
					'role' => "startEditor",
					'data-container' => 'messagesForm'.$parentId,
					'data-editor' => 'redactor',
					'data-id' => $parentId,
					'data-use-modal' => @$useModal ? 'true' : 'false',
					'class' => 'btn btn-default center-block'
				]
			);
			break;
			
			default:
			echo $form->field($model, 'title', [
					'addon' => [
						'prepend' => [
							'content' => \nitm\widgets\priority\Priority::widget([
								'size' => 'small',
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
							'class' => 'input-group input-group-sm'
						]
					],
					'options' => [
						'class' => 'chat-message-title',
					]
				])->textInput([
				'placeholder' => "Optional title",
				'tag' => 'span'
			])->label("Title", ['class' => 'sr-only']);
			$editorOptions['id'] = 'chat-message'.$parentId;
			$editorOptions['model'] = $model;
			$editorOptions['attribute'] = 'message';
			$editorOptions['role'] = 'message';
			echo Editor::widget($editorOptions);
			break;
		}
		echo Html::tag("div", '', ["role" => "replyToIndicator", "class" => "message-reply-to"]).$widget->getActions($useModal || !$inline);
	?>
	<?= Html::activeHiddenInput($model, "reply_to", ['value' =>  null, "role" => "replyTo"]); ?>
    <?php 
		ActiveForm::end();
	?>

<script type='text/javascript'>
$nitm.onModuleLoad('replies', function () {
	$nitm.replies.initCreating('chat-form<?= $parentId ?>');
});
</script>
</div>
<?php if(isset($useModal) && ($useModal == true))  \yii\bootstrap\Modal::end(); ?>
