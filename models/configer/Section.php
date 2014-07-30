<?php

namespace nitm\models\configer;

use Yii;

/**
 * This is the model class for table "config_sections".
 *
 * @property integer $id
 * @property integer $containerid
 * @property string $name
 * @property integer $author_id
 * @property integer $editor_id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $deleted
 *
 * @property Container $container
 * @property Value[] $values
 */
class Section extends BaseConfiger
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'config_sections';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['containerid', 'author_id', 'editor_id', 'deleted'], 'integer'],
            [['name'], 'string'],
			[['name'], 'filter', 'filter' => 'trim'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'containerid'], 'unique', 'targetAttribute' => ['name'], 'message' => 'This section already exists'],
        ];
    }
	
	public function scenarios()
	{
		return [
			'create' => ['containerid', 'name'],
			'update' => ['name'],
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
            'name' => Yii::t('app', 'Name'),
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
    public function getContainer()
    {
        return $this->hasOne(Container::className(), ['id' => 'containerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValues()
    {
        return $this->hasMany(Value::className(), ['sectionid' => 'id'])
		->select([
			'*',
			"`name` AS unique_id", 
			"name AS unique_name", 
			"(SELECT `name` AS 'section_name'", 
			"(SELECT `name` FROM `".Container::tableName()."` WHERE id=containerid) AS 'container_name'"
		])
		->orderBy(['name' => SORT_ASC]);
    }
}
