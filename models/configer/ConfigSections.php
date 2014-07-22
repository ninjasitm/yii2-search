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
 * @property ConfigContainers $container
 * @property ConfigValues[] $configValues
 */
class ConfigSections extends \nitm\models\Data
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
            [['containerid', 'name', 'author_id', 'editor_id'], 'required'],
            [['containerid', 'author_id', 'editor_id', 'deleted'], 'integer'],
            [['name'], 'string'],
            [['created_at', 'updated_at'], 'safe']
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
        return $this->hasOne(ConfigContainers::className(), ['id' => 'containerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigValues()
    {
        return $this->hasMany(ConfigValues::className(), ['sectionid' => 'id'])->select([
			'*',
			"CONCAT((SELECT `name` FROM `".ConfigSections::tableName()."` WHERE id=sectionid), '.', name) AS unique_id", 
			"name AS unique_name", 
			"(SELECT `name` FROM `".ConfigSections::tableName()."` WHERE id=sectionid) AS 'section_name'", 
			"(SELECT `name` FROM `".ConfigContainers::tableName()."` WHERE id=containerid) AS 'container_name'"
		])->orderBy(['name', SORT_ASC]);
    }
}
