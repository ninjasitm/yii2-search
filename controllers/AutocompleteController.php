<?php

namespace nitm\controllers;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\controllers\DefaultController;
use nitm\models\User;
use nitm\models\search\User as UserSearch;
/**
 * UserController implements the CRUD actions for User model.
 */
class AutocompleteController extends DefaultController
{
	
	public function behaviors()
	{
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'user' => ['get'],
				],
			],
			'access' => [
				'class' => \yii\web\AccessControl::className(),
				'only' => ['user'],
				'rules' => [
					[
						'actions' => ['user'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
	}
	
	/**
	 * Search for a user
	 */
	public function actionUser()
	{
		$ret_val = [];
		$searchModel = new UserSearch;
		$searchModel->setScenario('apiSearch');
		$dataProvider = $searchModel->search($_GET);
		foreach($dataProvider->getModels() as $user)
		{
			$name = empty($user->name) ? $user->username : $user->name.' ('.$user->username.')';
			$ret_val[] = [
				'value' => $user->id, 
				'label' => $name." (".$user->getStatus().", ".$user->getRole().")"
			];
		};
		$this->renderResponse($ret_val);
	}
}
