<?php

namespace nitm\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * BaseSearch represents the model behind the search form about `nitm\models\BaseSearch`.
 */
class BaseSearch extends Model
{
    public $id;
	
	public $primaryModel;
	
	public $queryOptions = [];
	
	public $withThese = ['icon'];
	public $searchType;
	
	const SEARCH_PARAM = '__searchType';
	const SEARCH_FULLTEXT = 'text';
	const SEARCH_NORMAL = 'default';
	
	protected $primaryModelAttributes;
	protected $primaryModelFormName;
	protected $primaryModelTableName;
	
	public function init()
	{
		$class = self::getModelClass(static::formName());
		$this->primaryModel = new $class;
		$this->primaryModelAttributes = $this->primaryModel->attributes();
		$this->primaryModelFormName = $this->primaryModel->formName();
		$this->primaryModelTableName = $this->primaryModel->tableName();
	}
	
	public function __set($name, $value)
	{
		try {
			parent::__set($name, $value);
		} catch(\yii\base\UnknownPropertyException $error) {
			$this->{$name} = $value;
		}
	}
	
	public function __get($name)
	{
		try {
			return parent::__get($name);
		} catch(\yii\base\UnknownPropertyException $error) {
			return $this->{$name};
		}
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
		foreach($this->primaryModelAttributes as $attr)
		{
			$ret_val[$attr] = \Yii::t('app', array_map('ucfirst', preg_split("/[_-]/", $attr)));
		}
		return $ret_val;
	}

    public function search($params=[])
    {
		$params = isset($params[$this->primaryModelFormName]) ? $params[$this->primaryModelFormName] : (is_array($params) ? $params : []);
        $query = $this->primaryModel->find();
		switch($this->primaryModelTableName)
		{
			case 'categories':
			//$query->where(['type_id' => 1]);
			break;
			
			case 'content':
			$query->andWhere(['type_id' => $this->primaryModel->getTypeId()]);
			break;
		}
		$query->with($this->withThese);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
		$params = array_intersect_key($params, array_flip($this->primaryModelAttributes));
		$params = empty($params) ? array_combine($this->primaryModelAttributes, array_fill(0, sizeof($this->primaryModelAttributes), '')) : $params;
		$this->setProperties(array_keys($params), array_values($params));
		$params = [$this->primaryModelFormName => $params];
        if (!($this->load($params) && $this->validate())) {
			$this->addQueryOptions($query);
            return $dataProvider;
        }

		$table = \Yii::$app->db->getTableSchema($this->primaryModel->tableName());
		foreach($params[$this->primaryModelFormName] as $attr=>$value)
		{
			$column = $table->columns[$attr];
			switch($column->phpType)
			{
				case 'integer':
				case 'boolean':
				case 'double':
        		if(is_numeric($this->{$column->name})) $this->addCondition($query, $column->name);
				break;
				
				case 'string':
				case 'datetime':
        		if(is_string($this->{$column->name}))$this->addCondition($query, $column->name, true);
				break;
			}
		}
		$this->addQueryOptions($query);
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
			if(\Yii::$app->request->get(static::SEARCH_PARAM) == static::SEARCH_FULLTEXT)
			{
            	$query->orWhere(['like', $attribute, $value]);
			}
			else
			{
            	$query->andWhere(['like', $attribute, $value]);
			}
        } else {
            $query->andWhere([$attribute => $value]);
        }
    }
	
	public function getModelClass($class)
	{
		return "\\nitm\models\\".array_pop(explode('\\', $class));
	}
	
	private function setProperties($names=[], $values=[])
	{
		$names = is_array($names) ? $names : [$names];
		$values = is_array($values) ? $values : [$values];
		switch(sizeof($names) == sizeof($values))
		{
			case true:
			foreach($names as $idx=>$name)
			{
				$this->{$name} = $values[$idx];
			}
			break;
		}
	}
	
	private function addQueryOptions($query)
	{
		foreach($this->queryOptions as $type=>$queryOpts)
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
				$query->$type($queryOpts);
				break;
			}
		}
	}
}