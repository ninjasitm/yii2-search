<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var nitm\module\models\Vote $model
 */

$this->title = 'Create Vote';
$this->params['breadcrumbs'][] = ['label' => 'Votes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vote-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
