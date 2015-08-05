<?php
/**
 * @link http://www.nitm.com/
 * @copyright Copyright (c) 2014 Ninjas In The Machine INC
 */

namespace nitm\search\widgets;

use yii\web\AssetBundle;

/**
 * @author Malcolm Paul <lefteyecc@nitm.com>
 */
class SearchAsset extends AssetBundle
{
	public $sourcePath = '@vendor/mhdevnet/yii2-search/widgets/assets';
	public $js = [
		'js/search.js'
	];
	public $css = [
	];	
	public $depends = [
		'nitm\assets\AppAsset',
	];
}