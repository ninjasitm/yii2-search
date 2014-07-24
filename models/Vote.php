<?php

namespace nitm\models;

/**
 * This is the model class for table "vote".
 *
 * @property integer $id
 * @property integer $author_id
 * @property string $created_at
 * @property string $parent_type
 * @property integer $parent_id
 */
class Vote extends BaseWidget
{
	protected static $is = 'replies';
	protected static $maxVotes;
	
	public function init()
	{
		parent::init();
		$this->initConfig(static::isWhat());
		$this->constraints['author_id'] = static::$currentUser->getId();
	}
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'vote';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['author_id', 'parent_type', 'parent_id'], 'required', 'on' => ['update', 'create']],
			[['value'], 'required', 'on' => ['update']],
            [['author_id', 'parent_id'], 'integer'],
            [['created_at'], 'safe'],
            [['parent_type'], 'string', 'max' => 64],
            [['author_id', 'parent_type', 'parent_id'], 'unique', 'targetAttribute' => ['author_id', 'parent_type', 'parent_id'], 'message' => 'The combination of User ID, parent Type and parent ID has already been taken.', 'on' =>['create']]
        ];
    }
	
	public function scenarios()
	{
		$scenarios = [
			'default' => ['author_id', 'parent_type', 'parent_id', 'value'],
			'update' => ['author_id', 'parent_type', 'parent_id', 'value'],
			'create' => ['author_id', 'parent_type', 'parent_id'],
		];
		
		return array_merge(parent::scenarios(), $scenarios);
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author_id' => 'User ID',
            'created_at' => 'Created At',
            'parent_type' => 'parent Type',
            'parent_id' => 'parent ID',
        ];
    }
	
	/**
	 * Get the rating, percentage out of 100%
	 * @return int
	 */
	public function getRating()
	{
		$ret_val = ['positive' => 0];
		//Only count votes with a 1 value
		$this->setConstraints([$this->parent_id, $this->parent_type]);
		switch(static::allowMultiple())
		{
			case true:
			$count = $this->getCount();
			switch(!$count)
			{
				case false:
				//Now look only for negative values
				$this->queryFilters['value'] = 'both';
				$votes = $this->getValue();
				break;
				
				default:
				$votes = ['down' => 0, 'up' => 0];
				break;
			}
			$ret_val = ['positive' => (int)$votes['down'], 'negative' => (int)$votes['up']];
			break;
			
			default:
			$this->queryFilters['value'] = 1;
			$ret_val['positive'] = ($this->getCount()/static::getMax()) * 100;
			//Now look only for negative values
			//$this->queryFilters['value'] = -1;
			//$ret_val['negative'] = ($this->getCount()/$userCount) * 100;
			break;
		}
		unset($this->queryFilters['value']);
		return $ret_val;
	}
	
	/**
	 * Get the rating, percentage out of 100%
	 * @return int
	 */
	public static function getMax()
	{
		switch(isset(static::$maxVotes))
		{
			case false:
			$ret_val = 100000000000000;
			switch(self::allowMultiple())
			{
				case false:
				$ret_val = User::find()->where(['disabled' => 0])->count();
				break;
			}
			static::$maxVotes = $ret_val;
			break;
			
			default:
			$ret_val = static::$maxVotes;
			break;
		}
		return $ret_val;
	}
	
	/**
	 *
	 */
	public function currentUserVoted($direction)
	{
		$ret_val = false;
		//If we don't multiple votes then we will check, otherwise let the user vote!
		switch(static::allowMultiple())
		{
			case false:
			$vote = static::find()
				->select('value')
				->where($this->getConstraints())
				->one();
			switch($vote instanceof Vote)
			{
				case true:
				switch(1)
				{
					case ($vote->value == -1) && $direction == 'down':
					case ($vote->value == 1) && $direction == 'up':
					$ret_val = true;
					break;
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Allow multiple voting?
	 * @return boolean
	 */
	public static function allowMultiple()
	{
		return static::setting(static::isWhat().'.globals.allowMultiple') == true;
	}
}
