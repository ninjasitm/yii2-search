<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use nitm\models\Issues;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 * @var yii\widgets\ActiveForm $form
 */

$action = ($model->getIsNewRecord()) ? "create" : "update";
?>

<div class="issues-form" id='issues-form'>
	<?= \nitm\widgets\alert\Alert::widget(); ?>
	<div id="alert"></div>

	<?php $form = ActiveForm::begin([
		"type" => ActiveForm::TYPE_HORIZONTAL,
		'action' => '/issue/'.$action.($model->getIsNewRecord() ? '' : "/".$model->id),
		'options' => [
			"role" => "updateIssue"
		],
		'fieldConfig' => [
			'inputOptions' => ['class' => 'form-control'],
			'template' => "{label}\n<div class=\"col-lg-10\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
			'labelOptions' => ['class' => 'col-lg-2 control-label'],
		],
		'enableAjaxValidation' => true
	]); ?>

    <?= $form->field($model, 'title') ?>
    <?= $form->field($model, 'notes')->textarea()->label("Issue") ?>
	<?=	$form->field($model, 'status')->radioList(Issues::getStatusLabels(), ['inline' => true])->label("Urgency"); ?>
	<?php
		switch($model->getIsNewRecord())
		{
			case true:
			echo Html::activeHiddenInput($model, 'parent_id', ['value' => $parentId]);
			echo Html::activeHiddenInput($model, 'parent_type', ['value' => $parentType]);
			break;
		}
	?>

    <div class="fixed-actions text-right">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script type='text/javascript'>
$nitm.addOnLoadEvent(function () {
	$nitm.issueTracker.initCreateUpdate('#issues-form');
});
</script>
