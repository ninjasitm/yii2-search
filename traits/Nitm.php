<?php

namespace nitm\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;
use nitm\models\User;
use nitm\models\Category;
use nitm\helpers\Cache as CacheHelper;

/**
 * Class Replies
 * @package nitm\module\models
 */

trait Nitm
{
	
	public function url($attribute='id', $text=null, $url=null) 
	{
		$property = is_array($text) ? $text[1] : $text;
		$text = is_array($text) ? $text[0]->$property : $text;
		$url = is_null($url) ? \Yii::$app->request->url : $url;
		$urlOptions = array_merge([$url], [$this->formName()."[".$attribute."]" => $this->$attribute]);
		$htmlOptions = [
			'href' => \Yii::$app->urlManager->createUrl($urlOptions), 
			'role' => $this->formName().'Link', 
			'id' => $this->isWhat().'-link-'.uniqid(),
			'data-pjax' => 1
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
			'Normal',
			'Important',
			'Critical'
		];
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
		switch(CacheHelper::cache()->exists('category-list-'.$type))
		{
			case false:
			$model = new Category([
				'queryFilters' => [
					'where' => [
						'parent_ids' => new \yii\db\Expression("(SELECT id FROM ".Category::tableName()." WHERE slug='".$type."' LIMIT 1)")
					],
					'orderBy' => ['name' => SORT_ASC]
				]
			]);
			$ret_val = $model->getList('name');
			CacheHelper::cache()->set('category-list-'.$type, $ret_val);
			break;
			
			default:
			$ret_val = CacheHelper::cache()->get('category-list-'.$type);
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