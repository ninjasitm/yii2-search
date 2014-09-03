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
	public $exclusiveSearch;
	public $mergeInclusive;
	
	const SEARCH_PARAM = '__searchType';
	const SEARCH_PARAM_BOOL = '__searchIncl';
	const SEARCH_FULLTEXT = 'text';
	const SEARCH_NORMAL = 'default';
	
	protected $primaryModelAttributes;
	protected $primaryModelFormName;
	protected $primaryModelTableName;
	protected $dataProvider;
	protected $conditions = [];
	
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
			$ret_val[$attr] = \Yii::t('app', $this->properName($attr));
		}
		return $ret_val;
	}

    public function search($params=[])
    {
		$this->reset();
		$params = $this->filterParams($params);
        if (!($this->load($params[$this->primaryModelFormName], false) && $this->validate())) {
			$this->addQueryOptions();
            return $this->dataProvider;
        }
		foreach($params[$this->primaryModelFormName] as $attr=>$value)
		{
			$column = $this->primaryModelTable->columns[$attr];
			switch($column->phpType)
			{
				case 'integer':
				case 'boolean':
				case 'double':
				case 'array':
        		$this->addCondition($column->name, $value);
				break;
				
				case 'string':
        		$this->addCondition($column->name, $value, $this->booleanSearch);
				break;
			}
		}
		$this->addConditions();
		$this->addQueryOptions();
        return $this->dataProvider;
    }
	
	public function reset()
	{
        $query = $this->primaryModel->find($this);
        $this->dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
		$this->conditions = [];
		return $this;
	}
	
	protected function addConditions()
	{
		foreach($this->conditions as $type=>$condition)
		{
			$where = ($this->exclusiveSearch) ? 'andWhere' : $type.'Where';
			array_unshift($condition, $type);
			$this->dataProvider->query->$where($condition);
		}
	}

    protected function addCondition($attribute, $value, $partialMatch=false)
    {
        if (($pos = strrpos($attribute, '.')) !== false) {
            $modelAttribute = substr($attribute, $pos + 1);
        } else {
            $modelAttribute = $attribute;
        }
        if (is_string($value) && trim($value) === '') {
            return;
        }
		switch(1)
		{
			case is_numeric($value) && !$partialMatch:
			case is_bool($value) && !$partialMatch:
			case is_array($value) && !$partialMatch:
            switch($this->inclusiveSearch && !$this->exclusiveSearch)
			{
				case true:
				$this->conditions['or'][] = [$attribute => $value];
				break;
				
				default:
				$this->conditions['and'][] = [$attribute => $value];
				break;
			}
			break;
			
			default:
			switch($partialMatch) 
			{
				case true:
				$attribute = "LOWER(".$attribute.")";
				$value = $this->expand($value);
				switch($this->inclusiveSearch)
				{
					case true:
					$this->conditions['or'][] = ['like', $attribute, $value, false];
					break;
					
					default:
					$this->conditions['and'][] = ['like', $attribute, $value, false];
					break;
				}
				break;
				
				default:
            	$this->conditions['and'][] = [$attribute => $value];
				break;
			}
			break;
		}
	}
	
	public function getModelClass($class)
	{
		return "\\nitm\models\\".array_pop(explode('\\', $class));
	}
	
	public function getJsonList($labelField='name')
	{
		$ret_val = [];
		$with = explode(',', \Yii::$app->request->get('with'));
		foreach($this->dataProvider->getModels() as $item)
		{
			$_ = [
				"id" => $item->getId(),
				"value" => $item->getId(), 
				"text" =>  $item->$labelField, 
				"label" => $item->$labelField
			];
			foreach($with as $attribute)
			{
				switch($attribute)
				{
					case 'htmlView':
					$view = isset($options['view']['file']) ? $options['view']['file'] : "/".$item->isWhat()."/view";
					$viewOptions = isset($options['view']['options']) ? $options['view']['options'] : ["model" => $item];
					$_['html'] = \Yii::$app->getView()->renderAjax($view, $viewOptions);
					break;
					
					case 'icon':
					/*$_['label'] = \lab1\widgets\Thumbnail::widget([
						"model" => $item->getIcon()->one(), 
						"htmlIcon" => $item->html_icon,
						"size" => "tiny",
						"options" => [
							"class" => "thumbnail text-center",
						]
					]).$_['label'];*/
					break;
				}
			}
			$ret_val[] = $_;
		}
		return (sizeof(array_filter($ret_val)) >= 1) ? $ret_val : [['id' => 0, 'text' => "No ".$this->properName($this->isWhat())." Found"]];
	}
	
	public function getList($label='name')
	{
		$ret_val = [];
		$items = $this->dataProvider->getModels();
		switch(empty($items))
		{
			case false:
			foreach($items as $item)
			{
				$ret_val[$item->getId()] = $item->$label;
			}
			break;
			
			default:
			$ret_val[] = ["No ".static::isWhat()." found"];
			break;
		}
		return $ret_val;
	}
	
	protected function expand($values)
	{
		$values = (array)$values;
		foreach($values as $idx=>$value)
		{
			switch($this->expand)
			{
				case 'right':
				$value = $value."%";
				break;
				
				case 'left':
				$value = "%".$value;
				break;
				
				case 'none':
				$value = $value;
				break;
				
				default:
				$value = "%".$value."%";
				break;
			}
			$values[$idx] = strtolower($value);
		}
		return sizeof($values) == 1 ? array_pop($values) : $values;
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
	
	private function addQueryOptions()
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
				$this->dataProvider->query->$type($queryOpts);
				break;
			}
		}
	}
	
	/**
	 * Filter the parameters and remove some options
	 */
	private function filterParams($params=[])
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
						case 'exclusive':
						$this->exclusiveSearch = (bool)$filterValue;
						break;
						
						case 'sort':
						$direction = isset($params['order']) ? $params['order'] : SORT_DESC;
						$this->dataProvider->query->orderBy([$filterValue => $direction]);
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
					$this->mergeInclusive = true;
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
		$this->exclusiveSearch = !isset($this->exclusiveSearch) ? (!(empty($params) && !$useEmptyParams)) : $this->exclusiveSearch;
		$params = (empty($params) && !$useEmptyParams) ? array_combine($this->primaryModelAttributes, array_fill(0, sizeof($this->primaryModelAttributes), '')) : $params;
		if(!empty($params)) $this->setProperties(array_keys($params), array_values($params));
		$params = [$this->primaryModelFormName => $params];
		return $params;
	}
}