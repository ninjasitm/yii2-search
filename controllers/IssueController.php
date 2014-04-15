<?php

namespace nitm\controllers;

use Yii;
use nitm\models\Issues;
use nitm\models\search\Issues as IssuesSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * IssueController implements the CRUD actions for Issues model.
 */
class IssueController extends DefaultController
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Issues models.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
     * @return mixed
     */
    public function actionIndex($type, $id)
    {
        $searchModel = new IssuesSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
			'type' => $type,
			'id' => $id
        ]);
    }

    /**
     * Displays a single Issues model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }
	
	/*
	 * Get the forms associated with this controller
	 * @param string $param What are we getting this form for?
	 * @param int $unique The id to load data for
	 * @return string | json
	 */
	public function actionForm($param=null, $id=null)
	{
		//$this->_view['args']['content'] = $ret_val['data'];
		$force = false;
		$options = [
			'id' => $id,
			'param' => $param
		];
		switch($param)
		{	
			//This is for generating the form for updating and creating a request
			default:
			$options['title'] = ['title', 'Create Refund'];
			$options['scenario'] = 'create';
			$options['provider'] = null;
			$options['dataProvider'] = null;
			$options['view'] = 'form/_form';
			$options['args'] = [false, true, true];
			$options['modelClass'] = Refunds::className();
			$options['force'] = true;
			break;
		}
		$modalOptions = [
			'contentOnly' => true,
			'body' => [
				'class' => 'modal-full'
			],
			'content' => [
				'class' => 'modal-full'
			],
			'dialog' => [
				'class' => 'modal-full'
			],
		];
		$this->setResponseFormat(\Yii::$app->request->isAjax ? 'modal' : 'html');
		echo $this->renderResponse($this->getFormVariables($options, $modalOptions), $this->_view, \Yii::$app->request->isAjax);
	}

    /**
     * Creates a new Issues model.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type, $id)
    {
        $model = new Issues();
		$model->setScenario('create');
		$model->load(Yii::$app->request->post());
		switch(\Yii::$app->request->isAjax && (@\nitm\helpers\Helper::boolval($_REQUEST['do']) !== true))
		{
			case true:
			$this->setResponseFormat('json');
			$model->validate();
			return $model->getErrors();
			break;
		}
		$ret_val = [
			'success' => false,
			'action' => 'create'
		];
        if($model->save()) {
			$ret_val['success'] = true;
			$model->completed = 0;
			$model->closed = 0;
			switch(\Yii::$app->request->isAjax)
			{
				case true:
           		$this->_view['view'] = 'data';
				$dataProvider = new ArrayDataProvider([
					'allModels' => [$model]
				]);
				$ret_val['data'] = $this->renderAjax($this->_view['view'], ["dataProvider" => $dataProvider]);
				break;
				
				default:
            	return $this->redirect(['index']);
				break;
			}
        }
		echo $this->renderResponse($ret_val, $this->_view, \Yii::$app->request->isAjax);
    }

    /**
     * Updates an existing Issues model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Issues model.
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
     * Finds the Issues model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Issues the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Issues::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
