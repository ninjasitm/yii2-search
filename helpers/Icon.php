<?php

namespace nitm\helpers;

class Icon extends \kartik\icons\Icon
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
		switch(!is_null($model) || !is_null($attribute))
		{
			case true;
			switch(1)
			{
				case is_object($model) && $model->hasAttribute($attribute):
				$value = $model->getAttribute($attribute);
				break;
				
				default;
				$value = $attribute;
				break;
			}
			switch($action)
			{
				case 'close':
				$icon = ($value == 1) ? 'lock' : 'unlock-alt';
				break;
				
				case 'resolve':
				case 'complete':
				$icon = ($value == 1) ? 'check-circle' : 'circle';
				break;
				
				case 'duplicate':
				$icon = ($value == 1) ?  'flag' : 'flag-o';
				break;
				
				case 'disable':
				$icon = ($value == 1) ?  'check-circle' : 'ban';
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
		return Icon::show($icon, $options);
	}
}
?>