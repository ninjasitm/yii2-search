<?php

namespace nitm\controllers;

use Yii;
use yii\helpers\Html;
use yii\web\Controller;
use nitm\helpers\Session;
use nitm\helpers\Response;
use nitm\models\Configer;

class DefaultController extends Controller
{
	use \nitm\traits\Configer;
	
	public $model;
	public $settings;
	public $metaTags = array();
	
	protected $responseFormat;
	
	/**
	 * Indicator types supports
	 */
	protected $statusIndicators = [
		'error' => 'content bg-danger',
		'default' => 'content',
		'success' => 'content bg-success',
		'info' => 'content bg-info',
		'warning' => 'content bg-warning'
	];

	private $_cssFiles = array();
	private $_jsFiles = array();
	
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
		$this->initCss();
		$this->initAssets();
		$this->initMetaTags();
		$this->initJs();
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
	 * Initialize any css needed for this controller
	 */
	public function initCss($_cssFiles = array())
	{
		//don't init on ajax requests so that we don't send duplicate files
		if(\Yii::$app->request->isAjax)
			return;
		$this->_cssFiles = is_array($this->_cssFiles) ? $this->_cssFiles : array($this->_cssFiles);
		$this->_cssFiles[] = $this->id;
		switch(!empty($this->_cssFiles))
		{
			case true:
			foreach($this->_cssFiles as $css)
			{
				$file = (is_array($css) ? $css['url'] : $css).'.css';
				switch(file_exists(Yii::$app->basePath.'/web'.$file))
				{
					case true:
					$this->view->registerCssFile(Yii::$app->UrlManager->baseUrl.$file, @$css['depends'], @$css['options']);
					break;
					
					default:
					//This is probably a module css file
					/*switch(file_exists(\Yii::$app->getModule('nitm')->basePath.'/assets/css/'.$file))
					{
						case true:
						$this->view->registerCssFile(Yii::$app->UrlManager->baseUrl.$file, '');
						break;
					}*/
					break;
				}
			}
			break;
		}
	}
	
	/*
	 * Initialize any javascript needed for this controller
	 * @param boolean $defaults
	 * @param boolean $footer
	 */
	public function initJs($defaults=true, $footer=false)
	{
		//don't init on ajax requests so that we don't send duplicate files
		if(\Yii::$app->request->isAjax)
			return;
		switch($defaults)
		{
			case true:
			$this->_jsFiles[] = array('src' => $this->id, 'position' => \yii\web\View::POS_END);
			break;
		}
		switch(!empty($this->_jsFiles))
		{
			case true:
			foreach($this->_jsFiles as $js)
			{
				switch($js['src'][0] == ':')
				{
					case true:
					$js['src'] = substr($script, 1, strlen($js['src']));
					break;
					
					default:
					$js['src'] = '/js/'.$js['src'];
					break;
				}
				$js['src'] = $js['src'].'.js';
				switch(file_exists(Yii::$app->basePath.'/web'.$js['src']))
				{
					case true:
					$js['src'] = Yii::$app->UrlManager->baseUrl.$js['src'];
					$this->view->registerJsFile($js['src'], @$js['depends'], ["position" => $js['position']]);
					break;
				}
			}
			break;
		}
	}
	
	/*
	 * Check to see if somethign is supported
	 * @param mixed $what
	 */
	public function isSupported($what)
	{
		return (@$this->settings['supported'][$what] == true);
	}

	/*
	 * Add js files to this controller
	 * @param string $jscript
	 * @param boolean $footer
	 */
	public function addJs($jscript, $footer=true)
	{
		$jscript = array_filter(is_array($jscript) ? $jscript : explode(',', $jscript));
		if(!empty($jscript))
		{
			switch(is_array($jscript) && !empty($jscript))
			{
				case true:
				foreach($jscript as $script)
				{
					$this->_jsFiles[] = array('src' => $script,
											'position' => (($footer === true) ? \yii\web\View::POS_END : \yii\web\View::POS_HEAD)
					);
				}
				break;
			}
		}
	}

	/*
	 * Add css files to this controller
	 * @param string $css
	 * @param boolean $footer
	 */
	public function addCss($css)
	{
		$css = array_filter(is_array($css) ? $css : explode(',', $css));
		if(!empty($css))
		{
			switch(is_array($css) && !empty($css))
			{
				case true:
				foreach($css as $file)
				{
					$this->_cssFiles[] = $file;
				}
				break;
			}
		}
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
	
	/*
	 * Initialize any meta tags needed for this controller
	 * @param mixed $metaTags
	 */
	public function initMetaTags($metaTags = array())
	{
		// meta tags
		$metaTags = is_array($metaTags) ? $metaTags : array($metaTags);
		$this->metaTags = array_filter(array_merge($this->metaTags, $metaTags));
		if(!empty($this->metaTags))
		{
			foreach(array_keys($this->metaTags) as $metaTag)
			{
				switch(empty($metaTag))
				{
					case true:
					Yii::$app->view->registerMetaTag($this->metaTags[$metaTag],$metaTag);
					break;
				}
			}
		} 
        
    }
	
	/**
	 * load the main navigation variables
	 * @param string from
	 * @return mixed $ret_val
	 */
	public static function loadNav($from="navigation")
	{
		$ret_val = array();
		$navigation = array();
		$navigation = Session::getVal($from);
		$priorities = array();
		if(is_array($navigation))
		{
			foreach($navigation as $group=>$val)
			{
				@list($name, $property, $property_name) = explode("_", $group);
				switch(@$val['item_disabled'] == 1)
				{
					//handle sublinks here. only one level deep
					case false:
					switch($property)
					{
						case "sub":
						$priority = isset($val['priority']) ? $val['priority'] : sizeof($ret_val);
						$ret_val[$priorities[$name]."_".$name][$property][$property_name] = $val;
						break;
						
						//this is a mainlink
						default:
						$priority = isset($val['priority']) ? $val['priority'] : sizeof($ret_val);
						$ret_val[$priority."_".$name] = @(!is_array($ret_val[$name])) ? array() : $ret_val[$priority."_".$name];
						$priorities[$name] = $priority;
						$ret_val[$priority."_".$name] = $val;
						break;
					}
					break;
				}
			}
		}
		ksort($ret_val);
		return $ret_val;
	}
	
	/*
	 * Get the HTML encoded navigation information
	 * @param mixed $navigation
	 * @param mixed $encapsulate Surround the elements in this tag
	 * @return mixed $ret_val
	 */
	public static function getNavHtml($navigation=null, $encapsulate=null)
	{
		$ret_val = array();
		$navigation = !is_array($navigation) ? static::loadNav('settings.navigation') : $navigation;
		$top = ($navigation === null) ? true : false;
		foreach($navigation as $idx=>$item)
		{
			$submenu = null;
			switch(isset($item['sub']) && is_array($item['sub']))
			{
				case true:
				$item['sub'] = static::getNavHtml($item['sub']);
				$submenu = $item['sub'];
				break;
			}
			$ret_val[$idx] = [
				'label' => Html::tag('span', @$item['name'], [
					'class' => @$item['label-class']
				]),
				'items' => $submenu,
				'url' => @$item['href'],
				"options" => [
					"class" => @$item['class'], 
					"encode" => false
				]
			];
			switch(empty($encapsulate))
			{
				case false:
				$ret_val[$idx]['label'] = Html::tag($encapsulate, $ret_val[$idx]['label'], 
				[
					"class" => @$item['class'], 
					"encode" => false
				]);
				$ret_val[$idx]['options']['class'] = null;
				break;
			}
		}
		return $ret_val;
	}
	
	/**
	 * Get the class indicator value for the users status
	 * @param Edit $token
	 * @return string $css class
	 */
	public function getStatusIndicator($item)
	{
		$item = is_null($item) ? $this : $item;
		$ret_val = 'default';
		switch(is_object($item))
		{
			case true:
			switch(1)
			{
				case $item->hasProperty('disabled');
				case '':
				$ret_val = 'default';
				break;
				
				case 'Public':
				$ret_val = 'info';
				break;
			}
			break;
		}
		$indicator = $this->statusIndicators[$ret_val];
		return $indicator;
	}
	
	/**
	 * Get the javascript requested for this ajax view
	 */
	public function getJavascriptForView(\yii\web\View $view=null)
	{
		$view = is_null($view) ? $this->getView() : $view;
		$ret_val = '';
		if(@is_array($view->js))
		{
			$aman = $view->getAssetManager();
			array_walk($aman->bundles, function ($bundle) use($aman, &$ret_val){
				 foreach($bundle->js as $file)
				 {
					 //Only load the validtion scripts
					 switch($file)
					 {
						case 'yii.validation.js':
						case 'yii.activeForm.js':
						$aman->publish($bundle->sourcePath);
						$ret_val .= Html::jsFile($aman->getPublishedUrl($bundle->sourcePath)."/".$file)."\n";
						break;
					 }
				 }
			});			
			$ret_val .= Html::script(
				array_walk($view->js[static::POS_READY], function ($v) {
					return $v;
				})
			);

		}
		return $ret_val;
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
	
	public function getFormVariables($model, $options, $modalOptions=[])
	{
		return \nitm\helpers\Form::getVariables($model, $options, $modalOptions);
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
