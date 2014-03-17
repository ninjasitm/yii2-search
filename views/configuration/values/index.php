<?php
use yii\helpers\Html;
?>
<?php switch(sizeof($values) >= 1) :
	case true: ?>
	<?php switch(is_array($values)) :
		case true: ?>
		<div class="list-group">
		<?php
			foreach($values as $section=>$value)
			{
				echo $this->render('values/value',  array("model" => $model,
									  "section" => $section,
									  "data" => $value,
									  "surround" => @$surround));
			}
		?>
		</div>
		<?php break;?>
	<?php endswitch;?>
	<?php break;?>
<?php endswitch;?>

<?php
	//render the footer for this section
	echo $this->render("values/footer", array("model" => $model,
							"section" => $parent,
							"container" => $model->config['current']['container']));
?>

<?php if(@is_array($this->js)) : ?>
<?php
	$aman = $this->getAssetManager();
	array_walk($aman->bundles, function ($bundle) use($aman){
		 foreach($bundle->js as $file)
		 {
			 //Only load the validtion scripts
			 switch($file)
			 {
				case 'yii.validation.js':
				case 'yii.activeForm.js':
				$aman->publish($bundle->sourcePath);
				echo Html::jsFile($aman->getPublishedUrl($bundle->sourcePath)."/".$file)."\n";
				break;
			 }
		 }
	});
?>
<script type="text/javascript">
<?php
	array_walk($this->js[static::POS_READY], function ($v) {
		echo $v;
	});
?>

</script>
<?php endif; ?>