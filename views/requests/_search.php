<?php
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\Select2;
use yii\widgets\ActiveField;
?>
<?php
	echo $this->context->alertWidget();
	echo $this->context->legendWidget();
?>
<?= 
	\nitm\widgets\modal\Modal::widget([
		'toggleButton' => [
			'tag' => 'a',
			'label' => Html::tag('h3', strtoupper("Add New ".$model->primaryModel->isWhat())), 
			'href' => \Yii::$app->urlManager->createUrl([$model->primaryModel->isWhat().'/form/create', '__format' => 'modal']),
			'title' => Yii::t('yii', "Add a new ".$model->primaryModel->isWhat()),
			'role' => 'dynamicAction createAction disabledOnClose',
		],
		'dialogOptions' => [
			"class" => "modal-full"
		]
	]);
?>
<h3 class="header text-left">FILTER USING THE FOLLOWING</h3>
<div id="filters">
<?php $form = ActiveForm::begin(['id' => 'filter',
	'method' => 'get',
	'type' => ActiveForm::TYPE_HORIZONTAL,
	'action' => '/'.$model->primaryModel->isWhat().'/search?__format=json',
	'method' => 'get',
	'options' => [
		'class' => 'form-horizontal',
		"role" => "filter",
		'data-id' => $model->primaryModel->isWhat()
	],
	'fieldConfig' => [
		'inputOptions' => ['class' => 'form-control'],
		'template' => "{label}\n<div class=\"col-lg-10 col-md-10\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
		'labelOptions' => ['class' => 'col-lg-2 col-md-2 control-label'],
	]
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
	?><br><br>
	
	<?=
		$form->field($model, 'filter[exclusive]', [
			'options' => [
				'data-toggle' => 'tooltip',
				'title' => "When set to Yes everything set below will be used to find results. Otherwise the search will find anything that matches at least one of the criteria you set."
			],
			'template' => '{label}\n<div class=\"col-lg-4 col-md-4\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>',
		])->widget(\kartik\widgets\SwitchInput::className(),
		[
			'pluginOptions' => [
				'size' => 'small',
				'onText' => 'Yes',
				'offText' => 'No'
			]
		])->label("Match All");
	?>
	
	<?=
		$form->field($model, 'text')->textInput()->label("Search");
	?>

	<?=
		$form->field($model, 'type_id')->widget(Select2::className(), [
			'options' => [
				'multiple' => true,
				'placeholder' => 'Select types...'
			],
			'data' => $model->primaryModel->getCategoryList($model->primaryModel->isWhat().'-categories'),
		])->label("Type");
	?>

	<?=
		$form->field($model, 'request_for_id')->widget(Select2::className(), [
			'options' => [
				'multiple' => true,
				'placeholder' => 'Select for...'
			],
			'data' => $model->primaryModel->getCategoryList($model->primaryModel->isWhat().'-for'),
		])->label("For");
	?>

	<?=
		$form->field($model, 'author_id')->widget(Select2::className(), [
			'options' => [
				'multiple' => true,
				'placeholder' => 'Select authors...'
			],
			'data' => $model->getFilter('author'),
		])->label("Author");
	?>

	<?=
		$form->field($model, 'closed')->checkboxList($model->getFilter('boolean'), ['inline' => true])->label("Closed");
	?>

	<?=
		$form->field($model, 'completed')->checkboxList($model->getFilter('boolean'), ['inline' => true])->label("Completed");
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
		Html::submitButton(
			Html::tag('span', '', ['class' => 'glyphicon glyphicon-filter']), 
			[
				'class' => 'btn btn-primary btn-lg',
				"title" => "Run this filer"
			]
		);
	?><br><br>
<?php ActiveForm::end(); ?>
</div>
<br>