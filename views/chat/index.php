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

$title = Yii::t('app', 'Chat');
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
	$listOptions = is_array($listOptions) ? $listOptions : [
		'class' => 'chat-messages',
		'role' => 'chatMessages',
	];
	$messages = ListView::widget([
		'layout' => "{items}",
		'options' => $listOptions,
		'dataProvider' => $dataProvider,
		'itemOptions' => ['class' => 'item'],
		'itemView' => function ($model, $key, $index, $widget) use ($primaryModel) {
				return $widget->render('@nitm/views/chat/view',['model' => $model, 'primaryModel' => $primaryModel]);
		},
	
	]);
	$form = (isset($withForm)&& $withForm == true) ? \nitm\widgets\replies\ChatForm::widget(['model' => $primaryModel]) : '';
	echo Html::tag('div', $messages.$form, $options);
?>
<?php
	if(isset($modal))
	{
		echo $modal;
	}
?>
<script type="text/javascript">
$nitm.addOnLoadEvent(function () {
	$nitm.replies.init("chat");
	$nitm.replies.initChatTabs("chat-navigation");
	<?php if($updateOptions['enabled']): ?>
	$nitm.replies.initChatActivity("chat-navigation", "<?= $updateOptions['url'] ?>", <?= $updateOptions['interval']; ?>);
	<?php endif; ?>
});
</script>
