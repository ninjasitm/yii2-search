<?php

use yii\helpers\Html;
use nitm\models\Issues;

/**
 * @var yii\web\View $this
 * @var app\models\Issues $model
 */

$this->title = Yii::t('app', 'Create {modelClass}', [
  'modelClass' => 'Issues',
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Issues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$enableComments = isset($enableComments) ? $enableComments : \Yii::$app->request->get(Issues::COMMENT_PARAM);
?>
<div class="issues-create wrapper">

    <h3><?= Html::encode($this->title) ?></h2>

    <?= $this->render('form/_form', [
        'model' => $model,
		'parentId' => $parentId,
		'parentType' => $parentType,
		'enableComments' => $enableComments
    ]) ?>

</div>
