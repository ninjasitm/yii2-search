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
	use \nitm\traits\Configer;
	
	public $model;
	public $settings;
	
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
	
	/*
	 * Check to see if somethign is supported
	 * @param mixed $what
	 */
	public function isSupported($what)
	{
		return (@$this->settings['supported'][$what] == true);
	}
	
	/**
	 * Initialze the assets supported by this controller. Taken from static::has();
	 */
	public function initAssets()
	{
		//don't init on ajax requests so that we don't send duplicate files
		if(\Yii::$app->request->isAjax)
			return;
		$has = static::has();
		switch(is_array($has))
		{
			case true:
			foreach($has as $asset)
			{
				//This may be an absolute namespace to an asset
				switch(class_exists($asset))
				{
					case true:
					$asset::register($this->getView());
					break;
					
					default:
					//It isn't then it may be an asset we have in nitm/assets or nitm/widgets
					$class = $asset.'\assets\Asset';
					switch(class_exists($class))
					{
						case true:
						$class::register($this->getView());
						//\Yii::$app->assetManager->bundles[] = $class;
						//\Yii::$app->assetManager->getBundle($class)->registerAssetFiles($this->getView());
						break;
						
						default:
						//This is probably not a widget asset but a module asset
						$class = '\nitm\assets\\'.static::properName($asset).'Asset';
						switch(class_exists($class))
						{
							case true:
							$class::register($this->getView());
							break;
						}
						break;
					}
					break;
				}
			}
			break;
		}
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
	
	public function getFormVariables($options, $modalOptions=[], $model)
	{
		return \nitm\helpers\Form::getVariables($options, $modalOptions, $model);
	}
	
	public function getResponseFormat()
	{
		return Response::getFormat();
	}
	
	/*
	 * Determine how to return the data
	 * @param mixed $result Data to be displayed
	 */
	protected function renderResponse($result=null, $params=null, $partial=true)
	{
		Response::setFormat();
		Response::initContext(\Yii::$app->controller,  \Yii::$app->controller->getView());
		$render = (($partial === true) || (Response::$forceAjax === true)) ? 'renderAjax' : 'render';
		return $this->$render(Response::$viewPath, ['content' => Response::render($result, $params, $partial)]);
	}
	
	/*
	 * Get the desired display format supported
	 * @return string format
	 */
	protected function setResponseFormat($format=null)
	{
		return Response::setFormat($format);
	}
	
	/*
	 * Return a string imploded with ucfirst characters
	 * @param string $name
	 * @return string
	 */
	protected static function properName($value)
	{
		$ret_val = empty($value) ?  [] : array_map('ucfirst', preg_split ("/[_-]/", $value));
		return implode($ret_val);
	}
}

?>
