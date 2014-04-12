<?php

namespace nitm\controllers;

use nitm\models\Rating;

class RatingController extends \nitm\controllers\DefaultController
{
	
	public function behaviors()
	{
		$behaviors = [
			'access' => [
				'class' => \yii\web\AccessControl::className(),
				'only' => ['down', 'up', 'reset'],
				'rules' => [
					[
						'actions' => ['down', 'up', ],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => \yii\web\VerbFilter::className(),
				'actions' => [
					'down' => ['get'],
					'up' => ['get'],
					'reset' => ['get'],
				],
			],
		];
		
		return array_replace_recursive(parent::behaviors(), $behaviors);
	}
    public function actionDown($type, $id)
    {
		$ret_val = false;
		$existing = new Rating();
		$existing->queryFilters['user_id'] = \Yii::$app->user->getId();
		$existing->queryFilters['remote_type'] = $type;
		$existing->queryFilters['remote_id'] = $id;
		switch($existing->find()->where($existing->queryFilters)->exists())
		{
			case false:
			$model = new Rating(['remote_type' => $type, 'remote_id' => $id]);
			$model->rating = 0;
			$model->save();
			break;
			
			default:
			$existing = new Rating();
			$existing->queryFilters['remote_type'] = $type;
			$existing->queryFilters['remote_id'] = $id;
			$count = $existing->getCount();
			switch(1)
			{
				case $count > 0;
				$ret_val = true;
				break;
			}
			break;
		}
		$this->renderResponse($ret_val);
    }

    public function actionUp($type, $id)
    {
		$ret_val = false;
		$existing = new Rating();
		$existing->queryFilters['user_id'] = \Yii::$app->user->getId();
		$existing->queryFilters['remote_type'] = $type;
		$existing->queryFilters['remote_id'] = $id;
		switch($existing->exists())
		{
			case false:
			$model = new Rating(['remote_type' => $type, 'remote_id' => $id]);
			$model->save();
			break;
			
			default:
			$existing = new Rating();
			$existing->queryFilters['remote_type'] = $type;
			$existing->queryFilters['remote_id'] = $id;
			$count = $existing->getCount();
			switch(1)
			{
				case $count < Users::find()->count();
				$ret_val = true;
				break;
			}
			break;
		}
		$this->renderResponse($ret_val);
    }

}
