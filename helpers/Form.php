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
			$options['modelOptions'] = is_array($options['modelOptions']) ? $options['modelOptions'] : null;
			$model->requestModel = new $options['modelClass']($options['modelOptions']);
			$model->requestModel->unique = @$options['id'];
			switch($model->validate())
			{
				case true:
				$model->setScenario($options['scenario']);
				//this means we found our object
				switch($options['modelClass'])
				{
					case $model->className():
					switch($model->unique)
					{
						case null:
						$model = $model;
						break;
						
						default:
						$find = $model->find($model->unique);
						switch(1)
						{
							case isset($options['modelOptions']['withThese']):
							$find->with($options['modelOptions']['withThese']);
							break;
						}
						$found = $find->one();
						$model = ($found instanceof $options['modelClass']) ? $found : $model;
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
					//Get the data accoriding to get$options['param'] functions
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
				switch(!is_null($model) || $force)
				{
					case true:
					$options['viewArgs'] = (isset($options['viewArgs']) && is_array($options['viewArgs'])) ? $options['viewArgs'] : (isset($options['viewArgs']) ? [$options['viewArgs']] : []);
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
					switch(\yii::$app->request->isAjax)
					{
						case false:
						Response::$viewOptions['options'] = [
							'class' => 'wrapper full-width full-height'
						];
						break;
					}
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