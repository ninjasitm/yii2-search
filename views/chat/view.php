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
?>
<div id="message<?= $model->id ?>" class="message <?= $model->hidden ? 'message-hidden' : '';?> wrapper">
	<?php
		switch(isset($isNew) && ($isNew === true) || $model->isNew())
		{
			case true:
			echo \nitm\widgets\activityIndicator\ActivityIndicator::widget();
			break;
		}
	?>
	<div class="avatar">
		<img id='messageAvatar<?= $model->id; ?>' class="avatar avatar-small" alt="<? $authorUser->username; ?>" src="<?= $authorUser->getAvatar(); ?>" />
	</div>
	<div id="messageBody<?= $model->id ?>" class="message-body">
		<p role='message'> <?=$model->message ?> </p>
	</div>
	<div id="messageFooter<?= $model->id ?>" class="message-footer">
		<div class="message-meta">
			Posted on <?= $model->created_at ?> by <?= $authorUser->username ?>
		</div>
		<div id="messageActions<?= $model->id ?>" class="message-actions">
		<?php
			if(\Yii::$app->userMeta->isAdmin())
			{
				echo Html::a($model->hidden ? 'unhide' : 'hide', \Yii::$app->urlManager->createUrl(['/reply/hide/'.$model->id]), [
					'id' => "hideMessage".$model->id,
					'title' => Yii::t('yii', ($model->hidden ? 'Unhide' : 'Hide').' this message'),
					'class' => 'fa-2x',
					'role' => 'hideReply',
					'data-parent' => 'tr',
					'data-pjax' => '0',
				]);
			}
			echo Html::a('reply', \Yii::$app->urlManager->createUrl(['/reply/to/'.$model->reply_to]), [
				'id' => "replyToMessage".$model->id,
				'title' => Yii::t('yii', "Reply to this message"),
				'class' => 'fa-2x',
				'role' => 'replyTo',
				'data-parent' => $model->parent_id,
				'data-reply-to' => $model->id,
			]);
			echo Html::a('quote', \Yii::$app->urlManager->createUrl(['/reply/quote/'.$model->reply_to]), [
				'id' => "quoteMessage".$model->id,
				'title' => Yii::t('yii', "Quote this message"),
				'class' => 'fa-2x',
				'role' => 'quoteReply',
				'data-parent' => $model->parent_id,
				'data-reply-to' => $model->id,
				'data-author' => $authorUser->username,
			]);
		?>
		</div>
	</div>
</div>
