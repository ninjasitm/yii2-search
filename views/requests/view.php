<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var frontend\models\Requests $model
 */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="requests-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php //echo Html::a('Update', ['update', 'id' => $model->getUnique()], ['class' => 'btn btn-primary']) ?>
        <?php /*echo Html::a('Delete', ['delete', 'id' => $model->getUnique()], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ])*/ ?>
    </p>

    <?php 
		echo /*DetailView::widget([
        'model' => $model,
        'attributes' => [
            /*'id',
            'created_at',
            'completed_by',
            'closed_by',
            'title:ntext',
            'author',
            'updated_at',
            'editor',
            'edits',*/
            //'request:ntext',
            /*'type:ntext',
            'request_for:ntext',
            'status',
            'completed',
            'completed_on',
            'closed',
            'closed_on',
            'rating',
            'rated_on',
        ],
    ])*/ $model->request;?>

</div>
