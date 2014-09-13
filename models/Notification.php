<?php

namespace nitm\models;

use Yii;

/**
 * This is the model class for table "notifications".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $message
 * @property string $priority
 * @property string $created_at
 * @property integer $read
 */
class Notification extends BaseWidget
{
	protected $link = [
		'user_id' => 'user_id',
	];
	
	public function init()
	{
		$this->_supportedConstraints['read'] = ['read'];
		$this->_supportedConstraints['user_id'] = [2, 'user_id'];
		parent::init();
		$this->withThese = ['user'];
	}
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'alerts_notifications';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'message', 'priority'], 'required'],
            [['user_id', 'read'], 'integer'],
            [['message'], 'string'],
            [['created_at'], 'safe'],
            [['priority'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'message' => Yii::t('app', 'Message'),
            'priority' => Yii::t('app', 'Priority'),
            'created_at' => Yii::t('app', 'Created At'),
            'read' => Yii::t('app', 'Read'),
        ];
    }
	
	public function getPriority()
	{
		switch($this->priority)
		{
			case 'critical':
			$ret_val = 'error';
			break;
			
			case 'important':
			$ret_val = 'info';
			break;
			
			default:
			$ret_val = 'default';
			break;
		}
		return $ret_val;
	}
}
