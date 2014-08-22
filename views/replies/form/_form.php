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

$uniqid = !isset($uniqid) ? uniqid() : $uniqid;
$action = ($model->getIsNewRecord()) ? "create" : "update";
?>

<div class="message-form" id='messagesForm<?= $uniqid ?>'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<div id="alert"></div>
	<h3>Reply</h3>
	<?php $form = ActiveForm::begin([
			'id' => 'reply_form'.$uniqid,
			'type' => ActiveForm::TYPE_HORIZONTAL,
			'method' => 'post',
			"action" => "/reply/new/".$parentType."/".$parentId.(isset($parentKey) ? "/".urlencode($parentKey) : ''),
			"options" => [
				'data-editor' => $editor,
				'data-parent' => 'messages'.$uniqid,
				"role" => "replyForm",
			],
			"fieldConfig" => [
				"inputOptions" => ["class" => "form-control"]
			],
			"validateOnSubmit" => true,
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
					'data-container' => 'messagesForm'.$uniqid,
					'data-editor' => $editor,
					'data-id' => $parentId,
					'data-use-modal' => @$useModal ? 'true' : 'false',
					'class' => 'btn btn-default center-block'
				]
			);
			break;
			
			default:
			$editorOptions['id'] = 'reply-message'.$uniqid;
			$editorOptions['model'] = $model;
			$editorOptions['attribute'] = 'message';
			$editorOptions['role'] = 'message';
			echo Editor::widget($editorOptions);
			break;
		}
	?>
	<?= Html::tag("div", '', ["role" => "replyToIndicator", "class" => "message-reply-to"]).$widget->getActions($useModal || !$inline); ?>
	<?= Html::activeHiddenInput($model, "reply_to", ['value' =>  null, 'role' => 'replyTo']); ?>
    <?php ActiveForm::end(); ?>

</div>
<script type="text/javascript">
$nitm.onModuleLoad('replies', function () {
	$nitm.module('replies').initCreating('messagesForm<?= $uniqid ?>');
});
</script>
