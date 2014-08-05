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
	public $_up;
	public $_down;
	protected static $is = 'vote';
	protected static $maxVotes;
	
	public function init()
	{
		parent::init();
		$this->initConfig(static::isWhat());
		static::$allowMultiple = isset(static::$allowMultiple) ? static::$allowMultiple : \Yii::$app->getModule('nitm')->voteOptions['allowMultiple'];
		static::$usePercentages = isset(static::$usePercentages) ? static::$usePercentages : \Yii::$app->getModule('nitm')->voteOptions['usePercentages'];
		static::$individualCounts = isset(static::$individualCounts) ? static::$individualCounts : \Yii::$app->getModule('nitm')->voteOptions['individualCounts'];
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
	
	public function rating()
	{
		$ret_val = ['positive' => 0, 'negative' => 0, 'ratio' => 0];
		switch(1)
		{
			case is_object($this->fetchedValue):
			switch(static::$allowMultiple)
			{
				case true:
				$ret_val = [
					'positive' => (int)$this->fetchedValue->_up, 
					'negative' => (int)$this->fetchedValue->_down
				];
				break;
				
				default:
				$ret_val = [
					'positive' => ((int)$this->fetchedValue->_up/static::getMax()) * 100, 
					'negative' => ((int)$this->fetchedValue->_down/static::getMax()) * 100
				];
				break;
			}
			$ret_val['ratio'] = (int)$this->fetchedValue->_up/static::getMax();
			break;
		}
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
			switch(static::$allowMultiple)
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
	
	public function getCurrentUserVoted()
	{
		$primaryKey = $this->primaryKey()[0];
		return $this->hasOne(static::className(), [
			'parent_type' => 'parent_type',
			'parent_id' => 'parent_id'
		])->andWhere(['author_id' => static::$currentUser->getId()]);
	}
	
	/**
	 *
	 */
	public function currentUserVoted($direction)
	{
		$ret_val = false;
		//If we don't multiple votes then we will check, otherwise let the user vote!
		switch(static::$allowMultiple)
		{
			case false:
			switch($this->currentUserVoted instanceof Vote)
			{
				case true:
				switch(1)
				{
					case ($this->currentUserVoted->value == -1) && $direction == 'down':
					case ($this->currentUserVoted->value == 1) && $direction == 'up':
					$ret_val = true;
					break;
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
}
