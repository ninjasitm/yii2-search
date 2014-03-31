<?php

namespace nitm\module\models;

use Yii;
use yii\web\Application;
use yii\base\Event;
use yii\base\Model;
use nitm\module\helpers\Session;
use nitm\module\helpers\Directory;

/**
 * Class Configer
 * @package nitm\module\models
 *
 * @property integer $cfg_id
 * @property string $cfg_n
 * @property string $cfg_v
 * @property string $cfg_s
 * @property string $cfg_c
 * @property string $cfg_w
 * @property string $cfg_e
 * @property string $cfg_comment
 * @property string $get_values
 */


class Configer extends Model
{
	//public data
	public $backups = true;
	public $backupExtention = '.cfg.bak';
	public $dir = ["default" => 'config/ini/', "config" => null];
	public $container = 'default';
	public $config = [
		'containers' => [],
		'from' => [
			"default" => [
				'desc' => "Config",
				'base' => '',
				"types" => [
					'ini',
					'cfg',
					null
				]
			],
			"xml" => [
				'desc' => "XML",
				'types' => 'xml'
			]
		]
	];
	
	public $configDb = null;
	
	//Form variables
	public $cfg_id;	//The id of the value
	public $cfg_n;	//The name of a key/value pair
	public $cfg_v;	//The value
	public $cfg_s;	//Current value section
	public $cfg_c;	//The container
	public $cfg_w;	//What is being done
	public $cfg_e;	//Current engine
	public $cfg_comment;	//The comment
	public $cfg_convert;	//Convert
	public $cfg_to;	//Convert to what engine?
	public $get_values;	//Should we try to get values as well?
	
	//protected data
	protected $contents = null;
	protected $sections = [];
	protected $containers = [];
	protected $classes = [
		"success" => "success",
		"failure" => "warning",
		"info" => "info"
	];
	
	//constant data
	const dm = 'configer';
	const NO_DEC = 'nodec:';
	
	//private data
	private $_o;
	private $filemode = 0775;
	private $handle = false;
	private $types = ['ini' => 'cfg', 'xml' => 'xml', 'file' => 'cfg'];
	private $location = "file";
	private $configTables = [
		"containers" => "config_containers",
		"sections" => "config_sections",
		"values" => "config_values"
	];
	private $supported = ["file" => "File", "db" => "Database"];
	private $event;
	
	public function __construct()
	{
		$this->init();
	}
	
	public function init($enable_backups=true, $backupExtention='.cfg.bak', $configDb=null)
	{
		$this->initLogger($configDb);
		$this->backups = $enable_backups;
		$this->backupExtention = $backupExtention;
		$this->dir['config'] = $this->dir['default'];
		$this->config['supported'] = $this->supported;
		$this->initEvents();
	}
	
	public function behaviors()
	{ 
		$behaviors = [
			"Behavior" => [
				"class" => \yii\base\Behavior::className(),
			],
			"DB" => [
				"class" => \nitm\module\models\DB::className(),
			],
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public function rules()
	{
		return [
			[['cfg_w', 'cfg_v', 'cfg_s', 'cfg_n', 'cfg_c'], 'required', 'on' => ['addValue']],
			[['cfg_w', 'cfg_v', 'cfg_c'], 'required', 'on' => ['addSection']],
			[['cfg_w', 'cfg_v'], 'required', 'on' => ['addContainer']],
			[['cfg_w', 'cfg_v', 'cfg_n', 'cfg_c', 'cfg_id'], 'required', 'on' => ['editValue']],
			[['cfg_w', 'cfg_v', 'cfg_s', 'cfg_c'], 'required', 'on' => ['editSection']],
			[['cfg_w', 'cfg_v', 'cfg_c'], 'required', 'on' => ['editContainer']],
			[['cfg_w', 'cfg_n', 'cfg_c', 'cfg_id'], 'required', 'on' => ['deleteValue']],
			[['cfg_w', 'cfg_v', 'cfg_c'], 'required', 'on' => ['deleteSection']],
			[['cfg_w', 'cfg_v'], 'required', 'on' => ['deleteContainer']],
			[['cfg_w', 'cfg_c', 'cfg_s'], 'required', 'on' => ['getSection']],
			[['cfg_convert'], 'required', 'on' => ['convert']],
			[['cfg_e'], 'safe'],
		];
	}
	
	public function scenarios()
	{
		return [
			'default' => ['cfg_v', 'cfg_c', 'cfg_s', 'cfg_w', 'cfg_e', 'get_values',],
			'addValue' => ['cfg_v', 'cfg_n', 'cfg_c', 'cfg_s', 'cfg_w'],
			'addSection' => ['cfg_v', 'cfg_c', 'cfg_w'],
			'addContainer' => ['cfg_v', 'cfg_w'],
			'editValue' => ['cfg_v', 'cfg_n', 'cfg_s', 'cfg_c', 'cfg_w', 'cfg_id'],
			'editSection' => ['cfg_v', 'cfg_c', 'cfg_w', 'cfg_id'],
			'deleteValue' => ['cfg_s', 'cfg_n', 'cfg_w', 'cfg_id'],
			'deleteSection' => ['cfg_n', 'cfg_c', 'cfg_w', 'cfg_id'],
			'deleteContainer' => ['cfg_v', 'cfg_w', 'cfg_id'],
			'getSection' => ['cfg_w', 'cfg_c', 'cfg_s', 'get_values'],
			'convert' => ['cfg_convert', 'cfg_e']
		    ];
	}
	
	public function attributeLabels()
	{
		return [
		    'cfg_v' => 'Value',
		    'cfg_n' => 'Name',
		    'cfg_c' => 'Container',
		    'cfg_e' => 'Engine',
		    'cfg_s' => 'Section',
		    'cfg_w' => 'Action',
		];
	}
	
	/*
	 * Setup logging functionality
	 * @param string $db
	 * @param strign $table
	 */ 
	protected function initLogger($db=null, $table='logs')
	{
		defined("DB_DEFAULT") or define("DB_DEFAULT", null);
		$this->configDb = is_null($db) ? DB::$active['db']['name'] : $db;
		$this->_o['logger'] = new Logger(null, null, null, Logger::LT_DB, $this->configDb, $table);
	}
	
	/*
	 * Initiate the event handlers for this class
	 */
	public function initEvents()
	{
		$this->on("afterAdd", function($e) {
			$this->config['current']['section'] = $this->event['data']['section'];
			Session::set($this->correctKey($this->event['data']['key']), $this->event['data']);
			switch($this->container == \Yii::$app->getModule('nitm')->configOptions['container'])
			{
				case true:
				Session::set(Session::settings.'.'.$this->event['data']['key'], (is_null($decoded = json_decode($this->event['data']['value'], true)) ? $this->event['data']['value'] : $decoded));
				break;
			}
			$this->_o['logger']->addTrans($this->event['data']['db'],
					  $this->event['data']['table'],
					  $this->event['data']['action'],
					  $this->event['data']['message']);
		});
		
		$this->on("afterEdit", function($e) {
			Session::set($this->correctKey($this->event['data']['key'].'.value'), $this->event['data']['value']);
			switch($this->container == @Yii::$app->getModule('nitm')->configOptions['container'])
			{
				case true:
				Session::set(Session::settings.'.'.$this->event['data']['key'], (is_null($decoded = json_decode($this->event['data']['value'], true)) ? $this->event['data']['value'] : $decoded));
				break;
			}
			$this->_o['logger']->addTrans($this->event['data']['db'],
					  $this->event['data']['table'],
					  $this->event['data']['action'],
					  $this->event['data']['message']);
		});
		
		$this->on("afterCreate", function($e) {
			$this->_o['logger']->addTrans($this->event['data']['db'],
				    $this->event['data']['table'],
				    $this->event['data']['action'],
				    $this->event['data']['message']);
		});
		
		$this->on("afterDelete", function($e) {
			switch($this->event['data']['action'])
			{
				case 'delete':
				$value = $section;
				break;
			}
			$this->config['current']['section'] = @$this->event['data']['section'];
			Session::del($this->correctKey($this->event['data']['key']));
			switch($this->container == @Yii::$app->getModule('nitm')->configOptions['container'])
			{
				case true:
				Session::del(Session::settings.'.'.$this->event['data']['key']);
				break;
			}
			$this->_o['logger']->addTrans($this->event['data']['db'],
					  $this->event['data']['table'],
					  $this->event['data']['action'],
					  $this->event['data']['message']);
		});
	}
	
	/*
     * Prepare the config info for editing
	 * @param string $engine
	 * @param string $container
	 * @param boolean $get_values
     * @return mixed $config
     */
	public function prepareConfig($engine='file', $container='config', $get_values=false)
	{
		$engine = empty($engine) ? (empty($this->engine) ? 'file' : $this->engine) : $engine;
		$container = empty($container) ? 'global' : $container;
		switch($engine)
		{
			case 'alt':
			switch($container)
			{
				case 'pma':
				$template = Session::getVal("settings.templates.iframe");
				$this->render($template, ['src' => '/phpmyadmin/main.php']);
				return;
				break;
			}
			break;
			
			default:
			$this->setEngine($engine);
			$this->setType($engine, $container);
			//if the selected config is not loaded then load it
			if((Session::getVal(self::dm.'.current.config') != $this->location.'.'.$container) || (Session::getVal(self::dm.'.current.engine') != $this->location))
			{
				$this->config['current']['config'] = $this->getConfig($engine, $container, $get_values, true);
				Session::set(self::dm.'.'.$this->location.'.config', $this->config['current']['config']);
			}
			//otherwise just get the current loaded config
			else
			{
				$this->config['current']['config'] = Session::getVal(self::dm.'.'.$this->location.'.config');
				$config = is_array($this->config['current']['config']) ? $this->config['current']['config'] : [];
				$this->config['current']['sections'] = array_merge(["" => "Select section..."], array_combine(array_keys($config), array_keys($config)));
			}
			switch($get_values)
			{
				case false:
				$this->config['current']['config'] = null;
				break;
			}
			$this->config['load']['current'] = empty($this->config['current']['config']) ? false : true;
			$this->config['load']['sections'] = (is_null($this->config['current']['sections'])) ? false : true;
			switch($this->container == \Yii::$app->getModule('nitm')->configOptions['container'])
			{
				case false:
				Session::set(Session::settings.'.'.$this->event['data']['key'], $this->event['data']['value']);
				break;
			}
			Session::set(self::dm.'.current.config', $this->location.'.'.$container);
			break;
		}
	}
	
	/*
     * Set the configuration type
	 * @param string $engine
	 * @param string $container
	 * @param string $from
     * @return mixed $this->config
     */	
	public function setType($engine, $container=null, $from='default')
	{
		$this->config['surround'] = [];
		$this->config['current']['type'] = $engine;
		$this->config['current']['type_text'] = 'a section';
		$this->config['current']['container'] = $container;
		$this->config['current']['sections'] = null;
		@$this->config['containers'][$container]['selected'] = "selected='selected'";
		$this->config['current']['selected_text'] = "selected='selected'";
		$this->config['load']['types'] = !is_array($this->supported) ? false : true;
		$this->getContainers();
		switch(isset($this->config['from'][$from]))
		{
			case true:
			switch(1)
			{
				case in_array('xml', $this->config['from'][$from]['types']) !== false:
				//$fb::$compatible = ['text' => '.xml');
				//$freeswitch_base = '/usr/local/freeswitch/conf/';
				$this->config['current']['container'] = $engine;
				$this->config['current']['from'][$engine]['selected'] = "selected='selected'";
				$this->config['current']['path'] = $this->config['from'][$from]['dir'];
				$this->config['current']['type'] = 'xml';
				$this->config['current']['surround'] = ['open' => "<code>", "close" => "</code>"];
				$this->config['current']['type_text'] = 'an xml file';
				break;
			
				default:
				switch(in_array($container, $this->config['containers']))
				{
					case true:
					$this->config['current']['container'] = $container;
					$this->config['current']['path'] = "@$container";
					break;
				
					default:
					$this->config['current']['container'] = "globals";
					$this->config['current']['path'] = '@globals';
					break;
				}
				break;
			}
			$this->container = $this->config['current']['container'];
			break;
		}
	}
	
	/*
		Set the stroage engine
		@param string $loc Either file or database
	*/
	public function setEngine($loc)
	{
		switch($this->isSupported($loc))
		{
			case true:
			switch($loc)
			{
				case 'db':
				$this->location = 'db';
				$this->setDb($this->configDb);
				break;
				
				case 'file':
				$this->location = 'file';
				$this->_o['directory'] = new Directory();
				break;
			}
			break;
		}
		switch(!empty($this->location))
		{
			case true:
			//clear any other unused engine data
			foreach(array_diff_key($this->supported, [$this->location => ucfirst($this->location)]) as $clear=>$key)
			{
				Session::del(self::dm.'.'.$clear);
			}
			Session::set(self::dm.'.current.engine', $this->location);
			break;
		}
	}
	
	public function initLogging($log_db=null, $log_table=null)
	{
		if(class_exists("Logger") && !($this->l instanceof Logger))
		{
			$this->l = new Logger(null, null, null, Logger::LT_DB, $log_db, $log_table);
		}
	}
	
	public function setBase($container)
	{
		switch(empty($container))
		{
			case false:
			switch($this->location)
			{
				case 'file':
				$container = explode('.', $container);
				$container = array_shift($container);
				$container = empty($container) ? $this->container : $container;
				$this->container = ($container[0] == '@') ? substr($container, 1, strlen($container)) : $container;
				break;
			}
			break;
		}
	}
	
	public function correctKey($key)
	{
		$key = explode('.', $key);		
		switch($key[0])
		{
			case self::dm:
			array_shift($key);
			switch($key[0] == $this->container)
			{
				case false;
				array_unshift($key, self::dm, $this->location, 'config');
				break;
				
				default:
				array_unshift($key, self::dm, $this->location);
				break;
			}
			break;
			
			default:
			switch($this->container)
			{
				case Yii::$app->getModule('nitm')->configOptions['container'];
				array_unshift($key, Session::settings);
				break;
				
				default:
				switch($key[0] == $this->container)
				{
					case false;
					array_unshift($key, self::dm, $this->location, 'config', $this->container);
					break;
				}
				break;
			}
			break;
		}
		return implode('.', $key);
	}
	
	/*
	 * Set the directory for the configuration. Backups will also be stroed here
	 * @param string $dir
	 */
	
	public function setDir($dir=null)
	{
		$this->dir['config'] = (is_dir($dir)) ? $dir : $this->dir['default'];
	}
	
	public function getDm()
	{
		return self::dm;
	}
	
	/*
	 * Write/save the configuration
	 * @param string $container
	 * @param mixed $data
	 * @param string $engine
	 * @return boolean success flag
	 */
	public function writeTo($container, $data, $engine='file')
	{
		$sections = '';
		$content = '';
		$ret_val = false;
		$this->setEngine($engine);
		switch($this->location)
		{
			case 'xml':	
			break;
			
			case 'file':
			$write = false;
			array_multisort($data, SORT_ASC);
			$container = stripslashes($container);
			if((file_exists($container)) && (sizeof($data) > 0))
			{
				foreach ($data as $key => $item)
				{
					if (is_array($item))
					{
						$sections .= "\n[{$key}]\n";
						foreach ($item as $key2 => $item2)
						{
							if (is_numeric($item2) || is_bool($item2))
							{
								$sections .= "{$key2} = {$item2}\n";
							}
							else
							{
								$sections .= "{$key2} = {$item2}\n";
							}
						}     
					}
					else
					{
						if(is_numeric($item) || is_bool($item))
						{
							$content .= "{$key} = {$item}\n";
						}
						else
						{
							$content .= "{$key} = {$item}\n";
						}
					}
				}
				$content .= $sections;
				$write = true;
			}
			break;
				
			case 'db':
			//write info to db
			$write = true;
			break;
		}
		switch($write)
		{
			case true:
			switch($this->location)
			{
				case 'db':
				$this->setTable($this->configTables['containers']);
				$pri['containers'] = $this->getCurPri();
				$cond = [
					"key" => ['name', $pri['containers']], 
					"data" => [$container, $container],
					"xor" => ["OR"]
				];
				$containerid = $this->check($cond['key'], $cond['data'], null, null, '=', $cond['xor'], [$this->getCurPri()]);
				switch($containerid)
				{
					case false:
					$this->createContainer($container, null, $this->location);
					$containerid = ["containerid" => $this->last_id['insert']];
					$container = $containerid[$pri['containers']];
					break;
				}
				$section = ['author' => \Yii::$app->user->getId(), "containerid" => $container];
				$message = "";
				$result = 'failure';
				$action = ["success" => "Add Config", "failure" => "Add Config Fail"];
				$this->setTable($this->configTables['sections']);
				$pri['sections'] = $this->getCurPri();
				//write the sections
				foreach($data as $name=>$values)
				{
					
					$sections[$name]['data'][$pri['sections']] = $this->check([$pri['containers'], 'name'], [$container, $name]);
					$sections[$name]['data'][$pri['containers']] = $container;
					switch($sections[$name]['data'][$pri['sections']])
					{
						case false:
						$section['name'] = $name;
						$section[$pri['containers']] = $container;
						$this->insert(array_keys($section), array_values($section));
						$sections[$name]['data'][$pri['sections']] = $this->last_id['insert'];
						$sections[$name]['data'] = array_merge($sections[$name]['data'], $section);
						break;
					}
				}
				//write the values
				$this->setTable($this->configTables['values']);
				foreach($data as $name=>$values)
				{
					foreach($values as $k=>$v)
					{
						switch($this->check([$pri['containers'], $pri['sections'], 'name'], [$container, $sections[$name]['data'][$pri['sections']], $v['name']]))
						{
							case false:
							$value = [];
							$value['name'] = $v['name'];
							$value['value'] = $v['value'];
							$value['comment'] = $v['comment'];
							$value = array_merge($sections[$name]['data'], $value);
							$this->insert(array_keys($value), array_values($value));
							break;
						}
					}
				}
				break;
				
				case 'file':
				$container = ($container[0] == '@') ? $this->dir['default'].substr($container, 1, strlen($container)) : $container;
				if(is_resource($this->open($container, 'write')))
				{
					fwrite($this->handle, stripslashes($content));
					$ret_val = true;
				}
				$this->close();
				if($this->backups && !empty($content))
				{
					$backup_dir = "/backup/".date("F-j-Y")."/";
					$container_backup = ($container[0] == '@') ? $this->dir['default'].$backup_dir.substr($container, 1, strlen($container)) : dirname($container).$backup_dir.basename($container);
					$container_backup .= '.'.date("D_M_d_H_Y", strtotime('now')).$this->backupExtention;
					if(!is_dir(dirname($container_backup)))
					{
						mkdir(dirname($container_backup), $this->filemode, true);
					}
					fopen($container_backup, 'c');
					chmod($container_backup, $this->filemode);
					if(is_resource($this->open($container_backup, 'write')))
					{
						fwrite($this->handle, stripslashes($content));
					}	
					$this->close();
				}
				/*
					After a change has been made and commited remove the current values in index $this->container
					to save memory/space and to not allow any new changes to be made
				*/
				$this->setBase($container);
				switch(Session::getVal(self::dm.'.'.$this->location.'.'.$this->container) != $data)
				{
					case true:
					Session::set(self::dm.'.'.$this->location.'.'.$this->container);
					break;
				}
				break;
			}
			break;
		}
		return $ret_val;
	} 
	
	/*
	 * Load the configuration
	 * @param string $container
	 * @param boolean $from_sess From the session?
	 * @param boolean $force Force a load?
	 * @return mixed configuration
	 */
	public function loadFrom($container=null, $from_sess=false, $force=false, $engine='file')
	{
		$ret_val = null;
		switch($from_sess === true)
		{
			case true:
			$ret_val = &$_SESSION[$this->sess_name.$_SERVER['SERVER_NAME']][self::dm];
			break;
			
			default:
			switch($this->location)
			{
				case 'xml':
				break;
				
				case 'file':
				$this->setBase($container);
				$container = $this->resolveDir($this->config['current']['path']);
				$container = $container.'.'.$this->types[$this->location];
				switch(file_exists($container))
				{
					case true:
					switch((filemtime($container) >= fileatime($container)) || ($force === true))
					{
						case true: 
						if(is_resource($this->open($container, 'read')))
						{
							$this->contents = file($container, 1);
							$this->close();
							$ret_val = $this->contents;
						}
						break;
					}
					break;
				}
				break;
				
				case 'db':
				/*
				 * Now get the container ID
				 */
				$pri = [];
				$this->setTable($this->configTables['values']);
				$pri['values'] = [
					'key' => $this->configTables['values'].'.'.$this->getCurPri(),
					'name' => $this->getCurPri()
				];
				/*
				 * We need to use activity states here to determine when to load form the database
				 */
				$this->select("1", true, [
					"key" => ['1000'], 
					"data" => [DB::PDO_NOBIND."NOW()-updated_at"],
					"operand" => ">="
				]);
				$new_change = $this->rows();
				switch($force || $new_change)
				{
					case true:
					$this->setTable($this->configTables['containers']);
					$pri['containers'] = [
						'key' => $this->configTables['containers'].'.'.$this->getCurPri(),
						'name' => $this->getCurPri()
					];
					$sel_fields = [$pri['containers']['name'], 'name'];
					$this->select($sel_fields, true, [
						"key" => ['name', $pri['containers']['name']], 
						"data" => [$container, $container],
						"xor" => ["OR"]
					]);
					switch($this->successful() && ($this->rows() >= 1))
					{
						case true:
						$this->config['current']['sections'] = [];
						$containerid = $this->result(DB::R_ASS);
						$this->setTable($this->configTables['sections']);
						$pri['sections'] = [
							'key' => $this->configTables['sections'].'.'.$this->getCurPri(),
							'name' => $this->getCurPri()
						];
						$sel_fields = ['name', $pri['containers']['name']];
						$this->select($sel_fields,
							true, 
							[
								"key" => [
									'containerid'
								], 
								"data" => [
									$containerid[$pri['containers']['name']]
								]
							], 
							["orderby" => "name"],
							true
						);
						switch($this->rows())
						{
							case true:
							$this->config['current']['sections'] = ['' => "Select section..."];
							$result = $this->result(DB::R_ASS, true);
							$ret_val = [];
							foreach($result as $value)
							{
								switch(isset($this->config['current']['sections'][$value['name']]))
								{
									case false:
									$this->config['current']['sections'][$value['name']] = $value['name'];
									$this->config['sections'][$value['name']] = $value;
									break;
								}
							}
							
							/*
							 * Now get the values
							 */
							$this->setTable($this->configTables['values']);
							$sel_fields = [
								$pri['values']['name'],
								$pri['values']['name']." AS `unique`",
								'sectionid',
								'containerid', 
								'name',
								'value',
								'comment',
								'author',
								'editor',
								'created_at',
								'updated_at',
								"CONCAT((SELECT `name` FROM `".$this->configTables['sections']."` WHERE ".$this->configTables['sections'].".id=sectionid), '.', name) AS unique_id", 
								"'".$pri['sections']['name']."' AS unique_name", 
								"(SELECT `name` FROM `".$this->configTables['sections']."` WHERE ".$this->configTables['sections'].".id=sectionid) AS 'section_name'", 
								"(SELECT `name` FROM `".$this->configTables['containers']."` WHERE ".$this->configTables['containers'].".id=containerid) AS 'container_name'"
							];
							$this->select($sel_fields,
								true, 
								[
									"key" => [
										'containerid'
									], 
									"data" => [
										$containerid[$pri['containers']['name']]
									]
								], 
								["orderby" => "name"],
								true
							);
							break;
						}
						switch($this->successful())
						{
							case true:
							//$this->config['current']['sections'] = ['' => "Select section..."];
							$result = $this->result(DB::R_ASS, true);
							$ret_val = $result;
							break;
						}
						break;
					}
					break;
				}
				break;
			}
			break;
		}

		return $ret_val;
	}
	
	/*
	 * Read the configuration from a database or file
	 * @param mixed $contents
	 * @param string $commentchar
	 * @param string $decode
	 * @param boolean $editing
	 * @param string $engine
	 * @return mixed $ret_val
	 */
	public function readFrom($contents=null, $commentchar=';', $decode='json', $editing=false, $engine='file') 
	{
		$ret_val = [];
		$decode = is_array($decode) ? $decode : [$decode];
		switch($this->location)
		{
			case 'db':
			//convert the raw config to the proper hierarchy;
			switch(is_array($contents))
			{
				case true:
				foreach($contents as $idx=>$data) 
				{
					//is the section already set?
					switch($editing)
					{
						case true:
						$section = $data['section_name'];
						$val_key = $data['name'];
						break;
						
						default:
						$section = $data['section_name'];
						$val_key = $data['name'];
						$data = $data['value'];
						break;
					}
					switch(isset($ret_val[$section]))
					{
						case false:
						$ret_val[$section] = [];
						break;
					}
					//set the value
					$ret_val[$section][$val_key] = $data;
				}
				break;
			}
			break;
			
			case 'xml':
			break;
			
			case 'file':
			switch(!empty($contents))
			{
				case true:
				$section = '';
				$contents = array_filter((is_null($contents)) ? $this->contents : $contents);
				$commentchar = is_null($commentchar) ? ';' : $commentchar;
				$this->config['current']['sections'] = ['' => "Select section"];
				if(is_array($contents) && (sizeof($contents) > 0))
				{
					foreach($contents as $filedata) 
					{
						$dataline = trim($filedata);
						$firstchar = substr($dataline, 0, 1);
						if($firstchar!= $commentchar && $dataline != '') 
						{
							//It's an entry (not a comment and not a blank line)
							if($firstchar == '[' && substr($dataline, -1, 1) == ']') 
							{
								//It's a section
								$section = substr($dataline, 1, -1);
								$this->config['current']['sections'][$section] = $section;
								$ret_val[$section] = [];
							}
							else
							{
								//It's a key...
								$delimiter = strpos($dataline, '=');
								if($delimiter > 0) 
								{
									//...with a value
									$key = trim(substr($dataline, 0, $delimiter));
									$value = trim(substr($dataline, $delimiter + 1));
									if(substr($value, 1, 1) == '"' && substr($value, -1, 1) == '"') 
									{ 
										$value = substr($value, 1, -1); 
									}
									//$ret_val[$section][$key] = stripslashes($value);
									//we may return comments if we're editing
									$ret_val[$section][$key] = ($editing == false) ? $value : ["value" => $value, "comment" => @$comment, 'section' => $section, "name" => $key, "unique" => "$section.$key"];
								}
								else
								{
									//we may return comments if we're editing
									//...without a value
									$ret_val[$section][trim($dataline)] = ($editing === false) ? $value : ["value" => '', "comment" => @$comment, 'section' => $section, "name" => $key, "unique" => "$section.$key"];
								}
							}
						}
					}
				}
				break;
			}

			break;
		}
		switch($this->location)
		{
			case 'xml':
			break;
			
			case 'db':
			case 'file':
			array_walk($ret_val, function (&$member) {ksort($member, SORT_STRING);});
			switch(is_array($decode))
			{
				case true:
				foreach($decode as $dec)
				{
					switch($dec)
					{
						case 'json':
						array_walk_recursive($ret_val, function (&$v) use ($editing) {
							switch($editing)
							{
								case false:
								$v = is_array($v) ? $v['value'] : $v;
								break;
							}
							switch(1)
							{
								case ((@$v[0] == "{") && ($v[strlen($v)-1] == "}")) && ($editing === false):
								$v = ((!is_null($data = json_decode($v, true))) ? $data : $v);
								break;
								
								case substr($v, 0, strlen(self::NO_DEC)) == self::NO_DEC:
								$v = substr($v, strlen(self::NO_DEC), strlen($v));
								break;
							}
						});
						break;
						
						case 'csv':
						array_walk_recursive($ret_val, function (&$v) {
							switch($editing)
							{
								case false:
								$v = $v['value'];
								break;
							}
							switch((@$v[0] == "{") && ($v[strlen($v)-1] == "}") && ($editing === false))
							{
								case true:
								$v = explode(',', $v);
								break;
							}
						});
						break;
					}
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/*
	 * Add a value to the configuration
	 * @param string $key
	 * @param string $container
	 * @param string sess_member
	 * @param string $engine
	 * @return mixed added value and success flag
	 */
	public function add($key, $value, $container, $sess_member=null, $engine='file')
	{
		$ret_val = [
			"success" => false, 
			"message" => "Couldn't perform the add: [$key], [$value], [$container]", 
			"action" => 'edit', 
			"class" => $this->classes["failure"]
		];
		$this->setEngine($engine);
		$this->setBase($container);
		$hierarchy = explode('.', $this->correctKey($key));
		switch($this->location)
		{
			case 'db':
			$ret_val = array_merge($ret_val, $this->_add($container, $key, $value));
			$ret_val['data'] = [$key, $value];
			$ret_val['class'] = $this->classes['failure'];
			switch($ret_val['success'])
			{
				case true:
				$ret_val['class'] = $this->classes['success'];
				$e = new Event;
				$this->event['data'] = array_merge($ret_val, [
					'table' => 'config',
					'db' => $this->configDb,
					'action' => 'Add Config',
					'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username') ." added new key->value ($key -> ".var_export($value, true).") to config ".$container
				]);
				$this->trigger('afterAdd');
				break;
			}
			break;
			
			case 'xml':
			break;
			
			case 'file':
			$container = $this->resolveDir($this->config['current']['path']);
			Session::set_csdm(self::dm.'.'.$this->location);
			switch(sizeof($hierarchy))
			{
				///we might be adding a container
				case 1:
				switch(empty($contaienr))
				{
					case false:
					$this->createContainer($container);
					break;
				}
				break;
			
				default:
				switch(1)
				{
					case !$container:
					$ret_val['debug'] = "Sorry I cannot add a value to a container that doesn't exist\nPlease try again again by passing the correct parameters to me.\nadd($key, ".var_export($value).", ".basename($container).", $sess_member) (0);";
					break;
				
					case !$key:
					$ret_val['debug'] = "Sorry I cannot add an empty key\nPlease try again again by passing the correct parameters to me.\nadd($key, ".var_export($value, true)."), ".basename($container).", $sess_member) (1);";
					break;
					
					default:
					$ret_val = array_merge($ret_val, $this->_add($container, $key, $value));
					$ret_val['class'] = $this->classes['failure'];
					$sess_member = (empty($sess_member)) ? Session::settings : $sess_member.'.'.$this->container;
					switch($ret_val['success'])
					{
						case true:
						$ret_val['class'] = $this->classes['success'];
						$e = new Event;
						$this->event['data'] = array_merge($ret_val, [
							'table' => 'NULL',
							'db' => 'NULL',
							'action' => 'Add Config',
							'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username') ." added new key->value ($key -> ".var_export($value, true).") to config file ".basename($container)
						]);
						$this->trigger('afterAdd');
						break;
					}
					break;
				}
				break;
			}
			break;
		}
		$ret_val['action'] = 'add';
		$this->config['current']['action'] = $ret_val;
		Session::set(Configer::dm.'.action', $ret_val);
	}
	
	/*
	 * Edit a value in the configuration
	 * @param string $key
	 * @param string $container
	 * @param string sess_member
	 * @param string $engine
	 * @return mixed edited value and success flag
	 */
	public function edit($key, $value, $container, $sess_member=null, $engine='file')
	{
		$ret_val = [
			"success" => false, 
			"message" => "Couldn't perform the edit: [$key], [$value], [$container]", 
			"action" => 'edit', 
			"class" => $this->classes["failure"]
		];
		$this->setEngine($engine);
		$this->setBase($container);
		$container = $this->resolveDir($this->config['current']['path']);
		$key = stripslashes ($key);
		$value = stripslashes(rawurldecode($value));
		switch($this->location)
		{
			case 'db':
			$ret_val = array_merge($ret_val, $this->_edit($container, $key, $value));
			switch($ret_val['success'])
			{
				case true:
				$ret_val['class'] = $this->classes['success'];
				$this->event['data'] = [
					'table' => 'config',
					'db' => $this->configDb,
					'key' => $key,
					'value' => $value,
					'action' => "Edit Config",
					'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." edited value ($key from '".var_export($ret_val['old_value'], true)."' to '".var_export($value, true)."') in container ".basename($container)
				];
				$this->trigger('afterEdit');
				break;
			}
			break;
			
			case 'file':
			Session::set_csdm(self::dm.'.'.$this->location);
			switch(1)
			{
				case !$container:
				$ret_val['class'] = $this->classes['failure'];
				$ret_val['debug'] ="Sorry I cannot edit a value in a file that doesn't exist\nPlease try again again by passing the correct parameters to me.\nedit($key, ".var_export($value).", ".basename($container).", $sess_member) (0);";
				break;
				
				case !$key:
				$ret_val['debug'] ="Sorry I cannot edit an empty key\nPlease try again again by passing the correct parameters to me.\nedit($key, ".var_export($value, true).", ".basename($container).", $sess_member) (1);";
				break;
				
				default:
				//use sed for editing
				$ret_val = array_merge($ret_val, $this->_edit($container, $key, $value));
				$sess_member = (empty($sess_member)) ? Session::settings : $sess_member.'.'.$this->container;
				switch($ret_val['success'])
				{
					case true:
					$ret_val['class'] = $this->classes['success'];
					$this->event['data'] = [
						'table' => 'NULL',
						'db' => 'NULL',
						'key' => $key,
						'value' => $value,
						'action' => "Edit Config File",
						'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." edited value ($key from '".var_export($ret_val['old_value'], true)."' to '".var_export($value, true)."') in config file ".basename($container)
					];
					$this->trigger('afterEdit');
					break;
				}
				break;
			}
		}
		$ret_val['action'] = 'edit';
		$ret_val['value'] = rawurlencode($value);
		$this->config['current']['action'] = $ret_val;
		Session::set(Configer::dm.'.action', $ret_val);
	}
	
	/*
	 * Delete a value in the configuration
	 * @param string $key
	 * @param string $container
	 * @param string sess_member
	 * @param string $engine
	 * @return mixed deleted value and success flag
	 */
	public function delete($key, $container, $sess_member=null, $engine='file')
	{
		$ret_val = [
			"success" => false, 
			"message" => "", 
			"action" => 'delete', 
			"class" => $this->classes["failure"]
		];
		$this->setEngine($engine);
		$this->setBase($container);
		$hierarchy = explode('.', $this->correctKey($key));
		$value = Session::getVal($this->correctKey($key));
		switch($this->location)
		{
			case 'db':
			$engine = $this->location;
			$ret_val = array_merge($ret_val, $this->_delete($this->container, $key));
			switch($ret_val['success'])
			{
				case true:
				$ret_val['class'] = $this->classes['success'];
				$this->event['data'] = [
					'table' => 'config',
					'db' => $this->configDb,
					'key' => $key,
					'value' => $value,
					'section' => $ret_val['section'],
					'action' => "Delete Config",
					'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." deleted value ($key -> '".var_export($value, true)."') from config file ".basename($container)
				];
				$this->trigger('afterDelete');
				break;
			}
			break;
			
			case 'file':
			$container = $this->resolveDir($this->config['current']['path']);
			switch(1)
			{
				case !$container:
				$ret_val['debug'] = "Sorry I cannot delete a value from a file that doesn't exist\nPlease try again again by passing the correct parameters to me.\delete($key, ".var_export($value, true)."), ".basename($container).", $sess_member) (0);";
				break;
				
				case !$key:
				$ret_val['debug'] = "Sorry I cannot delete an empty key\nPlease try again again by passing the correct parameters to me.\ndelete($key, ".var_export($value).", ".basename($container).", $sess_member) (1);";
				break;
				
				default:
				$ret_val = array_merge($ret_val, $this->_delete($container, $key));
				$sess_member = (empty($sess_member)) ? Session::settings : $sess_member.'.'.$this->container;
				switch($ret_val['success'])
				{
					case true:
					$ret_val['class'] = $this->classes['success'];
					$this->event['data'] = [
						'table' => 'NULL',
						'db' => 'NULL',
						'key' => $key,
						'value' => $value,
						'section' => $ret_val['section'],
						'action' => "Delete Config",
						'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." deleted value ($key -> '".var_export($value, true)."') from config file ".basename($container)
					];
					$this->trigger('afterDelete');
					break;
				}
				break;
			}
			break;
		}
		$ret_val['action'] = 'delete';
		$this->config['current']['action'] = $ret_val;
		Session::set(Configer::dm.'.action', $ret_val);
	}
	
	public function createContainer($name, $in=null, $engine='file')
	{
		$this->setEngine($engine);
		$ret_val = ["success" => false, 'class' => 'error'];
		switch($this->location)
		{
			case 'db':
			$data = ["containers" => ['author' => \Yii::$app->user->getId()]];
			$message = "";
			$result = 'failure';
			$action = ["success" => "Add Container", "failure" => "Add Container Fail"];
			$this->setTable($this->configTables['containers']);
			$pri['containers'] = $this->getCurPri();
			$data['containers']['name'] = $name;
			//first we chec the container info
			$cond = [
				"key" => ['name', $this->getCurPri()], 
				"data" => [$name, $name],
				"xor" => ["OR"]
			];
			$containerid = $this->check($cond['key'], $cond['data'], null, null, '=', $cond['xor'], true, $this->getCurPri());
			switch(empty($containerid))
			{
				case true:
				switch($this->insert(array_keys($data['containers']), array_values($data['containers'])))
				{
					case true:
					$message .= "added container for $in";
					$container_id = $this->last_id['insert'];
					$data["sections"][$pri['containers']] = $container_id;; 
					$data["sections"]['name'] = 'global';
					$this->setTable($this->configTables['sections']);
					$this->event['data'] = [
						'table' => 'config',
						'db' => $this->configDb,
						'action' => $action[$result],
						'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." ".$message
					];
					$this->trigger('afterCreate');
					$ret_val['class'] = $this->classes['success'];
					break;
				}
				break;
					
				default:
				$message = "was unable to add a new configuration container for $name becuase it already exists";
				break;
			}
			$ret_val['message'] = "The system ".$message;
			break;
			
			case 'file':
			$in = (!is_dir($in)) ? $this->dir['config'] : $in;
			$new_config_file = $in.$name.'.'.$this->types[$this->location];
			switch(empty($name))
			{
				case false:
				switch(file_exists($new_config_file))
				{
					case false:
					switch(fopen($new_config_file, 'c'))
					{
						case true:
						chmod($new_config_file, $filemode);
						$ret_val['success'] = true;
						$ret_val['message'] = "The system was able to create the config file".basename($new_config_file);
						$e = new Event;
						$this->event['data'] = [
							'table' => 'NULL',
							'db' => 'NULL',
							'action' => 'Add Config File',
							'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." added a new config file: ".basename($new_config_file)
						];
						$this->trigger('afterCreate');
						$ret_val['class'] = $this->classes['success'];
						break;
					}
					break;
					
					default:
					$ret_val['message'] = "The system was unable to create the config file because ".basename($new_config_file)." already exists";
					break;
				}
				break;
			}
			break;
		}
		$this->config['current']['action'] = $ret_val;
	}
	
	/*
     * Get the configuration information depending on container and location and store it in $this->config
	 * @param string $engine
	 * @param string $container
	 * @param boolean $get_values
     * @return mixed $config
     */
	public function getConfig($engine=null, $container=null, $get_values=false, $editing=false)
	{
		//$directory, $apppath=false, $match=null, $group=false, $empty=false, $skipthese=null, $parent=null
		$this->container = !empty($container) ? $container : $this->container;
		$engine = !empty($engine) ? $engine : $this->location;
		$ret_val = null;
		switch($engine)
		{
			case 'xml':
			$xml_files = $this->_o['directory']->getFilesMatching($this->config['current']['path'].$this->method['in'], false, ['.xml'], true, false, null, $this->config['current']['path']);
			foreach($xml_files[$this->method['in']] as $container)
			{
				$ret_val = [$container => '"'.file_get_contents($this->config['path'].$this->method['in'].DIRECTORY_SEPARATOR.$container).'"'];
			}
			break;
			
			case 'file':
			$ret_val = $this->readFrom($this->loadFrom($this->config['current']['path'], false, true), 
				null, 'json', $editing, $engine);
			break;
			
			
			case 'db':
			$ret_val = $this->readFrom($this->loadFrom($this->config['current']['container'], false, true), 
				null, 'json', $editing, $engine);
			break;
				
			default:
			break;
		}
		return $ret_val;
	}	
	
	/*
	 * Convert configuration between formats
	 * @param string $container
	 * @param string $from
	 * @param string $to
	 */
	public function convert($container, $from, $to)
	{
		$ret_val = [
			"success" => false, 
			"message" => "Unable to convert $container from $from to $to"
		];
		switch($this->isSupported($from) && $this->isSupported($to))
		{
			case true:
			$old_engine = $this->location;
			$this->setEngine($from);
			$config = $this->getConfig($from, $container, true, true);
			$this->setEngine($to);
			$this->writeTo($container, $config, $to);
			$ret_val['message'] = "Converted $container from $from to $to";
			$ret_val['success'] = true;
			$ret_val['action'] = 'convert';
			$this->config['current']['action'] = $ret_val;
			$this->setEngine($old_engine);
			break;
		}
	}
	
	/*---------------------
		Protected Functions
	---------------------*/
	
	/*
	 * Handle adding to DB or to file to simplify add function
	 * @param string|int $container
	 * @param string|int $key
	 * @return mixed
	 */
	protected function _add($container, $key, $value=null)
	{
		$correctKey = $this->correctKey($key);
		$hierarchy = explode('.', $correctKey);
		$name = isset($hierarchy[5]) ? $hierarchy[5] : (sizeof($hierarchy) == 3) ? $hierarchy[2] : null;
		$sectionName = isset($hierarchy[4]) ? $hierarchy[4] : $hierarchy[1];
		$ret_val = [
			'success' => false,
			'key' => $correctKey,
			'section' =>$sectionName,
			'container' => $container,
			'message' => "Unable to add value ".$value
		];
		$container = $this->getContainerId($container);
		switch($this->location)
		{
			case 'db':
			switch(sizeof($hierarchy))
			{
				//We're adding a section
				case 5:
				case 2:
				$section = [
					'author' => \Yii::$app->user->getId(), 
					"containerid" => $container,
					'name' => $sectionName,
				];
				$this->setTable($this->configTables['sections']);
				$check = ['name', 'containerid'];
				switch($this->check($check, array_intersect_key($section, array_flip($check))))
				{
					case false:
					switch($this->insert(array_keys($section), array_values($section)))
					{
						case true:
						$ret_val['value'] = [];
						$ret_val['success'] = true;
						$ret_val['message'] = "Added section ".$sectionName;
						break;
					}
					break;
				}
				break;
				
				case 6:
				case 3:
				$val = [];
				$val['author'] = \Yii::$app->user->getId();
				$val['name'] = $name;
				$val['value'] = $value;
				$val['containerid'] = $container;
				$val['sectionid'] = $this->getSectionId($container, $sectionName);
				//write the values
				$this->setTable($this->configTables['values']);
				$check = ['containerid', 'sectionid', 'name'];
				switch($this->check($check, array_intersect_key($val, array_flip($check))))
				{
					case false:
					switch($this->insert(array_keys($val), array_values($val)))
					{
						case true:
						$ret_val['value'] = rawurlencode($value);
						$ret_val['unique'] = $this->last_id['insert'];
						$ret_val['container_name'] = $ret_val['container'];
						$ret_val['unique_id'] = $key;
						$ret_val['section_name'] = $sectionName;
						$ret_val = array_merge($ret_val, $val);
						$ret_val['success'] = true;
						$ret_val['message'] = "Added $name to section $sectionName";
						break;
					}
					break;
				}
				break;
			}
			break;
			
			case 'file':
			$args = [];
			switch(sizeof($hierarchy))
			{
				//we're adding a section
				case 5:
				case 2:
				$args['command'] = "sed -i '\$a\\\n\\n[%s]' ";
				$args['args'] = [$name];
				$message = "Added new section [".$sectionName."] to ".$container;
				break;
				
	
				//we're adding a value
				case 6:
				case 3:
				$args['command'] = "sed -i '/\[%s\]/a %s = %s' ";
				$args['args'] = [$sectionName, $name, $value];
				$message = "Added new config option [".$name."] to ".$sectionName;
				break;
			}
			$args['command'] = vsprintf($args['command'], array_map(function ($v) {return preg_quote($v, DIRECTORY_SEPARATOR);}, $args['args'])).' "'.$container.'.'.$this->types[$this->location].'"';
			exec($args['command'], $output, $cmd_ret_val);
			//sed should return an empty value for success when editing files
			switch($cmd_ret_val)
			{
				case 0:
				$ret_val['unique'] = $sectionName.'.'.$name;
				$ret_val['name'] = $name;
				$ret_val['container_name'] = $ret_val['container'];
				$ret_val['section_name'] = $sectionName;
				$ret_val['unique_id'] = $key;
				$ret_val['message'] = $message;
				$ret_val['success'] = true;
				break;
				
			}
			break;
		}
		return $ret_val;
	}
	
	/*
	 * Handled editing in DB or in file to simplify edit function
	 * @param string|int $container
	 * @param string|int $key
	 * @return mixed
	 */
	protected function _edit($container, $key, $value)
	{
		$correctKey = $this->correctKey($key);
		$hierarchy = explode('.', $correctKey);
		$old_value = Session::getVal($correctKey);
		$old_value = sizeof($hierarchy == 3) ? json_encode($old_value) : $old_value['value'];
		$name = isset($hierarchy[5]) ? $hierarchy[5] : (sizeof($hierarchy) == 3) ? $hierarchy[2] : null;
		$sectionName = isset($hierarchy[4]) ? $hierarchy[4] : $hierarchy[1];
		$ret_val = [
			'success' => false,
			'old_value' => $old_value,
			'value' => rawurlencode($value),
			'section' => $sectionName,
			'container' => $key,
			'key' => $correctKey,
			'message' => "Unable to edit value ".$value
		];
		switch($this->location)
		{
			case 'db':
			switch(sizeof($hierarchy))
			{
				//we're editing a section
				case 5:
				case 2:
				$update['editor'] = \Yii::$app->user->getId();
				$update['updated_at'] = time();
				$update['table'] = $this->configTables['sections'];
				$update['keys'] = ['name'];
				$update['values'] = [$value];
				$message = "Edited the section name to $value";
				$update['condition'] = [
					'key' => ['id'], 
					'data' => [$this->cfg_id]
				];
				$update['process'] = true;
				break;
			
				//we're editing a value
				case 6:
				case 3:
				$update['editor'] = \Yii::$app->user->getId();
				$update['updated_at'] = time();
				$update['table'] = $this->configTables['values'];
				$update['keys'] = ['value'];
				$update['values'] = [$value];
				$message = "Edited the value [$key] from ".$old_value." to $value";
				$update['condition'] = [
					'key' => ['id'], 
					'data' => [$this->cfg_id]
				];
				$update['process'] = true;
				$ret_val['name'] = $name;
				break;
			}
			switch($update['process'])
			{
				case true:
				$this->setTable($update['table']);
				switch($this->update($update['keys'], $update['values'], $update['condition']))
				{
					case true:
					$ret_val['success'] = true;
					$ret_val['message'] = $message;
					break;
				}
				break;
			}
			break;
			
			case 'file':
			$args = [];
			$container = $this->resolveDir($this->config['current']['path']);
			switch(sizeof($hierarchy))
			{
				//we're editing a section
				case 5:
				case 2:
				$args['command'] = 'sed -i -e "s/^\[%s\]/%s/" ';	
				$args['args'] = [$sectionName, $value];
				$message = "Edited the section name from ".$name." to $value";
				break;
			
				//no support for editing section names as of yet
				case 6: 
				case 3:    
				$args['command'] = 'sed -i -e "/^\[%s\]/,/^$/{s/%s =.*/%s = %s/}" ';
				$args['args'] = [$sectionName, $name, $name, $value];
				$message = "Edited the value name from ".$name." to $value";
				break;
			}
			$args['command'] = vsprintf($args['command'], array_map(function ($v) {return preg_quote($v, DIRECTORY_SEPARATOR);}, $args['args'])).'"'.$container.'.'.$this->types[$this->location].'"';
			exec($args['command'], $output, $cmd_ret_val);
			//sed should return an empty value for success when editing files
			switch($cmd_ret_val)
			{
				case 0:
				$ret_val['message'] = $message;
				$ret_val['success'] = true;
				break;
				
			}
			break;
		}
		return $ret_val;
	}
	
	/*
	 * Handle deleting in DB or in file to simplify delete function
	 * @param string|int $container
	 * @param string|int $key
	 * @return mixed
	 */
	protected function _delete($container, $key)
	{
		$correctKey = $this->correctKey($key);
		$hierarchy = explode('.', $correctKey);
		$name = isset($hierarchy[5]) ? $hierarchy[5] : (sizeof($hierarchy) == 3) ? $hierarchy[2] : null;
		$sectionName = isset($hierarchy[4]) ? $hierarchy[4] : $hierarchy[1];
		$ret_val = [
			'success' => false,
			'value' => Session::getVal($correctKey),
			'container' => $key,
			'key' => $correctKey,
			'message' => "Unable to delete ".$key,
			'section' => $sectionName
		];
		switch($this->location)
		{
			case 'db':
			switch(sizeof($hierarchy))
			{
				//we're deleting a section
				case 5:
				case 2:
				$delete['table'] = $this->configTables['sections'];
				$delete['keys'] = ['id'];
				$delete['values'] = [$this->getSectionId($this->getContainerId($container), $sectionName)];
				$message = "Deleted the $key";
				$delete['process'] = true;
				break;
			
				//we're deleting a value
				case 6:
				case 3:
				$ret_val['name'] = $name;
				$delete['table'] = $this->configTables['values'];
				$delete['keys'] = ['id'];
				$delete['values'] = [$this->cfg_id];
				$message = "Deleted the value $key";
				$delete['process'] = true;
				break;
			}
			switch($delete['process'])
			{
				case true:
				$this->setTable($delete['table']);
				switch($this->remove($delete['keys'], $delete['values']))
				{
					case true:
					$ret_val['success'] = true;
					$ret_val['message'] = $message;
					break;
				}
				break;
			}
			break;
			
			case 'file':
			$args = [];
			$container = $this->resolveDir($this->config['current']['path']);
			switch(sizeof($hierarchy))
			{
				//are we deleting a value/line?
				case 6:
				case 3:
				$args['command'] = "sed -i '/^\[%s\]/,/^$/{/^%s =.*/d}' ";
				$args['args'] =[$name, $sectionName];
				$message = "Deleted value ".$hierarchy." in ".$sectionName;
				break;
				
				//we're deleting a section
				case 5:
				case 2:
				$args['command'] = "sed -i '/^\[%s\]/,/^$/d' ";
				$args['args'] = [$name];
				$message = "Deleted the section ".$name;
				break;
				
				//we're deleting a container
				case 2:
				$args['command'] = "rm -f '%s'";
				$args['args'] = [$container];
				$message = "Deleted the file ".$container;
				break;
			}
			$args['command'] = vsprintf($args['command'], array_map(function ($v) {return preg_quote($v, DIRECTORY_SEPARATOR);}, $args['args'])).' "'.$container.'.'.$this->types[$this->location].'"';
			exec($args['command'], $output, $cmd_ret_val);
			//sed should return an empty value for success when editing files
			switch($cmd_ret_val)
			{
				case 0:
				$ret_val['message'] = $message;
				$ret_val['success'] = true;
				break;
				
			}
			break;
		}
		return $ret_val;
	}
	
	/*
	 * Get teh proper path for this container
	 * @param string $container
	 * @return string
	 */
	protected function resolveDir($container)
	{
		return ($container[0] == '@') ? $this->dir['config'].substr($container, 1, strlen($container)) : $container;
	}
	
	/*
	 * Log the data to the DB
	 * @param mixed $data
	 */
	protected function log($data=[])
	{
		$this->initLogging();
		$this->l->addTrans($data['table'], $data['db'], $data['action'], $data['message']);
	}
	
	/*
	 * Get the configuration containers: file or database
	 * @param string $in
	 * @param boolean $multi
	 * @param boolean $containers_only
	 * @return mixed
	 */
	protected function getContainers($in=null, $multi=false, $containers_only=false)
	{
		$in = ($in == null) ? $this->dir['config'] : $in;
		$ret_val = [];
		switch($this->location)
		{
			case 'db':
			$this->setDb($this->configDb, $this->configTables['containers']);
			$pri = $this->getCurPri();
			$sel_fields = ["name"];
			$this->select(array_merge($sel_fields, ["$pri AS `unique`", "'$pri' AS unique_name"]), true, null, null, true);
			$result = $this->result(DB::R_ASS, true);
			array_walk($result, function ($val, $key) use(&$ret_val) {
				$ret_val[$val['name']] = $val['name'];
			});
			$this->config['containers'] = $ret_val;
			$this->config['load']['containers'] = true;
			break;
			
			case 'file':
			switch(is_dir($in))
			{
				case true:
				foreach(scandir($in) as $container)
				{
					switch($containers_only)
					{
						case true:
						switch(is_dir($in.$container))
						{
							case true:
							$ret_val[$container] = $container;
							break;
						}
						break;
						
						default:
						switch(1)
						{
							case is_dir($in.$container):
							switch($multi)
							{
								case true:
								$ret_val[$container] = $this->_o['directory']->getFilesMatching($in.$container, $multi, $containers_only);
								break;
							}
							break;
							
							case $container == '..':
							case $container == '.':
							break;
							
							default:
							$info = pathinfo($in.$container);
							switch(1)
							{
								case true:
								$label = str_replace(['-', '_'], ' ', $info['filename']);
								$ret_val[$info['filename']] = $label;
								break;
							}
							break;
						}
						break;
					}
				}
				$this->config['containers'] = $ret_val;
				$this->config['load']['containers'] = true;
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/*---------------------
		Private Functions
	---------------------*/
	
	private  function deleteContainer($in, $name, $ext)
	{
		$ret_val = ["success" => false];
		switch($this->location)
		{
			case 'db':
			//delete all config settings with $in $name $ext Just set the deleted key to deleted instead of actually deleting the value, for backup purposes
			$data = ["keys" => ['deleted', 'author'], "values" => [1, Yii::$app->user->getId()]];
			$cond = ["key" => [$pri['containers']], "data" => [$name]];
			$message = "";
			$action = ["success" => "Deleted Config", "failure" => "Delete Config Fail"];
			foreach($this->configTables as $table)
			{
				$this->setTable($table);
				$ret_val['success'] = $ret_val['success'] and $this->update($data['keys'], $data['values'], $cond);
				switch($ret_val)
				{
					case true:
					$message .= "deleted config for $name in $table\n\n";
					$result = "success";
					$this->trigger('afterDelete', new Event($this, [
							'table' => 'config',
							'db' => $this->configDb,
							'action' => $action[$result],
							'message' => "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." ".$message
						])
					);
					break;
					
					default:
					$message .= "couldn't delete config for $name in $table\n\n";
					$result = "failure";
					break;
				}
			}
			$ret_val['message'] = "I ".$message;
			break;
			
			case 'file':
			$config_file = $in.$name.'.'.$ext;
			$ret_val['message'] = 'I was unable to delete the config file '.basename($config_file);
			switch(empty($name))
			{
				case false:
				switch(file_exists($config_file))
				{
					case false:
					switch(@unlink($config_file))
					{
						case true:
						$this->trigger('afterDelete', new Event($this, [
								'table' => 'NULL',
								'db' => "NULL",
								'action' => "Delete Config File",
								'message' => $action[$result], "On ".date("F j, Y @ g:i a")." user ".Session::getVal('securer.username')." deleted config file: ".basename($config_file)
							])
						);
						$ret_val['success'] = @unlink($config_file);
						$ret_val['message'] = 'I was able to delete the config file '.basename($config_file);
						break;
					}
					break;
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	private function open($container, $rw='read')
	{
		if(!$container)
		{
			die("Attempting to open an empty file\n$this->open($container, $rw, $mode) (2);");
		}
		//the handle that needs to be returned if successful
		$ret_val = false;
		$continue = false;
		$flmode = null;
		switch($rw)
		{
			case 'write':
			$mode = 'w';
			switch(file_exists($container))
			{
				case true:
				$this->handle = @fopen($container, $mode);
				switch(is_resource($this->handle))
				{
					case true:
					$continue = true;
					$flmode = LOCK_EX;
					break;
					
					default:
					@chmod($container, $this->filemode);
					$this->handle = @fopen($container, $mode);
					switch(is_resource($this->handle))
					{
						case true:
						$continue = true;
						$flmode = LOCK_EX;
						break;
						
						default:
						break;
					}
					break;
				}
				break;
			}
			switch(is_resource($this->handle))
			{
				case false:
				die("Cannot open $container for writing\n$this->open($container, $rw) (2);");
				break;
			}
			break;
		
			default:
			$mode = 'r';
			$this->handle = @fopen($container, $mode);
			switch(is_resource($this->handle))
			{
				case true:
				$continue = true;
				$flmode = LOCK_SH;
				break;
				
				default:
				//die("Cannot open $container for reading\n<br>$this->open($container, $rw) (2);");
				break;
			}
			break;
		}
		if($continue)
		{
			$interval = 10;
			while(flock($this->handle, $flmode) === false)
			{
				//sleep a little longer each time (in microseconds)
				usleep($interval+10);
			}
			$ret_val = $this->handle;
		}
		return $ret_val;
	}
	
	private function close()
	{
		if(is_resource($this->handle))
		{
			flock($this->handle, LOCK_UN);
			fclose($this->handle);
		}
	}
	
	/*
	 * Is this engine supported?
	 * @param string $engine
	 * @return boolean is supported?
	 */
	 private function isSupported($engine)
	 {
		return isset($this->supported[$engine]);
	 }
	 
	 /*
	  * Get the container id for a given value
	  * @param string|int $container
	  * @return int containerid
	  */
	 private function getContainerId($container)
	 {
		 switch($this->location)
		 {
			case 'file':
			$containerid = [$this->resolveDir($this->config['current']['path'])];
			break;
			
			case 'db':
			$this->setTable($this->configTables['containers']);
			$pri = $this->getCurPri();
			$cond = [
				"key" => ['name', $pri],
				"data" => [$container, $container],
				"xor" => ["OR"]
			];
			$containerid = $this->check($cond['key'], $cond['data'], null, null, '=', $cond['xor'], [$pri]);
			break;
		 }
		return is_array($containerid) ? $containerid[0] : null;
	 }
	 
	 /*
	  * Get the section id for a given value
	  * @param string|int $container
	  * @param string|int $section
	  * @return int containerid
	  */
	 private function getSectionId($container, $section)
	 {
		$this->setTable($this->configTables['sections']);
		$pri = $this->getCurPri();
		$cond = [
			"key" => [
				DB::FLAG_ASIS."(`name`='$section' OR `$pri`='$section')", 'containerid'
			], 
			"data" => [
				DB::FLAG_NULL, $container
			],
			"xor" => [
				 'AND'
			]
		];
		$sectionid = $this->check($cond['key'], $cond['data'], null, null, '=', $cond['xor'], [$pri]);
		return is_array($sectionid) ? $sectionid[0] : null;
	 }
}
?>