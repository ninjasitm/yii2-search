<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\icons\Icon;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 */

//$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Issues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$uniqid = !isset($uniqid) ? uniqid() : $uniqid.$model->getId();
?>
<div id="message<?= $model->getId() ?>" class="message <?= $model->hidden ? 'message-hidden' : '';?> <?= \nitm\helpers\Statuses::getIndicator($model->getStatus()) ?>">
	<?php
		switch(isset($isNew) && ($isNew === true) || $model->isNew())
		{
			case true:
			echo \nitm\widgets\activityIndicator\ActivityIndicator::widget();
			break;
		}
	?>
	<div id="message-avatar<?= $model->getId() ?>" class="message-avatar">
		<img id='messageAvatar<?= $model->getId(); ?>' class="avatar-small" alt="<? $model->author()->username; ?>" src="<?= $model->author()->avatar(); ?>" />
	</div>
	<div id="message-body<?= $model->getId() ?>" class="message-body">
		<div role='message'> <?= \nitm\helpers\Helper::parseLinks($model->message); ?> </div>
	</div>
	<div id="message-footer<?= $model->getId() ?>" class="message-footer">
		<div id="message-meta<?= $model->getId() ?>" class="message-meta">
			Posted on <?= $model->created_at ?> by <a class="author" href="#" role="usernameLink"><?= $model->author()->username ?></a>
		</div>
		<div id="message-actions<?= $model->getId() ?>" class="message-actions">
		<?php
			if(\Yii::$app->user->identity->isAdmin())
			{
				echo Html::a($model->hidden ? 'unhide' : 'hide', \Yii::$app->urlManager->createUrl(['/reply/hide/'.$model->getId()]), [
					'id' => "hide-message".$model->getId(),
					'title' => Yii::t('yii', ($model->hidden ? 'Unhide' : 'Hide').' this message'),
					'class' => 'fa-2x',
					'role' => 'hideReply',
					'data-parent' => 'tr',
					'data-pjax' => '0',
					'onclick' => '(function (event) {$nitm.module("replies").hide(event)})(event)'
				]);
			}
			echo Html::a('reply', \Yii::$app->urlManager->createUrl(['/reply/to/'.$model->getId()]), [
				'id' => "reply-to-message".$model->getId(),
				'title' => Yii::t('yii', "Reply to this message"),
				'class' => 'fa-2x',
				'role' => 'replyTo',
				'data-parent' => '#chat-form0',
				'data-reply-to-id' => $model->getId(),
				'data-reply-to-message' => '#message-body'.$model->getId(),
				'data-author' => $model->author()->username,
				'data-title' => $model->title,
				'onclick' => '(function (event) {$nitm.module("replies").replyTo(event)})(event)'
			]);
			echo Html::a('quote', \Yii::$app->urlManager->createUrl(['/reply/quote/'.$model->getId()]), [
				'id' => "quote-message".$model->getId(),
				'title' => Yii::t('yii', "Quote this message"),
				'class' => 'fa-2x',
				'role' => 'quoteReply',
				'data-parent' => '#chat-form0',
				'data-reply-to-id' => $model->getId(),
				'data-reply-to-message' => '#message-body'.$model->getId(),
				'data-author' => $model->author()->username,
				'data-title' => $model->title,
				'onclick' => '(function (event) {$nitm.module("replies").quote(event)})(event)'
			]);
		?>
		</div>
	</div>
</div>
