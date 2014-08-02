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
