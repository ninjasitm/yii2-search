<?php

namespace nitm\controllers;

use Yii;
use nitm\helpers\Helper;
use nitm\helpers\Session;
use nitm\models\Configer;
use nitm\helpers\Response;
use nitm\interfaces\DefaultControllerInterface;

class ConfigurationController extends DefaultController implements DefaultControllerInterface
{	
	public function init()
	{
		$this->addJs('admin', true);
		parent::init();
		$this->model = new Configer();
	}
	
	public function behaviors()
	{
		$behaviors = [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				//'only' => ['index', 'update', 'create', 'index', 'get', 'delete', 'convert', 'undelete'],
				'rules' => [
					[
						'actions' => ['index',  'create',  'update',  'delete', 'get','convert', 'undelete'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'index' => ['get'],
					'delete' => ['post'],
					'undelete' => ['post'],
					'create' => ['post'],
					'update' => ['post'],
					'convert' => ['post'],
				],
			],
		];
		
		return array_replace_recursive(parent::behaviors(), $behaviors);
	}
	
	public static function has()
	{
		return [
			'configuration'
		];
	}
	
	function beforeAction($action)
	{
		switch(isset($_GET['engine']))
		{
			case true:
			$this->model->engine = $_GET['engine'];
			$this->model->container = @$_GET['container'];
			break;
		}
		$this->model->load($_REQUEST);
		switch(1)
		{
			case $_SERVER['REQUEST_METHOD'] == 'POST':
			case $_SERVER['REQUEST_METHOD'] == 'PUT':
			$params = $_POST;
			break;
			
			case $_SERVER['REQUEST_METHOD'] == 'GET':
			$params = $_GET;
			break;
		}
		
		$dm = $this->model->getDm();
		$container = Session::getVal($dm.'.current.config');
		$engine = Session::getVal($dm.'.current.engine');
		//set the engine
		$this->model->engine = empty($this->model->engine) ? (empty($engine) ? \Yii::$app->getModule('nitm')->configOptions['engine'] : $engine) : $this->model->engine;
		$this->model->setEngine($this->model->engine);
		
		switch($this->model->engine)
		{
			case 'file':
			$this->model->setDir(\Yii::$app->getModule('nitm')->configOptions['dir']);
			break;
		}
		//determine the correct container
		$this->model->container = empty($this->model->container) ? (empty($container) ? \Yii::$app->getModule('nitm')->configOptions['container'] : $container) : $this->model->container;
		
		//if we're not requesting a specific section then only load the sections and no values
		$this->model->prepareConfig($this->model->engine, $this->model->container, $this->model->getValues);
		parent::beforeAction($action);
		return true;
	}
	
	public function actionIndex()
	{
		return $this->render('index', ["model" => $this->model]);
	}
	
	/*
	 * Convert configuration from one format to antoher
	 */
	public function actionConvert()
	{
		$this->model->setScenario($this->action->id);
		$this->model->load($_REQUEST);
		switch($this->model->convert['do'])
		{
			case true:
			$this->model->convert($this->model->convert['container'], $this->model->convert['from'], $this->model->convert['to']);
			break;
		}
		return $this->finalAction();
	}
	
	public function actionUndelete()
	{
		$section = explode('.', $_REQUEST[$this->model->formName()]['name']);
		$name = explode('.', $_REQUEST[$this->model->formName()]['name']);
		$_REQUEST[$this->model->formName()]['section'] = array_shift($section);
		$_REQUEST[$this->model->formName()]['name'] = array_pop($name);
		$this->action->id = 'create';
		return $this->actionCreate();
	}
	
	public function actionCreate()
	{
		switch(isset($_REQUEST[$this->model->formName()]))
		{
			case true:
			$this->model->setScenario($this->action->id.ucfirst($_REQUEST[$this->model->formName()]['what']));
			$this->model->load($_REQUEST);
			switch(\Yii::$app->request->isAjax && (@Helper::boolval($_REQUEST['do']) !== true))
			{
				case true:
				$this->setResponseFormat('json');
				return \yii\widgets\ActiveForm::validate($this->model);
				break;
			}
			switch($this->model->validate())
			{
				case true:
				switch($this->model->getScenario())
				{
					case 'createContainer':
					$this->model->createContainer($this->model->value, null, $this->model->engine);
					break;
					
					case 'createValue':
					$view['data']['data'] = $this->model->create($this->model->section.'.'.$this->model->name,
							$this->model->value,
							$this->model->container,
							null,
							$this->model->engine);
					$this->model->config['current']['config'] = Session::getVal($this->model->correctKey($this->model->config['current']['action']['key']));
					$view = [
						'view' => 'values/value',
						'data' => [
							"model" => $this->model,
							"data" => $this->model->config['current']['action'],
							"parent" => $this->model->section
						]
					];
					break;
					
					case 'createSection':
					$this->model->create($this->model->value,
							null,
							$this->model->container,
							null,
							$this->model->engine);
					$view = [
						'view' => 'values/index',
						'data' => [
							"model" => $this->model,
							"data" => $this->model->config['current']['config']
						]
					];
					break;
				}
				break;
			}
			break;
		}
		switch($this->model->config['current']['action']['success'] && \Yii::$app->request->isAjax && (Helper::boolval(@$_REQUEST['getHtml']) === true))
		{
			case true:
			$this->model->config['current']['action']['data'] = $this->renderAjax($view['view'], $view['data']);
			break;
		}
		return $this->finalAction();
	}
	
	public function actionGet()
	{
		$ret_val = [
			'success' => false,
			'action' => 'get',
			'message' => 'Get configuration',
			'class' => ''
		];
		switch($this->model->validate())
		{
			case true:
			switch($this->model->what)
			{
				case 'section':
				switch(isset($this->model->config['current']['config'][$this->model->section]))
				{
					case true:
					$this->model->config['current']['config'] = $this->model->config['current']['config'][$this->model->section];
					break;
					
					default:
					$this->model->config['current']['config'] = null;
					break;
				}
				$ret_val["success"] = true;
				$ret_val["section"] = $this->model->section;
				switch(@$_REQUEST['__format'])
				{
					case true:
					$ret_val['data'] = $this->renderAjax('values/index', [
						"model" => $this->model,
						"values" => $this->model->config['current']['config'],
						"parent" => $this->model->section
					]);
					break;
				
					default:
					$ret_val['data'] = $this->model->config['current']['config'];
					break;
				}
				break;
			}
			break;
		}
		Response::$viewOptions['args'] = [
			'content' => $ret_val['data']
		];
		$this->model->config['current']['action'] = $ret_val;
		return $this->finalAction();
	}
	
	public function actionDelete()
	{
		switch(isset($_REQUEST[$this->model->formName()]))
		{
			case true:
			$this->model->setScenario($this->action->id.ucfirst($_REQUEST[$this->model->formName()]['what']));
			$this->model->load($_REQUEST);
			switch(\Yii::$app->request->isAjax && (@Helper::boolval($_REQUEST['do']) !== true))
			{
				case true:
				return $this->model->validate();
				break;
			}
			switch($this->model->validate())
			{
				case true:
				switch($this->model->getScenario())
				{
					case 'deleteContainer':
					//$this->model->update_container($this->model->value);
					break;
					
					case 'deleteValue':
					$this->model->delete($this->model->name,
							$this->model->container,
							null,
							$this->model->engine);
					$this->model->config['current']['config'] = Session::getVal($this->model->correctKey($this->model->config['current']['action']['key']));
					break;
					
					case 'deleteSection':
					$this->model->delete($this->model->section,
							$this->model->container,
							null,
							$this->model->engine);
					break;
				}
				break;
			}
			break;
		}
		return $this->finalAction();
	}
	
	public function actionUpdate()
	{
		switch(isset($_REQUEST[$this->model->formName()]))
		{
			case true:
			$this->model->setScenario($this->action->id.ucfirst($_REQUEST[$this->model->formName()]['what']));
			$this->model->load($_REQUEST);
			switch(\Yii::$app->request->isAjax && (@Helper::boolval($_REQUEST['do']) !== true))
			{
				case true:
				return $this->model->validate();
				break;
			}
			switch($this->model->validate())
			{
				case true:
				switch($this->model->getScenario())
				{
					case 'updateContainer':
					//$this->model->update_container($this->model->value);
					break;
					
					case 'updateValue':
					/*if (is_array($this->model->container)) 
					{
						print_r($this->model->container); 
						exit;
					}*/
					$this->model->update($this->model->name,
							$this->model->value,
							$this->model->container,
							null,
							$this->model->engine);
					break;
					
					case 'updateSection':
					/*$this->model->create($this->model->value,
							null,
							$this->model->container);*/
					break;
				}
				break;
			}
			break;
		}
		return $this->finalAction();
	}
	
	/*---------------------
	  Private functions
	 --------------------*/
	/*
	 * Where do we go after an action?
	 * @params mixed $params
	 */
	private function finalAction($params=null)
	{
		\Yii::$app->getSession()->setFlash(
			@$this->model->config['current']['action']['class'],
			$this->model->config['current']['action']['message']
		);
		switch(\Yii::$app->request->isAjax)
		{
			//if this is an ajax call then print the result
			case true:
			$this->model->config['current']['action']['flash'] = \Yii::$app->getSession()->getFlash(
			$this->model->config['current']['action']['class'], null, true);
			Response::$viewOptions['args']['content'] = $this->model->config['current']['action'];
			$format = Response::formatSpecified() ? $this->getResponseFormat() : 'json';
			$this->setResponseFormat($format);
			return $this->renderResponse($this->model->config['current']['action'], null, true);
			break;
			
			//otherwise we're going back to the index
			default;
			$this->redirect(\Yii::$app->request->getReferrer());
			break;
		}
	}
	
};

?>
