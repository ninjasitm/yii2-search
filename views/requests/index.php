<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var frontend\models\search\Refunds $searchModel
 */

$this->title = 'Requests';
$this->params['breadcrumbs'][] = $this->title;
?>

<div 'requests-ias-container' class="col-md-8 col-lg-8">
	<h1><?= Html::encode($this->title) ?></h1>
	<?php \yii\widgets\Pjax::begin([
		'options' => [
			'id' => 'requests-index',
		],
		'linkSelector' => "[data-pjax='1']",
		'timeout' => 5000
	]); ?>
	<?php
		echo $this->render("data", [
				'dataProvider' => $dataProvider,
				'searchModel' => $searchModel,
				'primaryModel' => $model
			]
		); 
	?>
	<?php \yii\widgets\Pjax::end(); ?>
</div>
<div class="col-md-4 col-lg-4">
	<?php
		echo @$this->render('_search', array("data" => array(), 'model' => $searchModel)); 
	?>
</div>