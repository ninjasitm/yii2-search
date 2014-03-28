<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\models\LoginForm $model
 */
$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
	<div class="col-md-4 bg-light col-md-offset-4">
		<h1><?= Html::encode($this->title) ?></h1>
		<?= common\widgets\Alert::widget(); ?>
	
		<p>Please fill out the following fields to login:</p>
	
		<div class="row">
			<div class="col-lg-12 col-md-12">
				<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
					<?= $form->field($model, 'username') ?>
					<?= $form->field($model, 'password')->passwordInput() ?>
					<?= $form->field($model, 'token')->passwordInput() ?>
					<?= $form->field($model, 'rememberMe')->checkbox() ?>
					<div class="form-group">
						<?= Html::submitButton('Login', ['class' => 'btn btn-primary']) ?>
					</div>
					<div style="color:#999;margin:1em 0">
						If you forgot your password you can <?= Html::a('reset it', ['site/request-password-reset']) ?>.
					</div>
				<?php ActiveForm::end(); ?>
			</div>
		</div>
	</div>
</div>
