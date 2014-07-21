<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use kartik\icons\Icon;

/**
 * @var yii\web\View $this
 * @var frontend\models\Requests $model
 * @var yii\widgets\ActiveForm $form
 */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$action = $model->getIsNewRecord() ? 'create' : 'update';
?>

<?= Html::tag('div', '', ['id' => 'alert']); ?>

<div id="<?= $model->isWhat()?>_form_container" class="row">
	<?php if (!$model->getIsNewRecord()) : ?>
	<div class="col-md-7 col-lg-7">
	<?= $this->render('meta_info', ['model' => $model]); ?>
	</div>
	<?php endif ?>
	
	<?php if (!$model->getIsNewRecord()) : ?>
	<div class="col-md-5 col-lg-5 full-height col-md-offset-7 col-lg-offset-7">
	<?php else: ?>
	<div class="col-md-12 col-lg-12">
	<?php endif ?>
		<div id="<?= $model->isWhat()?>_form">
		<?php $form = ActiveForm::begin([
			"action" => "/".$model->isWhat()."/$action/".$model->id,
			"type" => ActiveForm::TYPE_HORIZONTAL,
			'options' => [
				"role" => $action.$model->formName(),
				'id' => $model->isWhat().'_form'.$model->getId()
			],
			'fieldConfig' => [
				'inputOptions' => ['class' => 'form-control'],
				'template' => "{label}\n<div class=\"col-lg-10\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
				'labelOptions' => ['class' => 'col-lg-2 control-label'],
			],
			'enableAjaxValidation' => true
		]); ?>
	
		<?= $form->field($model, 'title') ?>
	
		<?=
			$form->field($model, 'type')->widget(Select2::className(), [
				'data' => $model->getCategoryList($model->isWhat().'-categories'),
			])->label("Type");
		?>
	
		<?=
			$form->field($model, 'request_for_id')->widget(Select2::className(), [
				'data' => $model->getCategoryList($model->isWhat().'-for'),
			])->label("Request For");
		?>
	
		<?=
			$form->field($model, 'status')->widget(Select2::className(), [
				'data' => $model->getStatuses(),
			])->label("Status");
		?>
		<div class="form-group wrapper">
			<div class="row">
			<div class="col-md-12 col-lg-12">
			<?php
				echo $this->context->RevisionsInputWidget([
					"parentId" => $model->getId(),
					"parentType" => $model->isWhat(),
					'name' => 'request',
					'model' => $model,
					'value' => $model->request
				]);
			?>
			</div>
			</div>
		</div>
		
		<?php if(!\Yii::$app->request->isAjax): ?>
		<div class="fixed-actions text-right">
			<?= Html::submitButton(ucfirst($action), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
		</div>
		<?php endif; ?>
	
		<?php ActiveForm::end(); ?>
		</div>
	</div>
</div>
<?php if (!$model->getIsNewRecord()) : ?>
<?php endif ?>
<script type='text/javascript'>
$nitm.onModuleLoad('nitm:requests', function () {
	$nitm.module('nitm').initForms('<?= $model->isWhat();?>_form_container', 'nitm:requests');
	$nitm.module('nitm').initMetaActions('#<?= $model->isWhat();?>_form_container', 'nitm:requests');
	<?php if(\Yii::$app->request->isAjax): ?>
	$nitm.module('tools').initVisibility('#<?= $model->isWhat();?>_form_container');
	<?php endif; ?>
});
</script>
