<?php

namespace nitm\models\configer;

use Yii;

/**
 * This is the model class for table "config_values".
 *
 * @property integer $id
 * @property integer $containerid
 * @property integer $sectionid
 * @property string $name
 * @property string $value
 * @property string $comment
 * @property integer $author_id
 * @property integer $editor_id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $deleted
 *
 * @property Section $section
 * @property Container $container
 */
class Value extends BaseConfiger
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'config_values';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['containerid', 'sectionid', 'name', 'value', 'author_id', 'editor_id'], 'required'],
            [['containerid', 'sectionid', 'author_id', 'editor_id', 'deleted'], 'integer'],
            [['value', 'comment'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 128],
            [['name', 'containerid', 'sectionid'], 'unique', 'targetAttribute' => ['name'], 'message' => 'This value already exists'],
        ];
    }
	
	public function scenarios()
	{
		return [
			'create' => ['containerid', 'sectionid', 'name', 'value', 'comment'],
			'update' => ['value', 'comment'],
			'delete' => ['deleted']
		];
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'containerid' => Yii::t('app', 'Containerid'),
            'sectionid' => Yii::t('app', 'Sectionid'),
            'name' => Yii::t('app', 'Name'),
            'value' => Yii::t('app', 'Value'),
            'comment' => Yii::t('app', 'Comment'),
            'author_id' => Yii::t('app', 'Author ID'),
            'editor_id' => Yii::t('app', 'Editor ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'deleted' => Yii::t('app', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(Section::className(), ['id' => 'sectionid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContainer()
    {
        return $this->hasOne(Container::className(), ['id' => 'containerid']);
    }
}
