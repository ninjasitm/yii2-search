<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Token $model
 */

$this->title = 'Create Token';
$this->params['breadcrumbs'][] = ['label' => 'Tokens', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="token-create">
	<?= yii\widgets\Breadcrumbs::widget([
		'links' => $this->params['breadcrumbs']
	]); ?>

	<h1><?= Html::encode($this->title) ?></h1>

	<?php echo $this->render('_form', [
		'model' => $model,
	]); ?>

</div>
