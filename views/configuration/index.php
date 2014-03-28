<?php
use yii\bootstrap\Dropdown;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
$this->title = "Edit configration parameters";
?>
<div class="col-md-3 col-lg-3 col-sm-12 full-height">
	<div class="configuration-actions">
	<?= \nitm\widgets\alert\widget\Alert::widget(); ?>
	<?php
		echo $this->render('containers/index',
				   array("model" => $model)
				   );
	?>
	</div>
</div>
<div class="col-md-9 col-lg-9 col-sm-12 col-md-offset-3 col-lg-offset-3 full-height">
	<div class="configuration-container" id="configuration_container">
		<div class="row">
		<?php
			switch(@$model->config['load']['sections'])
			{
				case true:
				echo $this->render('sections/index',  array("model" => $model));
				break;
				
				default:
				echo Html::tag('div',
						Html::tag('h2',  
							@$model->config['messges']['reason'], 
							array('class' => "well alert alert-warning")
						), 
						array('class' => 'row')
					);
				break;
			}
		?>
		</div>
	</div>
</div>

<script language='javascript' type="text/javascript">
addOnLoadEvent(function () {
	var config_path = '<?php echo $model->config['current']['path']; ?>';
	var c = new Configuration();	
	c.prepareChanging();
	c.prepareUpdating();
});
</script>