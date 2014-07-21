<?php

namespace nitm\controllers;

use Yii;
use nitm\models\Request;
use nitm\models\search\Request as RequestSearch;
use yii\web\NotFoundHttpException;
use nitm\helpers\Response;

/**
 * RequestController implements the CRUD actions for Request model.
 */
class RequestsController extends DefaultModuleController
{	
	public $legend = [
		'success' => 'Closed and Completed',
		'warning' => 'Open',
		'danger' => 'Closed and Incomplete',
		'info' => 'Completed',
	];
	
	public function init()
	{
		parent::init();
		$this->model = new Request(['scenario' => 'default']);
	}
	
    public function behaviors()
    {
        $behaviors = [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				//'only' => ['index', 'update', 'create', 'index', 'get', 'delete', 'convert', 'undelete'],
				'rules' => [
					[
						'actions' => ['index',  'create',  'update',  'delete', 'resolve','close', 'form', 'issues'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'create' => ['post'],
                    'update' => ['post'],
                    'close' => ['post'],
                    'resolve' => ['post'],
                    'duplicate' => ['post'],
                ],
            ],
        ];
		return array_merge(parent::behaviors(), $behaviors);
    }
	
	public static function has()
	{
		return [
			'\nitm\widgets\replies',
			'\nitm\widgets\activityIndicator',
			'\nitm\widgets\vote',
			'\nitm\widgets\issueTracker'
		];
	}

    /**
     * Lists all Request models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new RequestSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams(), ['type', 'requestFor']);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
			'model' => $this->model,
        ]);
    }
	
	/**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
		return parent::actionUpdate($id, null, ['completedBy', 'closedBy']);
	}
	
    /**
     * Displays a single model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $modelClass=null, $with=[])
    {
		Response::$forceAjax = true;
		return parent::actionView($id);
	}
	
	/*
	 * Get the forms associated with this controller
	 * @param string $param What are we getting this form for?
	 * @param int $unique The id to load data for
	 * @return string | json
	 */
	public function actionForm($type=null, $id=null)
	{
		$options = [
			'modelOptions' => [
				'withThese' => ['type', 'requestFor']
			]
		];
		return parent::actionForm($type, $id, $options);
	}
}
