<?php

namespace nitm\models\configer;

use Yii;

/**
 * This is the model class for table "config_containers".
 *
 * @property integer $id
 * @property string $name
 * @property integer $author_id
 * @property integer $editor_id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $deleted
 *
 * @property Section[] $sections
 * @property Value[] $values
 */
class Container extends BaseConfiger
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'config_containers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['author_id', 'editor_id', 'deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
			[['name', 'value'], 'filter', 'filter' => 'trim'],
            [['name'], 'unique', 'targetAttribute' => ['name'], 'message' => 'This container already exists', 'on' => ['create']],
        ];
    }
	
	public function scenarios()
	{
		return [
			'create' => ['name'],
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
    public function getSections()
    {
        return $this->hasMany(Section::className(), ['containerid' => 'id'])->orderBy(['name' => SORT_ASC])
		->indexBy('name');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValues()
    {
        return $this->hasMany(Value::className(), ['containerid' => 'id'])
		->select([
			'*',
			"CONCAT((SELECT `name` FROM `".Section::tableName()."` WHERE id=sectionid), '.', name) AS unique_id", 
			"name AS unique_name", 
			"(SELECT `name` FROM `".Section::tableName()."` WHERE id=sectionid) AS 'section_name'", 
			"(SELECT `name` FROM `".Container::tableName()."` WHERE id=containerid) AS 'container_name'"
		])
		->orderBy(['name' => SORT_ASC])
		->indexBy('unique_id');
	}
}
