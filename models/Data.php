<?php

namespace nitm\module\models;

use Yii;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use ReflectionClass;

/**
 * Base Data getter/operation class
 * @package common\models
 *
 * @property integer $id
 * @property date $added
 * @property date $edited
 * @property author $author
 * @property integer $editor
 * @property integer $edits
 * @property array $settings
 * @property array $filter
 */
 
class Data extends ActiveRecord implements \nitm\module\interfaces\DataInterface
{
	use \nitm\module\traits\Configer,
	\nitm\module\traits\Query,
	\nitm\module\traits\Relation;
	
	//public members
	public $unique;
	public $author_hr;
	public $editor_hr;
	public $added_hr;
	public $edited_hr;
	public $data;
	public $settings;
	public $queryFilters = [];
	public $filter;
	public static $active = [
		'driver' => 'mysql',
		'db' => [
			'name' => null
		],
		'table' => [
			'name' => null
		]
	];
	public static $old = [
		'db' => [
			'name' => null
		],
		'table' => [
			'name' => null
		]
	];
	
	protected $success;
	protected static $connection;
	protected static $is;
	protected static $tableName;
	protected static $supported;
	protected static $self;
	
	//private members
	
	public function __construct($init=true)
	{
		$this->init();
		static::$self = $this;
	}
	
	//public function  to print array data properly
	public static function pr($data)
	{
		foreach(func_get_args() as $data)
		{
			echo "<pre>".print_r($data, true)."</pre>";
		}
	}

	public function init()
	{
		parent::init();
	}
	
	public function rules()
	{
		return [
			[['filter'], 'required', 'on' => ['filtering']],
		];
	}
	
	public function scenarios()
	{
		return [
			'filter' => ['filter'],
			'create' => ['author'],
			'update' => ['editor'],
			'deleted' => ['unique']
		];
	}
	
	public function behaviors()
	{
		$behaviors = [
           // \yii\base\Behavior::className()
		];
		$has = is_array(static::has()) ? static::has() : [];
		foreach($has as $name=>$dataProvider)
		{
			switch($name)
			{
				case 'edits':
				$behaviors['edits'] = [
					'class' => \yii\behaviors\AttributeBehavior::className(),
					'attributes' => [
						ActiveRecord::EVENT_BEFORE_UPDATE => ['edits'],
					],
					'value' => function ($event) {
						switch($event->sender->HasProperty('edits'))
						{
							case true:
							return $event->sender->edits++;
							break;
						}
					},
				];
				break;
				
				case 'author':
				case 'editor':
				//Setup author/editor
				$behaviors["blamable"] = [
				'class' => \yii\behaviors\BlameableBehavior::className(),
					'attributes' => [
						ActiveRecord::EVENT_BEFORE_INSERT => 'author',
						ActiveRecord::EVENT_BEFORE_UPDATE => 'editor',
					],
				];
				break;
				
				case 'updated_at':
				case 'created_at':
				//Setup timestamping
				$behaviors['timestamp'] = [
					'class' => \yii\behaviors\TimestampBehavior::className(),
					'attributes' => [
						ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
						ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
					],
				];
				break;
				
				default:
				//setup special attribute behavior
				switch(is_array($dataProvider))
				{
					case true:
					$behaviors[$name] = $dataProvider;
					break;
				}
				break; 
			}
		}
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	/*
	 * Function to detemine what dictates a record as active
	 * @param ActiveQuery $query
	 */
	public static function active()
	{
		return [];
	}
	
	public static function tableName()
	{
		return static::$tableName;
	}
	
	/*
	 * Check to see if somethign is supported
	 * @param mixed $what
	 */
	public static function isSupported($what)
	{
		$thisSupports = [$what => false];
		switch(is_array(static::$supported))
		{
			case true:
			$thisSupports = static::$supported;
			break;
			
			default:
			$thisSupports = @static::$self->settings[static::isWhat()]['supported'];
			break;
		}
		return (isset($thisSupports[$what]) &&  ($thisSupports[$what] == true));
	}
	
	public static function filters()
	{
		return [
				'author' => null, 
				'editor' => null,
				'status' => null,
				'order' => null,
				'order_by' => null,
				'index_by' => null,
				'show' => null,
				'limit' => null,
				'unique' => null,
		];
	}
	
	public static function has()
	{
		return [
			'added' => null,
			'edited' => null,
		];
	}
	
	/*
	 * Function to return the columns to be selected
	 *
	 */
	public static function columns()
	{
		return array_keys(static::getTableSchema()->columns);
	}
	
	/*
	 * Change the database login information
	 * @param string $db_host
	 * @param string $db_user
	 * @param string $db_pass
	 */
	public function changeLogin($db_host=NULL, $db_user=NULL, $db_pass=NULL)
	{
		$this->host = ($db_host != NULL) ? $db_host : 'localhost';
		$this->username = ($db_user != NULL) ? $db_user : \Yii::$app->params['components.db']['username'];
		$this->password = ($db_pass != NULL) ? $db_pass : \Yii::$app->params['components.db']['password'];
	}
	
	/*
	 * set the current table
	 * @param string $table
	 * @return boolean
	 */
	public function setTable($table=null)
	{
		$ret_val = false;
		if(!empty($table))
		{
			switch($table)
			{
				case DB::NULL:
				case null:
				self::$active['table']['name'] = '';
				break;
				
				default:
				self::$active['table']['name'] = $table;
				self::$tableName = $table;
				break;
			}
			$ret_val = true;
		}
		return $ret_val;
	}
	
	/*
	 * Remove the second db component
	 */
	public function clearDb()
	{
		self::$connection = null;
		$this->setDb();
	}
	
	/**
	 * Returns the database connection used by this AR class.
	 * By default, the "db" application component is used as the  database connection.
	 * You may override this method if you want to use a different database connection.
	 * @return Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		$ret_val = \Yii::$app->getDb();
		switch(\Yii::$app->getComponent('db2') instanceof \yii\db\Connection)
		{
			case true:
			$ret_val = \Yii::$app->getComponent('db2');
			break;
		}
		return $ret_val;
	}
	
	/*
	 * set the current database
	 * @param string $db
	 * @param string $table
	 * @param bolean force the connection
	 * @return boolean
	 */
	public function setDb($db='__default__', $table=null, $force=false)
	{
		$ret_val = false;
		switch($db)
		{
			case '__default__':
			Yii::$app->setComponent('db2', null);
			self::$active = array();
			break;
			
 			default:
			switch(!empty($db) && ($force || ($db != self::$active['db']['name'])))
			{
				case true:
				self::$active['db']['name'] = $db;
				switch(empty(self::$active['driver']))
				{
					case true:
					throw new \yii\base\ErrorException("Invalid driver and host parameters. Please call ".$this->className()."->changeLogin to change host and conneciton info");
					break;
					
					default:
					static::$connection = new \yii\db\Connection([
						'dsn' => self::$active['driver'].":host=".$this->host.";dbname=".self::$active['db']['name'],
						'username' => $this->username,
						'password' => $this->password,
						'emulatePrepare' => true,
						'charset' => 'utf8',
					]);
					static::$connection->open();
					Yii::$app->setComponent('db2', static::$connection);
					break;
				}
				break;
			}
			break;
		}
		if(!empty($table))
		{
			$ret_val = $this->setTable($table);
		}
		return $ret_val;
	}
	
	/*
	 * Temporarily change the database or table for operation
	 * @param string $db
	 * @param string $table
	 */
	public function changeDb($db, $table=null)
	{
		if(empty($this->user) || empty($this->host) || empty($this->password))
		{
			$this->changeLogin();
		}
		if((!empty($db)))
		{
			self::$old['db']['name'] = self::$active['db']['name'];
			self::$active['db']['name'] = $db;
			$this->setDb(self::$active['db']['name'], null, true);
		}
		else
		{
			self::$old['db']['name'] = null;
		}
		if(!empty($table))
		{
			self::$old['table']['name'] = self::$active['table'];
			self::$active['table']['name'] = $table;
			$this->setTable(self::$active['table']['name']);
		}
		else
		{
			self::$old['table']['name'] = null;
		}
	}
	
	/*
	 *Reset the database and table back
	 */
	public function revertDb()
	{
		if(!empty(self::$old['db']['name']))
		{
			$this->setDb(self::$old['db']['name']);
		}
		if(!empty(self::$old['table']['name']))
		{
			self::$active['table'] = self::$old['table'];
		}
		switch(empty(self::$active['table']['name']))
		{
			case true:
			$this->setTable(self::$active['table']['name']);
			break;
		}
		self::$old['db'] = [];
		self::$old['table'] = [];
	}
	
	/*
	 * What does this claim to be?
	 */
	public static function isWhat()
	{
		return static::$is;
	}
	
	/*
	 * Sets the successfull parameter for query
	 */
	public function successful()
	{
		return $this->success === true;
	}

	/*
	 * Get the records for this provisioning template
	 */
	public function getRecords()
	{
		switch($this->getScenario() == null)
		{
			case true:
			$this->setScenario('select');
			break;
		}
		$query = $this->find();
		static::aliasColumns($query);
		static::applyFilters($query, $this->queryFilters);
		$ret_val = $query->all();
		$this->success = (sizeof($ret_val) >= 1) ? true : false;
		return $ret_val;
	}

	/*
	 * Get the records for this provisioning template
	 * @param boolean $templates Should we get the templates?
	 * @param boolead $files Should we get the files?
	 */
	public function getObjects()
	{
		$this->setScenario('select');
		$query = $this->find();
		static::aliasColumns($query);
		static::applyFilters($query, $this->queryFilters);
		$ret_val = $query->all();
		$this->success = (sizeof($ret_val) >= 1) ? true : false;
		return $ret_val;
	}
	
	/*
	 *
	 */
	public function grouping()
	{
		return null;
	}
	
	/*---------------------
		Protected Functions
	---------------------*/
	
	/**
	 * Log a transaction to the logger
	 * @param strign $table
	 * @param string $action
	 * @param strign $message
	 * @param string|null $db
	 */
	protected function log($table, $action, $message, $db=null)
	{
		preg_match("/dbname=([^;]*)/", \Yii::$app->db->connectionString, $matches);
		$db = ($db == null) ? $matches[1] : $db;
		$logger = new Logger(null, null, null, Logger::LT_DB, $matches[1], $table);
		$logger->addTrans($db, $table, $action, $message);
	}
	
	/*---------------------
		Private Functions
	---------------------*/
}
?>