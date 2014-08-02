<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use nitm\helpers\Icon;

/* @var $this yii\web\View */
/* @var $model nitm\models\Alerts */
?>
<?php if(!isset($notAsListItem)): ?>
<li id="alert<?= $model->getId(); ?>" class="<?= \nitm\helpers\Statuses::getListIndicator($model->getPriority()) ?>">
<?php endif;?>
<div class="row">
	<div class="col-md-3 col-lg-3">
		<?= $model::setting($model->isWhat().'.actions.'.$model->action) ?>
	</div>
	<div class="col-md-2 col-lg-2">
		<?php
			echo "<b>".$model::setting($model->isWhat().'.allowed.'.$model->remote_type)."</b>";
			if(!is_null($model->remote_for) && ($model->remote_for != 'any')) echo " for <b>".$model::setting('for.'.$model->remote_for)."</b>";
		?>
	</div>
	<div class="col-md-3 col-lg-3">
		that has a priority of <b><?= !empty($model->priority) ? $model::setting($model->isWhat().'.priorities.'.$model->priority) : 'Normal' ?></b>
	</div>
	<div class="col-md-2 col-lg-2">
		alert me using <b><?= $model->methods; ?></b>
	</div>
	<div class="col-md-1 col-lg-1">
		<?= \nitm\widgets\modal\Modal::widget([
				'toggleButton' => [
					'tag' => 'a',
					'class' => 'btn btn-info',
					'label' => Icon::forAction('update'), 
					'href' => \Yii::$app->urlManager->createUrl(['/alerts/form/update/'.$model->getId(), '__format' => 'modal']),
					'title' => Yii::t('yii', 'Update '),
					'role' => 'updateAlert',
				],
			]);
		?>
		<?= Html::a(Icon::forAction('delete'), '#', [
			'class' => 'btn btn-danger',
			'role' => 'removeAlert',
			'data-action' => '/alerts/delete/'.$model->getId()
			]); ?>
	</div>
</div>
<?php if(!isset($notAsListItem)): ?>
</li>
<?php endif; ?>
