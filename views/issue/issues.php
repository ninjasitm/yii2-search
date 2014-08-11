<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use yii\bootstrap\Modal;
use nitm\models\Issues;
use yii\widgets\Pjax;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Issues $searchModel
 */
 
$uniqid = uniqid();
Pjax::begin([
	'enablePushState' => false,
	'linkSelector' => "a[data-pjax], [data-pjax] a",
	'formSelector' => "[data-pjax]",
	'options' => [
		'id' => 'issues-list'
	]
]);
echo $this->render('_search', [
	'model' => $searchModel, 
	'enableComments' => $enableComments,
	'parentType' => $parentType,
	'parentId' => $parentId
]);
echo Html::tag('div', '', ['id' => 'issues-alerts-message']);
echo ListView::widget([
	'options' => [
		'id' => 'issues-'.$filterType.'-list'.$uniqid,
		'style' => 'color:black;'
	],
	'dataProvider' => $dataProvider,
	'itemOptions' => ['class' => 'item'],
	'itemView' => function ($model, $key, $index, $widget) use($options){
		$viewOptions = array_merge(['model' => $model], $options);
		return $widget->render('@nitm/views/issue/view', $viewOptions);
	},
	'pager' => ['class' => \kop\y2sp\ScrollPager::className()]

]);
?>
<script type="text/javascript">
$nitm.onModuleLoad('issueTracker', function () {
	$nitm.module('issueTracker').init("issues-<?=$filterType.'-list'.$uniqid?>");
	$nitm.module('tools').initVisibility("issues-<?=$filterType.'-list'.$uniqid?>");
}, 'issueTrackerIssues');
</script>
<br>
<?php Pjax::end(); ?>
