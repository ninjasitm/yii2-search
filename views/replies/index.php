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
		'data-parent' => 'replyFormParent'
	];
	$messages = ListView::widget([
		'summary' => false,
		'layout' => '{items}',
		'emptyText' => '',
		'options' => $widget->options,
		'dataProvider' => $dataProvider,
		'itemOptions' => ['class' => 'item'],
		'itemView' => function ($model, $key, $index, $_widget) use($widget) {
				return $widget->render('@nitm/views/replies/view',[
					'model' => $model, 
					'uniqid' => $widget->uniqid,
					'formId' => '#messages-form'.$widget->uniqid
				]);
		},
		/*'pager' => [
			'class' => \kop\y2sp\ScrollPager::className(),
			'container' => '#requests-ias-container',
			'item' => "tr"
		]*/
		'pager' => [
			'linkOptions' => [
				'data-pjax' => 1
			],
		]
	
	]);
	$form = !isset($widget->formOptions['enabled']) || (isset($widget->formOptions['enabled']) && $widget->formOptions['enabled'] !== false) ? \nitm\widgets\replies\RepliesForm::widget($widget->formOptions) : '';
	switch(isset($widget->noContainer) && $widget->noContainer == true)
	{
		case false:
		$messages = Html::tag('div', $messages, ['role' => 'replyFormParent']);
		$messages = $messages.$form;
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
