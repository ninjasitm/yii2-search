<?php

namespace nitm\module\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\module\models\Revisions;
use nitm\module\models\search\Revisions as RevisionsSearch;
use nitm\widgets\revisions\widget\Revisions as RevisionsWidget;
use nitm\module\controllers\DefaultController;

/**
 * RevisionsController implements the CRUD actions for Revisions model.
 */
class RevisionsController extends DefaultController
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
     * Lists all Revisions models.
     * @return mixed
     */
    public function actionIndex($type=null, $id=null)
    {
        /*$searchModel = new RevisionsSearch;
		$params = ['remote_type' => $type, 'id' => $id];
        $dataProvider = $searchModel->search(array_merge(array_filter($params), Yii::$app->request->getQueryParams()));*/

        /*$ret_val = [
			'args' => [
				'dataProvider' => $dataProvider,
				'searchModel' => $searchModel,
        	],
		];*/
		$ret_val = [
			'args' => [
				"content" => RevisionsWidget::widget([
					"parentId" => $id, 
					"parentType" => $type
				])
			]
		];
		$this->setResponseFormat('modal');
		$this->renderResponse(null, $ret_val);
    }

    /**
     * Displays a single Revisions model.
     * @param integer $user_id
     * @param string $remote_type
     * @param integer $remote_id
     * @return mixed
     */
    public function actionView($id)
    {
        $ret_val = [
			'args' => [
            	'model' => $this->findModel($id),
        	],
			'view' => 'view'
		];
		$this->setResponseFormat('modal');
		$this->renderResponse(null, $ret_val);
    }

    /**
     * Creates a new Revisions model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type, $id)
    {
		$ret_val = false;
        $model = new Revisions;
		$model->setScenario('create');
		$model->parent_id = $id;
		$model->parent_type = $type;
		$model->setAttribute('data', json_encode($_REQUEST));

        if ($model->validate() && $model->save()) {
            //return $this->redirect(['view', 'user_id' => $model->user_id, 'remote_type' => $model->remote_type, 'remote_id' => $model->remote_id]);
			$ret_val = true;
        } else {
            /*return $this->render('create', [
                'model' => $model,
            ]);*/
        }
		$this->renderResponse($ret_val);
    }

    /**
     * Updates an existing Revisions model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $user_id
     * @param string $remote_type
     * @param integer $remote_id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'user_id' => $model->user_id, 'remote_type' => $model->remote_type, 'remote_id' => $model->remote_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Revisions model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $user_id
     * @param string $remote_type
     * @param integer $remote_id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Revisions model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $user_id
     * @param string $remote_type
     * @param integer $remote_id
     * @return Revisions the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Revisions::find(['id' => $id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
