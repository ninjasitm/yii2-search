<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use nitm\helpers\Icon;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Issues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="issue<?= $model->id ?>" class="issues-view <?= \nitm\helpers\Statuses::getIndicator($model->getStatus())?> wrapper">
	<div class="row">
		<div class="col-md-10 col-lg-10">
			<h4 class="header"><?= $model->title; ?>&nbsp;<span class="badge"><?= $model->status ?></span></h4>
			<p class="small"><?= $model->notes; ?></p>
		</div>
		<div class="col-md-2 col-lg-2">
			<?php
				echo Html::a(Icon::forAction('update', null, $model), \Yii::$app->urlManager->createUrl(['/issue/form/update/'.$model->id, '__format' => 'modal']), [
					'title' => Yii::t('yii', 'Edit '),
					'class' => 'fa-2x'.($model->closed ? ' hidden' : ''),
					'role' => 'updateIssue',
					'data-toggle' => 'modal',
					'data-target' => '#issue-tracker-modal'
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
					'data-toggle' => 'modal',
					'data-target' => '#form'
				]);
			?>
		</div>
	</div>
</div>
