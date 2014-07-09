<?php

namespace nitm\helpers;

use kartik\icons\Icon as BaseIcon;

class Icon
{
	/**
	 * Get certain types of icons
	 * @param string $action
	 * @param string $attribute
	 * @param Object $model
	 * @param mixed $options
	 */
	public static function forAction($action, $attribute=null, $model=null, $options=[])
	{
		$icon = $action;
		switch(is_object($model) && $model->hasAttribute($attribute))
		{
			case true:
			switch($action)
			{
				case 'close':
				$icon = ($model->$attribute == 1) ? 'lock' : 'unlock-alt';
				break;
				
				case 'resolve':
				case 'complete':
				$icon = ($model->$attribute == 1) ? 'check-circle' : 'circle';
				break;
				
				case 'duplicate':
				$icon = ($model->$attribute == 1) ?  'flag' : 'flag-o';
				break;
				
				case 'disable':
				$icon = ($model->$attribute == 1) ?  'circle-o' : 'circle';
				break;
			}
			break;
			
			case false:
			switch($action)
			{
				case 'update':
				$icon = 'pencil';
				break;
				
				case 'delete':
				$icon = 'trash-o';
				break;
				
				case 'comment':
				$icon = 'comment';
				break;
				
				case 'view':
				$icon = 'eye';
				break;
			}
			break;
		}
		if(isset($options['size']))
		{
			$options['class'] = isset($options['class']) ? $options['class'] : '';
			$options['class'] .= \Yii::$app->params['icon-framework']."-".$options['size'];
			unset($options['size']);
		}
		return BaseIcon::show($icon, $options);
	}
}
?>