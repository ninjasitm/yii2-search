<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use kartik\icons\Icon;
use nitm\widgets\replies\RepliesModal;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Replies $searchModel
 */

$uniqid = !isset($uniqid) ? uniqid() : $uniqid;
$title = Yii::t('app', 'Replies: '.ucfirst($parentType).": ".$parentId);
switch(\Yii::$app->request->isAjax)
{
	case true:
	$this->title = $title;
	break;
}
$this->params['breadcrumbs'][] = $title;
if($useModal == true) {
	$modalOptions =[
		'class' => 'btn btn-success',
		'data-toggle' => 'modal',
		'data-target' => '#replies-modal-form'
	];
} else {
	$modalOptions = ['class' => 'btn btn-success'];
}
?>
<h3>Comments</h3>
<div class="wrapper">
	<?php
		$options = is_array($options) ? $options : [
			'role' => 'entityMessages',
			'id' => 'messages'.$uniqid,
			'data-parent' => 'replyFormParent'
		];
		echo ListView::widget([
			'summary' => false,
			'layout' => '{items}',
			'emptyText' => '',
			'options' => $options,
			'dataProvider' => $dataProvider,
			'itemOptions' => ['class' => 'item'],
			'itemView' => function ($model, $key, $index, $widget) use($uniqid) {
					return $widget->render('@nitm/views/replies/view',['model' => $model, 'uniqid' => $uniqid]);
			},
			'pager' => ['class' => \kop\y2sp\ScrollPager::className()]
		
		]);
	?>
	<?php
		if(isset($modal))
		{
			echo $modal;
		}
	?>
</div>
<?php if(\Yii::$app->request->isAjax ): ?>
<script type="text/javascript">
$nitm.onModuleLoad('replies', function () {
	$nitm.module('replies').init("messages<?=$uniqid?>");
});
</script>
<?php endif; ?>
