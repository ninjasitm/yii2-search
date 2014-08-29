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
$formId = !isset($formId) ? '#messages-form-'.$model->parent_type.$model->parent_id : $formId;
?>
<div id="message<?= $uniqid ?>" class="message <?= $model->hidden ? 'message-hidden' : '';?>">
	<?php
		switch(isset($isNew) && ($isNew === true) || $model->isNew())
		{
			case true:
			echo \nitm\widgets\activityIndicator\ActivityIndicator::widget();
			break;
		}
	?>
	<div class="message-avatar">
		<img id='message-avatar<?= $model->getId(); ?>' class="avatar avatar-small" alt="<? $model->author()->username; ?>" src="<?= $model->author()->avatar(); ?>" />
	</div>
	<div id="message-header<?= $uniqid ?>" class="message-header">
		<?php if($model->replyTo != null): ?>
			<a class="reply-to-author" href="#message<?= $model->replyTo->id ?>">@<?= $model->replyTo->author()->username ?></a><span class="reply-to-author"><?= $model->replyTo->title ?></span>
		<?php endif; ?>
	</div>
	<div id="message-body<?= $uniqid ?>" class="message-body">
		<div role='message'> <?= \nitm\helpers\Helper::parseLinks($model->message); ?> </div>
	</div>
	<div id="message-footer<?= $uniqid ?>" class="message-footer">
		<div id="message-meta<?= $uniqid ?>" class="message-meta">
			Posted on <?= $model->created_at ?> by<a class="author" href="#" role="usernameLink"><?= $model->author()->username ?></a>
		</div>
		<div id="message-actions<?= $uniqid ?>" class="message-actions">
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
				]);
			}
			echo Html::a('reply', \Yii::$app->urlManager->createUrl(['/reply/to/'.$model->getId()]), [
				'id' => "reply-to-link".$model->getId(),
				'title' => Yii::t('yii', "Reply to this message"),
				'class' => 'fa-2x',
				'role' => 'replyTo',
				'data-parent' => $formId,
				'data-reply-to-id' => $model->getId(),
				'data-reply-to-message' => '#message-body'.$uniqid,
				'data-author' => $model->author()->username,
				'data-title' => $model->title
			]);
			echo Html::a('quote', \Yii::$app->urlManager->createUrl(['/reply/quote/'.$model->getId()]), [
				'id' => "quote-link".$model->getId(),
				'title' => Yii::t('yii', "Quote this message"),
				'class' => 'fa-2x',
				'role' => 'quoteReply',
				'data-parent' => $formId,
				'data-reply-to-id' => $model->getId(),
				'data-reply-to-message' => '#message-body'.$uniqid,
				'data-author' => $model->author()->username,
				'data-title' => $model->title
			]);
		?>
		</div>
	</div>
</div>
