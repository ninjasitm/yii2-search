<?php
namespace nitm\traits;

use nitm\models\Data;
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
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'author_id'])->with('profile');
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
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'editor_id']);
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
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'completed_by']);
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
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'resolved_by']);
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
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'closed_by']);
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
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'disabled_by']);
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
        return $this->hasOne(\nitm\models\Category::className(), ['id' => 'type_id']);
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
        return $this->hasOne(\nitm\models\Category::className(), ['id' => 'category_id']);
    }
	
	public function category()
	{
		return $this->getCachedRelation('user.'.$this->category_id, 'category', \nitm\models\Category::className());
	}
	
	public function replyModel()
	{
		return $this->getCachedRelation('reply-model.'.$this->isWhat().'.'.$this->getId(), 'replyModel', \nitm\models\Replies::className(), false, ['parent_id' => $this->getId(), 'parent_type' => $this->isWhat()]);
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
		return $this->getCachedRelation('replies.'.$this->isWhat().'.'.$this->getId(), 'replies', null, true);
	}
	
	public function issueModel()
	{
		return $this->getCachedRelation('issue-model.'.$this->isWhat().'.'.$this->getId(), 'issueModel', \nitm\models\Issues::className(), false, ['parent_id' => $this->getId(), 'parent_type' => $this->isWhat()]);
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
		return $this->getCachedRelation('revision-model.'.$this->isWhat().'.'.$this->getId(), 'revisionModel', \nitm\models\Revisions::className(), false, ['parent_id' => $this->getId(), 'parent_type' => $this->isWhat()]);
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
		return $this->getCachedRelation('vote-model.'.$this->isWhat().'.'.$this->getId(), 'voteModel', \nitm\models\Vote::className(), false, ['parent_id' => $this->getId(), 'parent_type' => $this->isWhat()]);
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
		return $this->getCachedRelation('rating-model.'.$this->isWhat().'.'.$this->getId(), 'ratingModel', \nitm\models\Rating::className(), false, ['parent_id' => $this->getId(), 'parent_type' => $this->isWhat()]);
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
