<?php

namespace nitm\helpers;

use yii\base\Behavior;

//class that sets up and retrieves, deletes and handles modifying of contact data
class Response extends Behavior
{
	public static $view;
	public static $controller;
	public static $viewOptions = [
		'content' => '',
		'view' => '@nitm/views/response/index', //The view file
		'args' => [] //the arguments to the view file
	];
	public static $format;
	public static $forceAjax = false;
	public static $viewPath = '@nitm/views/response/index';
	public static $viewModal = '@nitm/views/response/modal';
	
	public static function getFormat()
	{
		switch(empty(self::$format))
		{
			case true:
			self::setFormat();
			break;
		}
		return self::$format;
	}
	
	public static function initContext($controller=null, $view=null)
	{
		self::$controller = !($controller) ? \Yii::$app->controller : $controller;
		self::$view = !($view) ? \Yii::$app->controller->getView() : $view;
	}
	
	/*
	 * Determine how to return the data
	 * @param mixed $result Data to be displayed
	 */
	public static function render($result=null, $params=null, $partial=true)
	{
		$contentType = "text/html";
		$render = (($partial === true) || (self::$forceAjax === true)) ? 'renderAjax' : 'render';
		$params = is_null($params) ? self::$viewOptions : $params;
		switch(self::getFormat())
		{
			case 'xml':
			//implement handling of XML responses
			$contentType = "application/xml";
			$ret_val = $result;
			break;
			
			case 'html':
			$params ['view'] =  empty($params['view']) ? self::$viewPath :  $params['view'];
			$ret_val = \Yii::$app->controller->getView()->$render($params['view'], $params['args'], static::$controller);
			break;
			
			case 'modal':
			$params ['view'] =  empty($params['view']) ? self::$viewPath :  $params['view'];
			$ret_val = static::$controller->getView()->$render(self::$viewModal, 
				[
					'content' => static::$view->$render($params['view'], $params['args'], static::$controller),
					'title' => @$params['title'],
					'modalOptions' => @$params['modalOptions'],
				],
				static::$controller
			);
			break;
			
			case 'text':
			$contentType = "text/plain";
			$ret_val = @strip_tags($result['data']);
			break;
			
			default:
			$contentType = "application/json";
			$ret_val = $result;
			break;
		}
		\Yii::$app->response->getHeaders()->set('Content-Type', $contentType);
		return $ret_val;
	}
	
	/*
	 * Get the desired display format supported
	 * @return string format
	 */
	public static function setFormat($format=null)
	{
		$ret_val = null;
		$format = (is_null($format)) ? (!\Yii::$app->request->get('__format') ? null : \Yii::$app->request->get('__format')) : $format;
		switch($format)
		{
			case 'text':
			case 'raw':
			$ret_val = 'raw';
			\Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
			break;
			
			case 'modal':
			$ret_val = $format;
			\Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
			break;
			
			case 'xml':
			$ret_val = $format;
			\Yii::$app->response->format = \yii\web\Response::FORMAT_XML;
			break;
			
			case 'jsonp':
			$ret_val = $format;
			\Yii::$app->response->format = \yii\web\Response::FORMAT_JSONP;
			break;
			
			case 'json':
			$ret_val = $format;
			\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			break;
			
			default:
			$ret_val = 'html';
			\Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
			break;
		}
		self::$format = $ret_val;
		return $ret_val;
	}
	
}
?>