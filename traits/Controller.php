<?php
namespace nitm\traits;

use nitm\helpers\Response;

/**
 * Traits defined for expanding active relation scopes until yii2 resolves traits issue
 */
 trait Controller {

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
		Response::initContext(\Yii::$app->controller,  \Yii::$app->controller->getView());
		$render = (($partial === true) || (Response::$forceAjax === true)) ? 'renderAjax' : 'render';
		switch(Response::getFormat())
		{
			/**
			 * Render only the raw response if we have requested any of the following formats
			 */
			case 'json':
			case 'jsonp':
			case 'xml':
			case 'raw':
			return Response::render($result, $params, $partial);
			break;
			
			default:
			return $this->$render(Response::$viewPath, ['content' => Response::render($result, $params, $partial)]);
			break;
		}	
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
