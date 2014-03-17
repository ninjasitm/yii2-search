<?php

namespace nitm\module\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\helpers\Security;
use nitm\module\models\Helper;
use nitm\module\models\Replies;

class ReplyController extends DefaultController
{	
	public function behaviors()
	{
		return [
			'access' => [
				'class' => \yii\web\AccessControl::className(),
				'only' => ['hide', 'new'],
				'rules' => [
					[
						'actions' => ['new', 'hide'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			"Helper" => array(
				"class" => \nitm\module\models\Helper::className(),
			),
		];
	}

	public function actions()
	{
		return [
			'error' => [
				'class' => 'yii\web\ErrorAction',
			]
		];
	}
	
	public function init()
	{
		$this->model = new \nitm\module\models\Replies();
	}

	public function actionNew()
	{
		$ret_val = [
			'success' => false,
			'message' => 'Unable to add reply',
			'action' => 'add'
		];
		$this->model->setScenario('create');
		$this->model->load($_POST);
		$namespace = explode('\\', $this->model->constrain['for']); 
		$namespace[sizeof($namespace)-1] = ucfirst($namespace[sizeof($namespace)-1]);
		//need to set the proper constraint value here
		$this->model->constrain['for'] = $namespace[0];
		$class = implode('\\', $namespace);
		switch(class_exists($class))
		{
			case true:
			$reply_for = $class::find($this->model->constrain['unique']);
			switch($reply_for instanceof $class)
			{
				case true:
				$constrain = [
					'one' => $this->model->constrain['unique'],
					'two' => $reply_for->added,
					'three' => $this->model->constrain['for']
				];
				$this->model->setConstraints($constrain);
				$this->model->load($this->model->constraints, false);
				switch(\Yii::$app->request->isAjax && (@Helper::boolval($_REQUEST['do']) !== true))
				{
					case true:
					$this->model->setScenario('validateNew');
					return $this->model->validate();
					break;
				}
				
				switch($this->model->reply())
				{
					case true:
					$reply = new \nitm\widgets\replies\widget\Replies([
						'reply' => $this->model,
						'constrain' => $constrain
					]);
					$reply->userExists($this->model);
					$ret_val['data'] = $reply->getReply();
					$ret_val['success'] = true;
					$ret_val['message'] = "Reply saved";
					$ret_val['unique_id'] = 'message'.$this->model->replyid;
					$this->_view['content'] = $ret_val['data'];
					break;
					
					case false:
					break;
				}
				break;
			}
			break;
		}
		echo $this->renderResponse($ret_val, $this->_view);	
	}
	
	public function actionHide($unique)
	{
		$ret_val = [
			'id' => $unique,
			'success' => false,
			'message' => 'Unable to hide reply',
			'action' => 'unhide'
		];
		$this->model = $this->model->find($unique);
		$this->model->setScenario('hide');
		$this->model->hidden = !$this->model->hidden;
		switch($this->model->save())
		{
			case true:
			$ret_val['success'] = true;
			$ret_val['message'] = "Successfully hid reply";
			//Determine if the model is hidden or not
			switch($this->model->hidden)
			{
				case 0:
				$ret_val['action'] = 'hide';
				break;
			}
			break;
		}
		echo $this->renderResponse($ret_val, $this->_view);	
	}
	
	public function actionTo()
	{
	}

	public function actionQuote()
	{
	}
}
