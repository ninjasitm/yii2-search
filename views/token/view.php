<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var app\models\Token $model
 */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Tokens', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="token-view">

	<h1><?= Html::encode($this->title) ?></h1>

	<p>
		<?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
		<?php echo Html::a('Delete', ['delete', 'id' => $model->id], [
			'class' => 'btn btn-danger',
			'data-confirm' => Yii::t('app', 'Are you sure to delete this item?'),
			'data-method' => 'post',
		]); ?>
	</p>

	<?php echo DetailView::widget([
		'model' => $model,
		'attributes' => [
			'id',
			[
				'attribute' => 'user_id',
				'name' => 'user_id',
				'value' => $model->authoruser->url(),
			],
			'token:ntext',
			'added',
			'active:boolean',
			[
				'attribute' => 'level',
				'name' => 'level',
				'value' => \nitm\module\models\api\Token::getLevel($model),
			], 
			'revoked:boolean',
			'revoked_on',
		],
	]); ?>

</div>
