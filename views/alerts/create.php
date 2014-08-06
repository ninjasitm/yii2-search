<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model nitm\models\Alerts */

$this->title = Yii::t('app', 'Create {modelClass}', [
    'modelClass' => 'Alerts',
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Alerts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="alerts-create">
	<?php if(!\Yii::$app->request->isAjax): ?>
	<?= \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]); ?>
	<h2><?= Html::encode($this->title) ?></h2>
	<?php endif; ?>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
