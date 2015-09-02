<?php
/**
* @link http://www.yiiframework.com/
* @copyright Copyright (c) 2008 Yii Software LLC
* @license http://www.yiiframework.com/license/
*/

namespace nitm\search\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\base\Widget;
use kartik\widgets\ActiveForm;
use kartik\widgets\ActiveField;
use kartik\widgets\Select2;

class SearchBar extends \yii\base\Widget
{	
	use BaseWidgetTrait;
	public $header;
	public $content;
	
	/*
	 * The size of the widget [large, medium, small, normal]
	 */
	public $size = 'large';
	
	public function init()
	{
		parent::init();
		Html::addCssClass($this->contentOptions, 'row');
		Html::addCssStyle($this->wrapperOptions, [
			'position' => 'fixed !important',
			'overflow-y' => 'scroll',
			'border-bottom-left-radius' => '6px', 
			'border-bottom-right-radius' => '6px', 
			'box-shadow' => '0px 2px 6px #999',
			'left' => 0, 'right' => 0, 'top' => 'auto',
			'max-height' => '90%',
			'margin' => '3px auto',
			'display' => 'none',
			'color' => "#fff",
			'padding' => '15px 0',
			'background' => 'rgba(90, 90, 90, 0.95)'
		]);
		Html::addCssClass($this->wrapperOptions, 'col-lg-6 col-md-8 col-sm-12');
		Html::addCssClass($this->formOptions, 'navbar-form');
		SearchAsset::register($this->getView());
	}
	
	public function run()
	{
		$header = !isset($this->header) ? $this->getDefaultHeader() : $this->header;
		$content = !($this->content) ? Html::tag('div', $this->getDefaultContent(), $this->wrapperOptions) : $this->content;
		
		$widget = $header.$content;
		
		return Html::tag('span', $widget, $this->options).Html::script("\$nitm.onModuleLoad('search', function (module) {
			module.initSearch('#".$this->options['id']."', 'bar');
		});");
	}
}
?>