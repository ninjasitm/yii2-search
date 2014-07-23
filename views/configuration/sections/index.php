<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\ActiveField;
?>
<div class="configuration-header">
	<div class="col-lg-12 col-md-12 col-sm-12">
        <div class="list-group">
            <div class="list-group-item active"><b>Note:</b></div>
            <div class="list-group-item">To set a value to empty simply set it to <b class='alert alert-danger'>null</b></div>
        </div>
    </div>
</div>
<div class="configuration-sections">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="pull-left">
        <?php 
            $form = ActiveForm::begin(['id' => 'show_section',
                        'action' => '/configuration/get',
                        'options' => ['class' => 'form-inline'],
                        'fieldConfig' => [
                                'inputOptions' => ['class' => 'form-control pull-left']
                                ],
                        ]); ?>
        <?=
            $form->field($model, 'section'
                        )->dropDownList($model->config['current']['sections'])->label("Select ".$model->config['current']['type_text']." to edit:", ['class' => 'sr-only']); 
        ?>
        <?php 
            echo Html::activeHiddenInput($model, 'what', array('value' => 'section'));
            echo Html::activeHiddenInput($model, 'getValues', array('value' => true));
            echo Html::activeHiddenInput($model, 'container', array('value' => $model->config['current']['container']));
            echo Html::submitButton('Change', [
                    'class' => 'btn btn-primary',
                    "data-loading-text" => "Loading..."
                ]
            );
            ActiveForm::end(); 
        ?>
        </div>
        <div class="pull-left">
        <?php $form = ActiveForm::begin(['id' => 'delete_section',
                        'action' => '/configuration/delete',
                        'options' => ['class' => 'form-inline',
                        'role' => 'deleteSection'],
                        'fieldConfig' => [
                                'inputOptions' => ['class' => 'form-control']
                                ],
                        ]); ?>
        <?php
            echo Html::activeHiddenInput($model, 'what', array('value' => 'section'));
            echo Html::activeHiddenInput($model, 'container', array('value' => $model->config['current']['container']));
            echo Html::activeHiddenInput($model, 'section', array('value' => @$model->config['current']['section']));
            echo Html::submitButton('Delete', [
                    'class' => 'btn btn-danger pull-left',
                    "data-loading-text" => "Deleting..."
                    ]
                );
        ?>
        <?php ActiveForm::end(); ?>
        </div>
	</div>
</div>
<div class="configuration-body">
	<?php
		echo $this->render('sections',  array("model" => $model));
	?>
</div>
<div class="configuration-footer">
    <div class="col-md-12 col-lg-12">
        <div class="bottom">
            <hr>
            <h4>Add a new section to the configuration</h4>
            <?php $form = ActiveForm::begin(['id' => 'new_section',
                            'action' => '/configuration/create',
                            'options' => ['class' => 'form-inline' ],
                            'fieldConfig' => [
                                    'inputOptions' => ['class' => 'form-control pull-left']
                                    ],
                            'enableAjaxValidation' => true
                            ]);
            ?>
            <?=
                $form->field($model, 'value')->textInput(array('placeholder' => 'Section name...'))->label("Name the section", ['class' => 'sr-only']);
            ?>
            <?php
                echo Html::activeHiddenInput($model, 'what', array('value' => "section"));
                echo Html::activeHiddenInput($model, 'container', array('value' => $model->config['current']['container']));
                echo Html::submitButton('Create Section', array('class' => 'btn btn-primary'));
            ?>
            <?php ActiveForm::end(); ?>
        </div>
	</div>
</div>