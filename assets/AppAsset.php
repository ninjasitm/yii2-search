<?php
/**
 * @link http://www.nitm.com/
 * @copyright Copyright (c) 2014 NITM Inc
 */

namespace nitm\assets;

use yii\web\AssetBundle;

/**
 * @author Malcolm Paul admin@nitm.com
 */
class AppAsset extends AssetBundle
{
	public $sourcePath = __DIR__;
	public $css = [
		'css/base.css'
	];
	public $js = [
		'js/nitm.js',
		'js/entity.js',
		'js/tools.js'
	];
	public $jsOptions = ['position' => \yii\web\View::POS_HEAD];
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapAsset',
		'yii\bootstrap\BootstrapPluginAsset',
	];
}
