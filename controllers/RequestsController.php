<?php

namespace nitm\controllers;

use Yii;
use nitm\models\Request;
use nitm\models\search\Request as RequestSearch;
use nitm\controllers\DefaultController;
use yii\web\NotFoundHttpException;
use yii\web\VerbFilter;
use yii\db\Expression;
use nitm\helpers\Response;

/**
 * RequestController implements the CRUD actions for Request model.
 */
class RequestsController extends DefaultController
{	
	public $legend = [
		'success' => 'Closed and Completed',
		'warning' => 'Open',
		'danger' => 'Closed and Incomplete',
		'info' => 'Completed',
	];
	
	public function init()
	{
		parent::init();
		$this->model = new Request(['scenario' => 'default']);
	}
	
    public function behaviors()
    {
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
    }
	
	public static function has()
	{
		return [
			'\nitm\widgets\replies',
			'\nitm\widgets\activityIndicator',
			'\nitm\widgets\vote',
			'\nitm\widgets\issueTracker'
		];
	}

    /**
     * Lists all Request models.
     * @return mixed
     */
    public function actionIndex()
    {
		$model = new Request();
        $searchModel = new RequestSearch;
		$searchModel->addWith([
			'author', 'type', 'requestFor', 
			'completedBy', 'closedBy', 'replyModel', 
			'issueModel', 'revisionModel', 'voteModel'
		]);
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
		switch((sizeof(\Yii::$app->request->getQueryParams()) == 0))
		{	
			case true:
			$dataProvider->query->select([
				'*',
				$this->getHasNewQuery()
			]);
			$dataProvider->query->orderBy($this->getOrderByQuery())
			->andWhere([
				'closed' => 0
			]);
			break;
		}

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
			'model' => $model,
        ]);
    }
	
	/**
	 * Add some custom values for the search model
	 */
	public function actionSearch()
	{
		return parent::actionSearch([
			'withThese' => [
				'author', 'type', 'requestFor', 
				'completedBy', 'closedBy', 'replyModel', 
				'issueModel', 'revisionModel', 'voteModel'
			]
		]);
	}
	
	/**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
		return parent::actionUpdate($id, null, ['completedBy', 'closedBy']);
	}
	
    /**
     * Displays a single model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $modelClass=null, $with=[])
    {
		Response::$forceAjax = true;
		return parent::actionView($id);
	}
	
	/*
	 * Get the forms associated with this controller
	 * @param string $type What are we getting this form for?
	 * @param int $unique The id to load data for
	 * @return string | json
	 */
	public function actionForm($type=null, $id=null)
	{
		$options = [
			'modelOptions' => [
				'withThese' => ['type', 'requestFor']
			]
		];
		return parent::actionForm($type, $id, $options);
	}
	
	/**
	 * Get the query that orders items by their activity
	 */
	protected function getHasNewQuery()
	{
		return "(SELECT SUM(hasNew) FROM 
			(
				SELECT SUM(
					IF(
						parent_id=id AND 
						parent_type='".$this->model->isWhat()."' AND
						(updated_at>=updated_at OR created_at>=updated_at) AND
						(".static::$currentUser->lastActive()."<=UNIX_TIMESTAMP(updated_at) OR ".static::$currentUser->lastActive()."<=UNIX_TIMESTAMP(updated_at)),
						1, 0
					)
				) AS hasNew FROM `".\nitm\models\Issues::tableName()."`
				UNION ALL 
				SELECT SUM(
					IF(
						parent_id=id AND 
						parent_type='".$this->model->isWhat()."' AND
						(updated_at>=updated_at OR created_at>=updated_at) AND 
						(".static::$currentUser->lastActive()."<=UNIX_TIMESTAMP(updated_at) OR ".static::$currentUser->lastActive()."<=UNIX_TIMESTAMP(updated_at)),
						1, 0
					)
				) AS hasNew FROM `".\nitm\models\Replies::tableName()."`
			) newActivity) as hasNewActivity
		";
	}
	
	/**
	 * Get the query that orders items by their activity
	 */
	protected function getOrderByQuery()
	{
		return [
			"(".new Expression("SELECT COUNT(*) FROM ".\nitm\models\Issues::tableName()." WHERE 
				parent_id=id AND 
				parent_type='".$this->model->isWhat())."' AND 
				(updated_at>=updated_at OR created_at>=updated_at)
			)" => SORT_DESC,
			"(".new Expression("SELECT COUNT(*) FROM ".\nitm\models\Replies::tableName()." WHERE 
				parent_id=id AND 
				parent_type='".$this->model->isWhat())."' AND 
				(updated_at>=updated_at OR created_at>=updated_at)
			)" => SORT_DESC,
			"(".new Expression("SELECT COUNT(*) FROM ".\nitm\models\Vote::tableName()." WHERE 
				parent_id=id AND 
				parent_type='".$this->model->isWhat())."'
			)" => SORT_DESC,
			"(CASE 
				WHEN updated_at > created_at THEN updated_at
				ELSE created_at
			END)" => SORT_DESC,
			"(CASE status 
				WHEN 'normal' THEN 0
				WHEN 'important' THEN 1 
				WHEN 'critical' THEN 2
			END)" => SORT_DESC,
		];
	}
}
