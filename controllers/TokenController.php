<?php

namespace nitm\module\controllers;

use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\module\models\search\Token as TokenSearch;
use nitm\module\models\api\Token;
use nitm\module\interfaces\DefaultControllerInterface;

/**
 * TokenController implements the CRUD actions for Token model.
 */
class TokenController extends DefaultController implements DefaultControllerInterface
{
	public $legend = [
		'success' => 'Active Token',
		'danger' => 'Revoked Token',
		'default' => 'Inactive Token',
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
						'actions' => ['index',  'view',  'create',  'delete', 'update', 'generate'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
	}
	
	public static function has ()
	{
		return [];
	}

	/**
	 * Lists all Token models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$searchModel = new TokenSearch;
		$dataProvider = $searchModel->search($_GET);

		return $this->render('index', [
			'dataProvider' => $dataProvider,
			'searchModel' => $searchModel,
		]);
	}

	/**
	 * Displays a single Token model.
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
	 * Creates a new Token model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new Token;

		$model->setScenario('create');
		$model->load($_POST);
		if($model->validate())
		{
			$model->token = $model->getUniqueToken();
			if($model->save()) {
				\Yii::$app->getSession()->setFlash(
					'success',
					'Added new token successfully'
				);
				return $this->redirect(['view', 'id' => $model->tokenid]);
			} 
			else 
			{
				\Yii::$app->getSession()->setFlash(
					'error',
					'Unable to add new token'
				);
				return $this->render('create', [
					'model' => $model,
				]);
			}
		}
		else
		{
			return $this->render('create', [
				'model' => $model,
			]);
		}
	}

	/**
	public $legend = [
		'success' => 'Active User',
		'default' => 'Inactive User',
		'danger' => 'Banned User',
		'info' => 'Admin User',
		'warning' => 'Api User'
	];
	 * Updates an existing Token model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		$model->setScenario('update');
		if ($model->load($_POST) && $model->save()) {
			return $this->redirect(['view', 'id' => $model->tokenid]);
		} else {
			return $this->render('update', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Deletes an existing Token model.
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
	 * Generates a token for a specific user
	 * @param integer $id
	 * @return string
	 */
	public function actionGenerate()
	{
		$token = new Token();
		return $token->getUniqueToken((int) $id);
	}

	/**
	 * Finds the Token model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return Token the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		if (($model = Token::find($id)) !== null) {
			return $model;
		} else {
			throw new NotFoundHttpException('The requested page does not exist.');
		}
	}
	
	/**
	 * Get the class indicator value for the users status
	 * @param Token $token
	 * @return string $css class
	 */
	public function getStatusIndicator($token=null)
	{
		$token = is_null($token) ? $this : $token;
		$ret_val = 'default';
		switch($token instanceof Token)
		{
			case true:
			switch(Token::getStatus($token))
			{
				case 'Active':
				$ret_val = 'success';
				break;
				
				case 'Inactive':
				$ret_val = 'default';
				break;
				
				case 'Revoked':
				$ret_val = 'error';
				break;
			}
			break;
		}
		$indicator = $this->statusIndicators[$ret_val];
		return $indicator;
	}
}
