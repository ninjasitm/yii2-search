<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\search\Token $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="token-search">

	<?php $form = ActiveForm::begin([
		'action' => ['index'],
		'method' => 'get',
	]); ?>

		<?= $form->field($model, 'tokenid') ?>

		<?= $form->field($model, 'userid') ?>

		<?= $form->field($model, 'token') ?>

		<?= $form->field($model, 'added') ?>

		<?= $form->field($model, 'active')->checkbox() ?>

		<?php // echo $form->field($model, 'level') ?>

		<?php // echo $form->field($model, 'revoked')->checkbox() ?>

		<?php // echo $form->field($model, 'revoked_on') ?>

		<div class="form-group">
			<?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
			<?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
		</div>

	<?php ActiveForm::end(); ?>

</div>
