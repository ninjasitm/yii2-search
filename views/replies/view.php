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
$authorUser = isset($model->authorUser) ? $model->authorUser : new \nitm\models\User;
$localUniqid = uniqid();
$uniqid = !isset($uniqid) ? uniqid() : $uniqid;
?>
<div id="message<?= $localUniqid ?>" class="message <?= $model->hidden ? 'message-hidden' : '';?>">
	<?php
		switch(isset($isNew) && ($isNew === true) || $model->isNew())
		{
			case true:
			echo \nitm\widgets\activityIndicator\ActivityIndicator::widget();
			break;
		}
	?>
	<div class="message-avatar">
		<img id='messageAvatar<?= $model->getId(); ?>' class="avatar avatar-small" alt="<? $authorUser->username; ?>" src="<?= $authorUser->getAvatar(); ?>" />
	</div>
	<div id="messageHeader<?= $localUniqid ?>" class="message-header">
		<?php if($model->replyTo != null): ?>
			<a class="reply-to-author" href="#message<?= $model->replyTo->id ?>">@<?= $model->replyTo->authorUser->username ?></a><span class="reply-to-author"><?= $model->replyTo->title ?></span>
		<?php endif; ?>
	</div>
	<div id="messageBody<?= $localUniqid ?>" class="message-body">
		<p role='message'> <?=$model->message ?> </p>
	</div>
	<div id="messageFooter<?= $localUniqid ?>" class="message-footer">
		<div id="messageMeta<?= $localUniqid ?>" class="message-meta">
			Posted on <?= $model->created_at ?> by<a class="author" href="#" role="usernameLink"><?= $authorUser->username ?></a>
		</div>
		<div id="messageActions<?= $localUniqid ?>" class="message-actions">
		<?php
			if(\Yii::$app->userMeta->isAdmin())
			{
				echo Html::a($model->hidden ? 'unhide' : 'hide', \Yii::$app->urlManager->createUrl(['/reply/hide/'.$model->getId()]), [
					'id' => "hideMessage".$model->getId(),
					'title' => Yii::t('yii', ($model->hidden ? 'Unhide' : 'Hide').' this message'),
					'class' => 'fa-2x',
					'role' => 'hideReply',
					'data-parent' => 'tr',
					'data-pjax' => '0',
				]);
			}
			echo Html::a('reply', \Yii::$app->urlManager->createUrl(['/reply/to/'.$model->getId()]), [
				'id' => "replyToMessage".$model->getId(),
				'title' => Yii::t('yii', "Reply to this message"),
				'class' => 'fa-2x',
				'role' => 'replyTo',
				'data-parent' => $uniqid,
				'data-reply-to' => $localUniqid,
				'data-author' => $authorUser->username,
				'data-title' => $model->title
			]);
			echo Html::a('quote', \Yii::$app->urlManager->createUrl(['/reply/quote/'.$model->getId()]), [
				'id' => "quoteMessage".$model->getId(),
				'title' => Yii::t('yii', "Quote this message"),
				'class' => 'fa-2x',
				'role' => 'quoteReply',
				'data-parent' => $uniqid,
				'data-reply-to' => $localUniqid,
				'data-author' => $authorUser->username,
				'data-title' => $model->title
			]);
		?>
		</div>
	</div>
</div>
