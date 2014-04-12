<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 */

$this->title = Yii::t('app', 'Create {modelClass}', [
  'modelClass' => 'Issues',
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Issues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="issues-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
