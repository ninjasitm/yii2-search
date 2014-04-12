<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="well" id="create_value_container">
        <?php $form = ActiveForm::begin(['id' => "create_new_value_$section",
                                        'action' => '/configuration/create',
                                        'options' => ['class' => 'form-inline',
                                        'role' => 'create_new_value'],
                                        'fieldConfig' => [
                                                          'inputOptions' => ['class' => 'form-control']
                                                        ],
                                        'enableAjaxValidation' => true,
                                        ]);
        ?>
        <?php
                echo $form->field($model, 'cfg_n')->textInput(array('placeholder' => 'Setting name...'))->label("Name", ['class' => 'sr-only']);
                echo $form->field($model, 'cfg_v')->textInput(array('placeholder' => 'Setting value...'))->label("Value", ['class' => 'sr-only']);
        ?>
        <?php
                echo Html::activeHiddenInput($model, 'cfg_c', array('value' => $container));
                echo Html::activeHiddenInput($model, 'cfg_s', array('value' => $section));
                echo Html::activeHiddenInput($model, 'cfg_w', array('value' => 'value'));
                echo Html::submitButton('Add Key/Value', [
												'class' => 'btn btn-primary',
												'title' => "Add value to $section",
												"data-loading-text" => "Adding..."
											]
                                        );
        ?>
        <?php ActiveForm::end(); ?>
</div>