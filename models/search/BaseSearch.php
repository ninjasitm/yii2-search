<?php

namespace nitm\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * BaseSearch represents the model behind the search form about `nitm\models\BaseSearch`.
 */
class BaseSearch extends \nitm\models\Data
{
    public $id;
	public $text;
	public $filter = [];
	public $expand = 'all';
	
	public $primaryModel;
	
	public $queryOptions = [];
	
	public $withThese = [];
	/**
	 * Should wildcards be used for text searching?
	 */
	public $booleanSearch;
	/**
	 * Should the or clause be used?
	 */
	public $inclusiveSearch;
	
	const SEARCH_PARAM = '__searchType';
	const SEARCH_PARAM_BOOL = '__searchIncl';
	const SEARCH_FULLTEXT = 'text';
	const SEARCH_NORMAL = 'default';
	
	protected $primaryModelAttributes;
	protected $primaryModelFormName;
	protected $primaryModelTableName;
	
	public function init()
	{
		$this->booleanSearch = isset($_REQUEST[self::SEARCH_PARAM_BOOL]) ? true : $this->booleanSearch;
		$this->inclusiveSearch = isset($_REQUEST[self::SEARCH_PARAM]) ? true : $this->inclusiveSearch;
		$class = $this->getModelClass(static::formName());
		$this->primaryModel = new $class;
		$this->primaryModelAttributes = $this->primaryModel->attributes();
		$this->primaryModelFormName = $this->primaryModel->formName();
		$this->primaryModelTableName = $this->primaryModel->tableName();
		static::$tableName = $this->primaryModelTableName;
		$this->primaryModelTable = \Yii::$app->db->getTableSchema($this->primaryModelTableName);
	}
	
	public function scenarios()
	{
		return ['default' => $this->primaryModelAttributes];
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
        $query = $this->primaryModel->find();
		$params = $this->filterParams($params, $query);
		$query->with($this->withThese);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        if (!($this->load($params[$this->primaryModelFormName], false) && $this->validate())) {
			$this->addQueryOptions($query);
            return $dataProvider;
        }
		foreach($params[$this->primaryModelFormName] as $attr=>$value)
		{
			$column = $this->primaryModelTable->columns[$attr];
			switch($column->phpType)
			{
				case 'integer':
				case 'boolean':
				case 'double':
        		if(is_numeric($this->{$column->name})) $this->addCondition($query, $column->name, $value);
				break;
				
				case 'string':
        		if(is_string($this->{$column->name})) $this->addCondition($query, $column->name, $value, $this->booleanSearch);
				break;
			}
		}
		$this->addQueryOptions($query);
        return $dataProvider;
    }

    protected function addCondition($query, $attribute, $value, $partialMatch=false)
    {
        if (($pos = strrpos($attribute, '.')) !== false) {
            $modelAttribute = substr($attribute, $pos + 1);
        } else {
            $modelAttribute = $attribute;
        }
        if (trim($value) === '') {
            return;
        }
		switch(1)
		{
			case is_numeric($value) && !$partialMatch:
			case is_bool($value) && !$partialMatch:
            switch($this->inclusiveSearch)
			{
				case true:
				$query->orWhere([$attribute => $value]);
				break;
				
				default:
				$query->andWhere([$attribute => $value]);
				break;
			}
			break;
			
			default:
			switch($partialMatch) 
			{
				case true:
				switch($this->inclusiveSearch)
				{
					case true:
					$query->orWhere(['like', "LOWER(".$attribute.")", $this->expand($value), false]);
					break;
					
					default:
					$query->andWhere(['like', "LOWER(".$attribute.")", $this->expand($value), false]);
					break;
				}
				break;
				
				default:
            	$query->andWhere([$attribute => $value]);
				break;
			}
			break;
		}
	}
	
	public function getModelClass($class)
	{
		return "\\nitm\models\\".array_pop(explode('\\', $class));
	}
	
	public function expand($value)
	{
		switch($this->expand)
		{
			case 'right':
			$value = $value."%";
			break;
			
			case 'left':
			$value = "%".$value;
			break;
			
			default:
			$value = "%".$value."%";
			break;
		}
		return $value;
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
	
	/**
	 * Filter the parameters and remove some options
	 */
	private function filterParams($params=[], &$query)
	{
		$params = isset($params[$this->primaryModelFormName]) ? $params[$this->primaryModelFormName] : (is_array($params) ? $params : []);
		$useEmptyParams = false;
		foreach($params as $name=>$value)
		{
			switch($name)
			{
				case 'filter':
				foreach($value as $filterName=>$filterValue)
				{
					switch($filterName)
					{
						case '_sort':
						$direction = isset($params['_order']) ? $params['_order'] : SORT_DESC;
						$query->orderBy([$filterValue => $direction]);
						$useEmptyParams = true;
						break;
						
						case '_order':
						$useEmptyParams = true;
						break;
					}
				}
				unset($params['filter']);
				break;
				
				case 'text':
				if(!empty($value)) 
				{
					$this->text = $value;
					foreach($this->primaryModelTable->columns as $column)
					{
						switch($column->phpType)
						{
							case 'string':
							case 'datetime':
							$params[$column->name] = isset($params[$column->name]) ? $params[$column->name] : $value;
							break;
						}
					}
				}
				unset($params[$name]);
				break;
			}
		}
		$params = array_intersect_key($params, array_flip($this->primaryModelAttributes));
		$params = (empty($params) && !$useEmptyParams) ? array_combine($this->primaryModelAttributes, array_fill(0, sizeof($this->primaryModelAttributes), '')) : $params;
		if(!empty($params)) $this->setProperties(array_keys($params), array_values($params));
		$params = [$this->primaryModelFormName => $params];
		return $params;
	}
}