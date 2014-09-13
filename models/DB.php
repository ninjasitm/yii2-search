<?php

namespace nitm\models;

use PDO;
use yii\db\Connection;
use yii\db\Query;
use yii\base\Behavior;
use nitm\helpers\Helper;

//DB.php class which handles database conectivity.
class DB extends Query
{	
	//define result returning global variables
	const R_RES = -1000;		//return the default result
	const R_OBJ = -1001;		//return the result in object form
	const R_ROW = -1002;		//return the result in array from
	const R_ASS = -1003;		//return the result in associated array from
	const R_ARR = -1004;		//return the result in associated array from
	//end defining global result constants
	
	//define operation constants
	const OP_CHK = -1200;		//this operation checks for problems
	const OP_ANA = -1201;		//this operation checks for problems
	const OP_OPT = -1202;		//this operation checks for problems
	const OP_FLU = -1203;		//this operation checks for problems
	const OP_REP = -1204;		//this operation checks for problems
	//end define operation constants
	
	//define the arbitrary constants for this class
	const NULL = NULL;			//this is the null value understood internally
	//end defining arbitrary constants
	
	//define select operation constants
	const SEL_RAND = -1100;		//select a random value
	const SEL_UNION = -2100;
	const SEL_UNION_ALL = -2101;
	//data and logic flags
	const FLAG_NULL = 'null:';
	const FLAG_ASIS = 'asis:';
	const FLAG_IGNORE = 'ignore:';
	//pdo processing flags
	const PDO_NOBIND = 'nobind:';
	
	public $host;
	public $username;
	public $collect_stats = false;
	public $quote_data = true;
	public $primary = ['key' => null];
	public static $active = [
		'table' => ['name' => null, 'resource' => null],
		'db' => ['name' => null, 'resource' => null]
	];
	public static $old = [
		'table' => ['name' => null, 'resource' => null],
		'db' => ['name' => null, 'resource' => null]
	];
	
	protected $type;
	protected $score = null;
	protected $connection;
	protected $resource;
	protected $quoter = '"';
	protected $query = '';
	protected $data = [];
	protected $parts = [];
	protected $stats = [
		'count' => 0, 
		'duration' => 0, 
		'queries' => []
	]; 
	protected $last_id = null;

	private $_on;
	private $_rows;
	private $_password;
	private $_default = [
		'driver' => 'mysql'
	];
	
	function init($db=NULL, $table=NULL, $db_host=NULL, $db_user=NULL, $db_pass=NULL, $db_driver=NULL)
	{
		$this->stats['start'] = microtime();
		//assign variables as needed
		$this->setDriver($db_driver);
		$this->setLogin($db_host, $db_user, $db_pass);
		static::$active['db']['name'] = (is_string($db)) ? $db : null;
		$this->setDb(static::$active['db']['name'], $table);
		$this->dieOnError(true, false);
		return true;
	}
	
	public function behaviors()
	{
		$behaviors = [
			"Behavior" => [
				"class" => \yii\base\Behavior::className(),
			],
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	/**
	 *
	 * set the current driver
	 * @param string $driver
	 */
	public function setDriver($db_driver)
	{
		static::$active['driver'] = ($db_driver != NULL) ? $db_driver : $this->_default['driver'];
	}
	
	/**
	 *
	 * Change the database login information
	 * @param string $db_host
	 * @param string $db_user
	 * @param string $db_pass
	 * @return boolean
	 */
	public function setLogin($db_host=NULL, $db_user=NULL, $db_pass=NULL)
	{
		switch($db_host != NULL)
		{
			case true:
			$this->host = $db_host;
			break;
			
			default:
			$this->host = static::getDefaultDbHost();
			break;
		}
		$this->username = ($db_user != NULL) ? $db_user : \Yii::$app->params['components.db']['username'];
		$this->_password = ($db_pass != NULL) ? $db_pass : \Yii::$app->params['components.db']['password'];
		return true;
	}
	
	/**
	 *
	 * Get the default database host
	 * @return string host
	 */
	public static function getDefaultDbHost()
	{
		preg_match("/(host=)(.+)([$;])/", \Yii::$app->params['components.db']['dsn'], $matches);
		return $matches[2];
	}
	
	/**
	 *
	 * Get the default database name
	 * @return string database name
	 */
	public static function getDefaultDbName()
	{
		preg_match("/(dbname=)(.+)($|;)/", \Yii::$app->params['components.db']['dsn'], $matches);
		return $matches[2];
	}
	
	/**
	 *
	 * Get the default database name
	 * @return string database name
	 */
	public static function getDbName()
	{
		preg_match("/(dbname=)(.+)($|;)/", \Yii::$app->db->dsn, $matches);
		return $matches[2];
	}
	
	public static function tableName()
	{
		return static::$active['table']['name'];
	}
	
	/**
	 * Set the error handling behavior
	 * @param boolean $die
	 * @param boolean $backtrace
	 * @return boolean
	 */
	public function dieOnError($die=false, $backtrace=false)
	{
		$this->_on['erorr'] = [];
		$this->_on['error']['die'] = ($die !== false) ? true : false;
		$this->_on['erorr']['backtrace'] = ($backtrace !== false) ? true : false;
	}
	
	
	/**
	 * set the current table
	 * @param string $table
	 * @return boolean
	 */
	public function setTable($table=null)
	{
		$ret_val = false;
		if(!empty($table) && !is_null($this->connection))
		{
			switch($table)
			{
				case DB::NULL:
				case null:
				static::$active['table']['name'] = '';
				break;
				
				default:
				static::$active['table']['name'] = $table;
				$this->resource['table'] = $this->createCommand($this->connection);
				$this->connection->getTableSchema($table);
				$this->getPrimaryKey();
				break;
			}
			$ret_val = true;
		}
		return $ret_val;
	}
	
	/**
	 * set the current database
	 * @param string $db
	 * @param string $table
	 * @return boolean
	 */
	public function setDb($db, $table=null)
	{
		$ret_val = false;
		switch(empty($db))
		{
			case true:
			switch(empty(static::$active['db']['name']))
			{
				case true:
				static::$active['db']['name'] = static::getDefaultDbName();
				break;
				
				default:
				break;
			}
			break;
			
			default:
			static::$active['db']['name'] = $db;
			break;
		}
		if(static::$active['db']['name'] == $db)
		{
			$ret_val = true;
		}
		
		if(!empty(static::$active['db']['name']))
		{
			$this->connect();
			if(!empty($table))
			{
				$ret_val = $this->setTable($table);
				$this->free();
			}
			$ret_val = true;
		}
		return $ret_val;
	}
	
	
	public function primaryKey()
	{
		return (static::$active['table']['name'] == NULL) ? NULL : $this->primary['key'];
	}
	
	/**
	 * function to properly escape data being for use in SQL statements
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize($data)
	{
		$ret_val = false;
		$was_array = is_array($data) ? true : false;
		$data = (array) $data;
		foreach($data as $i=>$d)
		{
			switch(is_null($d))
			{
				case true:
				$ret_val[$i] = $this->quoteValue($d);
				break;
				
				default:
				switch(is_array($d))
				{
					case true:
					$ret_val[$i] = array_map(__METHOD__, $d);
					break;
				}
				break;
			}
			$ret_val[$i] = $d;
		}
		switch($was_array)
		{
			case false:
			$ret_val = $ret_val[0];
			break;
		}
		return $ret_val;
		
	}
	
	/**
	 * Temporarily change the database or table for operation
	 * @param string $db
	 * @param string $table
	 */
	public function changeDbt($db, $table=null)
	{
		if(empty($this->user) || empty($this->host) || empty($this->_password))
		{
			$this->setLogin();
		}
		if((!empty($db)))
		{
			static::$old['db'] = static::$active['db'];
			static::$active['db']['name'] = $db;
			$this->setDb(static::$active['db']['name']);
		}
		else
		{
			static::$old['db']['name'] = null;
		}
		if(!empty($table))
		{
			static::$old['table'] = static::$active['table'];
			static::$active['table']['name'] = $table;
			$this->setTable(static::$active['table']['name']);
		}
		else
		{
			static::$old['table']['name'] = null;
		};
	}
	
	/**
	 * Reset the database and table back
	 * @param boolean $primary_key Get primary key?
	 */
	public function revertDbt($primary_key=true)
	{
		if(!empty(static::$old['db']['name']) && is_string(static::$old['db']['name']))
		{
			static::$active['db'] = static::$old['db'];
			$this->setDb(static::$active['db']['name']);
		}
		if(!empty(static::$old['table']['name']) && is_string(static::$old['table']['name']))
		{
			static::$active['table'] = static::$old['table'];
		}
		switch($primary_key && !(empty(static::$active['table']['name'])))
		{
			case true:
			$this->setTable(static::$active['table']['name']);
			break;
		}
		static::$old['db'] = [];
		static::$old['table'] = [];
	}
	
	/**
	 * Perform various operations on a table
	 * @param DB::constant the operation
	 * @param string table
	 * @ return mixed | boolean
	 */
	public function performOp($op=null, $table=NULL)
	{
		if(is_null($table))
		{
			$table = static::$active['table']['name'];
		}
		if(is_null($table))
		{
			return false;
		}
		switch($op)
		{
			case self::OP_CHK:
			$this->execute("CHECK TABLE ".static::$active['db']['name'].".$table");
			break;
			
			case self::OP_ANA:
			$this->execute("ANALYZE TABLE ".static::$active['db']['name'].".$table");
			break;
			
			case self::OP_FLU:
			$this->execute("FLUSH TABLE $table");
			break;
			
			case self::OP_OPT:
			$this->execute("OPTIMIZE TABLE ".static::$active['db']['name'].".$table");
			break;
			
			case self::OP_REP:
			$this->execute("REPAIR TABLE ".static::$active['db']['name'].".$table");
			break;
			
			case null:
			return false;
			break;
		}
		return $this->result(DB::R_ASS);
	}
	
	/**
	 * Add a key to the a table
	 * @param string $field
	 * @param string $db
	 * @param string $table
	 * @param boolean $null
	 * @param string | int $default
	 * @return boolean
	 */
	public final function addFieldTo($field, $db=null, $table=null, $null=true, $default=0)
	{
		extract(static::getCorrectDbTable($db, $table));
		$ret_val = false;
		if(is_array($field))
		{
			$this->changeDbt($db, $table);
			$default = ($default == "NULL") ? "" : $default;
			$null = ($null === true) ? "" : (($default == "") ? "" : "NOT NULL DEFAULT $default");
			$field['Type'] = empty($null) ? $field['Type'] : $field['Type']." $null";
			$this->createCommand()->addColumn($table, $field['Field'], $field['Type'])->execute();
			$uni = (isset($field['Key']) && $field['Key'] == 'UNI') ? true : false;
			if($uni === true)
			{
				$this->createCommand()->addPrimaryKey($field['Field'], $table, $field['Field'])->execute();
			}
			$this->revertDbt();
			$ret_val = true;
		}
		else
		{
			$ret_val =  false;
		}
		$this->free();
		return $ret_val;
	}
	
	/**
	 * Get the tables in a given database or returns for the current database
	 * @param strign $db
	 * @return mixed table names
	 */
	public function getTables($db=NULL)
	{
		$ret_val = false;
		$this->setDb($db);
		return $this->connections->getSchema()->getTableNames();
	}

	/**
	 * Check to see if a table exists
	 * @param string $table
	 * @param string $db
	 * @return boolean exists
	 */
	public function tableExists($table, $db=NULL)
	{
		$this->changeDbt($db);
		$schema = $this->getTableSchema($table);
		$this->revertDbt();
		return empty($schema) ? false : true;
	}
	
	/**
	 * Get the info for tables in a db
	 * @param string $db
	 * @return mixed $tables
	 */
	public function getTablesStatus($db=NULL)
	{
		$tables = false;
		if(empty(static::$active['db']['name']))
		{
			static::$active['db']['name'] = $db;
		}
		switch(empty($db))
		{
			case FALSE:
			$tables = $this->getTables($db);
			break;
	
			default:
			$tables = $this->getTables(static::$active['db']['name']);
			break;
		}
		if($tables)
		{
			foreach($tables as $idx=>$t)
			{
				$ret_val[] = $this->getTableStatus($t);
			}
			$this->free();
			return $ret_val;
		}
		$this->free();
		return $tables;
	}


    /**
	 * Get the staus of a table
	 * @param string $table
	 * @return mixed
	 */
	public function getTableStatus($table=NULL)
	{
		$table = (is_null($table)) ? static::$active['table']['name'] : $table;
		return $this->connection->getTableSchema($table);
	}

    /**
	 * Get the fields for a table
	 * @param string $table
	 * @param string $field?
	 * @return mixed
	 */
	public function getFields($table=null, $field=null)
	{
		return $this->getTableStatus($table)->columns;
	} 
	
    /**
	 * Get the type of a field
	 * @param strgin $field
	 * @return yii\db\ColumnSchema
	 */
	public function getFieldType($field=null)
	{
		return $this->connection->getTableSchema(static::$active['table']['name'])->getColumn($field)->dbType;
	} 

    /**
	 * Get the length of a field
	 * @param string $field
	 * @return int
	 */
	public function getFieldLen($field=null)
	{
		return $this->connection->getTableSchema(static::$active['table']['name'])->getColumn($field)->size;
	} 
	
    /**
	 * does the give key exist in the table?
	 * @param string $key
	 * @param string $table
	 * @return boolean
	 */
	public function keyExists($key, $table=null)
	{
		$table = (is_null($table)) ? static::$active['table']['name'] : $table;
		return $this->connection->getTableSchema(static::$active['table']['name'])->getColumn($field) !== null;
	}

    /**
	 * Get the primary key name
	 * @param string $table
	 * @return string
	 */
	public function getPrimaryKey($table=null)
	{
		$ret_val = $this->connection->getTableSchema(is_null($table) ? static::$active['table']['name'] : $table);
		switch($ret_val instanceof \yii\db\TableSchema)
		{
			case true:
			$this->primary['key'] = $ret_val->primaryKey[0];
			break;
			
			default:
			$this->primary['key'] = null;
			break;
		}
		return $this->primary['key'];
	}

    /**
	 * Get the indexes for a table
	 * @param string $db
	 * @param string $table
	 * @return mixed
	 */
	public function getIndexes($db=null, $table=null)
	{
		$ret_val = false;
		extract(static::getCorrectDbTable($db, $table));
		if(!is_null($table) && !is_null($db))
		{
			$this->type = 'indexes';
			$this->execute("SHOW INDEXES FROM ".$db.".".$table."");
			$ret_val = $this->result(self::R_ASS, true, false);
			$this->free();
		}
		return $ret_val;
	}

    /**
	 * Get the table information for a table
	 * @param string $db
	 * @param string $table
	 * @return mixed
	 */
	public function getTableInfo($db=null, $table=null)
	{
		$ret_val = false;
		extract(static::getCorrectDbTable($db, $table));
		if(!is_null($table) && !is_null($db))
		{
			$this->type = 'tableStatus';
			$this->execute("SHOW TABLE STATUS FROM `".$db."` LIKE '".$table."'");
			$ret_val = $this->result(self::R_ASS);
			$this->free();
		}
		return $ret_val;
	}
	
	protected static function getCorrectDbTable($db, $table)
	{
		$ret_val = [
			'db' => $db,
			'table' => $table
		];
		switch(!is_null($db) && !is_null($table))
		{
			case true:
			$ret_val['table'] = $table;
			$ret_val['db'] = $db;
			break;
			
			default:
			$ret_val['table'] = is_null($table) ? static::$active['table']['name'] : $table;
			$ret_val['db'] = isset(static::$active['db']['name']) ? static::$active['db']['name'] : static::getDbName();
			break;
		}
		return $ret_val;
	}
	
	protected static function parseForJson($data)
	{
		$ret_val = (array) $data;
		foreach($data as $key=>$value)
		{
			$ret_val[$key] = (is_null($jdata = @json_decode($value, true))) ? $value : $jdata;
		}
		return $ret_val;
	}
	
	/**
	 * function to check a result resource
	 * @return boolean
	 */
	public function successful()
	{
		$ret_val = false;
		switch(isset($this->resource['prepared']) && is_object($this->resource['prepared']->pdoStatement))
		{
			case true:
			$ret_val = $this->resource['prepared']->pdoStatement->errorCode() == 00000;
			break;
		}
		return $ret_val;		
	}
	
	/**
	 * Executing of queries
	 * @param mixed $db_query
	 * @param string $db
	 * @param string $table
	 * @param boolean $prepared Is the SQL already prepared?
	 * @return boolean
	 */
	public function execute($db_query=null, $db=null, $table=null, $prepared=false)
	{
		$ret_val = false;
		$this->_rows = [];
		switch(is_null($db))
		{
			case true:
			switch(is_null($table))
			{
				case false:
				$this->setTable($table);
				break;
			}
			break;
			
			case false:
			$this->changeDbt($db, $table);
			break;
		}
		if(!static::$active['db']['name'])
		{
			$this->generateError('local', 'local');
			die("empty database");
		}
		$this->query = (empty($db_query)) ? $this->query : $db_query;
		switch($prepared)
		{
			case false: 
			$this->resource['prepared'] = $this->connection->createCommand($this->query);
			break;
		}
		if($this->collect_stats === true)
		{
			$this->stats['count']++;
			$start = microtime(true);
		}
		try
		{
        	$this->resource['transaction'] = $this->connection->beginTransaction();
			$this->resource['result'] = $this->resource['prepared']->query();
			switch(isset($this->type) && $this->type != null)
			{
				case true:
				$this->last_id[$this->type] = $this->connection->getLastInsertId();
				$this->type = null;
				$this->data = [];
				break;
			}
			$this->resource['transaction']->commit();
		}
		catch (PDOException $error)
		{
			if($this->_on['error']['die'] === true)
			{
				$this->error = true;
				$this->resource['transaction']->rollback();
				switch($this->resource['result']->errorCode())
				{
					case 1062:
					$this->generateError($this->resource['result']->errorCode(), $this->resource['result']->errorInfo());
					echo "<script language=\"javascript\" type=\"text/javascript\">
							alert(\"Duplicate entry for query:\n$this->query\nIn DB ".static::$active['db']['name']."\");
						</script>";
					return false;
					break;
					
					default:
					$error = $this->resource['result']->errorInfo();
					$error2 = $this->errorInfo();
					echo "Error executing query:<br>".static::$active['db']['name'].":".static::$active['table']['name']."<br> code: ". $error[1]." | ".$error2[1]."<br>message: ".$error[2]."<br>query: ".$error2[2];
	 				$this->printQuery();
					$this->generateError($this->resource['result']->errorCode(), $this->resource['result']->errorInfo());
					exit();
					break;
				}
			}
		}
		if($this->collect_stats === true)
		{
			$end = microtime(true);
			$this->stats['duration'] += ($end - $start);
			$caller = array_shift(array_slice(debug_backtrace(), 1, 1, true));
			$this->stats['queries'][] = [
				'query' => $this->query, 
				'start' => $start, 
				'end' => $end, '
				duration' => ($end - $start), 
				'called_from' => $caller['line'], 
				'called_by' => $caller['function'], 
				'in_file' => $caller['file']
			];
		}
		$this->_rows['rows'] = $this->resource['result']->getRowCount();
		if(preg_match('/^SELECT\s+(?:ALL\s+|DISTINCT\s+)?(?:.*?)\s+FROM\s+(.*)$/i', $this->query, $output) > 0) 
		{
// 			//doing this here ebcasue PDO doesn't return proper rows on SELECT
			//$row_query = "SELECT COUNT(*) FROM {$output[1]}";
			//$rows_stmt =  $this->connection->createCommand($row_query)->query($row_query);
			//switch(is_object($rows_stmt))
			//{
			//	case true:
			//	$this->_rows['max'] = $rows_stmt->getRowCount();
			//	break;
			//}
		}
		else
		{
			$this->_rows['max'] = $this->resource['result']->getRowCount();
		}
		if(!is_null($db))
		{
			$this->revertDbt();
		}
		$this->data = [];
		//return the query result
		return $ret_val;
	}
	
	public function run()
	{
		$this->build();
		if(empty($this->parts['where']))
			$this->where();
		$this->execute(null, null, null, true);
		return $this;
	}
	
	/**
	 * Function to return the SQL
	 * @param boolean $stringOnly Only return the query string and not the 
	 */
	public function getSql($unbound=false)
	{
		$ret_val = '';
		switch(is_object($this->resource['prepared']))
		{
			case true:
			$ret_val = $this->resource['prepared']->getRawSql();
			break;
		}
		if($unbound)
			$ret_val .= ' | Unbound: '.$this->query;
		return $ret_val;
	}
	
	public function printQuery()
	{
		Helper::pr($this->getSql());
	}
	
	public function getSqlStats($return_queries=false)
	{
		$ret_val = false;
		if($this->collect_stats)
		{
			$this->stats['time_average'] = $this->stats['duration']/$this->stats['count'];
			$ret_val = $this->stats;
			switch($return_queries)
			{
				case false:
				unset($ret_val['queries']);
				break;
			}
		}
		return $ret_val;
	}
	
	public function check($key, $data, $table=null, $db=null, $oper='=', $xor='AND', $just1=false, $fields=null, $p_query=false)
	{
		switch(is_array($key))
		{
			case true:
			if(sizeof($key) != sizeof($data))
			{
				$this->generateError(-1, "You specified incorrect lengths for the keys and data to check DB::check();");
			}
			break;
		}
		$ret_val = false;
		if(!empty($key))
		{
			$this->data['check']['keys'] = (is_array($key)) ? Helper::splitF($key, ',') : $key;
			if($db)
			{
				$this->changeDbt($db, $table);
			}
			switch($just1)
			{
				case true:
				$limit = 1;
				break;
				
				default:
				$limit = null;
				break;
			}
			switch($fields)
			{
				case null:
				$sel = $this->primary['key'];
				break;
				
				default:
				$sel = $fields;
				break;
			}
			if($p_query)
			{
				echo "<pre>";
					echo $this->query;
				echo "</pre>";
			}
			$this->select($sel, false)
				->where($key, $data, $oper, $xor)
				->orderBy($this->getCurPri())
				->direction(true)
				->limit($limit)
				->run();
			$ret_val = $this->result(DB::R_ASS, true, true);
			$rows = $this->rows();
			$this->free();
			if($db)
			{
				$this->revertDbt();
			}
			unset($this->data['check']);
			switch(1)
			{
				case empty($rows):
				$ret_val = false;
//				echo "Exiting check here 0<br>";
				break;
				
				case ($sel == $this->primary['key']):
				$ret_val = $ret_val[$sel];
// 				echo "Exiting check here 1 $sel<br>";
//				pr($ret_val);
				break;
				
				case sizeof($sel) == 1:
				$ret_val = $ret_val[$sel[0]][0];
				break;
				
				case (is_string($fields) && ($fields != '*')):
				$fields = explode(',', $fields);
				$replaceFunc = function ($value){
					return preg_replace(['/([`]{1,})/', '/([\W]{1,})/'], '', $value);
				};
				$fields = (array) $fields;
				foreach($fields as $field=>$value)
				{
					$field = explode('AS', $field);
					$ret_val = (sizeof($field) == 2) ? $ret_val[$replace($field[1])] : $ret_val[$replace($field[0])];
					$temp_val[$field] = $ret_val[$field];	
				}				
				$ret_val = $temp_val;
//				echo "Exiting check here 2 $fields<br>";
// 				pr($ret_val);
				break;
				
				default:
//				echo "Exiting check here 3<br>";
				break;
			}
// 			if($rows)
// 			{
// 				echo "Returning <br>";
// 				$this->pr($ret_val);
// 				echo "<br> for <br>";
// 				echo $this->query."<br>";
// 			}
		}
		return $ret_val;
	}
	
	//simply pass the necessary arguments and everything will be calculated
	/**
	function to select data from a database + table
		@param mixed $f fields to be selected
		@param boolean $d whether or not this should be a distinct select
		@return static
	*/
	
	public function select($f=null, $distinct=true, $union=false)
	{
		$this->type = 'select';
		$this->parts = [];
		extract($this->parseForJson(['f' => $f]));
		$this->parts = ["SELECT"];
		$this->parts[] = ($distinct === true) ? '' : "DISTINCT";
		$this->fields($f, $union);
		return $this;
	}
	
	/**
	* Simply pass the necessary arguments and everything will be calculated
	 * Function to insert data into a database + table
	 * @param mixed $f = fields to be matched against $d
	 * @param mixed $d = data to be inserted
	 * @param boolean $delay = whether transaction is high priority or should be delayed
	 * @param mixed $dupe = data to be changed on a duplicate
	 */
	public function insert($f, $d, $delay=false, $dupe=null)
	{
		$this->type = 'insert';
		$this->free();
		extract($this->parseForJson(['f' => $f, 'd' => $d]));
		$this->parts = ["INSERT"];
		$this->parts[] = ($delay === true) ? "DELAYED" : '';
		$this->fields($f, false, $d);
		if(sizeof($f) != sizeof($d))
		{
			$this->generateError(-1, "You specified comma separated values yet the lengths don't match DB::insert();");
		}
		//using pdo format here
		//set the orderby part of query
		switch(is_array($dupe))
		{
			case true:
			$this->data['dupe'] = $this->prepareConditional(
				['values' => array_keys($dupe)], 
				['values' => array_values($dupe), "prep" => '"', "app" => '"'],
				'=', ', ');
			$this->query[] = "ON DUPLICATE KEY UPDATE ".$this->condition;
			break;
		}
		return $this;
	}
	
	/**
	* Simply pass the necessary arguments and everything will be calculated
	 * @param mixed $f fields to be updated against $d
	 * @param mixed $d data to be updated
	 * @param mixed $c condition to be matched (key, data, xor, operand)
	 * @param int $lim how many to update?
	 * @return mixed
	 */
	public function update($f=null, $d=null)
	{
		switch(!is_null($d) && !empty($f))
		{
			case false:
			return false;
			throw new \Exception("Empty data and fields ($d, $f)");
			break;
		}
		$this->type = 'update';
		$this->free();
		extract($this->parseForJson(['f' => $f, 'd' => $d]));
		$this->parts = ["UPDATE"];
		$this->fields($f, null, $d);
		return $this;
	}
	
	/**
	* Simply pass the necessary arguments and everything will be calculated
	 * Named remove because of conflicts with delete
	 * @param mixed $f fields to be matched against $d
	 * @param mixed $d data to be matched
	 * @param string $operand operand to use
	 * @param string $xor connector for conditional requirements
	 * @return $this 
	 */
	public function remove($f, $d, $operand='=', $xor='AND')
	{
		$this->type = 'delete';
		$this->free();
		extract($this->parseForJson(['f' => $f, 'd' => $d]));
		$this->parts = ["DELETE"];
		$this->fields();
		$this->where($f, $d, $oper, $xor);
		return $this;
	}
	
	/**
	* Set the where part of the query
	 * @param mixed $key 
	 * @param mixed $data 
	 * @param mixed $operand
	 * @param mixed $xor  
	 * @return string the union parameter
	 */
	public function where($glue=null, $key=null, $data=null, $operand=null, $xor=null, $union=false)
	{
		$ret_val = '1';
		if(is_null($glue))
		{
			$args = func_get_args();
			//Remove the glue parameter
			array_shift($args);
			@list($key, $data, $operand, $xor, $union) = $args;
		}
		switch($union)
		{
			case false:
			switch(empty($key))
			{
				case false:
				$data = (array) $data;
				$pdo_data = $this->pdoKeys($key, 10000, $data);
				$bind_fields = array_keys($pdo_data['pdo_data']);
				$bind_data = array_values($pdo_data['pdo_data']);
				$ret_val = $this->prepareConditional(
					['values' => $pdo_data['data']['keys']], 
					['values' => array_keys($pdo_data['pdo_data'])], 
					$operand, $xor);
				if(!isset($this->data['bind']))
					$this->data['bind'] = ['fields' => [], 'data' => [], 'raw' => []];
				$this->data['bind']['fields'] = array_merge($this->data['bind']['fields'], $bind_fields);
				$this->data['bind']['data'] = array_merge($this->data['bind']['data'], $bind_data);
				$this->data['bind']['raw'] = array_merge($this->data['bind']['raw'], $pdo_data);
				break;
			}
			break;
		}
		$ret_val = is_null($glue) ? "WHERE ".$ret_val : $glue.' '.$ret_val;
		$this->data['where'][] = $ret_val;
		$this->parts[] = $ret_val;
		return $this;
	}
	
	public function andWhere($key=null, $data=null, $operand=null, $xor=null, $union=false)
	{
		$this->where('and', $key, $data, $operand, $xor, $union);
		return $this;
	}
	
	public function orWhere($key=null, $data=null, $operand=null, $xor=null, $union=false)
	{
		$this->where('or', $key, $data, $operand, $xor, $union);
		return $this;
	}
	
	/**
	* Set the fields and union part of the query
	 * @param mixed $f 
	 * @return string the union parameter
	 */
	public function fields($f=null, $union=null, $d=null)
	{
		$ret_val = '';
		$u = [
			"where" => "",
			"from" => "",
			"join" => "",
			"fields" => "",
			"values" => ""
		];
		switch($this->type)
		{
			case 'select':
			case 'delete':
			$u['from'] = ' FROM ';
			break;
			
			case 'insert':
			$u['from'] = ' INTO ';
			break;
			
			case 'update':
			$u['from'] = '';
			break;
			
			default:
			$u['from'] = ' ';
			break;
		}
		switch(sizeof((array)$f) == 0)
		{
			case true:
			switch($this->type)
			{
				case 'select':
				$this->data['fields'] = "*";
				$u['from'] .= "`".static::$active['db']['name'].'`.`'.static::$active['table']['name']."`";
				$u['fields'] = '*';
				break;
				
				case 'delete':
				$this->data['fields'] = "";
				$u['from'] .= "`".static::$active['db']['name'].'`.`'.static::$active['table']['name']."`";
				$u['fields'] = '';
				break;
			}
			break;
			
			default:
			switch($union)
			{
				case self::SEL_UNION_ALL:
				case self::SEL_UNION:
				$this->parts = [];
				$u['join'] = ' UNION ';
				$u['from'] = "";
				switch($union)
				{
					case self::SEL_UNION_ALL:
					$u['join'] .= 'ALL ';
					break;
				}
				break;
				
				default:
				$u['join'] = ',';
				$u['from'] .= "`".static::$active['db']['name'].'`.`'.static::$active['table']['name']."`";
				break;
			}
			switch($this->type)
			{
				case 'insert':
				//forumalate fields part of query
				$u['fields'] = " (".Helper::splitF($f, $u['join'], false, '').")";
				break;
				
				case 'select':
				switch(1)
				{
					case $f == 'primaryKey':
					$f = $this->primaryKey();
					break;
					
					default:
					$u['fields'] = Helper::splitF($f, $u['join'], false);
					break;
				}
				break;
				
				case 'update':
				$u['fields'] = ' SET ';
				break;
			}
			break;
		}
		$this->data['bind']['fields'] = [];
		$this->data['bind']['data'] = [];
		$this->data['bind']['raw'] = [];
		$this->data['values'] = "";
		switch(!empty($f) && !empty($d))
		{
			case true:
			$pdo_data = $this->pdoKeys($f, 0, $d);
			$bind_fields = array_keys($pdo_data['pdo_data']);
			$bind_data = array_values($pdo_data['pdo_data']);
			$this->data['bind']['fields'] = $bind_fields;
			$this->data['bind']['data'] = $bind_data;
			$this->data['bind']['raw'] = $pdo_data;
			switch($this->type)
			{
				case 'insert':
				switch(is_array($pdo_data) && (sizeof($pdo_data) >= 1))
				{
					case true:
					$values = [];
					$bind_fields = (array) $bind_fields;
					foreach($bind_fields as $fields)
					{
						$values[] = "VALUES(".implode(',', ($fields)).")";
					}
					$values = Helper::splitF($values, ', ');
					$this->data['values'] = $values;
					break;
				}
				break;
				
				case 'update':
				switch(is_array($pdo_data) && (sizeof($pdo_data) >= 1))
				{
					case true:
					$this->data['values'] =  $this->prepareConditional(['values' => $pdo_data['data']['keys']], ['values' => $bind_fields], '=', ',');;
					break;
				}
				break;
			}
			break;
		}
		$this->data['fields'] = $u['fields'];
		$this->data['from'] = $u['from'];
		$this->data['union'] = $u['where'];
		switch($this->type)
		{
			case 'insert':
			$this->parts[] = $this->data['from'].$this->data['fields'].$this->data['union'].$this->data['values'];
			break;
			
			case 'select':
			$this->parts[] = $this->data['fields'].$this->data['from'].$this->data['union'].$this->data['values'];
			break;
			
			case 'update':
			$this->parts[] = $this->data['from'].$this->data['fields'].$this->data['union'].$this->data['values'];
			break;
			
			case 'delete':
			$this->parts[] = $this->data['from'];
			break;
		}
		return $this;
	}
	
	/**
	* Set the orderby part of the query
	 * @param mixed $o
	 * @param boolean $union
	 * @param mixed $u
	 * @return string
	 */
	public function ordering($o, $union, $u=null)
	{
		$u = (array) $u;
		$u['orderby'] = isset($u['orderby']) ? $u['orderby'] : '';
		$u['groupby'] = isset($u['groupby']) ? $u['groupby'] : '';
		$u['gbfields'] = '';
		$u['obfields'] = '';
		switch(is_array($o) && isset($o['groupby']))
		{
			case true:
			$u['groupby'] = "GROUP BY";
			$u['gbfields'] = Helper::splitC(explode(',', $o['groupby']), $this->data['direction'], " ", ', ', false, false, false);
			unset($o['groupby']);
			$this->data['groupby'] = $u['gbfields'];
			break;
		}
		switch(1)
		{
			case $o == self::SEL_RAND:
			$o = ["RAND()"];
			break;
			
			case is_string($o):
			$o = explode(',', $o);
			break;
			
			case is_array($o):
			break;
			
			default:
			$o = empty($o) ? $this->primary['key'] : explode(',', $o);
			break;
		}
		switch(empty($o))
		{
			case false:
			$u['orderby'] .= "ORDER BY";
			$u['obfields'] = Helper::splitC($o, $this->data['direction'], " ", ', ', false, false, false);
			$this->data['orderby'] = $u['obfields'];
			break;
		}
		array_push($this->parts, $u['groupby'], $u['gbfields'], $u['orderby'], $u['obfields']);
		return $this;
	}
	
	/**
	* How should the results be ordered
	 * @param mixed $asc
	 * @return string
	 */
	public function direction($asc=null)
	{
		$ret_val = '';
		switch($asc)
		{
			case 'y':
			case '1':
			case 'true':
 			case true:
			$ret_val = 'ASC';
			break;
			
			case 'n':
			$ret_val = 'DESC';
			break;
			
			default:
			$ret_val = empty($a) ? 'DESC' : $a;
			break;
		}
		$this->data['direction'] = $ret_val;
		$this->parts[] = $ret_val;
		return $this;
	}
	
	/**
	* Set the limit
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit($limit=1, $offset=null)
	{
		$ret_val = '';
		switch(!empty($limit))
		{
			case true:
			switch(($offset == 0) && ($offset > -1) || !$offset)
			{
				case true:
				$ret_val = "LIMIT $limit";
				break;
				
				case false:
				$ret_val = "LIMIT $offset, $limit";
				break;
			}
			break;
		}
		$this->data['limit'] = $ret_val;
		$this->parts[] = $ret_val;
		return $this;
	}
	
	/**
	get the result set from a quesry in an array
		$mode = ftype of result to return
		$ar = should I return an array of all the results or just one result according to mode
		$group = should I group the results by each key or not?
		$us_val_keys = should the group value/keys use the value as the index?
	*/
	public function result($mode=null, $array=false, $group=false, $use_val_keys=false)
	{		
		$ret_val = false;
		$as = PDO::FETCH_NUM;
		$gr = ($group === true) ? PDO::FETCH_GROUP : null;
		$ar = ($array === true) ? null : null;
		if($this->successful())
		{
			switch($mode)
			{
				case self::R_OBJ:
				$mode = PDO::FETCH_OBJ;
				break;
	
				case self::R_ASS:
				$mode = PDO::FETCH_ASSOC;
				break;
				
				default:
				$mode = PDO::FETCH_NUM;
				break;
			}
			$this->resource['result']->setFetchMode($mode);
			switch($array)
			{
				case true:
				switch($group)
				{
					case true:
					$this->resource['result']->setFetchMode($mode|$gr);
					while($row = $this->resource['result']->read())
					{
						foreach($row as $key=>$val)
						{
							switch($use_val_keys === true)
							{
								case true:
								$ret_val[$key][$val] = $val;
								break;
								
								default:
								$ret_val[$key][] = $val;
								break;
							}
						}
					}
					$this->resource['result']->close();
					break;
					
					default:
					$ret_val = $this->resource['result']->readAll();
					$this->resource['result']->close();
					break;
				}
				break;
				
				case false:
				$ret_val = $this->resource['result']->read();
				break;
			}
		}
		return $ret_val;	
	}
	
	/**
	Prepare a DB::PDO_NOBIND string for use in SQL query...etc
		@param mixed $keys keys for use in conditional
		@param mixed $data data for use in conditional
		@param mixed $operand operand to use in conditional
		@param mixed $xor connector to use with conditional
		@return mixed
	*/
	public function prepareConditional($keys, $data, $operand='=', $xor=' AND ')
	{
		$ret_val = '';
		switch(empty($data['values']) && empty($keys['values']))
		{
			case true:
			break;
			
			default:
			switch(!empty($keys['values']) && empty($data['values']))
			{
				case true:
				$data = $keys;
				break;
			}
			$keys['values'] = (array) $keys['values'];
			$data['values'] = (array) $data['values'];
			$operand = is_array($operand) ? $operand : array_fill(0, sizeof($keys['values']), $operand);
			$xor = is_array($xor) ? $xor : array_fill(0, sizeof($keys['values']), $xor);
			$data['values'] = (!empty($data['prep']) || !empty($data['app'])) ? explode(',', $data['prep'].implode($data['app'].','.$data['prep'], $data['values']).$data['app']) : $data['values']; 
			$keys['values'] = (!empty($keys['prep']) || !empty($keys['app'])) ? explode(',', $keys['prep'].implode($keys['app'].','.$keys['prep'], $keys['values']).$keys['app']) : $keys['values'];
			$ret_val = Helper::splitC($keys['values'], $data['values'], $operand, $xor, false, false, false);
			break;
		}
		return $ret_val;
	}
	
	/**
	Get PDO version of keys
		@param mixed $array array to use for determining PDO keys
		@param int $start where to start?
		@param mixed $data alternative data to use
		@return mixed
	*/
	public function pdoKeys($array, $start=0, $data=null)
	{
		$ret_val = [
			'data' => ['keys' => [], 'data' => []], 
			'pdo_data' => []
		];
		switch(empty($array))
		{
			case false;
			$array = (array) $array;
			$counter = $start;
			foreach($array as $idx=>$val)
			{
				//do we need to bind multiple arrays to $array?
				$array_data = (isset($data[$idx]) && is_array($data[$idx])) ? $data[$idx] : false;
				switch($array_data === false)
				{
					case false:
					$_data = $this->pdoKeys($array, $data[$idx]);
					$ret_val['data']['keys'] = array_merge($ret_val['data']['keys'], $_data['data']['keys']);
					$ret_val['data']['data'] = array_merge($ret_val['data']['data'], $_data['data']['data']);
					$ret_val['pdo_data'] = array_merge($ret_val['pdo_data'], $_data['pdo_data']);
					break;
					
					default:
					switch(1)
					{
						case ($val === self::FLAG_ASIS) && ((is_array($data) && (!isset($data[$idx]) || ($data[$idx] === self::FLAG_NULL)))):
						$unique = self::FLAG_IGNORE.$counter;
						$ret_val['data']['keys'][] = $unique;
						$ret_val['data']['data'][] = @$data[$idx];
						$ret_val['pdo_data'][$unique] = (is_array($data)) ? @$data[$idx] : (isset($data) ? $data : null);
						break;
						
						case (substr($val, 0, strlen(self::FLAG_NULL)) === self::FLAG_NULL):
						case ($val === null):
						$ret_val['data'][self::FLAG_ASIS.@$data[$idx]] = self::FLAG_ASIS.@$data[$idx];
						break;
						
						case (substr($val, 0, strlen(self::PDO_NOBIND)) === self::PDO_NOBIND):
						$unique = substr($val, strlen(self::PDO_NOBIND));
						$unique_val = @substr($data[$idx], strlen(self::PDO_NOBIND));
						$ret_val['data']['keys'][] = $unique;
						$ret_val['data']['data'][] = $unique_val;
						$ret_val['pdo_data'][$unique_val] = self::PDO_NOBIND.$unique;
						break;
						
						case (substr(@$data[$idx], 0, strlen(self::PDO_NOBIND)) === self::PDO_NOBIND):
						$unique_val = substr($data[$idx], strlen(self::PDO_NOBIND));
						$ret_val['data']['keys'][] = $val;
						$ret_val['data']['data'][] = $unique_val;
						$ret_val['pdo_data'][$unique_val] = self::PDO_NOBIND.$val;
						break;
						
						default:
						if(is_array($val) || is_array($counter))
						{
							echo "Error occurring here";
							print_r($val);
							print_r($counter);
							exit;
						}
						$unique = ":".preg_replace('/[[:^alnum:]]/', '', $val)."_".$counter;
						$ret_val['data']['keys'][] = $val;
						$ret_val['data']['data'][] = @$data[$idx];
						$ret_val['pdo_data'][$unique] = (is_array($data)) ? @$data[$idx] : (isset($data) ? $data : null);
						$counter++;
						break;
					}
					break;
				}
			}
			break;
		}
		return $ret_val;
	}
	
	/**
	* Bind the data using PDO
	 * @param mixed $queries
	 * @param mixed $fields
	 * @param mixed $data
	 */
	public function pdoBindData($queries, $fields, $data)
	{
		$queries = (array) $queries;
		$fields = (array) $fields;
		$data = (array) $data;
		$this->resource['prepared'] = $this->connection->createCommand($this->query);
		$this->resource['prepared']->prepare();
		foreach($queries as $query)
		{
			switch((sizeof($fields) >= 1))
			{
				case true:
				foreach($fields as $idx=>$field)
				{
					switch(1)
					{
						case empty($field):
						case (substr($field, 0, strlen(self::FLAG_IGNORE)) == self::FLAG_IGNORE):
						case $data[$idx] === self::FLAG_NULL:
						case substr($data[$idx], 0, strlen(self::PDO_NOBIND)) == self::PDO_NOBIND:
						continue;
						break;
						
						default:
						switch(is_array($data[$idx]) && isset($data[$idx]['pdo_opts']))
						{
							case true:
							$opts = $data[$idx]['pdo_opts'];
							$data[$idx] = $data[$idx]['data'];
							break;
							
							default:
							$opts = PDO::PARAM_STR;
							break;
						}
						$this->resource['prepared']->bindParam($field, $data[$idx], @$opts);
						break;
					}
				}
				break;
			}
		}
	}
	
	//function to get ows from result
	public function rows($max=false)
	{
		switch($max)
		{
			case true:
			$ret_val = $this->_rows['max'];
			break;

			default:
			$ret_val = $this->_rows['rows'];
			break;
		}
		return $ret_val;
	}
	
	///////////////////Protected functions\\\\\\\\\\\\\\\\\\\\\\
	

	/**
	function to print a nicely formated error message
		@param resource $loc_link mysql resource link
		@param string $loc_msg messageto use for error
		@return boolean */
	protected static function generateError($loc_link, $loc_msg)
	{
		$trace = '';
		$error = array_reverse(debug_backtrace(true));
		foreach($error as $idx=>$err)
		{
			$trace .= "\nStep $idx:  ".$err['function']."\n";
			foreach($err as $key=>$e)
			{
				switch($key)
				{
					case "class":
					case "type":
					case 'line':
					case 'file':
					$trace .= "$key: $e\n";
					break;
					
					case 'args':
					$args = [];
					ob_start();
					foreach($e as $num=>$arg)
					{
						$arg = (array) $arg;
						$args[] = $arg;
					}
					$trace .= wordwrap("$key: ".@implode(', ', $this->var_dump_string($args))."\n", 128);
					break;
				}
			}
		}
		$data = wordwrap("\n\nDB: ".static::$active['db']['name']."\nTable: \t".static::$active['table']['name']."\n<pre>".$trace."</pre>", 160, "\n");
		$data_html = nl2br($data);
		if(class_exists("Logger"))
		{
			$log = new Logger("DB-Log");
			$log->write($data);
			$log->endLog();
		}
		else
		{
			echo $data;
			echo "Class Logger doesn't exist<br>";
		}
		$this->free();
		$this->query = "";
		if(@$this->_on['error']['backtrace'] !== false)
		{
			echo "<pre>";
				//print_r(debug_backtrace());
			echo "<pre>";
		}
		return true;
	}

	//lim_off is the variable containing the offset and limit constraints
	protected function maxRows($loc_query=null, $lim_off=null, $prepared=true, $c=null)
	{
		switch($loc_query == null)
		{
			case true:
			$loc_query = "SELECT COUNT(*) FROM ".static::$active['db']['name'].".".static::$active['table']['name']." WHERE ";
			switch(empty($c['key']))
			{
				case true:
				$loc_query .= "1";
				break;
				
				default:
				$c['data'] = (array) $c['data'];
				$pdo_data = $this->pdoKeys($c['key'], 0, $c['data']);
				$bind_fields = array_keys($pdo_data['pdo_data']);
				$bind_data = array_values($pdo_data['pdo_data']);
				$loc_query .= $this->prepareConditional(['values' => $pdo_data['data']['data']], ['values' => $pdo_data['data']['keys']], @$c['operand'], @$c['xor']);
				$this->pdoBindData($loc_query, @$bind_fields, @$bind_data);
				$prepared = true;
				break;
			}
			break;
			
			default:
			break;
		}
		$loc_query = (array) $loc_query;
		$loc_maxRows = 0;
		foreach($loc_query as $query)
		{
			switch($prepared)
			{
				case false:
				$this->resource['result'] = $this->createCommand($this->connection);
				break;
			}
			switch(empty($query))
			{
				case true:
				$this->resource['result']->execute();
				$rel_res = $this->resource['result']->fetchAll();
				$this->best_match = ($rel_res[0]["COUNT(*)"] == 0) ? 0 : @$rel_res[0]['best_match'];
				$loc_maxRows += @$rel_res[0][0];
				break;
				
				default:
				$this->resource['result']->execute();
				$rel_res = $this->resource['result']->fetchAll();
				$this->best_match = ($rel_res[0]["COUNT(*)"] == 0) ? 0 : @$rel_res[0]['best_match'];
				$loc_maxRows += @$rel_res[0][0];
				break;
			}
		}
		$this->_rows['max'] = $loc_maxRows;
		return $this->_rows['max'];
	}
	
	/**
	------------------------
		Private Functions
	------------------------*/

	/**
	* Release the result information
	 */
	private function free()
	{
		if($this->successful())
		{
			$this->resource['result']->close();
		}
	}
	
	/**
	* Release the result information
	 */
	private function close()
	{
		$this->connection = null;
		$this->free();
	}
	
	/**
	* Handle connection to the database
	 */
	private function connect()
	{
		switch(static::getDbName())
		{
			case static::$active['db']['name']:
			$this->connection = \Yii::$app->db;
			break;
			
			default:
			$this->connection = new Connection([
				'dsn' => static::$active['driver'].":host=".$this->host.";dbname=".static::$active['db']['name'],
				'username' => $this->username,
				'password' => $this->_password
			]);
			$this->connection->open();
			break;
		}
	}
	
	/**
	* Return the unencrypted password for this current host and user
	 */
	public function getPassword()
	{
		$ret_val = base64_decode(convert_uudecode($this->_password));
		return !$ret_val ? $this->_password : $ret_val;
	}
	
	/**
	* Set up the max query
	 */
	private function maxQuery()
	{
		$this->maxQuery = ["SELECT COUNT(*)"];
		$this->maxQuery[] = is_null($this->score) ? '' : ",MAX($this->score) AS best_match";
		$this->maxQuery[] = "FROM ".static::$active['db']['name'].'.'.static::$active['table']['name'].$this->data['where'];
	}
	
	/**
	* Bind the quey using PDO binding
	 * @param mixed $queries
	 */
	public function build($withMax=false)
	{
		$this->query = '';
		$this->populateQuery();
		$this->query = implode(' ', $this->query);
		$queries = [
			'data' => $this->query
		];
		if($withMax)
		{
			$queries['max'] = implode(' ', $this->maxQuery);
		}
		foreach($queries as $type=>$query)
		{
			$this->pdoBindData($query, $this->data['bind']['fields'], $this->data['bind']['data']);
		}
	}
	
	private function populateQuery()
	{
		foreach($this->parts as $type=>$part)
		{
			switch(gettype($part))
			{
				case 'string':
				$this->query[] = $part;
				break;
			}
		}
		$this->query = array_filter($this->query);
	}
}
?>
