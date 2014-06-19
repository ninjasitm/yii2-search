<?php

namespace nitm\helpers;

use yii\imperavi\Widget;

/**
 * This class makes it easier to instantiate an editor widget by providing options 
 * for differrent types of widgets. THis class is based on the Redactor editor
 * by imperavi
 *
 * This wrapper uses air buttons by default with a minimal toolbar
 */

class Editor extends \yii\imperavi\Widget
{
	public $role = 'message';
	public $size;
	public $toolbarSize;
	
	public $options =  [
		'air'=> true,
		'height' => 'auto',
		'buttonOptions' => [
			'class' => 'btn btn-sm chat-form-btn'
		]
	];
	
	public $htmlOptions = [
		'style' => 'z-index: 98',
		'rows' => 3,
	];
	
	public function run()
	{
		switch($this->toolbarSize)
		{
			case 'full':
			$this->options['airButtons'] = [
				'html', 'formatting',  'bold', 'italic', 'deleted', 
				'unorderedlist', 'orderedlist', 'outdent', 'indent', 
				'image', 'video', 'file', 'table', 'link', 'alignment', 'horizontalrule'
			];
			break;
			
			case 'medium':
			$this->options['airButtons'] = [
				'bold', 'italic', 'deleted', 
				'unorderedlist', 'orderedlist', 
				'image', 'video', 'file', 'table', 'link'
			];
			break;
			
			default:
			$this->options['airButtons'] = [
				'bold', 'italic', 'deleted', 'link'
			];
			break;
		}
		switch($this->size)
		{
			case 'full':
			$this->htmlOptions['style'] = "height: 100%";
			break;
			
			case 'medium':
			$this->htmlOptions['rows'] = 6;
			break;
			
			default:
			$this->htmlOptions['rows'] = 3;
			break;
		}
		$this->htmlOptions['role'] = $this->role;
		return parent::run();
	}
}

?>