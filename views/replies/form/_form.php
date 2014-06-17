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

<div class="message-form" id='messagesForm<?= $parentId ?>'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<div id="alert"></div>

	<?php $form = ActiveForm::begin([
			'id' => 'reply_form'.$parentId,
			"action" => "/reply/new/".$parentType."/".$parentId."/".urlencode($parentKey),
			"options" => [
				'data-editor' => 'redactor',
				'data-parent' => 'messages'.$parentId,
				"class" => "form-inline",
				"role" => "replyForm",
			],
			"fieldConfig" => [
				"inputOptions" => ["class" => "form-control"]
			],
			"enableAjaxValidation" => true
		]); ?>
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
			$id = 'message'.$parentId;
			$ta = @Redactor::begin([
				'options' => $options,
				'htmlOptions' => $htmlOptions,
				'model' => $model,
				'attribute' => 'message'
			]);
			Redactor::end();
			Redactor::end();
			break;
		}
	?>
	<?= $widget->getActions($useModal || !$inline); ?>
	<?= Html::activeHiddenInput($model, "reply_to", ['value' =>  null, 'role' => 'replyTo']); ?>
    <?php ActiveForm::end(); ?>

</div>

<script type='text/javascript'>
$nitm.addOnLoadEvent(function () {
	$nitm.replies.initCreating('messagesForm<?= $parentId ?>');
});
</script>
