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
	public $model;
	
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
						'actions' => ['login', 'error'],
						'allow' => true,
						'roles' => ['?']
					],
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
			$this->model->cfg_e = $_GET['engine'];
			$this->model->cfg_c = @$_GET['container'];
			break;
		}
		switch(!empty($_POST))
		{
			case true:
			$this->model->load($_POST);
			break;
		}
		
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
		$this->model->cfg_e = empty($this->model->cfg_e) ? (empty($engine) ? \Yii::$app->getModule('nitm')->configOptions['engine'] : $engine) : $this->model->cfg_e;
		$this->model->setEngine($this->model->cfg_e);
		
		switch($this->model->cfg_e)
		{
			case 'file':
			$this->model->setDir(\Yii::$app->getModule('nitm')->configOptions['dir']);
			break;
		}
		//determine the correct container
		$this->model->cfg_c = empty($this->model->cfg_c) ? (empty($container) ? \Yii::$app->getModule('nitm')->configOptions['container'] : $container) : $this->model->cfg_c;
		
		//if we're not requesting a specific section then only load the sections and no values
		$this->model->prepareConfig($this->model->cfg_e, $this->model->cfg_c, $this->model->get_values);
		parent::beforeAction($action);
		return true;
	}
	
	public function actionIndex()
	{
		return $this->render('index', array("model" => $this->model));
	}
	
	/*
	 * Convert configuration from one format to antoher
	 */
	public function actionConvert()
	{
		$this->model->setScenario($this->action->id);
		$this->model->load($_REQUEST);
		switch($this->model->cfg_convert['do'])
		{
			case true:
			$this->model->convert($this->model->cfg_convert['container'], $this->model->cfg_convert['from'], $this->model->cfg_convert['to']);
			break;
		}
		$this->finalAction();
	}
	
	public function actionUndelete()
	{
		$section = explode('.', $_REQUEST[$this->model->formName()]['cfg_n']);
		$name = explode('.', $_REQUEST[$this->model->formName()]['cfg_n']);
		$_REQUEST[$this->model->formName()]['cfg_s'] = array_shift($section);
		$_REQUEST[$this->model->formName()]['cfg_n'] = array_pop($name);
		$this->action->id = 'create';
		$this->actionCreate();
	}
	
	public function actionCreate()
	{
		switch(isset($_REQUEST[$this->model->formName()]))
		{
			case true:
			$this->model->setScenario($this->action->id.ucfirst($_REQUEST[$this->model->formName()]['cfg_w']));
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
					case 'createContainer':
					$this->model->createContainer($this->model->cfg_v, null, $this->model->cfg_e);
					break;
					
					case 'createValue':
					$view['data']['data'] = $this->model->create($this->model->cfg_s.'.'.$this->model->cfg_n,
							$this->model->cfg_v,
							$this->model->cfg_c,
							null,
							$this->model->cfg_e);
					$this->model->config['current']['config'] = Session::getVal($this->model->correctKey($this->model->config['current']['action']['key']));
					$view = [
								'view' => 'values/value',
								'data' => [
									"model" => $this->model,
									"data" => $this->model->config['current']['action'],
									"parent" => $this->model->cfg_s
								]
							];
					break;
					
					case 'createSection':
					$this->model->create($this->model->cfg_v,
							null,
							$this->model->cfg_c,
							null,
							$this->model->cfg_e);
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
		switch(\Yii::$app->request->isAjax && (Helper::boolval(@$_REQUEST['getHtml']) === true))
		{
			case true:
			$this->model->config['current']['action']['data'] = $this->renderPartial($view['view'], $view['data']);
			break;
		}
		$this->finalAction();
	}
	
	public function actionGet()
	{
		$ret_val = array("success" => false, 'action' => 'get');
		switch($this->model->validate())
		{
			case true:
			switch($this->model->cfg_w)
			{
				case 'section':
				switch(isset($this->model->config['current']['config'][$this->model->cfg_s]))
				{
					case true:
					$this->model->config['current']['config'] = $this->model->config['current']['config'][$this->model->cfg_s];
					break;
					
					default:
					$this->model->config['current']['config'] = null;
					break;
				}
				$ret_val["success"] = true;
				$ret_val["section"] = $this->model->cfg_s;
				switch($_REQUEST['__format'])
				{
					case true:
					$ret_val['data'] = $this->renderPartial('values/index', array("model" => $this->model,
												      "values" => $this->model->config['current']['config'],
												      "parent" => $this->model->cfg_s));
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
		echo $this->renderResponse($ret_val, Response::$viewOptions, true);
	}
	
	public function actionDelete()
	{
		switch(isset($_REQUEST[$this->model->formName()]))
		{
			case true:
			$this->model->setScenario($this->action->id.ucfirst($_REQUEST[$this->model->formName()]['cfg_w']));
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
					//$this->model->update_container($this->model->cfg_v);
					break;
					
					case 'deleteValue':
					$this->model->delete($this->model->cfg_n,
							$this->model->cfg_c,
							null,
							$this->model->cfg_e);
					$this->model->config['current']['config'] = Session::getVal($this->model->correctKey($this->model->config['current']['action']['key']));
					break;
					
					case 'deleteSection':
					$this->model->delete($this->model->cfg_s,
							$this->model->cfg_c,
							null,
							$this->model->cfg_e);
					break;
				}
				break;
			}
			break;
		}
		$this->finalAction();
	}
	
	public function actionUpdate()
	{
		switch(isset($_REQUEST[$this->model->formName()]))
		{
			case true:
			$this->model->setScenario($this->action->id.ucfirst($_REQUEST[$this->model->formName()]['cfg_w']));
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
					//$this->model->update_container($this->model->cfg_v);
					break;
					
					case 'updateValue':
					if (is_array($this->model->cfg_c)) 
					{
						print_r($this->model->cfg_c); 
						exit;
					}
					$this->model->update($this->model->cfg_n,
							$this->model->cfg_v,
							$this->model->cfg_c,
							null,
							$this->model->cfg_e);
					break;
					
					case 'updateSection':
					/*$this->model->create($this->model->cfg_v,
							null,
							$this->model->cfg_c);*/
					break;
				}
				break;
			}
			break;
		}
		$this->finalAction();
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
			$params = [
				'view' => '@common/views/utils/wrapper'
			];
			echo $this->renderResponse($this->model->config['current']['action'], $params, true);
			break;
			
			//otherwise we're going back to the index
			default;
			$this->redirect(\Yii::$app->urlManager->createUrl('configuration/'));
			break;
		}
	}
	
};

?>
