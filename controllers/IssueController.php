<?php

namespace nitm\controllers;

use Yii;
use nitm\models\Issues;
use nitm\models\search\Issues as IssuesSearch;
use nitm\helpers\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * IssueController implements the CRUD actions for Issues model.
 */
class IssueController extends WidgetController
{
	use \nitm\traits\Widgets;
	
	public $legend = [
		'success' => 'Closed and Resolved',
		'warning' => 'Closed and Unresolved',
	];
	
	public function init()
	{
		parent::init();
		$this->model = new Issues(['scenario' => 'default']);
	}
	
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
			'useModal' => \Yii::$app->request->isAjax,
			'parentType' => $type,
			'parentId' => $id,
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
            'model' => $this->findModel(Issues::className(), $id),
        ]);
    }
	
	/*
	 * Get the forms associated with this controller
	 * @param string $param What are we getting this form for?
	 * @param int $unique The id to load data for
	 * @return string | json
	 */
	public function actionForm($type=null, $id=null)
	{
		//Response::$viewOptions['args']['content'] = $ret_val['data'];
		$force = false;
		$options = [
			'param' => $type
		];
		switch($param)
		{	
			//This is for generating the form for updating and creating a request
			default:
			$options['title'] = ['title', 'Create Issue'];
			$options['scenario'] = 'create';
			$options['provider'] = null;
			$options['dataProvider'] = null;
			$options['view'] = 'form/_form';
			$options['viewArgs'] = [
				'parentId' => $id,
				'parentType' => $type
			];
			$options['args'] = [false, true, true];
			$options['modelClass'] = Issues::className();
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
		return $this->renderResponse($this->getFormVariables($this->model, $options, $modalOptions), Response::$viewOptions, \Yii::$app->request->isAjax);
	}

    /**
     * Creates a new Issues model.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
		$post = \Yii::$app->request->post();
        $model = new Issues();
		$model->setScenario('create');
		$model->load($post);
		switch(\Yii::$app->request->isAjax && (@\nitm\helpers\Helper::boolval($_REQUEST['do']) !== true))
		{
			case true:
			$this->setResponseFormat('json');
            return \yii\widgets\ActiveForm::validate($model);
			break;
		}
		$ret_val = [
			'success' => false,
			'action' => 'create'
		];
        if($model->save()) {
			$ret_val['success'] = true;
			switch(\Yii::$app->request->isAjax)
			{
				case true:
           		Response::$viewOptions['view'] = 'data';
				$dataProvider = new ArrayDataProvider([
					'allModels' => [$model]
				]);
				$ret_val['data'] = $this->renderAjax(Response::$viewOptions['view'], ["dataProvider" => $dataProvider]);
				break;
				
				default:
            	return $this->redirect(['index']);
				break;
			}
        }
		echo $this->renderResponse($ret_val, Response::$viewOptions, \Yii::$app->request->isAjax);
    }

    /**
     * Updates an existing Issues model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
		$post = \Yii::$app->request->post();
        $model = $this->findModel(Issues::className(), $id);
		$model->setScenario('update');
		$model->load($post);
		switch(\Yii::$app->request->isAjax && (@\nitm\helpers\Helper::boolval($_REQUEST['do']) !== true))
		{
			case true:
			$this->setResponseFormat('json');
            return \yii\widgets\ActiveForm::validate($model);
			break;
		}

        if (!empty($post) && $model->save()) {
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
	 * Get the class indicator value for the users status
	 * @param Object $item
	 * @return string $css class
	 */
	public static function getStatusIndicator(Issues $item)
	{
		$indicator = $item->getStatus();
		return isset(parent::$statusIndicators[$indicator]) ? parent::$statusIndicators[$indicator] : 'default';
	}
}
