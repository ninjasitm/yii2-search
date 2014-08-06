<?php

namespace nitm\controllers;

use Yii;
use nitm\models\Alerts;
use nitm\models\search\Alerts as AlertsSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use nitm\helpers\Response;

/**
 * AlertsController implements the CRUD actions for Alerts model.
 */
class AlertsController extends DefaultController
{	
	public function init()
	{
		$this->model = new Alerts(['scenario' => 'default']);
		parent::init();
	}
	
	public function beforeAction($action)
	{
		switch($action->id)
		{
			case 'list':
			$this->enableCsrfValidation = false;
			break;
		}
		return parent::beforeAction($action);
	}
    public function behaviors()
    {
		$behaviors = [
			'access' => [
				//'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					[
						'actions' => ['notifications', 'mark-notification-read'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
		return array_merge_recursive(parent::behaviors(), $behaviors);
    }
	
    /**
     * Lists all Alerts models.
     * @return mixed
     */
    public function actionIndex()
    {
        Response::$viewOptions['args']['content'] = \nitm\widgets\alerts\Alerts::widget();
		$this->setResponseFormat('html');
		return $this->renderResponse(null, Response::$viewOptions, \Yii::$app->request->isAjax);
    }
	
    /**
     * Lists all Notifications models.
     * @return mixed
     */
    public function actionNotifications()
    {
        Response::$viewOptions['args']['content'] = \nitm\widgets\alerts\Notifications::widget();
		$this->setResponseFormat('html');
		return $this->renderResponse(null, Response::$viewOptions, \Yii::$app->request->isAjax);
    }
	
    /**
     * Mark notification read.
     * @return mixed
     */
    public function actionMarkNotificationRead($id)
    {
		$this->model = \nitm\models\Notification::findOne($id);
		if($this->model)
		{
			$this->model->read = 1;
			$ret_val = $this->model->save();
		}
		else
		{
			$ret_val = false;
		}
		$this->setResponseFormat('json');
		return $this->renderResponse($ret_val, Response::$viewOptions, \Yii::$app->request->isAjax);
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
			],
			'title' => function ($model) {
				if($model->isNewRecord)
					return "Create Alert";
				else
					$header = 'Update Alert: '
					.' Matching '.$model->properName($model->priority)
					.' '.($model->remote_type == 'any' ? 'Anything' : $model->properName($model->remote_type));
					if(!empty($model->remote_for) && !($model->remote_for == 'any'))
						$header .= ' for '.$model->properName($model->remote_for);
					if(!empty($model->remote_id))
						$header .= ' '.(!$model->remote_id ? 'with Any id' : ' with id '.$model->remote_id);
					return $header;
			}
		];
		$options['force'] = true;
		return parent::actionForm($type, $id, $options);
	}
	
	public function actionList($id)
	{
		$this->setResponseFormat('json');
		$options = [];
		$dependsOn = \Yii::$app->request->post('depdrop_parents')[0];
		switch($id)
		{	
			case 'for':
			switch($dependsOn)
			{
				case 'issue':
				$types = Alerts::$settings[$this->model->isWhat()]['for'];
				$ret_val = [
					"output" => array_map(function ($key, $value) {
						return [
							'id' => $key,
							'name' => $value
						];
					}, array_keys($types), array_values($types)), 
					"selected" => 0
				];
				//array_unshift($ret_val['output'], ['id' => 0, 'name' => "choose one..."]);
				break;
				
				default:
				$ret_val = ["output" => [['id' => 'any', 'name' => "ignore this"]], "selected" => 'any'];
				break;
			}
			break;	
			
			case 'priority':
			switch($dependsOn)
			{
				case 'chat':
				case 'replies':
				$ret_val = ["output" => [['id' => 'any', 'name' => "Ignore priority"]], "selected" => "any"];
				break;
				
				default:
				$types = Alerts::$settings[$this->model->isWhat()]['priorities'];
				$ret_val = [
					"output" => array_map(function ($key, $value) {
						return [
							'id' => $key,
							'name' => $value
						];
					}, array_keys($types), array_values($types)), 
					"selected" => ''
				];
				//array_unshift($ret_val['output'], ['id' => 'any', 'name' => "Any"]);
				break;
			}
			break;
			
			case 'types':
			switch($dependsOn)
			{
				case 'create':
				case 'update':
				$types = Alerts::$settings[$this->model->isWhat()]['allowed'];
				break;
				
				case 'reply_my':
				case 'reply':
				$types = Alerts::$settings[$this->model->isWhat()]['reply_allowed'];
				break;
			
				default:
				$types = ['any' => 'Anything'];
				break;
			}
			$ret_val = [
				"output" => array_map(function ($key, $value) {
					return [
						'id' => $key,
						'name' => $value
					];
				}, array_keys($types), array_values($types)), 
				"selected" => ''
			];
			//array_unshift($ret_val['output'], ['id' => 'any', 'name' => "Anything"]);
			break;
		}
		return $this->renderResponse($ret_val, null, true);
	}
}
