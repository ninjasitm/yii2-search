<div class="wrapper">
	<div class="row">
		<div class="col-lg-12 col-md-12">
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
		</div>
	</div>
</div>