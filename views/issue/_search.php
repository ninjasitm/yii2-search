<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\search\Issues $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="issues-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'parent_id') ?>

    <?= $form->field($model, 'parent_type') ?>

    <?= $form->field($model, 'notes') ?>

    <?= $form->field($model, 'resolved') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'author') ?>

    <?php // echo $form->field($model, 'closed_by') ?>

    <?php // echo $form->field($model, 'resolved_by') ?>

    <?php // echo $form->field($model, 'resolved_on') ?>

    <?php // echo $form->field($model, 'closed') ?>

    <?php // echo $form->field($model, 'closed_on') ?>

    <?php // echo $form->field($model, 'duplicate') ?>

    <?php // echo $form->field($model, 'duplicate_id') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
