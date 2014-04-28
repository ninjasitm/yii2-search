<?php
namespace nitm\traits;

/**
 * Traits defined for expanding query scopes until yii2 resolves traits issue
 */
trait Query {

	/*
	 * Apply the filters specified by the end user
     * @param ActiveQuery $query
	 * @param mixed $filters
	 */
	public static function applyFilters($query, $filters=null)
	{
		//search for special filters
		switch(is_array($filters))
		{
			case true:
			foreach($filters as $name=>$value)
			{
				switch(static::hasFilter($name))
				{
					case true:
					switch($name)
					{
						case 'custom':
						$query->orWhere($value['attribute'], $value['value']);
						break;
						
						case 'order_by':
						$query->orderBy($value);
						if(@isset($filters['order']))
						{
							unset($filters['order']);
						}
						unset($filters[$name]);
						break;
						
						case 'group_by':
						$query->groupBy($value);
						break;
						
						case 'text':
						$query->orWhere(['like', $name, $value]);
						break;
						
						case 'show':
						switch(is_null($value))
						{
							case true:
							continue;
							break;
							
							case false:
							$fieldName = 'disabled';
							switch($value)
							{
								case 'disabled':
								$fieldName = $value;
								$value = 1;
								break;
								
								case 1:
								case true:
								$value = 1;
								break;
								
								default:
								$value = 0;
								break;
							}
							$query->andWhere([$fieldName => $value]);
							break;
						}
						unset($filters[$name]);
						break;
						
						case 'limit':
						$query->limit($value);
						unset($filters[$name]);
						break;
						
						case 'unique':
						$pk = static::primaryKey();
						$query->andWhere([$pk[0] => $value]);
						unset($filters[$name]);
						break;
					}
					break;
				}
			}
			//now search for conditional filters
			$filters = array_filter($filters, 'strlen');
			switch(is_array($filters) && (sizeof($filters) >= 1))
			{
				case true:
				$query->andWhere($filters);
				break;
			}
			break;
		}
		return $query;
	}
	
	/*
	 * Some common filters
	 * @param $name The name of the filter
	 * @param $default Should a default value be appended?
	 * @return mixed $filter
	 */
	public function getFilter($name=null, $default=true)
	{
		$ret_val = null;
		switch(static::hasFilter($name))
		{
			case true:
			$filters = static::filters();
			switch(is_null($filters[$name]))
			{
				case false;
				switch(is_array($filters[$name]))
				{
					case true:
					switch(sizeof($filters[$name]))
					{
						case 3:
						$class = @$filters[$name][0];
						$method = @$filters[$name][1];
						$args = @$filters[$name][2];
						break;
					
						case 2:
						$class = get_class($this);
						$method = @$filters[$name][0];
						$args = @$filters[$name][1];
						break;
					}
				}
				$r = new ReflectionClass($class);
				switch($r->hasMethod($method))
				{
					case false:
					throw new \base\ErrorException("The method: $method does not exist in class: $class");
					return false;
					break;
				}
				break;
				
				default:
				$class = null;
				break;
			}
			switch($name)
			{
				case 'author':
				case 'editor':
				switch($class == null)
				{
					case true:
					$o = new \nitm\models\User;
					$filters = $o->getAll();
					break;
					
					default:
					$filters = call_user_func_array(array($class, $method), $args);
					break;
				}
				$ret_val = ($default === true) ? array_merge(['' => 'Select User'], $filters) : $filters;
				break;
				
				case 'status':
				$ret_val = ['0' => 'Disabled', '1' => 'Enabled'];
				$ret_val = ($default === true) ? array_merge(['' => 'Any'], $ret_val) : $ret_val;
				break;
				
				case 'boolean':
				$ret_val = ['0' => 'No', '1' => 'Yes'];
				//$ret_val = ($default === true) ? array_merge(['' => 'Any'], $ret_val) : $ret_val;
				break;
				
				case 'order':
				$ret_val = ['desc' => 'Descending', 'asc' => 'Ascending'];
				break;
				
				case 'order_by':
				$pkey = $this->primaryKey();
				$default = [$pkey[0] => "Unique"];
				$filters = static::$settings[static::isWhat()]['filter'][$name];
				$ret_val = is_array($filters) ? array_merge($default, $filters) : $default;
				break;
				
				default:
				switch($class == null)
				{
					case true:
					$filters = isset(static::$settings[static::isWhat()]['filter'][$name]) ? static::$settings[static::isWhat()]['filter'][$name] : [];
					break;
					
					default:
					$filters = call_user_func_array(array($class, $method), $args);
					break;
				}
				$ret_val = ($default === true) ? array_merge(['' => 'Select '.$this->properName($name)], $filters) : $filters;
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/*
	 * Does this object support this filter?
	 * @param string|int #name
	 * @return boolean
	 */
	public static function hasFilter($name)
	{
		return array_key_exists($name, static::filters());
	}
	
	/*
	 * Set the aliased fields according to the class columns() function
     * @param ActiveQuery $query
	 */
	public static function aliasColumns($query)
	{
		$pri = static::primaryKey();
		$ret_val = [$pri[0]." AS `unique`"];
		$columns = static::columns();
		$has = is_array(static::has()) ? static::has() : null;
		switch(is_null($has))
		{
			case false:
			foreach($has as $property=>$value)
			{
				$special = explode(':', $property);
				switch(sizeof($special))
				{
					case 2:
					$property = $special[1];
					$column = $special[0];
					break;
					
					case 1:
					$property = $special[0];
					$column = $property;
					break;
				}
				switch($property)
				{
					case 'author':
					case 'editor':
					switch(isset(\Yii::$app->getDb()->getTableSchema(static::TableName())->columns[$column]))
					{
						case true:
						$columns[] = "(SELECT username FROM ".\nitm\models\User::tableName()." WHERE [[id]]=[[$property]] LIMIT 1) AS ".$column."_hr";
						break;
					}
					break;
					
					case 'added':
					case 'edited':
					case 'date':
					switch(isset(\Yii::$app->getDb()->getTableSchema(static::TableName())->columns[$column]))
					{
						case true:
						$columns[] = "DATE_FORMAT(`$column`, '".\nitm\helpers\DateFormatter::getFormat(\nitm\helpers\DateFormatter::FORMAT_MYSQL_HR)."') AS ".$column."_hr";
						break;
					}
					break;
				}
			}
			break;
		}
		$query->select(array_merge($ret_val, $columns));
		return $query;
	}
}
?>