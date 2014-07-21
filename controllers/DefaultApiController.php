<?php

namespace nitm\controllers;

use Yii;
use yii\helpers\Html;
use yii\rest\Controller;
use nitm\helpers\Session;
use nitm\helpers\Response;
use nitm\models\Configer;

class DefaultApiController extends Controller
{
	use \nitm\traits\Configer, \nitm\traits\Controller;
	
	public $model;
	
	public function behaviors()
	{ 
		$behaviors = array(
			'verbs' => [
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'filter' => ['get', 'post'],
				]
			],
		);
		return $behaviors;
	}

	public function init()
	{
		// get the default css and meta tags
		$this->initAssets();
		$registered = Session::isRegistered(Session::settings);
		switch(!$registered || !(Session::size(Session::settings)))
		{
			case true:
			$this->initConfig();
			break;
		}
		//$this->initConfig(@Yii::$app->controller->id);
		parent::init();
	}
	
	public static function has()
	{
		return [
		];
	}
	
	public function actionSearch()
	{
		$ret_val = [
			"success" => false, 
			'action' => 'filter', 
			"pour" => $this->model->isWhat(),
			"format" => $this->getResponseFormat(),
			'message' => "No data found for this filter"
		];
		switch(class_exists('@app/models/search/'.$this->model->isWhat()))
		{
			case true:
			$class = '@app/models/search/'.$this->model->isWhat();
			$className = $class::className();
			$searchModel = new $className();
			break;
			
			default:
			$serchModel = $this->model;
			break;
		}
		//$search->setScenario('filter');
		$class = array_pop(explode('\\', $this->model->className()));
        $this->data = $searchModel->search($_REQUEST[$class]['filter']);
		$partial = true;
		switch($this->model->successful())
		{
			case true:
			switch(\Yii::$app->request->isAjax)
			{
				case true:
				$ret_val['data'] = $this->renderPartial('data', ["model" => $this->model]);
				$ret_val['success'] = true;
				$ret_val['message'] = "Found data for this filter";
				break;
				
				default:
				$this->setResponseFormat('html');
				Response::$viewOptions['args'] = [
					"content" => $this->renderAjax('data', ["model" => $this->model]),
				];
				$partial = false;
				break;
			}
			break;
		}
		echo $this->renderResponse($ret_val, Response::$viewOptions, $partial);
	}
}

?>
