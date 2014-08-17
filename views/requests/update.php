<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var frontend\models\Requests $model
 */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="requests-update <?= !\Yii::$app->request->isAjax ? 'wrapper' : '' ?>" id='update-request<?=$model->getId()?>'>
	<?php if(!\Yii::$app->request->isAjax): ?>
	<?= \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]); ?>
	<h2><?= Html::encode($this->title) ?></h2>
	<?php endif; ?>
    <?= $this->render('form/_form', [
        'model' => $model,
    ]) ?>
</div>
