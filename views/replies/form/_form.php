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

$widget->uniqid = !isset($widget->uniqid) ? uniqid() : $widget->uniqid;
$action = ($model->getIsNewRecord()) ? "create" : "update";
?>

<div class="message-form" id='messages-form-container<?= $widget->uniqid ?>'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<h3>Reply</h3>
	<?php $form = ActiveForm::begin([
			'id' => $widget->options['id'],
			'type' => ActiveForm::TYPE_HORIZONTAL,
			'method' => 'post',
			"action" => "/reply/new/".$widget->parentType."/".$widget->parentId.(isset($widget->parentKey) ? "/".urlencode($widget->parentKey) : ''),
			"options" => [
				'data-editor' => $widget->editor,
				'data-parent' => 'messages'.$widget->uniqid,
				"role" => "replyForm",
			],
			"fieldConfig" => [
				"inputOptions" => ["class" => "form-control"]
			],
			"validateOnSubmit" => true,
			"enableAjaxValidation" => true
		]); ?>
	<?php 
		switch(isset($widget->inline) && ($widget->inline == true)) 
		{
			case false:
			echo Html::button(
				'Click to Reply',
				[
					'role' => "startEditor",
					'data-container' => 'messages-form'.$widget->uniqid,
					'data-editor' => $widget->editor,
					'data-id' => $widget->parentId,
					'data-use-modal' => $widget->useModal ? 'true' : 'false',
					'class' => 'btn btn-default center-block'
				]
			);
			break;
			
			default:
			$widget->editorOptions['id'] = 'reply-message'.$widget->uniqid;
			$widget->editorOptions['model'] = $model;
			$widget->editorOptions['attribute'] = 'message';
			$widget->editorOptions['role'] = 'message';
			echo Editor::widget($widget->editorOptions);
			break;
		}
	?>
	<?= Html::tag("div", '', ["role" => "replyToIndicator", "class" => "message-reply-to"]).$widget->getActions($widget->useModal || !$widget->inline); ?>
	<?= Html::activeHiddenInput($model, "reply_to", ['value' =>  null, 'role' => 'replyTo']); ?>
    <?php ActiveForm::end(); ?>

</div>
<script type="text/javascript">
$nitm.onModuleLoad('replies', function () {
	$nitm.module('replies').initCreating('<?= $widget->options['id'] ?>');
});
</script>
