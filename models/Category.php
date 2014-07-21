<?php

namespace nitm\models;

use Yii;

/**
 * This is the model class for table "categories".
 *
 * @property integer $id
 * @property integer $parent_ids
 * @property string $name
 * @property string $slug
 * @property string $html_icon
 * @property string $created
 * @property string $updated
 */
class Category extends Data
{
	use \nitm\traits\Nitm;
	protected static $is = 'category';
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'slug'], 'required'],
            [['created', 'updated', 'html_icon'], 'safe'],
            [['name', 'slug'], 'string', 'max' => 255],
            [['type_id', 'name', 'slug'], 'unique', 'targetAttribute' => ['type_id', 'name', 'slug'], 'message' => 'This category already exists for the given Type'],
			[['type_id'], 'filter', 'filter' => [$this, 'setType']],
			[['parent_ids'], 'filter', 'filter' => [$this, 'setParentIds']]
		];
    }
	
	public function scenarios()
	{
		return [
			'create' => ['type_id', 'parent_ids', 'name', 'slug', 'html_icon'],
			'update' => ['type_id', 'parent_ids', 'name', 'slug', 'html_icon'],
		];
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'parent_ids' => Yii::t('app', 'Parents'),
            'name' => Yii::t('app', 'Name'),
            'slug' => Yii::t('app', 'Slug'),
            'created' => Yii::t('app', 'Created'),
            'updated' => Yii::t('app', 'Updated'),
        ];
    }
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	/**
	 * @param string action
	 * @param mixed $constrain
	 * @return array
	 */
	public static function getCategories($constrain=null)
	{
		$ret_val = [];
		$where = is_array($constrain) ? $constrain : ['type_id' => 1];
		return Category::find()->where($where)->orderBy('slug');
	}
	
	/**
	 * @param string action
	 * @param mixed $constrain
	 * @return array
	 */
	public static function getNav($action=null, $constrain=null)
	{
		$categories = static::getCategories($action, $constrain)->all();
		switch(sizeof($categories) >= 1)
		{
			case true:
			foreach($categories as $category)
			{
				switch($category['id'])
				{
					case 1:
					$uncategorized = [
						'url' => \Yii::$app->controller->id.(is_null($action) ? '/' : "/$action/").$category['slug'],
						'label' => $category['name'],
						'icon' => 'plus',
						'id' => $category['id']
					];
					break;
					
					default:
					$ret_val[$category['slug']] = [
						'url' => \Yii::$app->controller->id.(is_null($action) ? '/' : "/$action/").$category['slug'],
						'label' => $category['name'],
						'icon' => 'plus',
						'id' => $category['id']
					];
					break;
				}
			}
			if(isset($uncategorized) && is_array($uncategorized)) {
				array_unshift($ret_val, $uncategorized);
			}
			break;
			
			default:
			$ret_val = [
				[
					'url' => \Yii::$app->controller->id.(is_null($action) ? '/' : "/$action/")."category",
					'label' => "Category",
					'icon' => 'plus'
				]
			];
			break;
		}
		unset($ret_val[0]);
		array_unshift($ret_val, [
			'url' => \Yii::$app->controller->id.(is_null($action) ? '/' : "/$action/")."category",
			'label' => "Category",
			'icon' => 'plus'
		]);
		return $ret_val;
	}
	
	public function setType()
	{
		switch($this->isNewRecord)
		{
			case true:
			$type = static::find()->select('id')->where(['slug' => static::isWhat()])->one();
			return $type->id;
			break;
			
			default:
			return $this->type_id;
			break;
		}
	}
	
	public function setParentIds($ids) {
		$ids = is_array($ids) ? $ids : [$ids];
		return is_array(array_filter($ids)) ? implode(',', $ids) : null;
	}
}
