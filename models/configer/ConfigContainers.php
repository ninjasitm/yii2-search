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
 * @property ConfigSections[] $configSections
 * @property ConfigValues[] $configValues
 */
class ConfigContainers extends \nitm\models\Data
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
            [['name', 'author_id', 'editor_id'], 'required'],
            [['author_id', 'editor_id', 'deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique']
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
    public function getConfigSections()
    {
        return $this->hasMany(ConfigSections::className(), ['containerid' => 'id'])->orderBy(['name', SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigValues()
    {
        return $this->hasMany(ConfigValues::className(), ['containerid' => 'id'])->select([
			'*',
			"CONCAT((SELECT `name` FROM `".ConfigSections::tableName()."` WHERE id=sectionid), '.', name) AS unique_id", 
			"name AS unique_name", 
			"(SELECT `name` FROM `".ConfigSections::tableName()."` WHERE id=sectionid) AS 'section_name'", 
			"(SELECT `name` FROM `".ConfigContainers::tableName()."` WHERE id=containerid) AS 'container_name'"
		])->orderBy(['name', SORT_ASC]);
	}
}
