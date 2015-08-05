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

trait BaseWidgetTrait
{	
	public $options = [
		'id' => 'search'
	];
	
	public $contentOptions = [
		'id' => 'search-results',
	];
	
	public $wrapperOptions = [
		'class' => 'wrapper',
		'id' => 'search-results-container'
	];
	
	public $formOptions = [
		'method' => 'get',
		'action' => '/search',
		'role' => 'search'
	];
	
	public function init()
	{
		parent::init();
		SearchAsset::register($this->getView());
	}
	
	protected function getDefaultHeader()
	{
		return Html::tag('form', '<div class="form-group" style="display:inline">
			<div style="display:table" class="input-group">
			  <input onFocus="this.value = this.value;" type="text" name="q" class="form-control" id="search-field" placeholder="Click here to start searching!!">
			  <span class="input-group-btn" style="width:1%">
				<button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
			  </span>
			</div><!-- /input-group -->
		</div><!-- /form-group -->', $this->formOptions);
	}
	
	protected function getDefaultContent()
	{
		return Html::tag('div', 
			Html::tag('div', 
				Html::tag('p', "You haven't searched for anything yet. Search above!"), [
				'class' => 'col-md-12 col-sm-12 col-lg-12 text-center'
			]),
		$this->contentOptions);
	}
}
?>