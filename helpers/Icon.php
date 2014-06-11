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
		$icon = '';
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
				$icon = (is_object($model) && $model->hasAttribute($attribute) && $model->$attribute == 1) ?  'pencil' : 'pencil';
				break;
				
				case 'delete':
				$icon = (is_object($model) && $model->hasAttribute($attribute) && $model->$attribute == 1) ?  'plus' : 'trash-o';
				break;
			}
			break;
		}
		return BaseIcon::show($icon, $options);
	}
}
?>