<?php
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use yii\widgets\ActiveField;
?>
<br>
<?php
	echo $this->context->alertWidget();
	echo $this->context->legendWidget();
?>
<?= 
	\nitm\widgets\modal\Modal::widget([
		'toggleButton' => [
			'tag' => 'a',
			'label' => Html::tag('h3', strtoupper("Add New ".$model->isWhat())), 
			'href' => \Yii::$app->urlManager->createUrl([$model->isWhat().'/form/create', '__format' => 'modal']),
			'title' => Yii::t('yii', "Add a new ".$model->isWhat()),
			'role' => 'dynamicAction createAction disabledOnClose',
		],
		'contentOptions' => [
			"class" => "modal-full"
		],
		'dialogOptions' => [
			"class" => "modal-full"
		]
	]);
?>
<h3 class="header text-left">FILTER USING THE FOLLOWING</h3>
<div id="filters">

<?php $form = ActiveForm::begin(['id' => 'filter',
	'type' => ActiveForm::TYPE_HORIZONTAL,
	'action' => '/'.$model->isWhat().'/search?__format=json',
	'method' => 'get',
	'options' => [
		'class' => 'form-horizontal',
		"role" => "filter",
		'data-id' => $model->isWhat()
	],
	'fieldConfig' => [
		'inputOptions' => ['class' => 'form-control'],
		'template' => "{label}\n<div class=\"col-lg-10 col-md-10\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
		'labelOptions' => ['class' => 'col-lg-2 col-md-2 control-label'],
	],
	'enableAjaxValidation' => true
	]);
?>
	<?=
		Html::submitButton(
			Html::tag('span', '', ['class' => 'glyphicon glyphicon-filter']), 
			[
				'class' => 'btn btn-primary btn-lg',
				"title" => "Run this filer"
			]
		);
	?>
	<?=
		$form->field($model, 'filter[text]')->textInput()->label("Search");
	?>

	<?=
		$form->field($model, 'filter[closed]')->checkboxList($model->getFilter('boolean'), ['inline' => true])->label("Closed");
	?>

	<?=
		$form->field($model, 'filter[completed]')->checkboxList($model->getFilter('boolean'), ['inline' => true])->label("Completed");
	?>

	<?=
		$form->field($model, 'filter[order]')->widget(Select2::className(), [
			'data' => $model->getFilter('order'),
		])->label("Order");
	?>

	<?=
		$form->field($model, 'filter[order_by]')->widget(Select2::className(), [
			'data' => $model->getFilter('order_by'),
		])->label("Order By");
	?>
	<?=
		Html::submitButton(Html::tag('span', '', array('class' => 'glyphicon glyphicon-filter')), array('class' => 'btn btn-primary btn-lg',
		"title" => "Run this filer"));
	?>
<?php ActiveForm::end(); ?>
</div>
<br>