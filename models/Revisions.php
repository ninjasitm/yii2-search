<?php

namespace nitm\models;

/**
 * This is the model class for table "revisions".
 *
 * @property integer $id
 * @property integer $author
 * @property string $created_at
 * @property string $data
 * @property string $parent_type
 * @property integer $parent_id
 */
class Revisions extends BaseWidget
{	
	public $interval = 300; //Time in seconds for updating/creating new revisions
	
	private $_lastActivity = '___lastActivity';
	private $_dateFormat = "D M d Y h:iA";
	
	public function init()
	{
		parent::init();
	}
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'revisions';
    }
	
	public function scenarios()
	{
		$scenarios = [
		];
		return array_merge(parent::scenarios(), $scenarios);
	}
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data', 'parent_type', 'parent_id'], 'required'],
            [['id', 'author', 'parent_id'], 'integer'],
            [['created_at'], 'safe'],
            [['data'], 'string'],
            [['parent_type'], 'string', 'max' => 64],
            [['author', 'parent_type', 'parent_id'], 'unique', 'targetAttribute' => ['author', 'parent_type', 'parent_id'], 'message' => 'The combination of User ID, Parent Type and Parent ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author' => 'User ID',
            'created_at' => 'Created At',
            'data' => 'Data',
            'parent_type' => 'Remote Type',
            'parent_id' => 'Remote ID',
        ];
    }
}
