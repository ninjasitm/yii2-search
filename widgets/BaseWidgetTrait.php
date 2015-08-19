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
	public $typeList;
	
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
		$searchBar = Html::tag('div', 
			$this->getPrepend()
			.Html::textInput('q', '', [
				'class' => 'form-control',
				'id' => 'search-field',
				'placeholder' => 'Click here to start searching!',
				'onfocus' => 'this.value = this.value'
			])
			.$this->getAppend(), [
			"style" => "display:table",
			"class" => "input-group"
		]);
		return Html::tag('form', Html::tag('div', $searchBar, [
			"class" => "form-group",
			"style" => "display:inline"
		]), $this->formOptions);
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
	
	/**
	 * Get the contend to be appended to the search bar
	 */
	protected function getAppend()
	{
		return Html::tag('span', 
			Html::submitButton(Html::tag('i', '', [
				'class' => 'fa fa-search'
			]), [
				"class" => "btn btn-default"
			]), [
			'class' => 'input-group-btn', 
			'style' => 'width: 1%'
		]);
	}
	
	/**
	 * Get the content to be prepended to the search bar
	 */
	protected function getPrepend()
	{
		$ret_val = '';
		switch(1)
		{
			case isset($this->typeList) && !empty($this->typeList):	
			$ret_val = Html::tag('span', 
				Html::dropdownList('type', 
					\Yii::$app->request->get('type'), 
					$this->typeList, [
						"class" => "form-control"
				]), [
				'class' => 'input-group-btn', 
				'style' => 'width: 1%'
			]);
			break;
		}
		return $ret_val;
	}
}
?>