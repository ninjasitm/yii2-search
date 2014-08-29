<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use kartik\icons\Icon;
use nitm\widgets\replies\ChatModal;
use nitm\models\Replies;

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

$widget->withForm = isset($widget->withForm) ? $widget->withForm : \Yii::$app->request->get(Replies::FORM_PARAM);

?>
<?php
	$_GET[Replies::FORM_PARAM] = 0;
	$widget->listOptions = !$widget->noContainer ? $widget->listOptions : ['id' => 'page'.$_GET['page']];
	$dataProvider->pagination->route = '/reply/index/chat/0/1';
	$params = array_intersect_key($_GET, [
		Replies::FORM_PARAM => null,
		'page' => null
	]);
	$dataProvider->pagination->params = $params;
	$messages = ListView::widget([
		'layout' => "{items}\n{pager}",
		'options' => $widget->listOptions,
		'dataProvider' => $dataProvider,
		'itemOptions' => ['class' => 'item'],
		'itemView' => function ($model, $key, $index, $_widget) use($widget) {
				return $widget->render('@nitm/views/chat/view',['model' => $model, 'primaryModel' => $widget->model]);
		},
		/*'pager' => [
			'class' => \kop\y2sp\ScrollPager::className(),
			'container' => '#'.$widget->options['id'],
			'negativeMargin' => 100,
			'triggerText' => 'More Replies',
		]*/
		'pager' => [
			'linkOptions' => [
				'data-pjax' => 1
			],
		]
	]);
	$form = ($widget->withForm == true) ? \nitm\widgets\replies\ChatForm::widget(['model' => $widget->model]) : '';
	switch(isset($widget->noContainer) && $widget->noContainer == true)
	{
		case false:
		$messages = Html::tag('div', $messages, ['class' => 'chat-messages-container']);
		$messages = Html::tag('div', $messages.$form, $widget->options);
		break;
	}
	echo $messages;
?>
<script type="text/javascript">
$nitm.onModuleLoad('replies', function () {
	$nitm.module('replies').init("<?= $widget->options['id']?>");
	$nitm.module('replies').initChatTabs("chat-navigation");
	<?php if($widget->updateOptions['enabled']): ?>
	$nitm.module('replies').initActivity("chat-navigation", "<?= $widget->updateOptions['url'] ?>", <?= $widget->updateOptions['interval']; ?>);
	<?php endif; ?>
});
</script>
