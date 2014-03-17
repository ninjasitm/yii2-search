<?php
	extract($data);
	//$value = is_array($value) ? json_encode($value) : $value;
	use yii\helpers\Html;
	use yii\widgets\ActiveForm;
?>

<div class="list-group-item col-md-12 col-lg-12" id="value_<?= $unique_id; ?>">
	<div class="row">
		<div class="col-md-2 col-lg-2 col-sm-3">
			<label><?= $name; ?></label>
		</div>
		<div class="col-md-9 col-lg-9 col-sm-7">
			<div id='<?= $unique_id; ?>'>
				<?= @$surround['open']; ?><div id='<?= $unique_id; ?>.div' role="edit_field_div" data-id="<?= $unique_id; ?>" data-type="<?= $model->config['current']['type']; ?>"><?= htmlentities($value); ?></div><?= @$surround['close']; ?>
				<div class="row">
				<?php $form = ActiveForm::begin(['id' => "value_comment_$unique_id",
								'action' => '/configuration/comment',
								'options' => ['class' => 'form-inline'],
								'fieldConfig' => [
										  'inputOptions' => ['class' => 'form-control']
										],
								]);
				?>
					<div class="col-md-11 col-lg-11 col-sm-10">
					<?php
						echo Html::activeTextInput($model, 'cfg_comment', array("placeholder" => "Type comment here...",
												     'value' => @$comment,
												     'class' => 'form-control input-sm col-md-10'
												     )
									   );
					?>
					</div>
					<div class="col-md-1 col-lg-1 col-sm-2">
					<?php
						echo Html::activeHiddenInput($model, 'cfg_id', array('value' => $unique));
						echo Html::submitButton('save', array('class' => 'btn btn-primary btn-xs',
										      'title' => "Edit $section.$name")
									);
					?>	
					</div>
				<?php ActiveForm::end(); ?>
				</div>
			</div>
		</div>
		<div class="col-lg-1 col-md-1 col-sm-2">
			<?php $form = ActiveForm::begin(['id' => "edit_value_form_$unique_id",
							'action' => '/configuration/edit',
							'options' => [
								'class' => 'form-inline'
							],
							'fieldConfig' => [
									  'inputOptions' => ['class' => 'form-control']
									],
							]);
			?>
			<?php
				echo Html::activeHiddenInput($model, 'cfg_w', array('value' => 'value'));
				echo Html::activeHiddenInput($model, 'cfg_id', array('value' => $unique));
				echo Html::activeHiddenInput($model, 'cfg_c', array('value' => $container_name));
				echo Html::activeHiddenInput($model, 'cfg_n', array('value' => $unique_id));
				echo Html::activeHiddenInput($model, 'cfg_v', array('value' => $value,
										    'role' => 'value'));
				echo Html::submitButton('edit', [
										'id' => 'edit_value',
										'class' => 'btn btn-primary btn-sm',
										'title' => "Edit $unique",
										'role' => 'edit_field_button',
										'data-id' => $unique.'.div',
										'data-type' => $model->config['current']['type'],
										"data-loading-text" => "Editing..."
									]
							);
			?>
			<?php ActiveForm::end(); ?>
			<?php 
				$model->setScenario('addValue');
				$form = ActiveForm::begin(['id' => 'delete_value',
							'action' => '/configuration/delete',
							'options' => ['class' => 'form-inline',
							'role' => 'delete_value'],
							'fieldConfig' => [
									  'inputOptions' => ['class' => 'form-control']
									],
							]);
			?>
			<?php
				echo Html::activeHiddenInput($model, 'cfg_id', array('value' => $unique));
				echo Html::activeHiddenInput($model, 'cfg_n', array('value' => $unique_id));
				echo Html::activeHiddenInput($model, 'cfg_w', array('value' => 'value'));
				echo Html::activeHiddenInput($model, 'cfg_c', array('value' => $container_name));
				echo Html::activeHiddenInput($model, 'cfg_container', array('value' => $unique.'.div'));
				echo Html::submitButton('del', [
							'class' => 'btn btn-danger btn-sm',
							'title' => "Are you sure you want to delete the $unique_id",
							"data-loading-text" => "Deleting..."
						]
					);
			?>
			<?php ActiveForm::end(); ?>
		</div>
	</div>
</div>