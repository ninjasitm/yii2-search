<?php

namespace nitm\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\helpers\Security;
use yii\helpers\Html;
use nitm\helpers\Helper;
use nitm\helpers\Response;
use nitm\models\Replies;
use nitm\widgets\replies\Replies as RepliesWidget;
use nitm\widgets\replies\RepliesForm;

class ReplyController extends DefaultController
{	
	public function behaviors()
	{
		return [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					[
						'actions' => ['new', 'hide'],
						'allow' => true,
						'roles' => ['@'],
					],
					[
						'actions' => ['get-new'],
						'allow' => true,
						'roles' => ['?'],
					],
				],
			],
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
		parent::init();
		$this->model = new \nitm\models\Replies();
	}
	
	public static function has()
	{
		$has = [
			'\nitm\widgets\replies'
		];
		return array_merge(parent::has(), $has);
	}
	
	public function beforeAction($action)
	{
		switch($action->id)
		{
			case 'hide':
			$this->enableCsrfValidation = false;
			break;
		}
		return true;
	}

    /**
     * Lists all Replies models.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
	 * @param string $key The key of the parent
     * @return mixed
     */
    public function actionIndex($type, $id, $key=null)
    {
		$uniqid = uniqid();
		$this->model = new Replies(['constrain' => [$id, $type, $key]]);
		switch($type)
		{
			case 'chat':
			$updateOptions = [
				"interval" => 60000,
				"enabled" => true,
				'url' => '/reply/get-new/chat/0'
			];
			$replies = \nitm\widgets\replies\ChatMessages::widget([
				'model' => $this->model, 
				'withForm' => is_null(\Yii::$app->request->get(Replies::FORM_PARAM, null)) ? true : \Yii::$app->request->get(Replies::FORM_PARAM),
				'updateOptions' => $updateOptions
			]);
			$form = false;
			break;
			
			default:
			$replies = RepliesWidget::widget([
				"model" => $this->model, 
				'uniqid' => $uniqid
			]);
			$form = RepliesForm::widget([
				"model" => $this->model, 
				'useModal' => false, 
				'uniqid' => $uniqid
			]);
			break;
		}
		Response::$viewOptions = [
			'args' => [
				"content" => $replies.$form,
			],
			'modalOptions' => [
				'contentOnly' => true
			]
		];
		return $this->renderResponse(null, null, \Yii::$app->request->isAjax);
    }

	public function actionNew($type, $id, $key=null)
	{
		$ret_val = [
			'success' => false,
			'message' => 'Unable to add reply',
			'action' => 'create'
		];
		$this->model->setScenario('create');
		$this->model->load(\Yii::$app->request->post());
		switch($type)
		{
			case 'chat':
			$id = 0;
			$key = new \yii\db\Expression('NOW()');
			break;
		}
		$constrain = [$id, $type, urldecode($key)];
		$this->model->setConstraints($constrain);
		
		switch($this->model->load($this->model->constraints, false))
		{
			case true:
			switch(\Yii::$app->request->isAjax && (@Helper::boolval($_REQUEST['do']) !== true))
			{
				case true:
				$this->model->setScenario('validateNew');
				$this->setResponseFormat('json');
				return $this->model->validate();
				break;
			}
			
			switch($this->model->reply())
			{
				case true:
				switch($type)
				{
					case 'chat':
					$ret_val['data'] = $this->renderAjax('@nitm/views/chat/view', ['model' => $this->model, 'isNew' => true]);
					break;
					
					default:
					$ret_val['data'] = $this->renderAjax('@nitm/views/replies/view', ['model' => $this->model, 'isNew' => true]);
					break;
				}
				$ret_val['success'] = true;
				$ret_val['message'] = "Reply saved";
				$ret_val['id'] = $this->model->id;
				$ret_val['unique_id'] = 'message'.$this->model->id;
				$this->setResponseFormat(\Yii::$app->request->isAjax ? 'json' : 'html');
				Response::$viewOptions['args']['content'] = $ret_val['data'];
				break;
				
				case false:
				$this->setResponseFormat('json');
				Response::$viewOptions['args']['content'] = $ret_val;
				break;
			}
			break;
		}
		return $this->renderResponse($ret_val, Response::$viewOptions);	
	}
	
	public function actionHide($id)
	{
		$ret_val = [
			'id' => $id,
			'success' => false,
			'message' => 'Unable to hide reply',
			'action' => 'hide'
		];
		$this->model = $this->model->findOne($id);
		$this->model->setScenario('hide');
		$this->model->hidden = !$this->model->hidden;
		switch($this->model->save())
		{
			case true:
			$ret_val['action'] = $this->model->hidden ? 'unhide' : 'hide';
			$ret_val['value'] = $this->model->hidden;
			$ret_val['success'] = true;
			$ret_val['message'] = "Successfully hid reply";
			break;
		}
		$this->setResponseFormat('json');
		return $this->renderResponse($ret_val, Response::$viewOptions);	
	}

    /**
     * Lists all new Replies models according to user activity.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
	 * @param string $key The key of the parent
     * @return mixed
     */
    public function actionGetNew($type, $id, $key=null)
    {
		$this->model = new Replies(['constrain' => [$id, $type, $key]]);
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
			$ret_val['message'] = $ret_val['count']." new messages";
			$searchModel = new \nitm\models\search\Replies([
				'withThese' => ['replyTo', 'authorUser'],
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
				switch($type)
				{
					case 'chat':
					$ret_val['data'] .= $this->renderAjax('@nitm/views/chat/view', ['model' => $newReply, 'isNew' => true]);
					break;
					
					default:
					$ret_val['data'] .= $this->renderAjax('@nitm/views/replies/view', ['model' => $newReply, 'isNew' => true]);
					break;
				}
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
	
	public function actionTo()
	{
	}

	public function actionQuote()
	{
	}
}
