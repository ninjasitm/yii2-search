<?php
namespace nitm\traits;

/**
 * Class Provisioning
 * @package nitm\models
 *
 * @property string $type The type of item being added
 * @property string $in The context we're in
 */

trait ApiBase 
{
	public $fields;
	
	public static $type;
	public static $in;
	
	public function ApiInit($type=null, $in=null)
	{
		$this->setType($type, $in);
		$this->init();
		return $this;
	}
	
	public function init()
	{
		$this->changeLogin();
		switch(!empty(self::$type) && static::isSupported(self::$type))
		{
			case true:
			$class = static::$_descriptors.self::safeName(self::$type);
			$this->setDb($class::dbName(), $class::tableName());
			break;
		}
		$this->initConfig(static::isWhat());
		static::$supported = $this->settings[static::isWhat()]['supported'];
		parent::init();
	}
	
	public function getModel()
	{
		$ret_val = $this;
		switch(!empty(self::$type) && static::isSupported(self::$type))
		{
			case true:
			$class = static::$_descriptors.self::safeName(self::$type);
			$ret_val = new $class;
			$ret_val->changeLogin();
			$ret_val->setDb($class::dbName(), $class::tableName());
			break;
		}
		return $ret_val;
	}
	
	public static function tableName()
	{
		$ret_val = null;
		switch(!empty(self::$type) && static::isSupported(self::$type))
		{
			case true:
			$class = static::$_descriptors.self::safeName(self::$type);
			$ret_val = $class::tableName();
			break;
		}
		return $ret_val;
	}
	
	public static function setType($type=null, $in=null)
	{
		self::$type = self::safeName($type);
		self::$in = $in;
	}
	
	public static function getType()
	{
		return self::$type;
	}
	
	public static function filters($parentOnly=false)
	{
		switch(empty(self::$type) || $parentOnly)
		{
			case false:
			$class = "\nitm\models\descriptors\\".self::safeName(self::$type);
			$method = "filters";
			$filters = $class::$method();
			break;
			
			default:
			$filters = [
				'navigation' => null,
			];
			break;
		}
		return array_merge(
			parent::filters(),
			$filters
		);
	}
	
	public function scenarios()
	{
		$ret_val = array();
		switch(!empty(self::$type) && static::isSupported(self::$type))
		{
			case true:
			$class = static::$_descriptors.self::safeName(self::$type);
			$method = "scenarios";
			$obj = new $class();
			$ret_val = $obj->$method();
			break;
		}
		return array_merge(
			parent::scenarios(),
			$ret_val
		);
	}
	
	public function rules()
	{
		$ret_val = array();
		switch(!empty(self::$type) && static::isSupported(self::$type))
		{
			case true:
			$class = static::$_descriptors.self::safeName(self::$type);
			$method = "rules";
			$obj = new $class();
			$ret_val = $obj->$method();
			break;
		}
		return array_merge(
			parent::rules(),
			$ret_val
		);
	}
	
	/*
	 * Return a string imploded with ucfirst characters
	 * @param string $name
	 * @return string
	 */
	public static function safeName($value)
	{
		$ret_val = empty($value) ?  [] : array_map('ucfirst', explode('_', $value));
		return implode($ret_val);
	}
	
	/*
	 * Return a string imploded with ucfirst characters
	 * @param string $name
	 * @return string
	 */
	public static function properName($value)
	{
		$ret_val = explode('_', $value);
		return implode(' ', $ret_val);
	}
	
	public static function columns()
	{
		$ret_val = [];
		switch(!is_null(self::$type) && static::isSupported(self::$type))
		{
			case true:
			switch(self::$in)
			{
				case 'form':
				case 'add':
				case 'apiSelect':
				$class = static::$_descriptors.self::safeName(self::$type);
				$in = self::$in."Columns";
				$ret_val = $class::$in();
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/**
     * Get the categories asd return a certain set of clumns dependeing on $for
	 * @param string $for
     */
	public function getCategories($for='form')
	{
		$ret_val = array();
		$class = static::$_descriptors.self::safeName(self::$type);
		$db = $class::categoriesLocation();
		$data = new \nitm\models\Data(self::$type, $for);
		$data->changeDb($db['db'], $db['table']);
		$categories = $data->getRecords();
		$columnSelector = $for."Columns";
		$columns = $class::$columnSelector();
		array_walk($categories,  function ($v) use ($columns,  &$ret_val) {
	  		foreach($v as $column=>$value)
	  		{
				switch(isset($columns[$column]))
				{
					case true:
					$ret_val[$v['unique']] = $v[$columns[$column]['label']];
					break;
				}
			}
		});
		$data->revertDb();
		return $ret_val;
	}
}