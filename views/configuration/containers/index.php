<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?> 
		<?php 
			switch ($model->config['load']['types'])
			{
				case true:
		?>
		<div class="well">
		<?php
			$_engines = [];
			array_walk($model->config['supported'], function ($k, $v) use(&$_engines, $model) {
				$url = \Yii::$app->urlManager->createUrl('configuration/load/'.$v."/");
				switch($model->cfg_e)
				{
					case $v:
					$model->config['current']['engine_url'] = $url;
					break;
				}
				$_engines[$url] = $k;
			});
			echo Html::label(
				("Current engine: ".$model->cfg_e)
			);
			echo Html::dropDownList(
				'engine',
				@$model->config['current']['engine_url'],
				$_engines,
				[
					"class" => "btn-default form-control",
					"role" => "changeSubmit"
				]
			);
		?>
		</div>
		<?php
				break;

				default:
				echo Html::tag('div',
						Html::tag('h4',  
							"Unable to load supported types",  
							array('class' => "alert alert-danger")), 
						array('class' => 'control-label')
					);
				break;
			}
		?>
		<?php
			switch(@$model->config['load']['containers'])
			{
				case true:
				switch ($model->config['current']['type'])
				{
					case 'file':
					case 'db':
					echo $this->render('containers/actions',
							   array("model" => $model)
							);
					break;
				}
				break;

				default:
				echo Html::tag('div',
						Html::tag('h4',  
							"Unable to load configuration containers. Check to make sure configuration containers exist",  
							array('class' => "alert alert-danger")), 
						array('class' => 'control-label')
					);
				break;
			}
		?>