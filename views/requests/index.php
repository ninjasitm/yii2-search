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

<div class="col-md-8 col-lg-8">
	<h1><?= Html::encode($this->title) ?></h1>
	<?php
		echo $this->render("data", [
				'dataProvider' => $dataProvider,
				'searchModel' => $searchModel,
			]
		); 
	?>
</div>
<div class="col-md-4 col-lg-4">
	<?php
		echo @$this->render('_search', array("data" => array(), 'model' => $model)); 
	?>
</div>