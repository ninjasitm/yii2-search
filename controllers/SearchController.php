<?php

namespace nitm\search\controllers;

use Yii;
use nitm\controllers\DefaultController;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\helpers\Response;
use \yii\helpers\Html;

/**
 * SearchController implements the CRUD actions for Search model.
 */
class SearchController extends DefaultController
{
	use \nitm\search\traits\controllers\SearchControllerTrait;
	
	public $legend = [
		'normal' => 'Normal',
		'info' => 'Important',
		'danger' => 'Critical',
		'disabled' => 'Hidden'
	];
	public $searchClass;
	
	public function init()
	{
		$class = !isset($this->searchClass) ? \nitm\search\BaseSearch::className() : $this->searchClass;
		$this->model = new $class([
			'scenario' => 'default',
			//'primaryModelClass' => $class::className(),
			'useEmptyParams' => true,
		]);
		$class::$sanitizeType = false;
		$this->engine = \Yii::$app->getModule('nitm-search')->engine;
		parent::init();
	}

    /**
     * Lists all Search models.
     * @return mixed
     */
    public function actionIndex($options=[])
    {
		$this->model->queryOptions['limit'] = 20;
		list($results, $dataProvider) = $this->search();
        $ret_val = [
			'success' => true,
			'query' => $this->model->text,
			'data' => $this->renderAjax('index', array_merge([
				'dataProvider' => $dataProvider,
				'model' => $this->model,
				'stats' => [
					'duration' => @$results['took'].'ms',
					'total' => @$results['hits']['total'],
					'max_score' => @$results['hits']['max_score']
				]
			], (array)@$options['viewOptions'])),
			'message' => "Found ".@(int)$results['hits']['total']." results matching your search"
		];
		Response::viewOptions('args.content', $ret_val['data']);
		return $this->renderResponse($ret_val, Response::viewOptions(), \Yii::$app->request->isAjax);
    }
	
	public function actionFilter($type, $options=[], $searchOptions=[])
	{
		$ret_val = [
			"success" => false, 
			'action' => 'filter',
			"format" => $this->getResponseFormat(),
			'message' => "No data found for this filter"
		];

		$searchModelOptions = array_merge([
			'inclusiveSearch' => true,
			'booleanSearch' => true,
		], $searchOptions);
		
		$className = $this->getSearchClass($options);
		
		$this->model = new $className($searchModelOptions);
		$this->model->setIndexType($type);
		
		list($results, $dataProvider) = $this->search([
			'forceType' => true,
			'types' => $type,
			'isWhat' => $type
		]);
		
		$dataProvider->pagination->route = '/search/filter';
		
		$view = isset($options['view']) ? $options['view'] : 'index';
		
		//Change the context ID here to match the filtered content
		$this->id = $type;
		$ret_val['data'] = $this->renderAjax($view, [
			"dataProvider" => $dataProvider,
			'searchModel' => $this->model,
			'primaryModel' => $this->model->primaryModel,
			'isWhat' => $type,
		]);
		
		if(!\Yii::$app->request->isAjax)
		{
			$ret_val['data'] = Html::tag('div', \yii\widgets\Breadcrumbs::widget([
				'links' => [
					[
						'label' => $this->model->primaryModel->properName(), 
						'url' => $this->model->primaryModel->isWhat()
					], 
					[
						'label' => 'Filter',
					]
				]
			])
			.$ret_val['data'], [
				'class' => 'col-md-12 col-lg-12'
			]);
			$this->setResponseFormat('html');
		}
		$getParams = array_merge([$type], \Yii::$app->request->get());
		
		foreach(['__format', '_type', 'getHtml', 'ajax', 'do'] as $prop)
			unset($getParams[$prop]);
			
		$ret_val['url'] = \Yii::$app->urlManager->createUrl($getParams);
		$ret_val['message'] = !$dataProvider->getCount() ? $ret_val['message'] : "Found ".$dataProvider->getTotalCount()." results matching your search";
		
		Response::viewOptions('args', [
			"content" => $ret_val['data'],
		]);
		
		return $this->renderResponse($ret_val, Response::viewOptions(), \Yii::$app->request->isAjax);
	}
	
	protected function getSearchClass($options)
	{
		if(!isset($options['className']))
		{
			$class = (isset($options['namespace']) ? $options['namespace'] : '\nitm\models\search\\').$this->model->formName();
			switch(class_exists($class))
			{
				case true:
				$className = $class::className();
				break;
				
				default:
				$class = (isset($options['namespace']) ? rtrim($options['namespace'], '\\')."\BaseSearch" : '\nitm\models\search\BaseSearch');
				$className = $class::className();
				break;
			}
		}
		else
			$className = $options['className'];
		return $className;
	}
}
