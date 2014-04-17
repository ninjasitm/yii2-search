<?php

namespace nitm\helpers;

use yii\base\Behavior;
use nitm\helpers\Response;

/**
 * Form trait which supports teh retrieval of form variables
 */
class Form extends Behavior
{
	public static function getVariables($model, $options, $modalOptions=[])
	{
		$ret_val = [
			"success" => false, 
			'data' => \yii\helpers\Html::tag('h3', "No form found", ['class' => 'alert alert-danger text-center'])
		];
		switch(!empty($options['scenario']) && (is_a($model, \nitm\models\Data::className())))
		{
			case true:
			$attributes = [];
			$model->unique = @$options['id'];
			$model->requestModel = new $options['modelClass'];
			$model->requestModel->unique = @$options['id'];
			switch($model->validate())
			{
				case true:
				$model->setScenario($options['scenario']);
				//this means we found our object
				switch($options['modelClass'])
				{
					//if we're dealing with a Request object then simply find the right Request info
					case $model->className():
					switch($model->unique)
					{
						case null:
						$model = $model;
						break;
						
						default:
						$found = $model->findOne($model->unique);
						$model = is_null($found) ? $model : $found;
						$model = $model;
						break;
					}
					switch(!is_null($options['provider']) && $model->hasMethod($options['provider']))
					{
						case true:
						$model = call_user_func_array([$model, $options['provider']], $args);
						$model->requestModel = $model;
						break;
					}
					break;
					
					default:
					//Does Request have this function?
					//Get the data accoriding to Request get$options['param'] functions
					$model->requestModel->queryFilters['limit'] = 1;
					$model->requestModel->queryFilters['unique'] = $model->requestModel->unique;
					$model = $model->requestModel->getArrays()[0];
					switch($model->hasMethod($options['provider']))
					{
						case true:
						call_user_func_array([$model, $options['provider']], $args);
						$model->queryFilters['unique'] = $model->provid;
						$model->queryFilters['limit'] = 1;
						$found = $model->getArrays();
						$model = empty($found) ? $model : $found[0];
						break;
					}
					break;
				}
				switch((sizeof($model) >= 1) || $force)
				{
					case true:
					$options['viewArgs'] = (isset($options['viewArgs']) && is_array($options['viewArgs'])) ? $options['viewArgs'] : (isset($otions['viewArgs']) ? [$options['viewArgs']] : []);
					$data = (!is_null($options['dataProvider']) && $model->hasProperty($options['dataProvider'])) ? $data->$dataProvider : $model;
					Response::$viewOptions = [
						"view" => $options['view'],
						'modalOptions' => $modalOptions,
						'title' => (($model->hasProperty(@$options['title'][0]) || $model->hasAttribute(@$options['title'][0])) ? @$model->$options['title'][0] : ($model->getIsNewRecord() ? "Create" : "Update")." ".ucfirst($model->isWhat()))
					];
					Response::$viewOptions["args"] = array_merge([
							"action" => $options['param'],
							"model" => $model,
							'data' => $data,
						], $options['viewArgs']);
					$ret_val['data'] = $data;
					$ret_val['success'] = true;
					$ret_val['action'] = 'view_'.$options['param'];
					break;
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
	
}
?>