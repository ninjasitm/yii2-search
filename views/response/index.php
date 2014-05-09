<?php 
	switch(isset($content))
	{
		case false:
		throw new \yii\web\NotFoundHttpException('No Data Found', 404);
		break;
		
		default: 
		echo $content;
		break;
	}
?>