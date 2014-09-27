<?php

namespace nitm\models;

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
 
class Data extends ActiveRecord implements \nitm\interfaces\DataInterface
{
	use \nitm\traits\Configer,
	\nitm\traits\Query,
	\nitm\traits\Relations,
	\nitm\traits\Cache,
	\nitm\traits\Data;
	
	//public members
	public $initLocalConfig = true;
	public $unique;
	public $requestModel;
	public static $initClassConfig = true;
	public static $settings;
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
	
	protected $connection;
	protected static $supported;
	
	//private members

	public function init()
	{
		parent::init();
		if((bool)$this->initLocalConfig && (bool)static::$initClassConfig)
			$this->initConfig(static::isWhat());
	}
	
	public function rules()
	{
		return [
			[['filter'], 'required', 'on' => ['filtering']],
			[['unique'], 'safe']
		];
	}
	
	public function scenarios()
	{
		return [
			'default' => ['unique'],
			'filter' => ['filter'],
			'create' => ['author'],
			'update' => ['editor'],
			'deleted' => ['unique']
		];
	}
	
	public function behaviors()
	{
		$behaviors = [
		];
		$has = is_array(static::has()) ? static::has() : [];
		foreach($has as $name=>$dataProvider)
		{
			switch($this->hasProperty($name) || $this->hasAttribute($name))
			{
				case true:
				switch($name)
				{
					case 'updates':
					case 'edits':
					$behaviors[$name] = [
						'class' => \yii\behaviors\AttributeBehavior::className(),
						'attributes' => [
							ActiveRecord::EVENT_BEFORE_UPDATE => [$name],
						],
						'value' => function ($event) use($name) {
							switch($event->sender->hasProperty($name))
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
							ActiveRecord::EVENT_BEFORE_INSERT => 'author_id',
						],
					];
					switch($this->hasProperty('editor_id') || $this->hasAttribute('editor_id'))
					{
						case true:
						$behaviors['blamable']['attributes'][ActiveRecord::EVENT_BEFORE_UPDATE] = 'editor_id';
						break;
					}
					break;
					
					case 'updated_at':
					case 'created_at':
					//Setup timestamping
					$behaviors['timestamp'] = [
						'class' => \yii\behaviors\TimestampBehavior::className(),
						'attributes' => [
							ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
						],
						'value' => new \yii\db\Expression('NOW()')
					];
					switch($this->hasProperty('updated_at') || $this->hasAttribute('updated_at'))
					{
						case true:
						$behaviors['timestamp']['attributes'][ActiveRecord::EVENT_BEFORE_UPDATE] = 'updated_at';
						break;
					}
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
				break;
			}
		}
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public static function tableName()
	{
		return static::$tableName;
	}
	
	/*
	 * Check to see if somethign is supported
	 * @param mixed $what
	 */
	public function isSupported($what)
	{
		$thisSupports = [$what => false];
		switch(is_array($this->supported))
		{
			case true:
			$thisSupports = $this->supported;
			break;
			
			default:
			$thisSupports = @$this->settings[static::isWhat()]['supported'];
			break;
		}
		return (isset($thisSupports[$what]) &&  ($thisSupports[$what] == true));
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
				static::$active['table']['name'] = '';
				break;
				
				default:
				static::$active['table']['name'] = $table;
				$this->tableName = $table;
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
		static::$connection = null;
		static::setDb();
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
		switch(\Yii::$app->has('db2'))
		{
			case true:
			switch(\Yii::$app->get('db2') instanceof \yii\db\Connection)
			{
				case true:
				$ret_val = \Yii::$app->get('db2');
				break;
			}
			break;
			
			default:
			$ret_val = \Yii::$app->get('db');
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
			Yii::$app->set('db2', static::getConnection($this->username, $this->password, $this->host));
			static::$active = array();
			break;
			
 			default:
			switch(!empty($db) && ($force || ($db != static::$active['db']['name'])))
			{
				case true:
				static::$active['db']['name'] = $db;
				switch(empty(static::$active['driver']))
				{
					case true:
					throw new \yii\base\ErrorException("Invalid driver and host parameters. Please call ".$this->className()."->changeLogin to change host and conneciton info");
					break;
					
					default:
					Yii::$app->set('db2', static::getConnection($this->username, $this->password, $this->host));
					break;
				}
				break;
			}
			break;
		}
		if(!empty($table))
		{
			$ret_val = static::setTable($table);
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
			$this->old['db']['name'] = static::$active['db']['name'];
			static::$active['db']['name'] = $db;
			static::setDb(static::$active['db']['name'], null, true);
		}
		else
		{
			$this->old['db']['name'] = null;
		}
		if(!empty($table))
		{
			$this->old['table']['name'] = static::$active['table'];
			static::$active['table']['name'] = $table;
			static::setTable(static::$active['table']['name']);
		}
		else
		{
			$this->old['table']['name'] = null;
		}
	}
	
	/*
	 *Reset the database and table back
	 */
	public function revertDb()
	{
		if(!empty($this->old['db']['name']))
		{
			static::setDb($this->old['db']['name']);
		}
		if(!empty($this->old['table']['name']))
		{
			static::$active['table'] = $this->old['table'];
		}
		switch(empty(static::$active['table']['name']))
		{
			case true:
			static::setTable(static::$active['table']['name']);
			break;
		}
		$this->old['db'] = [];
		$this->old['table'] = [];
	}
	
	/**
	 * Overriding default find function
	 */
	public static function find($model=null, $options=null)
	{
		$query = parent::find($options);
		static::aliasColumns($query);
		if(is_object($model))
		{
			if(!empty($model->withThese))
				$query->with($model->withThese);
			foreach($model->queryFilters as $filter=>$value)
			{
				switch(strtolower($filter))
				{
					case 'select':
					case 'indexby':
					case 'orderby':
					if(is_string($value) && ($value == 'primaryKey'))
					{
						unset($model->queryFilters[$filter]);
						$query->$filter(static::primaryKey()[0]);
					}
					break;
				}
			}
			static::applyFilters($query, $model->queryFilters);
		}
		return $query;
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
	
	/**
	 * Create the connection to the database
	 * @param string $username
	 * @param string $password
	 * @param string $host
	 * @return Connection
	 */
	 protected static function getConnection($username, $password, $host)
	 {
		 switch(static::$connection instanceof yii\db\Connection)
		 {
			 case false:
			 static::$connection = new \yii\db\Connection([
				'dsn' => static::$active['driver'].":host=".$host.";dbname=".static::$active['db']['name'],
				'username' => $username,
				'password' => $password,
				'emulatePrepare' => true,
				'charset' => 'utf8',
			]);
			static::$connection->open();
			break;
		 }
		return static::$connection;
	 }
	
	
	/*---------------------
		Private Functions
	---------------------*/
}
?>