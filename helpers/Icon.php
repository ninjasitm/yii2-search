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
	public static function forAction($action, $attribute, $model, $options=[])
	{
		switch($model->hasAttribute($attribute))
		{
			case true:
			switch($action)
			{
				case 'close':
				$icon = ($model->$attribute == 1) ? 'lock' : 'unlock-alt';
				break;
				
				case 'resolve':
				$icon = ($model->$attribute == 1) ? 'check-circle' : 'circle';
				break;
				
				case 'duplicate':
				$icon = ($model->$attribute == 1) ?  'flag' : 'flag-o';
				break;
			}
			$ret_val = BaseIcon::show($icon, $options);
			break;
			
			case false:
			switch($action)
			{
				case 'update':
				$ret_val = BaseIcon::show('pencil');
				break;
				
				case 'delete':
				$ret_val = BaseIcon::show('remove');
				break;
			}
			break;
		}
		return $ret_val;
	}
}
?>