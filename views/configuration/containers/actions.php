<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="well">
<?php
	$_containers = [];
	array_walk($model->config['containers'], function ($k, $v) use(&$_containers, $model) {
		$url = \Yii::$app->urlManager->createUrl('configuration/load/'.$model->engine."/".$v);
		switch($model->container)
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
				$form->field($model, 'convert[container]')->dropDownList($model->config['containers'])->label("Current Config", ['class' => 'sr-only']); 
			?>
			<h4>From format: <b><?= ucfirst($model->engine);?></b></h4>
			<h5>To</h5>
			<?=
				$form->field($model, 'convert[to]')->dropDownList(array_diff($model->config['supported'], array($model->engine => ucfirst($model->engine))),
											array('class' => 'form-control')
											)->label("Engine", ['class' => 'sr-only']); 
			?>
			<?php 
				echo Html::activeHiddenInput($model, 'convert[from]', array('value' => $model->engine));
				echo Html::activeHiddenInput($model, 'convert[do]', array('value' => true));
				echo Html::activeHiddenInput($model, 'engine', array('value' => $model->engine));
				echo Html::submitButton('Convert Config', array('class' => 'btn btn-primary pull-right'));
				ActiveForm::end(); 
			?>
			</div>
		</div>
</div>
<div class="well">
<?php $form = ActiveForm::begin(['id' => 'container_create',
				 'action' => '/configuration/create',
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
				$form->field($model, 'value')->textInput(array('placeholder' => 'Container name...'))->label("Create a new container");
		?>
		<?php
				echo Html::activeHiddenInput($model, 'what', array('value' => 'container'));
				echo Html::submitButton('Create Container', array('class' => 'btn btn-primary pull-right'));
		?>
		</div>
<?php ActiveForm::end(); ?>
</div> 
