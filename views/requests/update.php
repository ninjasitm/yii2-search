<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var frontend\models\Requests $model
 */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->getId()]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="requests-update" id='request<?=$model->getId()?>'>
	<?php if(!\Yii::$app->request->isAjax): ?>
	<h2><?= Html::encode($this->title) ?></h2>
	<?php endif; ?>
    <?= $this->render('form/_form', [
        'model' => $model,
    ]) ?>
</div>
