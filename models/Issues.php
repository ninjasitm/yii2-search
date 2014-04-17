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
		];
		return array_merge(parent::has(), $has);
	}
	
	public function scenarios()
	{
		$scenarios = [
			'create' => ['created_at', 'parent_id', 'parent_type', 'notes', 'status', 'duplicate', 'duplicate_id'],
			'update' => ['notes', 'status', 'closed', 'closed_by', 'resolved', 'resolved_by'],
		];
		return array_merge(parent::scenarios(), $scenarios);
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'parent_type', 'notes'], 'required', 'on' => ['create']],
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
		return self::$statuses[$this->status];
	}
	
	public static function getStatusLabels()
	{
		$statuses = array_keys(self::$statuses);
		return array_combine($statuses, array_map('ucfirst', $statuses));
	}
}
