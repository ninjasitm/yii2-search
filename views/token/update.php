<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Token $model
 */

$this->title = 'Update Token: ' . $model->tokenid;
$this->params['breadcrumbs'][] = ['label' => 'Tokens', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->tokenid, 'url' => ['view', 'id' => $model->tokenid]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="token-update">

	<h1><?= Html::encode($this->title) ?></h1>
	<?php echo $this->render('_form', [
		'model' => $model,
	]); ?>

</div>
