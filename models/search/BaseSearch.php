<?php

namespace nitm\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Prefixes represents the model behind the search form about `lab1\models\Prefixes`.
 */
class BaseSearch extends Model
{
	public $id;
	public $queryOptions = [];
	public $withThese;
	
    public function search($modelClass, $params, $with=[])
    {
        $query = $modelClass::find();
		$has = $modelClass::has();
		switch(1)
		{
			case array_key_exists('author', $has):
			$with[] = 'authorUser';
			case array_key_exists('editor', $has):
			$with[] = 'editorUser';
			break;
		}
		$query->with($with);
		$this->setQueryOptions($query);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params, false) && $this->validate())) {
            return $dataProvider;
        }

		foreach($this->getConditions() as $condition)
		{
			$condition = is_array($condition) ? $condition : [$condition];
			array_unshift($condition, $query);
        	call_user_func_array([$this, 'addCondition'], $condition);
		}
        return $dataProvider;
    }

    protected function addCondition($query, $attribute, $partialMatch = false)
    {
        if (($pos = strrpos($attribute, '.')) !== false) {
            $modelAttribute = substr($attribute, $pos + 1);
        } else {
            $modelAttribute = $attribute;
        }

        $value = $this->$modelAttribute;
        if (trim($value) === '') {
            return;
        }
        if ($partialMatch) {
            $query->andWhere(['like', $attribute, $value]);
        } else {
            $query->andWhere([$attribute => $value]);
        }
    }
	
	/*
	 * Set query options usable by \yii\db\Query
	 * @param \yi\db\Query $query
	 * @param array $options List of options to add to Query
	 */
	protected function setQueryOptions($query)
	{
		foreach($this->queryOptions as $option=>$params)
		{
			switch($option)
			{
				case 'where':
				case 'andWhere':
				case 'orWhere':
				case 'orderby':
				$query->$option($params);
				break;
			}
		}
	}
}
