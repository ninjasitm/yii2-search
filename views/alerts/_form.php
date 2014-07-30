<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\widgets\DepDrop;

/* @var $this yii\web\View */
/* @var $model nitm\models\Alerts */
/* @var $form kartik\widgets\ActiveForm */
$action = $model->getIsNewRecord() ? 'create' : 'update';
$model->setScenario('create');
?>

<div class="alerts-form" >

	<?php $form = ActiveForm::begin([
		'action' => '/alerts/create',
		"type" => ActiveForm::TYPE_INLINE,
		'options' => [
			"role" => "createAlert"
		],
		'fieldConfig' => [
			'inputOptions' => ['class' => 'form-control'],
			'template' => "{label}\n<div class=\"col-lg-10\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
			'labelOptions' => ['class' => 'col-lg-2 control-label'],
		],
		'validateOnSubmit' => true,
		'enableAjaxValidation' => true
	]); ?>
	<?=
		$form->field($model, 'action')->widget(Select2::className(), [
			'data' => $model::$settings[$model->isWhat()]['actions'],
			'options' => ['id' => 'new-alert-action', 'placeholder' => 'Alert me when someone...', "allowClear" => true]
		])->label("Action");
	?>    
	<?=
		$form->field($model, 'remote_type')->widget(DepDrop::className(), [
			'value' => $model->remote_type,
			'options' => ['placeholder' => ' type of ', 'id' => 'new-alert-type', ],
			'type' => DepDrop::TYPE_SELECT2,
			'select2Options'=>['id' => 'new-alert-remote-type', 'pluginOptions'=>['allowClear'=>true]],
			'pluginOptions'=>[
				'depends'=>['new-alert-action'],
				'url' => Url::to(['/alerts/list/types']),
				'loadingText' => '...',
			]
		])->label("Remote Type");
	?>    
	<?=
		$form->field($model, 'remote_for')->widget(DepDrop::className(), [
			'value' => $model->remote_type,
			'options' => ['placeholder' => ' for ', 'id' => 'new-alert-for', ],
			'type' => DepDrop::TYPE_SELECT2,
			'select2Options'=>['id' => 'new-alert-remote-type', 'pluginOptions'=>['allowClear'=>true]],
			'pluginOptions'=>[
				'depends'=>['new-alert-type'],
				'url' => Url::to(['/alerts/list/for']),
				'loadingText' => '...',
			]
		])->label("Remote For");
	?>
	<?=
		$form->field($model, 'priority')->widget(DepDrop::className(), [
			'value' => $model->remote_type,
			'options' => ['placeholder' => 'that is'],
			'type' => DepDrop::TYPE_SELECT2,
			'select2Options'=>['pluginOptions'=>['allowClear'=>true]],
			'pluginOptions'=>[
				'depends'=>['new-alert-type'],
				'url' => Url::to(['/alerts/list/priority']),
				'loadingText' => '...',
			]
		])->label("Priority");
	?>
	<?=
		$form->field($model, 'methods')->widget(Select2::className(), [
			'value' => explode(',', $model->methods),
			'options' => ['placeholder' => 'using'],
			'data' => $model->supportedMethods(),
			
		])->label("Priority");
	?>
	
		
	<?php if(!\Yii::$app->request->isAjax): ?>
	<div class="btn-group">
		<?= Html::submitButton(ucfirst($action), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	</div>
	<?php endif; ?>
	
	<?php ActiveForm::end(); ?>

</div>
