<?php

namespace nitm\helpers\alerts;

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
class Notification extends \nitm\models\Data
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notifications';
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
}
