<?php

namespace backend\controllers;

use common\models\User;
use backend\models\search\User as UserSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\module\controllers\DefaultController;
/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends DefaultController
{

	public $legend = [
		'success' => 'Active User',
		'default' => 'Inactive User',
		'danger' => 'Banned User',
		'info' => 'Admin User',
		'warning' => 'Api User'
	];
	
	public function behaviors()
	{
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
			'access' => [
				'class' => \yii\web\AccessControl::className(),
				'rules' => [
					[
						'actions' => ['login', 'error'],
						'allow' => true,
						'roles' => ['?']
					],
					[
						'actions' => ['index',  'create',  'update',  'delete',  'view', 'autocomplete'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
	}
	
	public static function has()
	{
		return [];
	}

	/**
	 * Lists all User models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$searchModel = new UserSearch;
		$dataProvider = $searchModel->search($_GET);

		return $this->render('index', [
			'dataProvider' => $dataProvider,
			'searchModel' => $searchModel,
		]);
	}

	/**
	 * Displays a single User model.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionView($id)
	{
		return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * Creates a new User model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new User;

		$model->setScenario('create');
		if ($model->load($_POST) && $model->save()) {
			return $this->redirect(['view', 'id' => $model->id]);
		} else {
			return $this->render('create', [
				'model' => $model,
			]);
		}
	}
	
	/**
	 * Search for a user
	 */
	public function actionAutocomplete()
	{
		$ret_val = [];
		$searchModel = new UserSearch;
		$searchModel->setScenario('apiSearch');
		$dataProvider = $searchModel->search($_GET);
		$serializer = new \yii\data\ModelSerializer([
			'fields' => ['id', 'f_name', 'l_name']
		]);
		foreach($dataProvider->getModels() as $user)
		{
			$ret_val[] = [
				'value' => $user->id, 
				'label' => $user->f_name.' '.$user->l_name." (".$user->getStatus().", ".$user->getRole().")"
			];
		};
		$this->displayAjaxResult($ret_val);
	}

	/**
	 * Updates an existing User model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		$model->setScenario('update');
		if ($model->load($_POST) && $model->save()) {
			\Yii::$app->getSession()->setFlash(
				'success',
				'Updated user information successfully'
			);
			return $this->redirect(['index', 'id' => $model->id]);
		} else {
			switch(sizeof($model->getErrors()) >= 1)
			{
				case true:
				\Yii::$app->getSession()->setFlash(
					'danger',
					'Unable to update user information'
				);
				break;
			}
			return $this->render('update', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Deletes an existing User model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionDelete($id)
	{
		$this->findModel($id)->delete();
		return $this->redirect(['index']);
	}

	/**
	 * Finds the User model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return User the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		if (($model = User::find($id)) !== null) {
			return $model;
		} else {
			throw new NotFoundHttpException('The requested page does not exist.');
		}
	}
	
	/**
	 * Get the class indicator value for the users status
	 * @param User $user
	 * @return string $css class
	 */
	public function getStatusIndicator($user=null)
	{
		$user = is_null($user) ? $this : $user;
		$ret_val = 'default';
		switch($user instanceof User)
		{
			case true:
			switch(User::getStatus($user))
			{
				case 'Active':
				$ret_val = 'success';
				break;
				
				case 'Banned':
				$ret_val = 'error';
				break;
			}
			switch(User::getRole($user))
			{
				case 'Admin':
				$ret_val = 'info';
				break;
				
				case 'Api User':
				$ret_val = 'warning';
				break;
			}
			break;
		}
		$indicator = $this->statusIndicators[$ret_val];
		return $indicator;
	}
}
