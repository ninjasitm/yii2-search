<?php

namespace nitm\controllers;

use nitm\models\Vote;

/**
 * Upvoting based on democratic one vote per user system
 *
 */

class VoteController extends DefaultController
{
	
	public function behaviors()
	{
		$behaviors = [
			'access' => [
				//'class' => \yii\filters\AccessControl::className(),
				'only' => ['down', 'up', 'reset'],
				'rules' => [
					[
						'actions' => ['down', 'up', 'reset'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				//'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'down' => ['get'],
					'up' => ['get'],
					'reset' => ['get'],
				],
			],
		];
		
		return array_merge_recursive(parent::behaviors(), $behaviors);
	}
	
	/**
	 * Place a downvote
	 * @param string $type the type of object
	 * @param int $id the id
	 * @return boolean should we allow more downvoting?
	 */
    public function actionDown($type, $id)
    {
		$ret_val = ['success' => false, 'value' => null, 'id' => $id];
		$existing = new Vote();
		$existing->queryFilters['author_id'] = \Yii::$app->user->getId();
		$existing->queryFilters['parent_type'] = $type;
		$existing->queryFilters['parent_id'] = $id;
		$vote = $existing->find()->where($existing->queryFilters)->one();
		switch($vote instanceof Vote)
		{
			case false:
			$vote = new Vote();
			$vote->setScenario('create');
			$vote->load([
				'parent_type' => $type, 
				'parent_id' => $id, 
				'author_id' => \Yii::$app->user->getId()
			]);
			break;
			
			default:
			$vote->setScenario('update');
			break;
		}
		$vote->value = Vote::$allowMultiple ? $vote->value-1 : -1;
		$ret_val['success'] = $vote->save();
		unset($existing->queryFilters['author_id']);
		$ret_val['value'] = $vote->rating();
		$ret_val['atMax'] = Vote::$allowMultiple ? false : ($vote->value == 1);
		$ret_val['atMin'] = Vote::$allowMultiple ? false : ($vote->value == -1);
		$this->setResponseFormat('json');
		return $this->renderResponse($ret_val);
    }
	
	/**
	 * Place an upvote
	 * @param string $type the type of object
	 * @param int $id the id
	 * @return boolean should we allow more upvoting?
	 */
    public function actionUp($type, $id)
    {
		$ret_val = ['success' => false, 'value' => null, 'id' => $id];
		$existing = new Vote();
		$existing->queryFilters['author_id'] = \Yii::$app->user->getId();
		$existing->queryFilters['parent_type'] = $type;
		$existing->queryFilters['parent_id'] = $id;
		$vote = $existing->find()->where($existing->queryFilters)->one();
		switch($vote instanceof Vote)
		{
			case false:
			$vote = new Vote();
			$vote->setScenario('create');
			$vote->load([
				'Vote' => [
					'parent_type' => $type, 
					'parent_id' => $id, 
					'author_id' => \Yii::$app->user->getId()
				]
			]);
			break;
			
			default:
			$vote->setScenario('update');
			$vote->value = Vote::$allowMultiple ? $vote->value+1 : 1;
			break;
		}
		$ret_val['success'] = $vote->save();
		unset($existing->queryFilters['author_id']);
		$ret_val['value'] = $vote->rating();
		$ret_val['atMax'] = Vote::$allowMultiple ? false : ($vote->value == 1);
		$ret_val['atMin'] = Vote::$allowMultiple ? false : ($vote->value == -1);
		$this->setResponseFormat('json');
		return $this->renderResponse($ret_val);
    }
	
	/**
	 * Place an upvote
	 * @param string $type the type of object
	 * @param int $id the id
	 * @return boolean should we allow more upvoting?
	 */
    public function actionReset($type, $id)
    {
		$ret_val = ['success' => false, 'value' => null, 'id' => $id];
		switch(\Yii::$app->user->identity->isAdmin())
		{
			case true:
			$deleted = Vote::deleteAll([
				'parent_id' => $id,
				'parent_type' => $type
			]);
			$ret_val['success'] = $deleted;
			break;
		}
		$this->setResponseFormat('json');
		return $this->renderResponse($ret_val);
    }
}
