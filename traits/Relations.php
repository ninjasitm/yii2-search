<?php
namespace nitm\traits;

use nitm\helpers\Cache;

/**
 * Traits defined for expanding active relation scopes until yii2 resolves traits issue
 */

trait Relations {
	/**
	 * User based relations
	 */
	
	protected function getUserRelationQuery($link, $options=[], $className=null)
	{
		if(is_null($className))
		{
			if(\Yii::$app->hasProperty('user'))
				$className = \Yii::$app->user->identityClass;
			else
				$className = \nitm\models\User::className();
		}
		$options['select'] = isset($options['select']) ? $options['select'] : ['id', 'username', 'disabled'];
		$options['with'] = isset($options['with']) ? $options['select'] : ['profile'];
		$options['where'] = [];
		return $this->getRelationQuery($className, $link, $options);
	}
	
	protected function getCachedUserModel($idKey, $className=null)
	{
		$className = is_null($className) ? \Yii::$app->user->identityClass : \nitm\models\User::className();
		return $this->getCachedRelation('user-'.$this->$idKey, $className, [], false, \nitm\helpers\Helper::getCallerName());
	}
	 
    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getUser($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'user_id'], $options);
    }
	
	public function user()
	{
		return $this->getCachedUserModel('user_id');
	}

    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'author_id'], $options);
    }
	
	public function author()
	{
		return $this->getCachedUserModel('author_id');
	}

    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getEditor($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'editor_id'], $options);
    }
	
	public function editor()
	{
		return $this->getCachedUserModel('editor_id');
	}

    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getCompletedBy($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'completed_by'], $options);
    }
	
	public function completedBy()
	{
		return $this->getCachedUserModel('completed_by');
	}

    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getResolvedBy($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'resolved_by'], $options);
    }
	
	public function resolvedBy()
	{
		return $this->getCachedUserModel('resolved_by');
	}

    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getClosedBy($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'closed_by'], $options);
    }
	
	public function closedBy()
	{
		return $this->getCachedUserModel('closed_by');
	}

    /**
	 * Get user relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getDisabledBy($options=[])
    {
		return $this->getUserRelationQuery(['id' => 'disabled_by'], $options);
    }
	
	public function disabledBy()
	{
		return $this->getCachedUserModel('disabled_by');
	}
	
	/**
	 * Category based relations
	 */
	
	protected function getCategoryRelation($link, $options=[], $className=null)
	{
		$className = is_null($className) ? \nitm\models\Category::className() : $className;
		$options['select'] = isset($options['select']) ? $options['select'] : ['id', 'parent_ids', 'name', 'slug'];
		$options['with'] = isset($options['with']) ? $options['select'] : ['parent'];
		return $this->getRelationQuery($className, $link, $options);
	}	
	
	protected function getCachedCategoryModel($idKey, $className=null)
	{
		$className = is_null($className) ? \nitm\models\Category::className() : $className;
		return $this->getCachedRelation('category-'.$this->$idKey, $className, [], false, \nitm\helpers\Helper::getCallerName());
	}

    /**
	 * Get type relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getType($options=[])
    {
		return $this->getCategoryRelation(['id' => 'type_id'], $options);
    }
	/**
	 * Changed becuase of clash with \yii\elasticsearch\ActiveRecord::type()
	 */
	public function typeOf()
	{
		 return $this->getCachedCategoryModel('type_id');
	}

    /**
	 * Get category relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getCategory($options=[])
    {
		return $this->getCategoryRelation(['id' => 'category_id'], $options);
    }
	
	public function category()
	{
		return $this->getCachedCategoryModel('category_id');
	}
	
	/**
	 * Widget based relations
	 */
	protected function getWidgetRelationQuery($className, $link=null, $options=[], $many=false)
	{
		$link = !is_array($link) ? ['parent_id' => 'id'] : $link;
		$options['select'] = isset($options['select']) ? $options['select'] : ['id', 'parent_id', 'parent_type'];
		$options['with'] = isset($options['with']) ? $options['select'] : ['profile'];
		$options['andWhere'] = isset($options['andWhere']) ? $options['andWhere'] : ['parent_type' => $this->isWhat()];
		return $this->getRelationQuery($className, $link, $options, $many);
	}
	
	/**
	 * Widget based relations
	 */
	protected function getWidgetRelationModelQuery($className, $link=null, $options=[])
	{
		$link = !is_array($link) ? ['parent_id' => 'id'] : $link;
		$options['select'] = isset($options['select']) ? $options['select'] : ['id', 'parent_id', 'parent_type'];
		$options['with'] = isset($options['with']) ? $options['with'] : [];
		return $this->getRelationQuery($className, $link, $options);
	}
	
	protected function getCachedWidgetModel($className, $idKey=null, $many=false, $options=[])
	{
		$relation = \nitm\helpers\Helper::getCallerName();
		$options['construct'] = isset($options['construct']) ? $options['construct'] : [
			'parent_id' => $this->getId(), 
			'parent_type' => $this->isWhat()
		];
		$idKey = is_null($idKey) ? ['getId', 'isWhat'] : $idKey;
		return $this->getCachedRelation($relation.'-'.$this->concatAttributes($idKey), $className, $options, $many, $relation);
	}
	
	protected function concatAttributes($attributes, $glue='-')
	{
		$self = $this;
		$ret_val = array_map(function ($attribute) use($self) {
			try {
				return call_user_func([$self, $attribute]);
			} catch(\Exception $e) {
				return $self->$attribute;
			}
		}, (array)$attributes);
		return implode($glue, array_filter($ret_val));
	}
	
	public function replyModel()
	{
		return $this->getCachedWidgetModel(\nitm\models\Replies::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplyModel()
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Replies::className());
    }

    /**
	 * Get replies relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getReplies($options=[])
    {
		$params = [
			"parent_type" => $this->isWhat()
		];
		switch(\Yii::$app->user->identity->isAdmin())
		{
			case false:
			$params['hidden'] = 0;
			break;
		}
		$options = array_merge([
			'orderBy' => ['id' => SORT_DESC],
			'with' => 'replyTo',
			'andWhere' => $params
		], $options);
        return $this->getWidgetRelationModelQuery(\nitm\models\Replies::className(), null, $options, true);
    }
	
	public function replies()
	{
		return $this->getCachedRelation('replies.'.$this->isWhat().'.'.$this->getId(), 'replies', null, true);
	}
	
	public function issueModel()
	{
		return $this->getCachedWidgetModel(\nitm\models\Issues::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssueModel()
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Issues::className());
    }

    /**
	 * Get issues relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getIssues($options=[])
    {
		$options = array_merge([
			'orderBy' => ['id' => SORT_DESC],
		], $options);
        return $this->getWidgetRelationModelQuery(\nitm\models\Issues::className(), null, $options, true);
    }
	
	public function issues()
	{
		return $this->getCachedRelation('issues.'.$this->isWhat().'.'.$this->getId(), 'issues', null, true);
	}

    /**
	 * Get revisions relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getRevisions($options=[])
    {
		$options = array_merge([
			'orderBy' => ['id' => SORT_DESC],
		], $options);
        return $this->getWidgetRelationModelQuery(\nitm\models\Revisions::className(), null, $options, true);
    }
	
	public function revisions()
	{
		return $this->getCachedRelation('revisions.'.$this->isWhat().'.'.$this->getId(), 'revisions', null, true);
	}
	
	public function revisionModel()
	{
		return $this->getCachedWidgetModel(\nitm\models\Revisions::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRevisionModel()
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Revisions::className());
    }

    /**
	 * Get votes relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getVotes($options=[])
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Issues::className(), null, $options, true);
    }
	
	public function votes()
	{
		return $this->getCachedRelation('votes.'.$this->isWhat().'.'.$this->getId(), 'votes', null, true);
	}
	
	public function voteModel()
	{
		return $this->getCachedWidgetModel(\nitm\models\Vote::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVoteModel()
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Vote::className());
    }

    /**
	 * Get rating relation
	 * @param array $options Options for the relation
     * @return \yii\db\ActiveQuery
     */
    public function getRating($options=[])
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Rating::className(), ['parent_id' => 'id'], [], true);
    }
	
	public function rating()
	{
		$options = array_merge([
			'orderBy' => ['id' => SORT_DESC],
		], $options);
        return $this->getWidgetRelationModelQuery(\nitm\models\Issues::className(), [
			'remote_id' => $this->getId(), 
			'remote_type' => $this->isWhat()
		], $options, true);
	}
	
	public function ratingModel()
	{
		return $this->getCachedWidgetModel(\nitm\models\Rating::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRatingModel()
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Rating::className());
    }
	
	public function followModel()
	{
		return $this->getCachedWidgetModel(\nitm\models\Alerts::className(), null, false, [
			'select' => ['id', 'remote_id', 'remote_type'],
			'construct' => [
				'remote_id' => $this->getId(), 
				'remote_type' => $this->isWhat()
			]
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFollowModel()
    {
        return $this->getWidgetRelationModelQuery(\nitm\models\Alerts::className(), ['remote_id' => 'id'], [
			//Disabled due to Yii framework inability to return statistical relations
			//'with' => ['currentUserVoted', 'fetchedValue']
			'select' => ['id', 'remote_id', 'remote_type'],
			'where' => [
				'remote_type' => $this->isWhat(),
				'user_id' => \Yii::$app->user->getId()
			]
		]);
    }
	
	public function getRelationClass($relationClass, $callingClass)
	{
		$parts = explode('\\', $relationClass);
		$baseName = array_pop($parts);
		if(\nitm\traits\Search::useSearchClass($callingClass) !== false)
			$parts[] = 'search';
		$parts[] = $baseName;
		return implode('\\', $parts);
	}
	
	protected function getRelationQuery($className, $link, $options=[], $many=false)
	{
		$className = $this->getRelationClass($className, get_called_class());
		$callers = debug_backtrace(null, 3);
		$relation = $callers[2]['function'];
		$options['where'] = isset($options['where']) ? $options['where'] : ["parent_type" => $this->isWhat()]; 
		$options['select'] = isset($options['select']) ? $options['select'] : '*';
		//Disabled due to Yii framework inability to return statistical relations
		//if(static::className() != $className)
			//$ret_val->with(['count', 'newCount']);
		$relationFunction = ($many === true) ? 'hasMany' : 'hasOne';
		$ret_val = $this->$relationFunction($className, $link);
		if(is_array($options) && !empty($options))
		{
			foreach($options as $option=>$params)
			{
				$ret_val->$option($params);
			}
		}
		return $ret_val;
	}
	
	public function getCachedRelation($key, $modelClass, $options=[], $many=false, $relation=null)
	{
		$relation = is_null($relation) ? \nitm\helpers\Helper::getCallerName() : $relation;
		//Disabled due to Yii framework inability to return statistical relations
		//if(static::className() != $className)
			//$ret_val->with(['count', 'newCount']);
		$cacheFunction = $many === true ? 'getCachedModelArray' : 'getCachedModel';
		return $this->$cacheFunction($key, $modelClass, $relation, $options);
	}
 }
?>
