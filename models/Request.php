<?php

namespace nitm\models;

/**
 * This is the model class for table "Request".
 *
 * @property integer $id
 * @property string $added
 * @property string $completed_by
 * @property string $closed_by
 * @property string $title
 * @property string $author
 * @property string $edited
 * @property string $editor
 * @property integer $edits
 * @property string $request
 * @property string $type_id
 * @property string $request_for_id
 * @property string $status
 * @property integer $completed
 * @property string $completed_on
 * @property integer $closed
 * @property string $closed_on
 * @property integer $rating
 * @property string $rated_on
 */
class Request extends \nitm\models\Data
{
	use \nitm\traits\Nitm;
	
	public $requestModel;
	
	protected static $is = 'requests';
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'requests';
    }
	
	/*
	 * Initialize with 
	 */
	public function init()
	{
		parent::init();
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['added', 'completed_by', 'closed_by', 'title', 'author', 'editor', 'request', 'type_id', 'request_for_id', 'status', 'completed', 'closed'], 'required'],
            [['added', 'edited', 'completed_on', 'closed_on', 'rated_on'], 'safe'],
            [['title', 'request', 'type_id', 'request_for_id'], 'string'],
            [['edits', 'completed', 'closed', 'rating'], 'integer'],
            [['completed_by', 'closed_by', 'author', 'editor'], 'string', 'max' => 64],
            [['status'], 'string', 'max' => 16]
        ];
    }
	
	public function scenarios()
	{
		$scenarios = [
			'create' => ['author', 'title', 'request', 'type_id', 'request_for_id', 'status'],
			'update' => ['author', 'title', 'request', 'type_id', 'request_for_id', 'status'],
		];
		return array_merge(parent::scenarios(), array_merge($this->nitmScenarios(), $scenarios));
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'added' => 'Added',
            'completed_by' => 'Completed By',
            'closed_by' => 'Closed By',
            'title' => 'Title',
            'author' => 'Author',
            'edited' => 'Edited',
            'editor' => 'Editor',
            'edits' => 'Edits',
            'request' => 'Request',
            'type_id' => 'Type',
            'request_for_id' => 'Request For',
            'status' => 'Status',
            'completed' => 'Completed',
            'completed_on' => 'Completed On',
            'closed' => 'Closed',
            'closed_on' => 'Closed On',
            'rating' => 'Rating',
            'rated_on' => 'Rated On',
        ];
    }
	
	public static function filters()
	{
		return array_merge(
			parent::filters(),
			[
				'type_id' => null,
				'request_for_id' => null,
				'completed' => null,
				'closed' => null,
				'order_by' => null,
				'show' => null,
				'status' => null,
			]
		);
	}
	
	public function getUrgency()
	{
		$ret_val = "normal";
		switch($this->status)
		{
			case 'important':
			case 'critical':
			$ret_val = $this->status;
			break;
		}
		return ucfirst($ret_val);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestFor()
    {
        return $this->hasOne(Category::className(), ['id' => 'request_for_id_id']);
    }
	
	public function requestFor()
	{
		return $this->requestFor instanceof Category ? $this->requestFor : new Category();
	}
}
