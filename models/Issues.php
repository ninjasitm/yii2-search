<?php

namespace nitm\models;

use Yii;

/**
 * This is the model class for table "issues".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $parent_type
 * @property string $notes
 * @property integer $resolved
 * @property string $created_at
 * @property integer $author
 * @property integer $closed_by
 * @property integer $resolved_by
 * @property string $resolved_on
 * @property integer $closed
 * @property string $closed_on
 * @property integer $duplicate
 * @property integer $duplicate_id
 */
class Issues extends BaseWidget
{
	
	public static $statuses = [
		'normal' => 'default',
		'important' => 'info',
		'critical' => 'error'
	];
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'issues';
    }
	
	public static function has()
	{
		$has = [
			'created_at' => null, 
			'updated_at' => null,
			'updates' => null
		];
		return array_merge(parent::has(), $has);
	}
	
	public function scenarios()
	{
		$scenarios = [
			'create' => ['title', 'created_at', 'parent_id', 'parent_type', 'notes', 'status', 'duplicate', 'duplicate_id'],
			'update' => ['title', 'notes', 'status', 'closed', 'closed_by', 'resolved', 'resolved_by'],
			'close' => ['closed'],
			'resolve' => ['resolved'],
			'duplicate' => ['duplicate_id', 'duplicate']
		];
		return array_merge(parent::scenarios(), $scenarios);
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'parent_type', 'notes', 'title'], 'required', 'on' => ['create']],
            [['parent_id', 'resolved', 'author', 'closed_by', 'resolved_by', 'closed', 'duplicate', 'duplicate_id'], 'integer'],
            [['notes'], 'string'],
            [['created_at', 'resolved_on', 'closed_on'], 'safe'],
            [['parent_type'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'parent_type' => Yii::t('app', 'Parent Type'),
            'notes' => Yii::t('app', 'Notes'),
            'resolved' => Yii::t('app', 'Resolved'),
            'created_at' => Yii::t('app', 'Created At'),
            'author' => Yii::t('app', 'Author'),
            'closed_by' => Yii::t('app', 'Closed By'),
            'resolved_by' => Yii::t('app', 'Resolved By'),
            'resolved_on' => Yii::t('app', 'Resolved On'),
            'closed' => Yii::t('app', 'Closed'),
            'closed_on' => Yii::t('app', 'Closed On'),
            'duplicate' => Yii::t('app', 'Duplicate'),
            'duplicate_id' => Yii::t('app', 'Duplicate ID'),
        ];
    }
	
	public function getStatus()
	{
		switch(1)
		{
			case $this->duplicate:
			$ret_val = 'duplicate'; //need to add duplicate css class
			break;
			
			case $this->closed && $this->resolved:
			$ret_val = 'success';
			break;
			
			case $this->closed && !$this->resolved:
			$ret_val = 'warning';
			break;
			
			case !$this->closed && $this->resolved:
			$ret_val = 'info';
			break;
			
			default:
			$ret_val = isset(self::$statuses[$this->status]) ? self::$statuses[$this->status] : 'default';
			break;
		}
		return $ret_val;
	}
	
	public static function getStatusLabels()
	{
		$statuses = array_keys(self::$statuses);
		return array_combine($statuses, array_map('ucfirst', $statuses));
	}
}
