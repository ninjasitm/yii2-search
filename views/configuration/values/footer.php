<?php
use yii\helpers\Html;
use kartik\widgets\ActiveForm;

$model->setScenario('createValue');
?>
<div class="well" id="create_value_container">

		<?php $form = ActiveForm::begin([
			'action' => '/configuration/create',
			"type" => ActiveForm::TYPE_INLINE,
			'options' => [
				'id' => "create_new_value_$section",
				"role" => "filter"
			],
			'fieldConfig' => [
				'inputOptions' => ['class' => 'form-control'],
				'template' => "{label}\n<div class=\"col-lg-10\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
				'labelOptions' => ['class' => 'col-lg-2 control-label'],
			],
			'enableAjaxValidation' => true
		]); ?>
        <?php
                echo $form->field($model, 'cfg_n')->textInput(array('placeholder' => 'Setting name...'))->label("Name", ['class' => 'sr-only']);
                echo $form->field($model, 'cfg_v')->textInput(array('placeholder' => 'Setting value...'))->label("Value", ['class' => 'sr-only']);
        ?>
        <?php
                echo Html::activeHiddenInput($model, 'cfg_c', array('value' => $container));
                echo Html::activeHiddenInput($model, 'cfg_s', array('value' => $section));
                echo Html::activeHiddenInput($model, 'cfg_w', array('value' => 'value'));
                echo Html::submitButton('Add Key/Value', [
												'class' => 'btn btn-primary',
												'title' => "Add value to $section",
												"data-loading-text" => "Adding..."
											]
                                        );
        ?>
        <?php ActiveForm::end(); ?>
</div>

<script language='javascript' type="text/javascript">
$nitm.onModuleLoad('configuration', function () {
	$nitm.module('configuration').initCreating("create_value_container");
});
</script>