<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var common\models\User $model
 */
$this->title = 'Reset password';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-reset-password">
	<div class="col-md-4 bg-light col-md-offset-4">
		<h1><?= Html::encode($this->title) ?></h1>
	
		<p>Please choose your new password:</p>
	
		<div class="row">
			<div class="col-lg-12 col-md-12">
				<?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>
					<?= $form->field($model, 'password')->passwordInput() ?>
					<div class="form-group">
						<?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
					</div>
				<?php ActiveForm::end(); ?>
			</div>
		</div>
	</div>
</div>
