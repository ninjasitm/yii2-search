<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use nitm\helpers\Icon;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 */

//$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Issues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
if(isset($enableComments) && ($enableComments == true)) $repliesModel = new \nitm\models\Replies([
	"constrain" => [$model->getId(), $model->isWhat(), $model->created_at]
]);
?>
<div id="issue<?= $model->id ?>" class="issues-view <?= \nitm\helpers\Statuses::getIndicator($model->getStatus())?> wrapper">
	<div class="row">
		<div class="col-md-9 col-lg-9">
			<div class="row">
				<h4 class="col-md-7 col-lg-7 text-left">
					<?php if(isset($isNew) && ($isNew === true) || $model->isNew()) echo \nitm\widgets\activityIndicator\ActivityIndicator::widget();?>
					<?= $model->title; ?>&nbsp;<span class="badge"><?= $model->status ?></span>
				</h4>
				<h4 class="col-md-5 col-lg-5 text-right"><small>by <b><?= $model->authorUser->fullName(true) ?></b> on <?= $model->created_at ?></small></h4>
			</div>
			<p class="small"><?= $model->notes; ?></p>
			<div class="pull-left">
			<?php if($model->edits) :?>
				<i class="small  text-right">Edited by <b><?= $model->authorUser->fullName(true) ?></b> on <?= $model->created_at ?></i>&nbsp;
			<?php endif; ?>
			<?php if($model->resolved) :?>
				<i class="small  text-right">Resolved by <b><?= $model->resolveUser->fullName(true) ?></b> on <?= $model->resolved_at ?></i>&nbsp;
			<?php endif; ?>
			<?php if($model->closed) :?>
				<i class="small  text-right">Closed by <b><?= $model->closeUser->fullName(true) ?></b> on <?= $model->closed_at ?></i>
			<?php endif; ?>
			</div>
		</div>
		<div class="col-md-3 col-lg-3 pull-right">
			<?php
				echo Html::a(Icon::forAction('update', null, $model), \Yii::$app->urlManager->createUrl(['/issue/form/update/'.$model->id, '__format' => 'html']), [
					'title' => Yii::t('yii', 'Edit '),
					'class' => 'fa-2x'.($model->closed ? ' hidden' : ''),
					'role' => 'updateIssueTrigger',
				]);
				echo Html::a(Icon::forAction('close', 'closed', $model), \Yii::$app->urlManager->createUrl(['/issue/close/'.$model->id]), [
					'title' => Yii::t('yii', ($model->closed ? 'Open' : 'Close').' '),
					'class' => 'fa-2x',
					'role' => 'closeIssue',
					'data-parent' => 'tr',
					'data-pjax' => '0',
				]);
				echo Html::a(Icon::forAction('resolve', 'resolved', $model), \Yii::$app->urlManager->createUrl(['/issue/resolve/'.$model->id]), [
					'title' => Yii::t('yii', ($model->resolved ? 'Unresolve' : 'Resolve').' '),
					'class' => 'fa-2x',
					'role' => 'resolveIssue',
					'data-parent' => 'tr',
					'data-pjax' => '0',
				]);
				echo Html::a(Icon::forAction('duplicate', 'duplicate', $model), \Yii::$app->urlManager->createUrl(['/issue/duplicate/'.$model->id]), [
					'title' => Yii::t('yii', ($model->duplicate ? 'Flag as not duplicate' : 'flag as duplicate').' '),
					'class' => 'fa-2x',
					'role' => 'duplicateIssue',
				]);
				if(isset($enableComments) && ($enableComments==true))
				{
					echo Html::a(Icon::forAction('comment'), \Yii::$app->urlManager->createUrl(['#']), [
						'title' => 'See comments for this issue',
						'class' => 'fa-2x',
						'role' => 'visibility',
						'data-id' => 'issue-comments'.$model->getId()
					]);
				}
			?>
		</div>
		<?php if(isset($enableComments) && ($enableComments==true)): ?>
		<div class="col-lg-12 col-md-12">
			<div class="clear" style="display:none;" id="issue-comments<?=$model->getId();?>">
				<?= \nitm\widgets\replies\Replies::widget([
					"model" => $repliesModel,
				]); ?>
				<?= \nitm\widgets\replies\RepliesForm::widget([
					"model" => $repliesModel,
					'useModal' => false,
					'hidden' => $model->closed,
					'inline' => true,
				]); ?>
			</div>
		</div>
		<?php endif; ?>
		<br>
	</div>
</div>
