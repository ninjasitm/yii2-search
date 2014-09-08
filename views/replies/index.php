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

$widget->uniqid = !isset($widget->uniqid) ? uniqid() : $widget->uniqid;
$title = Yii::t('app', 'Replies: '.ucfirst($widget->parentType).": ".$widget->parentId);
switch(\Yii::$app->request->isAjax)
{
	case true:
	$this->title = $title;
	break;
}
$this->params['breadcrumbs'][] = $title;
?>
<h3>Comments</h3>
<div class="wrapper">
<?php
	$widget->options = is_array($widget->options) ? $widget->options : [
		'role' => 'entityMessages',
		'id' => 'messages'.$uniqid,
		'data-parent' => 'replyFormParent',
		'class' => 'absolute full-height'
	];
	$messages = ListView::widget([
		'summary' => false,
		'layout' => '{items}',
		'emptyText' => '',
		'options' => $widget->options,
		'dataProvider' => $dataProvider,
		'itemOptions' => ['tag' => false],
		'itemView' => function ($model, $key, $index, $_widget) use($widget) {
				return $widget->render('@nitm/views/replies/view',[
					'model' => $model, 
					'uniqid' => $widget->uniqid,
					'formId' => '#messages-form'.$widget->uniqid
				]);
		},
		'pager' => [
			'class' => \kop\y2sp\ScrollPager::className(),
			'container' => '#messages'.$uniqid,
			'item' => ".message",
			'negativeMargin' => 250,
			'delay' => 1000,
			'triggerText' => 'More messages',
			'noneLeftText' => 'No more messages'
		]
	
	]);
	$form = !isset($widget->formOptions['enabled']) || (isset($widget->formOptions['enabled']) && $widget->formOptions['enabled'] !== false) ? \nitm\widgets\replies\RepliesForm::widget($widget->formOptions) : '';
	switch(isset($widget->noContainer) && $widget->noContainer == true)
	{
		case false:
		$messages = Html::tag('div', $messages.$form, ['role' => 'replyFormParent']);
		break;
	}
	echo $messages;
?>
</div>
<?php if(\Yii::$app->request->isAjax ): ?>
<script type="text/javascript">
$nitm.onModuleLoad('replies', function () {
	$nitm.module('replies').init("<?=$widget->options['id']?>");
});
</script>
<?php endif; ?>
