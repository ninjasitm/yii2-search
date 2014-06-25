<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
  'modelClass' => 'Issue',
]) . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Issues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
$enableComments = isset($enableComments) ? $enableComments : \Yii::$app->request->get($model->commentParam);
?>
<div class="issues-update <?= \nitm\helpers\Statuses::getIndicator($model->getStatus())?> wrapper">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('form/_form', [
        'model' => $model,
		'enableCOmments' => $enableComments
    ]) ?>

</div>
