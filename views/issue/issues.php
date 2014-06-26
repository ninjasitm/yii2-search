<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use yii\bootstrap\Modal;
use nitm\models\Issues;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Issues $searchModel
 */
echo ListView::widget([
	'options' => [
		'id' => 'issues'.$parentId
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
$nitm.addOnLoadEvent(function () {
	$nitm.issueTracker.init("issue<?=$parentId?>");
	<?php if(\Yii::$app->request->isAjax): ?>
	$nitm.tools.initVisibility("issue<?=$parentId?>");
	<?php endif ?>
});
</script>
