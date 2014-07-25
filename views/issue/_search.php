<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use nitm\models\Issues;
use nitm\helpers\Icon;

/**
 * @var yii\web\View $this
 * @var app\models\search\Issues $model
 * @var yii\widgets\ActiveForm $form
 */

$uniqid = uniqid();
?>

<br>
<div class="issues-search">
	
	<?php $form = ActiveForm::begin([
		'method' => 'get',
        "type" => ActiveForm::TYPE_VERTICAL,
        'action' => \Yii::$app->urlManager->createUrl(["/issue/issues/$parentType/$parentId", 
			Issues::COMMENT_PARAM => $enableComments,
			$model::SEARCH_PARAM => 1,
			$model::SEARCH_PARAM_BOOL => 1
		]),
        'options' => [
            "role" => "searchIssue",
            'id' => 'issue-search-form'.$uniqid,
			'data-pjax' => 1,
        ],
        'fieldConfig' => [
            'inputOptions' => ['class' => 'form-control input-sm'],
            'template' => "{label}\n<div class=\"col-lg-12 col-md-12\">{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
            'labelOptions' => ['class' => 'control-label sr-only'],
        ],
        'enableAjaxValidation' => true
    ]); ?>

    <?= $form->field($model, 'text', [
			'addon' => [
			'append' => [
				'content' => Html::button(Icon::forAction('search'), ['class'=>'btn btn-primary btn-sm', 'data-pjax' => 1]),
				'asButton' => true
			]
		]
	]) ?>

    <?php // echo $form->field($model, 'author') ?>

    <?php // echo $form->field($model, 'closed') ?>

    <?php // echo $form->field($model, 'duplicate') ?>

    <?php ActiveForm::end(); ?>

</div>
