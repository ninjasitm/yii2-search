<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Token $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="token-form <?= $this->context->getStatusIndicator($model); ?>">

	<?php $form = ActiveForm::begin(); ?>
		<?= Html::label('User', 'usersearch', []); ?>
		<?php 
			if(!$model->isNewRecord)
			{
				echo Html::tag('h4', \nitm\module\models\User::getFullName($model->userid));
			}
			else
			{
				echo \yii\jui\AutoComplete::widget([
					'name' => 'name',
					'attribute' => 'name',
					'options' => [
						'class' => 'form-control',
						'id' => 'usersearch',
						'role' => 'autocompleteSelect',
						'data-select' => \yii\helpers\Json::encode([
							"value" => "unique", 
							"label" => "name", 
							"container" => "token-userid"
						]),
					],
					'clientOptions' => [
						'source' => '/autocomplete/user',
					],
				]);
			}
		?>
		<?= Html::activeHiddenInput($model, 'userid') ?>
		<?= $form->field($model, 'active')->checkbox() ?>

		<?= $form->field($model, 'revoked')->checkbox() ?>

		<?= $form->field($model, 'level')->dropDownList($model->getLevels()) ?>

		<div class="form-group">
			<?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
		</div>

	<?php ActiveForm::end(); ?>

</div>
