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
				'only' => ['hide', 'new'],
				'rules' => [
					[
						'actions' => ['new', 'hide'],
						'allow' => true,
						'roles' => ['@'],
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

    /**
     * Lists all Replies models.
	 * @param string $type The parent type of the issue
	 * @param int $id The id of the parent
	 * @param string $key The key of the parent
     * @return mixed
     */
    public function actionIndex($type, $id, $key)
    {
		$this->model = new Replies(['constrain' => [$id, $type, $key]]);
		$replies = RepliesWidget::widget([
					"model" => $this->model, 
				]);
		$form = RepliesForm::widget([
					"model" => $this->model, 
					'useModal' => false
				]);
		Response::$viewOptions = [
			'args' => [
				"content" => Html::tag('div', $form.$replies, ['id' => 'messagesWrapper'.$id, 'class' => 'messages']),
			],
			'modalOptions' => [
				'contentOnly' => true
			]
		];
		$this->setResponseFormat(\Yii::$app->request->isAjax ? 'modal' : 'html');
		return $this->renderResponse(null, null, \Yii::$app->request->isAjax);
    }

	public function actionNew($type, $id, $key)
	{
		$ret_val = [
			'success' => false,
			'message' => 'Unable to add reply',
			'action' => 'create'
		];
		$this->model->setScenario('create');
		$this->model->load(\Yii::$app->request->post());
		$constrain = [$id, $type, urldecode($key)];
		$this->model->setConstraints($constrain);
		switch($this->model->load($this->model->constraints, false))
		{
			case true:
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
				$ret_val['data'] = $this->renderPartial('@nitm/views/replies/view', ['model' => $this->model, 'isNew' => true]);
				$ret_val['success'] = true;
				$ret_val['message'] = "Reply saved";
				$ret_val['id'] = 'message'.$this->model->id;
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
	
	public function actionTo()
	{
	}

	public function actionQuote()
	{
	}
}
