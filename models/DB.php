<?php

namespace nitm\module\models;

use PDO;
use yii\db\Connection;
use yii\db\Query;
use yii\base\Behavior;
use nitm\module\helpers\Helper;

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
	
	protected $score = null;
	protected $connection;
	protected $resource;
	protected $quoter = '"';
	protected $query = [
		"stats" => [
			'count' => 0, 'time_total' => 0, 'queries' => []
		], 
		"string" => null
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
		$this->query['stats']['start'] = microtime();
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
		$behaviors = array(
				"Behavior" => array(
					"class" => \yii\base\Behavior::className(),
				),
			);
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
	 * Set the error handling behavior
	 * @param boolean $die
	 * @param boolean $backtrace
	 * @return boolean
	 */
	public function dieOnError($die=false, $backtrace=false)
	{
		$this->_on['erorr'] = array();
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
	
	
	public function getCurPri()
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
		$data = is_array($data) ? $data : array($data);
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
			static::$old['db']['name'] = static::$active['db']['name'];
			static::$active['db']['name'] = $db;
			$this->setDb(static::$active['db']['name']);
		}
		else
		{
			static::$old['db']['name'] = null;
		}
		if(!empty($table))
		{
			static::$old['table']['name'] = static::$active['table'];
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
		static::$old['db'] = array();
		static::$old['table'] = array();
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
		$db = (is_null($db)) ? static::$active['db']['name'] : $db;
		$table = (is_null($table)) ? static::$active['table'] : $table;
		$ret_val = false;
		if(is_array($field))
		{
			$this->changeDbt($db, $table);
			$default = ($default == "NULL") ? "" : $default;
			$null = ($null === true) ? "" : (($default == "") ? "" : "NOT NULL DEFAULT $default");
			$field['Type'] = empty($null) ? $field['Type'] : $field['Type']." $null";
			$this->addColumn($table, $field['Field'], $field['Type']);
			$uni = ($field['Key'] == 'UNI') ? true : false;
			if($uni === true)
			{
				$this->addPrimaryKey($field['Field'], $table, $field['Field']);
			}
			$this->performOp(DB::OP_FLU);
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
		$table = (empty($table)) ? static::$active['table']['name'] : $table;
		$db = (empty($db)) ? static::$active['db']['name'] : $db;
		if(!is_null($table) && !is_null($db))
		{
			$this->execute("SHOW INDEX FROM ".$db.".".$table."");
			$ret_val = $this->result(self::R_ASS, true, false);
			$this->free();
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
		$this->_rows = array();
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
		$this->query['string'] = (empty($db_query)) ? $this->query['string'] : $db_query;
		switch($prepared)
		{
			case false: 
			$this->resource['prepared'] = $this->connection->createCommand($this->query['string']);
			break;
		}
		if($this->collect_stats === true)
		{
			$this->query['stats']['count']++;
			$start = microtime(true);
		}
		try
		{
        	$this->resource['transaction'] = $this->connection->beginTransaction();
			$this->resource['result'] = $this->resource['prepared']->query();
			switch($this->query['type'] != null)
			{
				case true:
				$this->last_id[$this->query['type']] = $this->connection->getLastInsertId();
				$this->query['type'] = null;
				$this->query['data'] = array();
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
			$this->query['stats']['time_total'] += ($end - $start);
			$caller = array_shift(array_slice(debug_backtrace(), 1, 1, true));
			$this->query['stats']['queries'][] = array('query' => $this->query['string'], 'start' => $start, 'end' => $end, 'duration' => ($end - $start), 'called_from' => $caller['line'], 'called_by' => $caller['function'], 'in_file' => $caller['file']);
		}
		$this->_rows['rows'] = $this->resource['result']->getRowCount();
		if(preg_match('/^SELECT\s+(?:ALL\s+|DISTINCT\s+)?(?:.*?)\s+FROM\s+(.*)$/i', $this->query['string'], $output) > 0) 
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
		$this->query['data'] = null;
		//return the query result
		return $ret_val;
	}
	
	public function getQuery()
	{
		$ret_val = $this->query['string'];
		switch(is_object($this->resource['prepared']))
		{
			case true:
			$ret_val .= $this->resource['prepared']->getRawSql();
			break;
		}
		return $ret_val;
	}
	
	public function printQuery()
	{
		Helper::pr($this->getQuery());
	}
	
	public function getQueryStats($return_queries=false)
	{
		$ret_val = false;
		if($this->collect_stats)
		{
			$this->query['stats']['time_average'] = $this->query['stats']['time_total']/$this->query['stats']['count'];
			$ret_val = $this->query['stats'];
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
			$this->query['data']['check']['keys'] = (is_array($key)) ? Helper::splitF($key, ',') : $key;
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
			$this->select($sel, false, array('key' => $key, 'data' => $data, 'operand' => $oper, 'xor' => $xor), $this->getCurPri(), true, $limit, true);
			$ret_val = $this->result(DB::R_ASS, true, true);
			$rows = $this->rows();
			$this->free();
			if($db)
			{
				$this->revertDbt();
			}
			unset($this->query['data']['check']);
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
				switch(is_array($fields) && sizeof($fields) >= 2)
				{
					case true:
					$temp_val = array();
					foreach($fields as $field=>$value)
					{
						$field = explode('AS', $field);
						$ret_val = (sizeof($field) == 2) ? $ret_val[preg_replace(array('/([`]{1,})/', '/([\W]{1,})/'), '', $field[1])] : $ret_val[preg_replace(array('/([`]{1,})/', '/([\W]{1,})/'), '', $field[0])];
						$temp_val[$field] = $ret_val[$field];	
					}				
					$ret_val = $temp_val;
					break;
					
					default:
					$fields = explode('AS', $fields[0]);
					$ret_val = (sizeof($fields) == 2) ? $ret_val[preg_replace(array('/([`]{1,})/', '/([\W]{1,})/'), '', $fields[1])] : $ret_val[preg_replace(array('/([`]{1,})/', '/([\W]{1,})/'), '', $fields[0])];
					$ret_val = $ret_val[0];
					break;
				}
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
// 				echo $this->query['string']."<br>";
// 			}
		}
		return $ret_val;
	}
	
	//simply pass the necessary arguments and everything will be calculated
	/**
	function to select data from a database + table
		@param mixed $f fields to be selected
		@param boolean $d whether or not this should be a distinct select
		@param mixed $c the conditions for selecting data
		@param mixed $o order the results by
		@param mixed $a ascending or descing order?
		@param mixed $lim the manimum number of results to return
		@param boolean $esc escape arguments
		@param int $offset start selecting from here
		@param boolean $union Should a union be used to merge multiple selects?
		@param mixed $max maximum number of results to return
		@return mixed
	*/
	
	public function select($f=null, $d=false, $c=null, $o=null, $a=null, $lim=null, $esc=true, $offset=0, $max=false, $union=false)
	{
		$this->query['type'] = 'select';
		$this->query['data'] = array();
		$f = (is_null($jdata = @json_decode($f, true))) ? $f : $jdata;
		$d = (is_null($jdata = @json_decode($d, true))) ? $d : $jdata;
		$c = (is_null($jdata = @json_decode($c, true))) ? $c : $jdata;
		$this->query['string'] = "SELECT";
		$this->query['string'] .= ($d === true) ? '' : " DISTINCT ";
		$u = $this->fields($f, $union);
		$this->where(@$c['key'], @$c['data'], @$c['operand'], @$c['xor'], $union);
		//set the ascending or descinding value of query
		$this->direction($a);
		$this->ordering($o, $union, $u);
		//do the max query before doing the limit
		//$this->maxQuery();
		$this->limit($lim, $offset);
		$queries = array("data" => $this->query['string']);
		$this->bindQuery($queries);
		$this->execute(null, null, null, true);
		$ret_val = ($this->_rows['rows'] >= 1) ? true : false;
		return $ret_val;
	}
	
	/**
	* Simply pass the necessary arguments and everything will be calculated
	 * Function to insert data into a database + table
	 * @param mixed $f = fields to be matched against $d
	 * @param mixed $d = data to be inserted
	 * @param boolean $delay = whether transaction is high priority or should be delayed
	 * @param mixed $dupe = data to be changed on a duplicate
	 * @param boolean $esc Should the data be escaped?
	 */
	public function insert($f, $d, $delay=false, $dupe=null, $esc=true)
	{
		$this->query['type'] = 'insert';
		$this->free();
		$f = (is_null($jdata = @json_decode($f, true))) ? $f : $jdata;
		$d = (is_null($jdata = @json_decode($d, true))) ? $d : $jdata;
		$esc = ($esc == true) ? true : false;
		$this->query['string'] = "INSERT";
		$this->query['string'] .= ($delay === true) ? " DELAYED " : '';
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
			$this->query['data']['dupe'] = $this->prepareConditional(array('values' => array_keys($dupe)), array('values' => array_values($dupe), "prep" => '"', "app" => '"'), '=', ', ');
			$this->query['string'] .= " ON DUPLICATE KEY UPDATE ".$this->condition;
			break;
		}
		
		//spare parts
		$queries = array("data" => $this->query['string']);
		$this->bindQuery($queries);
		$this->execute(null, null, null, true);
		$ret_val = ($this->_rows['rows'] >= 1) ? true : false;
		return $ret_val;
	}
	
	/**
	* Simply pass the necessary arguments and everything will be calculated
	 * @param mixed $f fields to be updated against $d
	 * @param mixed $d data to be updated
	 * @param mixed $c condition to be matched (key, data, xor, operand)
	 * @param int $lim how many to update?
	 * @return mixed
	 */
	public function update($f, $d, $c, $lim=1, $esc=true)
	{
		switch(!is_null($d) && !empty($f))
		{
			case false:
			return false;
			return "empty data and fields ($d, $f)";
			break;
		}
		$this->query['type'] = 'update';
		$this->free();
		$f = (is_null($jdata = @json_decode($f, true))) ? $f : $jdata;
		$d = (is_null($jdata = @json_decode($d, true))) ? $d : $jdata;
		$c = (is_null($jdata = @json_decode($c, true))) ? $c : $jdata;
		$this->query['string'] = "UPDATE ";
		$f = is_array($f) ? $f : array($f);
		$this->fields($f, null, $d);
		$this->where(@$c['key'], @$c['data'], @$c['operand'], @$c['xor']);
		$this->limit($lim);
		
		//spare parts
		$queries = array("data" => $this->query['string']);
		$this->bindQuery($queries);
		$this->execute(null, null, null, true);
		$ret_val = ($this->_rows['rows'] >= 1) ? true : false;
		return $ret_val;
	}
	
	/**
	* Simply pass the necessary arguments and everything will be calculated
	 * Named remove because of conflicts with delete
	 * @param mixed $f fields to be matched against $d
	 * @param mixed $d data to be matched
	 * @param int $lim delete limit
	 * @param string $oper operand to use
	 * @param string $xor connector for conditional requirements
	 * @return boolean */
	public function remove($f, $d, $table=null, $db=null, $lim=1, $oper='=', $xor=' AND ')
	{
		$this->query['type'] = 'delete';
		$this->free();
		$f = (is_null($jdata = @json_decode($f, true))) ? $f : $jdata;
		$d = (is_null($jdata = @json_decode($d, true))) ? $d : $jdata;
		$this->query['string'] = "DELETE ";
		$f = is_array($f) ? $f : array($f);
		$d = is_array($d) ? $d : array($d);
		$this->fields();
		$this->where($f, $d, $oper, $xor);
		$this->limit($lim);
		
		//spare parts
		$queries = array("data" => $this->query['string']);
		$this->bindQuery($queries);
		$this->execute(null, null, null, true);
		$ret_val = ($this->_rows['rows'] >= 1) ? true : false;
		return $ret_val;
	}
	
	/**
	* Set the where part of the query
	 * @param mixed $key 
	 * @param mixed $data 
	 * @param mixed $operand
	 * @param mixed $xor  
	 * @return string the union parameter
	 */
	public function where($key=null, $data=null, $operand=null, $xor=null, $union=false)
	{
		$ret_val = '';
		switch($union)
		{
			case false:
			switch(empty($key))
			{
				case true:
				$ret_val = ' WHERE 1';
				break;
				
				default:
				$data = is_array($data) ? $data : array($data);
				$pdo_data = $this->pdoKeys($key, 10000, $data);
				$bind_fields = array_keys($pdo_data['pdo_data']);
				$bind_data = array_values($pdo_data['pdo_data']);
				$ret_val = " WHERE ".$this->prepareConditional(array('values' => $pdo_data['data']['keys']), array('values' => array_keys($pdo_data['pdo_data'])), $operand, $xor);
				$this->query['data']['bind']['fields'] = array_merge($this->query['data']['bind']['fields'], $bind_fields);
				$this->query['data']['bind']['data'] = array_merge($this->query['data']['bind']['data'], $bind_data);
				$this->query['data']['bind']['raw'] = array_merge($this->query['data']['bind']['raw'], $pdo_data);
				break;
			}
			break;
			
			default:
			$ret_val = '';
			break;
		}
		$this->query['data']['where'] = $ret_val;
		$this->query['string'] .= $ret_val;
		return $ret_val;
	}
	
	/**
	* Set the fields and union part of the query
	 * @param mixed $f 
	 * @return string the union parameter
	 */
	public function fields($f=null, $union=null, $d=null)
	{
		$ret_val = '';
		$u = array("where" => "",
		"from" => "",
		"join" => "",
		"fields" => "",
		"values" => "");
		switch($this->query['type'])
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
		switch(empty($f))
		{
			case true:
			switch($this->query['type'])
			{
				case 'select':
				$this->query['data']['fields'] = "*";
				$u['from'] .= "`".static::$active['db']['name'].'`.`'.static::$active['table']['name']."`";
				$u['fields'] = '*';
				break;
				
				case 'delete':
				$this->query['data']['fields'] = "";
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
				$this->query['string'] = "";
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
			switch($this->query['type'])
			{
				case 'insert':
				//forumalate fields part of query
				$u['fields'] = " (".Helper::splitF($f, $u['join'], false, '').")";
				break;
				
				case 'select':
				$u['fields'] = Helper::splitF($f, $u['join'], false);
				break;
				
				case 'update':
				$u['fields'] = ' SET ';
				break;
			}
			break;
		}
		$this->query['data']['bind']['fields'] = array();
		$this->query['data']['bind']['data'] = array();
		$this->query['data']['bind']['raw'] = array();
		$this->query['data']['values'] = "";
		switch(!empty($f) && !empty($d))
		{
			case true:
			$pdo_data = $this->pdoKeys($f, 0, $d);
			$bind_fields = array_keys($pdo_data['pdo_data']);
			$bind_data = array_values($pdo_data['pdo_data']);
			$this->query['data']['bind']['fields'] = $bind_fields;
			$this->query['data']['bind']['data'] = $bind_data;
			$this->query['data']['bind']['raw'] = $pdo_data;
			switch($this->query['type'])
			{
				case 'insert':
				switch(is_array($pdo_data) && (sizeof($pdo_data) >= 1))
				{
					case true:
					$values = array();
					$bind_fields = is_array($bind_fields[0]) ? $bind_fields : array($bind_fields);
					foreach($bind_fields as $fields)
					{
						$values[] = " VALUES(".implode(',', ($fields)).")";
					}
					$values = Helper::splitF($values, ', ');
					$this->query['data']['values'] = $values;
					break;
				}
				break;
				
				case 'update':
				switch(is_array($pdo_data) && (sizeof($pdo_data) >= 1))
				{
					case true:
					$this->query['data']['values'] =  $this->prepareConditional(array('values' => $pdo_data['data']['keys']), array('values' => $bind_fields), '=', ',');;
					break;
				}
				break;
			}
			break;
		}
		$this->query['data']['fields'] = $u['fields'];
		$this->query['data']['from'] = $u['from'];
		$this->query['data']['union'] = $u['where'];
		switch($this->query['type'])
		{
			case 'insert':
			$ret_val = $this->query['data']['from'].$this->query['data']['fields'].$this->query['data']['union'].$this->query['data']['values'];
			break;
			
			case 'select':
			$ret_val = $this->query['data']['fields'].$this->query['data']['from'].$this->query['data']['union'].$this->query['data']['values'];
			break;
			
			case 'update':
			$ret_val = $this->query['data']['from'].$this->query['data']['fields'].$this->query['data']['union'].$this->query['data']['values'];
			break;
			
			case 'delete':
			$ret_val = $this->query['data']['from'];
			break;
		}
		$this->query['string'] .= $ret_val;
		return $ret_val;
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
		$ret_val = '';
		$u = is_array($u) ? $u : array($u);
		$u['orderby'] = isset($u['orderby']) ? $u['orderby'] : '';
		$u['groupby'] = isset($u['groupby']) ? $u['groupby'] : '';
		$u['gbfields'] = '';
		$u['obfields'] = '';
		switch(is_array($o) && isset($o['groupby']))
		{
			case true:
			$u['groupby'] = "GROUP BY";
			$u['gbfields'] = Helper::splitC(explode(',', $o['groupby']), $this->query['data']['direction'], " ", ', ', false, false, false);
			unset($o['groupby']);
			$this->query['data']['groupby'] = $u['gbfields'];
			break;
		}
		switch(1)
		{
			case $o == self::SEL_RAND:
			$o = array("RAND()");
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
			$u['obfields'] = Helper::splitC($o, $this->query['data']['direction'], " ", ', ', false, false, false);
			$this->query['data']['orderby'] = $u['obfields'];
			break;
		}
		$ret_val = $u['groupby']." ".$u['gbfields']." ".$u['orderby']." ".$u['obfields']." ";
		$this->query['string'] .= " $ret_val ";
		return $ret_val;
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
		$this->query['data']['direction'] = $ret_val;
		return $ret_val;
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
				$ret_val .= " LIMIT $limit";
				break;
				
				case false:
				$ret_val .= " LIMIT $offset, $limit";
				break;
			}
			break;
		}
		$this->query['data']['limit'] = $ret_val;
		$this->query['string'] .= $ret_val;
		return $ret_val;
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
			$keys['values'] = is_array($keys['values']) ? $keys['values'] : array($keys['values']);
			$data['values'] = is_array($data['values']) ? $data['values'] : array($data['values']);
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
		$ret_val = array('data' => array('keys' => array(), 'data' => array()), 
		'pdo_data' => array());
		switch(empty($array))
		{
			case false;
			$array = is_array($array) ? $array : array($array);
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
		$queries = is_array($queries) ? $queries : array($queries);
		$fields = is_array($fields) ? $fields : array($fields);
		$data = is_array($data) ? $data : array($data);
		$this->resource['prepared'] = $this->connection->createCommand($this->query['string']);
		$this->resource['prepared']->prepare();
		foreach($queries as $query)
		{
			switch((sizeof($fields) >= 1) && @is_array($fields))
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
					$args = array();
					ob_start();
					foreach($e as $num=>$arg)
					{
						$arg = is_array($arg) ? $arg : array($arg);
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
		$this->query['string'] = "";
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
				$c['data'] = is_array($c['data']) ? $c['data'] : array($c['data']);
				$pdo_data = $this->pdoKeys($c['key'], 0, $c['data']);
				$bind_fields = array_keys($pdo_data['pdo_data']);
				$bind_data = array_values($pdo_data['pdo_data']);
				$loc_query .= $this->prepareConditional(array('values' => $pdo_data['data']['data']), array('values' => $pdo_data['data']['keys']), @$c['operand'], @$c['xor']);
				$this->pdoBindData($loc_query, @$bind_fields, @$bind_data);
				$prepared = true;
				break;
			}
			break;
			
			default:
			break;
		}
		$loc_query = is_array($loc_query) ? $loc_query : array($loc_query);
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
		$this->connection = new Connection([
			'dsn' => static::$active['driver'].":host=".$this->host.";dbname=".static::$active['db']['name'],
			'username' => $this->username,
			'password' => $this->_password
		]);
		$this->connection->open();
	}
	
	/**
	* Return the unencrypted password for this current host and user
	 */
	private function getPassword()
	{
		return base64_decode(convert_uudecode($this->_password));
	}
	
	/**
	* Set up the max query
	 */
	private function maxQuery()
	{
		$this->query['max'] = "SELECT COUNT(*)";
		$this->query['max'] .= is_null($this->score) ? '' : ",MAX($this->score) AS best_match ";
		$this->query['max'] .= " FROM ".static::$active['db']['name'].'.'.static::$active['table']['name'].$this->query['data']['where'];
	}
	
	/**
	* Bind the quey using PDO binding
	 * @param mixed $queries
	 */
	private function bindQuery($queries, $bind_fields=null, $bind_data=null)
	{
		foreach($queries as $type=>$query)
		{
			$this->pdoBindData($query, $this->query['data']['bind']['fields'], $this->query['data']['bind']['data']);
		}
	}
}
?>
