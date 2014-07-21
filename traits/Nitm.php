<?php

namespace nitm\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;
use nitm\models\User;
use nitm\models\Category;

/**
 * Class Replies
 * @package nitm\module\models
 */

trait Nitm
{	
	public $replies;
	public $issues;
	public $revisions;
	
	public function url($attribute='id', $text=null, $url=null) 
	{
		$property = is_array($text) ? $text[1] : $text;
		$text = is_array($text) ? $text[0]->$property : $text;
		$url = is_null($url) ? \Yii::$app->request->url : $url;
		$urlOptions = array_merge([$url], [$this->formName()."[".$attribute."]" => $this->$attribute]);
		$htmlOptions = [
			'href' => \Yii::$app->urlManager->createUrl($urlOptions), 
			'role' => $this->formName().'Link', 
			'id' => $this->isWhat().'-link-'.uniqid()
		];
		return \yii\helpers\Html::tag('a', $text, $htmlOptions);
	}
	
	public function nitmScenarios()
	{
		return [
			'disable' => ['disabled', 'disabled_at', 'disabled'],
			'complete' => ['completed', 'completed_at', 'closed'],
			'close' => ['closed', 'closed_at', 'completed', 'resolved'],
			'resolve' => ['resolved', 'resolved_at', 'closed']
		];
	}
	
	public function getStatuses()
	{
		return [
			'notice' => 'Normal',
			'info' => 'Important',
			'danger' => 'Urgent'
		];
	}
	
	public static function getAccountInfo($ccid)
	{
		return \nitm\models\Lab1::getAccountInfo($ccid);
	}
	
	public static function getTicketInfo($id)
	{
		return \nitm\models\Lab1::getTicketInfo($id);
	}
	
	public function getStatus()
	{
		$ret_val = 'default';
		switch(1)
		{
			case $this->hasAttribute('duplicate') && $this->duplicate:
			$ret_val = 'duplicate'; //need to add duplicate css class
			break;
			
			case $this->hasAttribute('closed') && $this->hasAttribute('resolved'):
			switch(1)
			{
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
				$ret_val = 'error';
				break;
			}
			break;
			
			case $this->hasAttribute('closed') && $this->hasAttribute('completed'):
			switch(1)
			{
				case $this->closed && $this->completed:
				$ret_val = 'success';
				break;
			
				case $this->closed && !$this->completed:
				$ret_val = 'warning';
				break;
				
				case !$this->closed && $this->completed:
				$ret_val = 'info';
				break;
				
				default:
				$ret_val = 'error';
				break;
			}
			break;
			
			case $this->hasAttribute('disabled'):
			switch(1)
			{
				case $this->disabled:
				$ret_val = 'disabled';
				break;
				
				default:
				$ret_val = 'success';
				break;
			}
			break;
			
			case isset(self::$statuses):
			$ret_val = isset(self::$statuses[$this->getAttribute('status')]) ? self::$statuses[$this->getAttribute('status')] : 'default';
			break;
			
			default:
			$ret_val = 'default';
			break;
		}
		return $ret_val;
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'author']);
    }
	
	public function author()
	{
		return $this->author instanceof User ? $this->author : new User();
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEditor()
    {
        return $this->hasOne(User::className(), ['id' => 'editor']);
    }
	
	public function editor()
	{
		return $this->editor instanceof User ? $this->editor : new User();
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompletedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'completed_by']);
    }
	
	public function completedBy()
	{
		return $this->completedBy instanceof User ? $this->completedBy : new User();
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResolvedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'resolved_by']);
    }
	
	public function resolvedBy()
	{
		return $this->resolvedBy instanceof User ? $this->resolvedBy : new User();
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClosedBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'closed_by']);
    }
	
	public function closedBy()
	{
		return $this->closedBy instanceof User ? $this->closedBy : new User();
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDisabledBy()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'disabled_by']);
    }
	
	public function disabledBy()
	{
		return $this->disabledBy instanceof User ? $this->disabledBy : new User();
	}	

    /**
	 * Get Categories
     * @return array
     */
    public static function getCategories($type)
    {
		return Category::find()->where([
			'parent_ids' => (new \yii\db\Query)->
				select('id')->
				from(Category::tableName())->
				where(['slug' => $type])
		])->orderBy(['name' => SORT_ASC])->all();
	}

    /**
	 * Get types for use in an HTML element
     * @return array
     */
    public static function getCategoryList($type)
    {
		return Category::getList('name', [
			'where' => [
				'parent_ids' => new \yii\db\Expression("(SELECT id FROM ".Category::tableName()." WHERE slug='".$type."' LIMIT 1)")
			],
			'orderBy' => ['name' => SORT_ASC]
		]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(Category::className(), ['id' => 'type_id']);
    }
	
	public function type()
	{
		return $this->type instanceof Category ? $this->type : new Category();
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }
	
	public function category()
	{
		return $this->category instanceof Category ? $this->category : new Category();
	}
	
	/**
	 * Get items for use in an HTML element
	 * @param string $label The attribute housin the label
	 * @param array $otptions Some Options for the query
     * @return array
     */
    public static function getList($label='name', $options=[], $print=false)
    {
		$label = empty($label) ? 'name' : $label;
		$ret_val = [];
		$query = static::find();
		foreach($options as $type=>$params)
		{
			switch(strtolower($type))
			{
				case 'where':
				case 'andwhere':
				case 'orwhere':
				case 'limit':
				case 'with':
				case 'orderby':
				case 'indexby':
				case 'groupby':
				case 'addgroupby':
				case 'join':
				case 'leftjoin':
				case 'rightjoin':
				case 'innerjoin':
				case 'having':
				case 'andhaving':
				case 'orhaving':
				case 'union':
				call_user_func_array([$query, $type], [$params]);
				break;
			}
		}
		$id = static::primaryKey();
		$optionKeys = array_map('strtolower', array_keys($options));
		switch(1)
		{
			case in_array('orderby', $optionKeys):
			$query->orderBy($id[0]);
			case in_array('indexby', $optionKeys):
			$query->indexby($id[0]);
			break;
		}
		$items = $query->all();
		switch(empty($items))
		{
			case false:
			foreach($items as $item)
			{
				$ret_val[$item->$id[0]] = $item->$label;
			}
			break;
			
			default:
			$ret_val[] = ["No ".static::isWhat()." found"];
			break;
		}
		return $ret_val;
    }
	
	/*
	 * Return a string imploded with ucfirst characters
	 * @param string $name
	 * @return string
	 */
	protected static function resolveModelClass($value)
	{
		$ret_val = empty($value) ?  [] : array_map('ucfirst', preg_split("/[_-]/", $value));
		return implode($ret_val);
	}
}
?>