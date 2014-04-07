<?php
use yii\bootstrap\Dropdown;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
$this->title = "Edit configration parameters";
?>
<div class="col-md-3 col-lg-3 col-sm-12 full-height">
	<div class="row">
        <div class="configuration-actions">
        <?= \nitm\widgets\alert\widget\Alert::widget(); ?>
        <?php
            echo $this->render('containers/index',
                       array("model" => $model)
                       );
        ?>
        </div>
    </div>
</div>
<div class="col-md-9 col-lg-9 col-sm-12 full-height">
	<div class="configuration-container" id="configuration_container">
            <?php
                switch(@$model->config['load']['sections'])
                {
                    case true:
                    echo $this->render('sections/index',  array("model" => $model));
                    break;
                    
                    default:
                    echo Html::tag('div',
                            Html::tag('h2',  
                                @$model->config['messges']['reason']
                            ),
                            ['class' => "alert alert-danger"]
                        );
                    break;
                }
            ?>
    </div>
</div>

<script language='javascript' type="text/javascript">
$nitm.addOnLoadEvent(function () {
	$nitm.configuration.prepareChanging();
	$nitm.configuration.prepareUpdating();
});
</script>