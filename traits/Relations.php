<?php
namespace nitm\traits;

use nitm\helpers\Cache;

/**
 * Traits defined for expanding active relation scopes until yii2 resolves traits issue
 */
 trait Relations {
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'user_id']);
    }
	
	public function user()
	{
		return $this->getCachedRelation('user.'.$this->user_id, 'user', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'author_id'])
			->select(['id', 'username', 'disabled'])
			->with('profile');
    }
	
	public function author()
	{
		return $this->getCachedRelation('user.'.$this->author_id, 'author', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEditor()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'editor_id'])
			->select(['id', 'username', 'disabled'])
			->with('profile');
    }
	
	public function editor()
	{
		return $this->getCachedRelation('user.'.$this->editor_id, 'editor', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompletedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'completed_by'])
			->select(['id', 'username', 'disabled'])
			->with('profile');
    }
	
	public function completedBy()
	{
		return $this->getCachedRelation('user.'.$this->completed_by, 'completedBy', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResolvedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'resolved_by'])
			->select(['id', 'username', 'disabled'])
			->with('profile');
    }
	
	public function resolvedBy()
	{
		return $this->getCachedRelation('user.'.$this->resolved_by, 'resolvedBy', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClosedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'closed_by'])
			->select(['id', 'username', 'disabled'])
			->with('profile');
    }
	
	public function closedBy()
	{
		return $this->getCachedRelation('user.'.$this->closed_by, 'closedBy', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDisabledBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'disabled_by'])
			->select(['id', 'username', 'disabled'])
			->with('profile');
    }
	
	public function disabledBy()
	{
		return $this->getCachedRelation('user.'.$this->disabled_by, 'disabledBy', \nitm\models\User::className());
	}	

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(\nitm\models\Category::className(), ['id' => 'type_id'])
			->select(['id', 'parent_ids', 'parent_type', 'name', 'slug']);
    }
	
	public function type()
	{
		return $this->getCachedRelation('category.'.$this->type_id, 'type', \nitm\models\Category::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(\nitm\models\Category::className(), ['id' => 'category_id'])
			->select(['id', 'parent_ids', 'parent_type', 'name', 'slug']);
    }
	
	public function category()
	{
		return $this->getCachedRelation('user.'.$this->category_id, 'category', \nitm\models\Category::className());
	}
	
	public function replyModel()
	{
		return $this->replyModel instanceof \nitm\models\Replies ? $this->replyModel : new \nitm\models\Replies([
			'parent_id' => $this->getId(), 
			'parent_type' => $this->isWhat()
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplyModel()
    {
        return $this->getRelatedWidgetModel(\nitm\models\Replies::className());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
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
        return $this->hasMany(\nitm\models\Replies::className(), ['parent_id' => 'id'])->andWhere($params)->orderBy(['id' => SORT_DESC])->with('replyTo');
    }
	
	public function replies()
	{
		return $this->getCachedRelation('replies.'.$this->isWhat().'.'.$this->getId(), 'replies', null, true);
	}
	
	public function issueModel()
	{
		return $this->issueModel instanceof \nitm\models\Issues ? $this->issueModel : new \nitm\models\Issues([
			'parent_id' => $this->getId(), 
			'parent_type' => $this->isWhat()
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssueModel()
    {
        return $this->getRelatedWidgetModel(\nitm\models\Issues::className());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssues()
    {
        return $this->hasMany(\nitm\models\Issues::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()])->orderBy(['id' => SORT_DESC]);
    }
	
	public function issues()
	{
		return $this->getCachedRelation('issues.'.$this->isWhat().'.'.$this->getId(), 'issues', null, true);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRevisions()
    {
        return $this->hasMany(\nitm\models\Revisions::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()])->orderBy(['id' => SORT_DESC]);
    }
	
	public function revisions()
	{
		return $this->getCachedRelation('revisions.'.$this->isWhat().'.'.$this->getId(), 'revisions', null, true);
	}
	
	public function revisionModel()
	{
		return $this->revisionModel instanceof \nitm\models\Revisions ? $this->revisionModel : new \nitm\models\Revisions([
			'parent_id' => $this->getId(), 
			'parent_type' => $this->isWhat()
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRevisionModel()
    {
        return $this->getRelatedWidgetModel(\nitm\models\Revisions::className());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotes()
    {
        return $this->hasMany(\nitm\models\Vote::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()]);
    }
	
	public function votes()
	{
		return $this->getCachedRelation('votes.'.$this->isWhat().'.'.$this->getId(), 'votes', null, true);
	}
	
	public function voteModel()
	{
		return $this->voteModel instanceof \nitm\models\Vote ? $this->voteModel : new \nitm\models\Vote([
			'parent_id' => $this->getId(), 
			'parent_type' => $this->isWhat()
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVoteModel()
    {
        return $this->getRelatedWidgetModel(\nitm\models\Vote::className(), [
			//Disabled due to Yii framework inability to return statistical relations
			//'with' => ['currentUserVoted', 'fetchedValue']
		]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRating()
    {
        return $this->hasMany(\nitm\models\Rating::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()]);
    }
	
	public function rating()
	{
		return $this->getCachedRelation('rating.'.$this->isWhat().'.'.$this->getId(), 'rating', null, true);
	}
	
	public function ratingModel()
	{
		return $this->ratingModel instanceof \nitm\models\Rating ? $this->ratingModel : new \nitm\models\Rating([
			'parent_id' => $this->getId(), 
			'parent_type' => $this->isWhat()
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRatingModel()
    {
        return $this->getRelatedWidgetModel(\nitm\models\Rating::className(), [
			//Disabled due to Yii framework inability to return statistical relations
			//'with' => ['currentUserVoted', 'fetchedValue']
		]);
    }
	
	public function followModel()
	{
		return $this->followModel instanceof \nitm\models\Alerts ? $this->followModel : new \nitm\models\Alerts([
			'remote_id' => $this->getId(), 
			'remote_type' => $this->isWhat()
		]);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFollowModel()
    {
        return $this->getRelatedWidgetModel(\nitm\models\Alerts::className(), [
			//Disabled due to Yii framework inability to return statistical relations
			//'with' => ['currentUserVoted', 'fetchedValue']
			'andWhere' => [
				'remote_type' => $this->isWhat(),
				'user_id' => \Yii::$app->user->getId()
			]
		], [
			'remote_id' => 'id'
		]);
    }
	
	protected function getRelatedWidgetModel($className, $options=[], $link=[])
	{
		switch(empty($link))
		{
			case true:
			$ret_val = $this->hasOne($className, ['parent_id' => 'id'])
				->select(['id', 'parent_type', 'parent_id'])
				->andWhere([$className::tableName().".parent_type" => $this->isWhat()]);
			break;
			
			default:
			$ret_val = $this->hasOne($className, $link);
			break;
		}
		//Disabled due to Yii framework inability to return statistical relations
		//if(static::className() != $className)
			//$ret_val->with(['count', 'newCount']);
		if(is_array($options) && !empty($options))
		{
			foreach($options as $option=>$params)
			{
				$ret_val->$option($params);
			}
		}
		return $ret_val;
	}
	
	public function getCachedRelation($key, $property, $modelClass, $asArray=false, $options=[])
	{
		switch($this->inCache($key))
		{
			case true:
			$ret_val = $this->getCachedModel($key, $options);
			break;
			
			default:
			switch($asArray)
			{
				case false:
				$ret_val = $this->getCachedModel($key, $property, $modelClass, $options);
				break;
				
				default:
				$ret_val = $this->getCachedModelArray($key, $property, $options);
				break;
			}
			break;
		}
		return $ret_val;
	}
 }
?>
