<?php
namespace nitm\traits;

use nitm\helpers\Response;

/**
 * Controller actions
 */
trait ControllerActions {
	
	/*
	 * Get the forms associated with this controller
	 * @param string $param What are we getting this form for?
	 * @param int $unique The id to load data for
	 * @param array $options
	 * @return string | json
	 */
	public function actionForm($param=null, $id=null, $options=[])
	{
		$force = false;
		$options['id'] = $id;
		$options['param'] = $param;
		switch($param)
		{	
			//This is for generating the form for updating and creating a form for $this->model->className()
			default:
			$action = !$id ? 'create' : 'update';
			$options['title'] = !isset($options['title']) ? ['title', 'Create '.static::properName($this->model->isWhat())] : $options['title'];
			$options['scenario'] = $action;
			$options['provider'] = null;
			$options['dataProvider'] = null;
			$options['view'] = $param;
			$options['args'] = [false, true, true];
			$options['modelClass'] = $this->model->className();
			$options['force'] = true;
			break;
		}
		$modalOptions = [
			'body' => [
				'class' => 'modal-full'
			],
			'dialog' => [
				'class' => 'modal-full'
			],
			'content' => [
				'class' => 'modal-full'
			],
			'contentOnly' => true
		];
		
		$format = Response::formatSpecified() ? $this->getResponseFormat() : 'html';
		$this->setResponseFormat($format);
		return $this->renderResponse($this->getFormVariables($this->model, $options, $modalOptions), Response::$viewOptions, \Yii::$app->request->isAjax);
	}
}