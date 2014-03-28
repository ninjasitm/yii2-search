<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var nitm\module\models\Vote $model
 */

$this->title = 'Update Vote: ' . $model->user_id;
$this->params['breadcrumbs'][] = ['label' => 'Votes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->user_id, 'url' => ['view', 'user_id' => $model->user_id, 'remote_type' => $model->remote_type, 'remote_id' => $model->remote_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="vote-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
