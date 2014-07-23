<?php
namespace nitm\traits;

use nitm\models\Data;

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
		return static::getCachedRelation('user.'.$this->user_id, 'user', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'author_id'])->with('profile');
    }
	
	public function author()
	{
		return static::getCachedRelation('user.'.$this->author_id, 'author', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEditor()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'editor_id']);
    }
	
	public function editor()
	{
		return static::getCachedRelation('user.'.$this->editor_id, 'editor', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompletedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'completed_by']);
    }
	
	public function completedBy()
	{
		return static::getCachedRelation('user.'.$this->completed_by, 'completedBy', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResolvedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'resolved_by']);
    }
	
	public function resolvedBy()
	{
		return static::getCachedRelation('user.'.$this->resolved_by, 'resolvedBy', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClosedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'closed_by']);
    }
	
	public function closedBy()
	{
		return static::getCachedRelation('user.'.$this->clsoed_by, 'closedBy', \nitm\models\User::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDisabledBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'disabled_by']);
    }
	
	public function disabledBy()
	{
		return static::getCachedRelation('user.'.$this->disabled_by, 'disabledBy', \nitm\models\User::className());
	}	

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(\nitm\models\Category::className(), ['id' => 'type_id']);
    }
	
	public function type()
	{
		return static::getCachedRelation('category.'.$this->type_id, 'type', \nitm\models\Category::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(\nitm\models\Category::className(), ['id' => 'category_id']);
    }
	
	public function category()
	{
		return static::getCachedRelation('user.'.$this->category_id, 'category', \nitm\models\Category::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplyModel()
    {
        return $this->hasOne(\nitm\models\Replies::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()]);
    }
	
	public function replyModel()
	{
		return static::getCachedRelation('reply-model.'.$this->isWhat().'.'.$this->getId(), 'replyModel', \nitm\models\Replies::className());
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(\nitm\models\Replies::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()])->orderBy(['id' => SORT_DESC]);
    }
	
	public function replies()
	{
		return static::getCachedRelation('replies.'.$this->isWhat().'.'.$this->getId(), 'replies', null, true);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssueModel()
    {
        return $this->hasOne(\nitm\models\Issues::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()]);
    }
	
	public function issueModel()
	{
		return static::getCachedRelation('issue-model.'.$this->isWhat().'.'.$this->getId(), 'issueModel', \nitm\models\Issues::className());
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
		return static::getCachedRelation('issues.'.$this->isWhat().'.'.$this->getId(), 'issues', null, true);
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
		return static::getCachedRelation('revisions.'.$this->isWhat().'.'.$this->getId(), 'revisions', null, true);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRevisionModel()
    {
        return $this->hasOne(\nitm\models\Revisions::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()]);
    }
	
	public function revisionModel()
	{
		return static::getCachedRelation('revision-model.'.$this->isWhat().'.'.$this->getId(), 'revisionModel', \nitm\models\Revisions::className());
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
		return static::getCachedRelation('votes.'.$this->isWhat().'.'.$this->getId(), 'votes', null, true);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVoteModel()
    {
        return $this->hasOne(\nitm\models\Vote::className(), ['parent_id' => 'id'])->andWhere(["parent_type" => $this->isWhat()]);
    }
	
	public function voteModel()
	{
		return static::getCachedRelation('vote-model.'.$this->isWhat().'.'.$this->getId(), 'voteModel', \nitm\models\Vote::className());
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
		return static::getCachedRelation('rating.'.$this->isWhat().'.'.$this->getId(), 'rating', null, true);
	}
	
	public function ratingModel()
	{
		return static::getCachedRelation('rating-model.'.$this->isWhat().'.'.$this->getId(), 'ratingModel', \nitm\models\Rating::className());
	}
	
	public function getCachedRelation($key, $property, $modelClass, $asArray=false)
	{
		switch(static::$cache->exists($key))
		{
			case true:
			$ret_val = static::$cache->get($key);
			break;
			
			default:
			switch($asArray)
			{
				case false:
				$ret_val = $this->getCachedModel($key, $property, $modelClass);
				break;
				
				default:
				$ret_val = $this->getCachedArray($key, $property);
				break;
			}
			break;
		}
		return $ret_val;
	}
 }
?>
