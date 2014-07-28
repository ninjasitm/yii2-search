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
<h3 class="header text-left">
		<?=
			 Html::a(
					strtoupper("Add New ".$model->isWhat()), 
					$model->isWhat()."/form/create?__format=modal",
					[
						'class' => "", 
						'title' => "Add a new ".$model->isWhat(),
						'data-toggle' => 'modal',
						'data-target' => '#view'
					]
				);
		?>
</h3>
<h3 class="header text-left">FILTER USING THE FOLLOWING</h3>
<div id="filters">

<?php $form = ActiveForm::begin(['id' => 'filter',
	'type' => ActiveForm::TYPE_HORIZONTAL,
	'method' => 'get',
	'action' => '/'.$model->isWhat().'/search?__format=json',
	'options' => [
		'class' => 'form-horizontal',
		"role" => "filter",
		'data-id' => 'requests'
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
		$form->field($model, 'filter[type]')->widget(Select2::className(), [
			'data' => $model->getCategoryList($model->isWhat().'-categories'),
		])->label("Type");
	?>

	<?=
		$form->field($model, 'filter[request_for]')->widget(Select2::className(), [
			'data' => $model->getCategoryList($model->isWhat().'-for'),
		])->label("Request For");
	?>

	<?=
		$form->field($model, 'filter[status]')->widget(Select2::className(), [
			'data' => $model->getStatuses(),
		])->label("Status");
	?>

	<?=
		$form->field($model, 'filter[author]')->widget(Select2::className(), [
			'data' => $model->getFilter('author'),
		])->label("Author");
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