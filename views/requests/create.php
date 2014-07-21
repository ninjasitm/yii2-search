<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var frontend\models\Requests $model
 */

$this->title = 'Create Request';
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="requests-create">
	<?php if(!\Yii::$app->request->isAjax): ?>
	<h2><?= Html::encode($this->title) ?></h2>
	<?php endif; ?>
    <?= $this->render('form/_form', [
        'model' => $model,
    ]) ?>
</div>
