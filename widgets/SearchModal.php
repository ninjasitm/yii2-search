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

class SearchModal extends Modal
{
	use BaseWidgetTrait;

	public $options = [
		'id' => 'search-modal'
	];

	/*
	 * The size of the widget [large, mediaum, small, normal]
	 */
	public $size = 'large';

	public function run()
	{
		$this->header = !isset($this->header) ? Html::tag('br').$this->getDefaultHeader() : $this->header;
		$this->content = !($this->content) ? $this->getDefaultContent() : $this->content;
		return parent::run().Html::script("\$nitm.onModuleLoad('search', function (module) {
			module.initSearch('#".$this->options['id']."', 'modal');
		});");
	}

	protected function getDefaultHeader()
	{
		return Html::tag('form', Html::tag('div',
			'<br>'.
			Html::tag('div', '<div class="input-group">
			  <input onFocus="this.value = this.value;" type="text" name="q" class="form-control" id="search-field" placeholder="Click here to start searching!!">
			  <span class="input-group-btn">
				<button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
			  </span>', [
				'class' => "form-group col-lg-12 col-sm-12 col-md-12"
			]), [
		  'class' => 'row'
	  ], $this->formOptions));
	}
}
?>
