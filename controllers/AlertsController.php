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
						'actions' => ['notifications', 'mark-notification-read', 'get-new-notifications'],
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
	
	/**
     * Lists all new Replies models according to user activity.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
	 * @param string $key The key of the parent
     * @return mixed
     */
    public function actionGetNewNotifications()
    {
		$this->model = new \nitm\models\Notification(['constrain' => 
			[
				'user_id' => \Yii::$app->user->getId()
			]
		]);
		$ret_val = false;
		$new = $this->model->hasNew();
		switch($new >= 1)
		{
			case true:
			$ret_val = [
				'data' => '',
				'count' => $new,
				'success' => true
			];
			$ret_val['message'] = $ret_val['count']." new notifications";
			$searchModel = new \nitm\models\search\Notification([
				'queryOptions' => [
					'andWhere' => new \yii\db\Expression('UNIX_TIMESTAMP(created_at)>='.\Yii::$app->userMeta->lastActive())
				]
			]);
			$dataProvider = $searchModel->search($this->model->constraints);
			$dataProvider->setSort([
				'defaultOrder' => [
					'id' => SORT_DESC,
				]
			]);
			$newReplies = $dataProvider->getModels();
			foreach($newReplies as $newReply)
			{
				$ret_val['data'] .= $this->renderAjax('@nitm/views/alerts/view-notification', ['model' => $newReply, 'isNew' => true]);
			}
			Response::$viewOptions = [
				'args' => [
					"content" => $ret_val['data'],
				],
				'modalOptions' => [
					'contentOnly' => true
				]
			];
			break;
		}
		$this->setResponseFormat(\Yii::$app->request->isAjax ? 'json' : 'html');
		return $this->renderResponse($ret_val, null, \Yii::$app->request->isAjax);
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
				case 'replies':
				$types = Alerts::setting('for');
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
			switch(1)
			{
				case in_array($dependsOn, (array)Alerts::setting('priorities_allowed')):
				case $dependsOn == 'chat':
				$types = Alerts::setting('priorities');
				$ret_val = [
					"output" => array_map(function ($key, $value) {
						return [
							'id' => $key,
							'name' => $value
						];
					}, array_keys($types), array_values($types)), 
					"selected" => ''
				];
				break;
				
				default:
				$ret_val = ["output" => [['id' => 'any', 'name' => "Ignore priority"]], "selected" => "any"];
				break;
			}
			break;
			
			case 'types':
			switch($dependsOn)
			{
				case 'create':
				case 'update':
				$types = Alerts::setting('allowed');
				break;
				
				case 'reply_my':
				case 'reply':
				$types = Alerts::setting('reply_allowed');
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
