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
?>
<div id="issue<?= $model->id ?>" class="issues-view <?= \nitm\helpers\Statuses::getIndicator($model->getStatus())?> wrapper">
	<div class="row">
		<div class="col-md-10 col-lg-10">
			<h4 class="header">
				<?php
					switch(isset($isNew) && ($isNew === true) || $model->isNew())
					{
						case true:
						echo \nitm\widgets\activityIndicator\ActivityIndicator::widget();
						break;
					}
				?>
				<?= $model->title; ?>&nbsp;<span class="badge"><?= $model->status ?></span>
				<span class="small text-right">Created by <b><?= $model->authorUser->fullName(true) ?></b> on <?= $model->created_at ?></span>
			</h4>
			<p class="small"><?= $model->notes; ?></p>
			<div class="pull-right">
			<?php if($model->edits) :?>
				<span class="small  text-right">Edited by <b><?= $model->authorUser->fullName(true) ?></b> on <?= $model->created_at ?></span>&nbsp;
			<?php endif; ?>
			<?php if($model->resolved) :?>
				<span class="small  text-right">Resolved by <b><?= $model->resolveUser->fullName(true) ?></b> on <?= $model->resolved_at ?></span>&nbsp;
			<?php endif; ?>
			<?php if($model->closed) :?>
				<span class="small  text-right">Closed by <b><?= $model->closeUser->fullName(true) ?></b> on <?= $model->closed_at ?></span>
			<?php endif; ?>
			</div>
		</div>
		<div class="col-md-2 col-lg-2">
			<?php
				echo Html::a(Icon::forAction('update', null, $model), \Yii::$app->urlManager->createUrl(['/issue/form/update/'.$model->id, '__format' => 'modal']), [
					'title' => Yii::t('yii', 'Edit '),
					'class' => 'fa-2x'.($model->closed ? ' hidden' : ''),
					'role' => 'updateIssue',
					'data-toggle' => 'modal',
					'data-target' => '#issue-tracker-modal-form'
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
			?>
		</div>
	</div>
</div>
