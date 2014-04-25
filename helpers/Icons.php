<?php

namespace nitm\helpers;

use kartick\icons\Icon;

class Icon
{
	/**
	 * Get certain types of icons
	 * @param string $action
	 * @param string $attribute
	 * @param Object $model
	 */
	public static function forAction($action, $attribue, $model)
	{
		switch($model->hasProperty($action))
		{
			case true:
			switch($action)
			{
				case 'close':
				$ret_val = ($model->$attribute) ? Icon::show('unlock-alt') : Icon::show('lock');
				break;
				
				case 'resolve':
				$ret_val = ($model->$attribute) ? Icon::show('circle') : Icon::show('check-circle');
				break;
				
				case 'duplicate':
				$ret_val = ($model->$attribute) ? Icon::show('file-o') : Icon::show('copy');
				break;
				
				case 'update':
				$ret_val = Icon::show('pencil');
				break;
				
				case 'delete':
				$ret_val = Icon::show('remove');
				break;
			}
			break;
		}
		return $ret_val;
	}
}
?>