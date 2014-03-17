<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="well">
<?php
	$_containers = [];
	array_walk($model->config['containers'], function ($k, $v) use(&$_containers, $model) {
		$url = \Yii::$app->urlManager->createUrl('configuration/load/'.$model->cfg_e."/".$v);
		switch($model->cfg_c)
		{
			case $v:
			$model->config['current']['container_url'] = $url;
			break;
		}
		$_containers[$url] = $k;
	});
	echo Html::label(
		(empty($model->config['current']['container']) ? "Load config from" : "Current config: ".$model->config['current']['container'])
	);
	echo Html::dropDownList(
		'container',
		@$model->config['current']['container_url'],
		$_containers,
		[
			"class" => "btn-default form-control",
			"role" => "changeSubmit"
		]
	);
?>
</div>
<div class="well">
	<h4>Convert the config</h4>
<?php $form = ActiveForm::begin(['id' => 'config_container',
				 'action' => '/configuration/convert',
				 'options' => [
					       'class' => 'form-horizontal',
					       ],
				'fieldConfig' => [
						'inputOptions' => ['class' => 'form-control']
						],
				'enableAjaxValidation' => false
				]); ?>
		<div class="form-group">
			<div class="col-md-12 col-lg-12">
			<?=
				$form->field($model, 'cfg_convert[container]')->dropDownList($model->config['containers'])->label("Current Config", ['class' => 'sr-only']); 
			?>
			<h4>From format: <b><?= ucfirst($model->cfg_e);?></b></h4>
			<h5>To</h5>
			<?=
				$form->field($model, 'cfg_convert[to]')->dropDownList(array_diff($model->config['supported'], array($model->cfg_e => ucfirst($model->cfg_e))),
											array('class' => 'form-control')
											)->label("Engine", ['class' => 'sr-only']); 
			?>
			<?php 
				echo Html::activeHiddenInput($model, 'cfg_convert[from]', array('value' => $model->cfg_e));
				echo Html::activeHiddenInput($model, 'cfg_convert[do]', array('value' => true));
				echo Html::activeHiddenInput($model, 'cfg_e', array('value' => $model->cfg_e));
				echo Html::submitButton('Convert Config', array('class' => 'btn btn-primary pull-right'));
				ActiveForm::end(); 
			?>
			</div>
		</div>
</div>
<div class="well">
<?php $form = ActiveForm::begin(['id' => 'container_add',
				 'action' => '/configuration/add',
				 'options' => [
					       'class' => 'form-inline',
					       ],
				'fieldConfig' => [
						'inputOptions' => ['class' => 'form-control']
						],
				'enableAjaxValidation' => true
				]); ?>

		<div class="form-group">
		<?=
				$form->field($model, 'cfg_v')->textInput(array('placeholder' => 'Container name...'))->label("Add a new container");
		?>
		<?php
				echo Html::activeHiddenInput($model, 'cfg_w', array('value' => 'container'));
				echo Html::submitButton('Add Container', array('class' => 'btn btn-primary pull-right'));
		?>
		</div>
<?php ActiveForm::end(); ?>
</div> 
