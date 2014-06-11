<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use nitm\widgets\replies\ChatModal;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\Chat $searchModel
 */

$title = Yii::t('app', 'Chat: '.ucfirst($parentType).": ".$parentId);
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
<?php
	$options = is_array($options) ? $options : [
		'class' => 'chat',
		'role' => 'entityChat',
		'id' => 'chat'.$parentId,
		'data-parent' => 'chatFormParent'
	];
	echo ListView::widget([
		'layout' => "{items}\n{pager}\n{summary}",
		'summary' => isset($withForm) ? \nitm\widgets\replies\RepliesChatForm::widget(['model' => $chatModel]) : false,
		'options' => $options,
		'dataProvider' => $dataProvider,
		'itemOptions' => ['class' => 'item'],
		'itemView' => function ($model, $key, $index, $widget) {
				return $widget->render('@nitm/views/chat/view',['model' => $model]);
		},
	
	]);
?>
<?php
	if(isset($modal))
	{
		echo $modal;
	}
?>
<script type="text/javascript">
$nitm.addOnLoadEvent(function () {
	$nitm.replies.init("chat<?=$parentId?>");
	$nitm.replies.initChatTabs("chat-navigation");
});
</script>
