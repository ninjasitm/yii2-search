<?php

namespace nitm\search\controllers;

use Yii;
use nitm\controllers\DefaultController;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use nitm\helpers\Response;

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
			'primaryModelClass' => $class::className(),
			'useEmptyParams' => true,
		]);
		$class::$noSanitizeType = true;
		$this->engine = \Yii::$app->params['components.search']['engine'];
		parent::init();
	}
	
    /*public function behaviors()
    {
		$behaviors = [
		];
		return array_merge_recursive(parent::behaviors(), $behaviors);
    }*/

    /**
     * Lists all Search models.
     * @return mixed
     */
    public function actionIndex($options=[])
    {	
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
		Response::$viewOptions['args']['content'] = $ret_val['data'];
		return $this->renderResponse($ret_val, Response::$viewOptions, \Yii::$app->request->isAjax);
    }
}
