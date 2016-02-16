<?php

namespace nitm\search\traits;

use nitm\helpers\ArrayHelper;
use nitm\helpers\QueryFilter;

/**
 * Traits defined for expanding query scopes until yii2 resolves traits issue
 */
trait SearchTrait {

	public $text;
	public $filter = [];
	public $expand = 'all';
	/**
	 * The default parameters for the dataProvider
	 * [
	 *		'sort' => ...
	 *		'params' => ...
	 * ]
	 */
	public $defaults = [];

	public $primaryModel;
	public $primaryModelClass;
	public $useEmptyParams;

	/**
	 * Should wildcards be used for text searching?
	 * This should be renamed...:-/
	 */
	public $booleanSearch;

	/**
	 * Should the or clause be used?
	 */
	public $inclusiveSearch;
	public $exclusiveSearch;
	public $forceExclusiveBooleanSearch;
	public $mergeInclusive;

	public static $sanitizeType = true;
	public static $tableNames;
	public static $namespace = '\nitm\models\\';

	protected $dataProvider;
	protected $conditions = [];
	protected $parsedParams;
	protected $_customAttributes;

	public function __set($name, $value)
	{
		try {
			parent::__set($name, $value);
		} catch(\Exception $error) {
			$this->{$name} = $value;
		}
	}

	public function __get($name)
	{
		try {
			$ret_val = parent::__get($name);
		} catch(\Exception $error) {
			if($this->hasProperty($name) && !empty($this->{$name}))
				$ret_val = $this->{$name};
			else
				$ret_val = $this->hasAttribute($name) ? $this->getAttribute($name) : null;
		}
		return $ret_val;
	}

	public function scenarios()
	{
		return ['default' => ($this->getPrimaryModel()->tableName() ? $this->attributes() : ['id'])];
	}

	public function getParsedParams()
	{
		return $this->parsedParams;
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
		$ret_val = [];
		foreach($this->attributes() as $attr)
			$ret_val[$attr] = \Yii::t('app', $this->properName($attr));
		return $ret_val;
	}

	public function restart($options=[])
	{
		$oldType = $this->type();
		$this->getPrimaryModel();

		$query = $this->primaryModel->find($this);

		$this->filter = ArrayHelper::getValue($options, $this->primaryModel->formName().'.filter', []);

		$sortFromModel = ArrayHelper::getValue($this->filter, 'sort', ArrayHelper::getValue($this->filter, 'order_by', null));
		$directionFromModel = ArrayHelper::getValue($this->filter, 'order', null);

		$getParams = empty(ArrayHelper::getValue($options, $this->primaryModel->formName(), null));

		if(!is_null($sortFromModel))
			$options['sort'] = ($directionFromModel == 'desc' ? '-' : '').ltrim($sortFromModel, '-');

		$this->dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
			'sort' => [
				'enableMultiSort' => true,
				'params' => array_merge(ArrayHelper::getValue($options, $this->primaryModel->formName().'.filter', []), $options),
			],
			'pagination' => [
				'pageSize' => ArrayHelper::getValue($this->queryOptions, 'limit', null)
			]
        ]);

		$this->dataProvider->sort->attributes = array_merge($this->dataProvider->sort->attributes, $this->primaryModel->getSort());

		foreach($this->defaults as $name=>$value)
		{
			switch(strtolower($name))
			{
				case 'sort':
				$this->dataProvider->sort->defaultOrder = $value;
				break;

				case 'orderby':
				if(!isset($options['sort']))
					$this->dataProvider->query->orderBy($value);
				break;

				case 'params':
				case 'where':
				if($getParams) {
					QueryFilter::aliasWhereFields($value, $this);
					$this->dataProvider->query->andWhere($value);
				}
				break;
			}
		}

		$this->conditions = [];
		$this->setIndexType($oldType);
		return $this;
	}

    public function search($params=[])
    {
		$this->restart($params);
		$originalParams = $this->filterParams($params);
		$params = $this->getParams($originalParams, $this->useEmptyParams);

        if (!($this->load($params, $this->primaryModel->formName()) && $this->validate(null, true))) {
			$this->setQueryParams();
            return $this->dataProvider;
        }

		$this->parsedParams = $this->getDirtyAttributes();

		foreach($this->parsedParams as $attr=>$value)
		{
			if(array_key_exists($attr, $this->columns()))
			{
				$column = \yii\helpers\ArrayHelper::getvalue($this->columns(), $attr);
				switch($column->type)
				{
					case 'integer':
					case 'long':
					case 'boolean':
					case 'smallint':
					case 'bigint':
					case 'double':
					case 'decimal':
					case 'float':
					case 'array':
					$this->addCondition($column->name, $value);
					break;

					case 'timestamp':
					case 'date':
					case 'datetime':
					//$this->addCondition($column->name, $value);
					break;

					case 'string':
					case 'text':
					$this->addCondition($column->name, $value, $this->booleanSearch);
					break;
				}
			}
		}
		$this->setQueryParams($originalParams);
        return $this->dataProvider;
    }

	public function isFilterSpecified($param)
	{
		return isset($this->parsedParams);
	}

	public function isOnlyFilterSpecified($params)
	{
		$paramCount = count($params);

		//If any other parameter was specified then return false
		if(count(array_diff_key($this->parsedParams, $params)) >= 1)
			return false;

		$existing = array_intersect_key($this->parsedParams, $params);

		//If the existing parameters are different than what the user specified then return false
		if(count(array_diff_assoc($existing, $params)))
			return false;
		return true;
	}

	public function noFilterSpecified()
	{
		return count(array_filter($this->parsedParams)) == 0;
	}

	protected function setQueryParams($params=[])
	{
		$this->addConditions();

		if(isset($params['queryOptions']) && is_array($params['queryOptions']))
			foreach($params['queryOptions'] as $method=>$options)
				$this->dataProvider->query->$method($options);

		/**
		 * Set the sort values if necessary
		 */
		if(!isset($params['sort']) && !isset($params['filter']['order_by']) && !isset($params['filter']['sort'])) {
			if($this->dataProvider->query->orderBy == null) {
				$pk = ArrayHelper::getValue($this->primaryModel->primaryKey(), '0', null);
				if($pk)
					$this->dataProvider->sort->params = [
						$pk
					];
			}

		} else {
			$orders = $this->dataProvider->sort->getOrders(true);
			$this->dataProvider->query->orderBy($orders);
		}

		/**
		 * Quote the field names
		 * ONly do this for traditional DBMS
		 */
		 if($this instanceof \nitm\search\BaseSearch) {
		 	QueryFilter::aliasSelectFields($this->dataProvider->query, $this->primaryModel);
		 	$orders = QueryFilter::aliasOrderByFields($this->dataProvider->query, $this->primaryModel);
			QueryFilter::setDataProviderOrders($this->dataProvider, $orders);
		 }
	}

	protected function getPrimaryModel()
	{
		if(!isset($this->primaryModel)){
			$class = $this->getPrimaryModelClass(true);
			if(class_exists($class))
				$options = [];
			else {
				$class = "\\nitm\search\\".ucFirst($this->engine)."Search";
				$options = [
					'indexType' => $this->type(),
				];
			}
			if($this->engine == 'db')
				$this->primaryModel = new $class([
					'noDbInit' => true
				]);
			else {
				$this->primaryModel = new static([
					'is' => $this->type()
				]);
			}
		}
		return $this->primaryModel;
	}

	/**
	 * Overriding default find function
	 */
	protected static function findInternal($className, $model=null, $options=null)
	{
		$query = \Yii::createObject($className, [get_called_class()]);
		if(is_object($model))
		{
			foreach($model->queryOptions as $filter=>$value)
			{
				switch(strtolower($filter))
				{
					case 'select':
					case 'indexby':
					case 'orderby':
					if(is_string($value) && ($value == 'primaryKey')){
						unset($model->queryOptions[$filter]);
						$query->$filter(static::primaryKey()[0]);
					}
					break;

					case 'where':
					case 'andWhere':
					$query->andWhere($value);
					break;
				}
			}
			static::applyFilters($query, $model->queryOptions);
		}
		return $query;
	}

	public function getSearchModelClass($class)
	{
		return rtrim(static::$namespace, '\\').'\\search\\'.array_pop(explode('\\', $class));
	}

	public function getPrimaryModelClass($force=false)
	{
		if(!isset($this->primaryModelClass) || $force)
			$this->setPrimaryModelClass(null, $force);
		return $this->primaryModelClass;
	}

	public function setPrimaryModelClass($class=null, $force=fasle)
	{
		if(!is_null($class) && class_exists($class))
			$this->primaryModelClass = $class;
		else {
			if(!isset($this->primaryModelClass) || $force) {
				$class = $this->getModelClass($this->properClassName($this->type()));
				$this->primaryModelClass = class_exists($class) ? $class : $this->className();
			}
		}
	}

	public function getModelClass($class)
	{
		$class = explode('\\', $class);
		return rtrim(static::$namespace, '\\').'\\'.array_pop($class);
	}

	public static function useSearchClass($callingClass)
	{
		return strpos(strtolower($callingClass), 'models\search') !== false;
	}

	protected function addConditions()
	{
		///If conditions were set by user then reset the where values
		//if(count($this->conditions))
		//	$this->dataProvider->query->where = [];

		foreach($this->conditions as $type=>$condition)
		{
			$where = ($this->exclusiveSearch) ? 'andWhere' : $type.'Where';
			array_unshift($condition, $type);
			$this->dataProvider->query->$where($condition);
		}
	}

    protected function addCondition($attribute, $value, $partialMatch=false)
    {
        if (($pos = strrpos($attribute, '.')) !== false)
            $modelAttribute = substr($attribute, $pos + 1);
		else
            $modelAttribute = $attribute;

		$modelAttribute = $this->tableName().'.'.$modelAttribute;

        if (is_string($value) && trim($value) === '')
            return;

		$value = (is_array($value) && count($value) == 1) ? current($value) : $value;

		$simpleAddCondition = function ($params) {
            switch($this->inclusiveSearch && !$this->exclusiveSearch)
			{
				case true:
				$this->conditions['or'][] = $parsedParams;
				break;

				default:
				$this->conditions['and'][] = $params;
				break;
			}
		};

		switch(1)
		{
			case $value == 'null':
			$simpleAddCondition([$modelAttribute => null]);
			break;

			case is_array($value) && is_string(current($value)) && in_array(strtolower(current($value)), ['and', 'or']):
			print_r($value);
			exit;
			break;

			case is_numeric($value):
			case !$partialMatch:
			case \nitm\helpers\Helper::boolval($value):
			$simpleAddCondition([$modelAttribute => $value]);
			break;

			default:
			switch($partialMatch)
			{
				case true:
				$modelAttribute = "LOWER(".$modelAttribute.")";
				switch(true)
				{
					case ($this->inclusiveSearch && !$this->forceExclusiveBooleanSearch):
					case $this->inclusiveSearch:
					$value = $this->expand($value);
					$this->conditions['or'][] = ['or like', $modelAttribute, $value, false];
					break;

					default:
					$this->conditions['and'][] = ['like', $modelAttribute, $value];
					break;
				}
				break;

				default:
            	$this->conditions['and'][] = [$modelAttribute => $value];
				break;
			}
			break;
		}
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

	/**
	 * Filter the parameters and remove some options
	 */
	private function filterParams($params=[])
	{
		$modelParams = ArrayHelper::remove($params, $this->primaryModel->formName(), ArrayHelper::getValue($params, $this->properName($this->type()), []));

		////$params = array_merge([
		//	'filter' => array_intersect_key($params, array_flip(['sort']))
		//], (array)array_intersect_key($params, array_flip(['q', 'text'])));

		$filterParser = function ($options) {
			foreach($options as $name=>$value)
			{
				switch($name)
				{
					case 'filter':
					foreach($value as $filterName=>$filterValue)
					{
						switch($filterName)
						{
							case 'exclusive':
							case 'inclusive':
							$this->{$filterName.'Search'} = (bool)$filterValue;
							break;

							case 'sort':
							case 'order_by':
							if(isset($options['order']))
								$direction = $options['order'];
							else {
								$direction = $filterValue[0]  == '-' ? 'desc' : 'asc';
								$filterValue = $filterValue[0] == '-' ? substr($filterValue, 1) : $filterValue;
							}

							$this->dataProvider->query->orderBy([$filterValue => $direction]);
							$this->useEmptyParams = true;
							$this->filter['sort'] = $filterValue;
							unset($options['order']);
							break;

							default:
							$options[$filterName] = $filterValue;
							break;
						}
					}
					break;

					case 'text':
					case 'q':
					if(!empty($value)) {
						$this->text = $value;
						$options = array_merge((array)$options, $this->getTextParam($value));
					}
					unset($options[$name]);
					break;
				}
			}
			return $options;
		};
		$params = array_merge($filterParser($params), (array)$filterParser($modelParams));
		return $params;
	}

	protected function getTextParam($value)
	{
		$params = [];
		$this->mergeInclusive = true;
		if(!$this->primaryModel->tableName())
			return $params;

		foreach($this->primaryModel->getTableSchema()->columns as $column)
		{
			switch($column->phpType)
			{
				case 'string':
				case 'datetime':
				$params[$column->name] = isset($params[$column->name]) ? $params[$column->name] : $value;
				break;
			}
		}
		return $params;
	}

	public function setCustomAttributes($attributes) {
		$this->_customAttributes = $attribtues;
	}

	public function getCustomAttributes() {
		if(!isset($this->_customAttributes))
			return $this->attributes();
		else
			return $this->_customAttributes;
	}

	protected function getParams($params)
	{
		if($this->primaryModel->tableName()) {
			$params = array_intersect_key($params, array_flip($this->customAttributes));
			if(sizeof($this->customAttributes) >= 1)
				$params = (empty($params) && !$this->useEmptyParams) ? array_combine($this->customAttributes, array_fill(0, sizeof($this->customAttributes), '')) : $params;
		}
		if(sizeof($params) >= 1)
			$this->setProperties(array_keys($params), array_values($params));

		foreach($params as $attribute=>$value)
		{
			if(in_array($attribute, $this->customAttributes)) {
				$params[$this->primaryModel->formName()][$attribute] = $value;
				unset($params[$attribute]);
			}
		}

		$this->exclusiveSearch = !isset($this->exclusiveSearch) ? (!(empty(current($params)) && !$this->useEmptyParams)) : $this->exclusiveSearch;

		return $params;
	}

	/**
	 * This function properly maps the object to the correct class
	 */
	protected static function instantiateInternal($attributes, $type=null)
	{
		$type = isset($attributes['_type']) ? $attributes['_type'] : $type;
		$type = is_null($type) ? static::isWhat() : $type;
		$found = $class = false;

		foreach([
			null, 'singularize', 'pluralize'
		] as $inflector) {

			if(!is_null($inflector))
				$type = \yii\helpers\Inflector::$inflector($type);

			$properName = \nitm\models\Data::properClassName($type);

			foreach(\Yii::$app->getModule('nitm-search')->getNamespaces(static::$namespace) as $namespace)
			{
				$class = [rtrim($namespace, '\\')];
				if(end(array_filter(explode('\\', $namespace))) !== 'search')
					$class[] = 'search';
				$class[] = $properName;
				$class = implode('\\', $class);
				if(class_exists($class)) {
					$found = true;
					break;
				}
			}
			if($found)
				break;
		}
		if(!class_exists($class))
			$class = static::className();
		return new $class(['is' => $type]);
	}

	/**
	 * Get a synonym value
	 */
	protected function getFilterSynonym($filter, $value)
	{
		$value = $this->translateValue($value);
		$ret_val = [$filter, $value];
		switch($filter)
		{
			case 'open':
			$filter = 'closed';
			$value = (int)!$value;
			break;
		}
		return $ret_val;
	}

	/**
	 * Translate a value
	 * @param mixed $value
	 * @return mixed
	 */
	protected function translateValue($value)
	{
		$ret_val = $value;
		switch(1)
		{
			case $value == 'false':
			$ret_val = 0;
			break;

			case $value == 'true':
			$ret_val = 1;
			break;
		}
		return $ret_val;
	}

	/**
	 * Convert some common properties
	 * @param array $item
	 * @param boolean decode the item
	 * @return array
	 */
	public static function normalize(&$item, $decode=false, $columns=null)
	{
		$columns = is_array($columns) ? $columns : static::columns();

		foreach((array)$item as $f=>$v)
		{
			if(!isset($columns[$f]))
				continue;
			$info = \yii\helpers\ArrayHelper::toArray($columns[$f]);
			switch(array_shift(explode('(', $info['dbType'])))
			{
				case 'tinyint':
				$item[$f] = $info['dbType'] == 'tinyint(1)' ? (boolean)$v : (int)$v;
				break;

				case 'int':
				case 'integer':
				case 'long':
				$item[$f] = (int)$item[$f];
				break;

				case 'float':
				$item[$f] = (float)$item[$f];
				break;

				case 'double':
				$item[$f] = (double)$item[$f];
				break;

				case 'real':
				$item[$f] = (real)$item[$f];
				break;

				case 'blob':
				case 'longblob':
				case 'mediumblob':
				unset($item[$f]);
				continue;
				break;

				case 'timestamp':
				/**
				 * This is most likely a timestamp behavior.
				 * Convert it to a time value here
				 */
				if(is_object($v))
					$item[$f] = strtotime('now');
				else
					$item[$f] = strtotime($item[$f]);
				$item[$f] = date('Y-m-d H:i:s', $item[$f]);
				break;

				case 'text':
				case 'varchar':
				case 'string':
				if(is_array($v))
					$item[$f] = static::normalize($v, $decode, $columns);
				else
					$item[$f] = (string)$item[$f];
				break;
			}
		}
		return $item;
	}

	protected function defaultColumns()
	{
		$defaultColumns = ['_id' => 'int', '_type' => 'string', '_index' => 'string'];
		foreach($defaultColumns as $name=>$type)
		{
			$defaultColumns[$name] = new \yii\db\ColumnSchema([
				'name' => $type,
				'type' => $type,
				'phpType' => $type,
				'dbType' => $type
			]);
		}
		return $defaultColumns;
	}

	protected function populateRelations($record, $row)
	{
		$relations = [];
		foreach($row as $name=>$value)
		{
			$originalName = $name;
			switch(1)
			{
				case $name == 'type':
				$name = ['typeof', 'type'];
				break;

				case $name == 'id':
				continue;
				break;

				default:
				$name = [$name];
				break;
			}
			if(is_array($value) && $value != []) {

				$value = is_array(current($value)) ? $value : [$value];

				foreach((array)$name as $n)
				{
					if(($relation = $record->hasRelation($n)) != null) {
						$value = array_map(function ($attributes) use($relation, $record) {
							$object = \Yii::createObject(array_merge([
								'class' => $relation->modelClass
							], array_intersect_key($attributes, array_flip((new $relation->modelClass)->attributes()))));
							return $object;
						}, $value);

						if(!$relation->multiple)
							$value = current($value);

						if(count($value)) {
							if(is_array($value))
								array_walk($value, function ($related) use($n, $value){
									static::populateRelations($related, ArrayHelper::toArray($related));
								});
							else
								static::populateRelations($value, ArrayHelper::toArray($value));
							$record->populateRelation($originalName, $value);
						}

						if($record->hasAttribute($originalName)) {
							$record->setAttribute($originalName, null);
							$record->setOldAttribute($originalName, null);
							break;
						}
					}
				}
			}
		}
		static::normalize($record, true);
	}

	protected function extractAttributesAndRelations($from)
	{
		$ret_val = [
			'attributes' => $from,
			'relations' => $from
		];
		foreach($from as $attribute=>$value)
		{
			if(is_array($value)) {
				$ret_val['relations'][$attribute] = $value;
				unset($ret_val['attributes'][$attribute]);
			} else {
				$ret_val['attributes'][$attribute] = $value;
				unset($ret_val['relations'][$attribute]);
			}
		}
		return $ret_val;
	}
}
?>
