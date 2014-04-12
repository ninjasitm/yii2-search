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
				echo $this->render('value',  array("model" => $model,
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
	echo $this->render("footer", array("model" => $model,
							"section" => $parent,
							"container" => $model->config['current']['container']));
?>