<?php

namespace nitm\models;

use yii\base\Model;
use nitm\helpers\Session;
use nitm\helpers\Network;

class Logger extends DB
{
	//constant data
	const SEP = DIRECTORY_SEPARATOR;
	const LT_FILE = 'file';
	const LT_DB = 'db';
	const L_FERM = -10001;	//code to write a default close message
	const L_SHIN = -10002;	//code to write a default new data message
	const L_CONC = -10003;	//code to write default append message
	const ERR_NOFNAME = "Error : Empty File (Enter filename as first argument to constructor)";	//code to indicate empty filename
	const ERR_BADDIR = "Error : Log directory (%s) doesn't exist";	//code to indicate invalid directory
	const ERR_DIRCREATE = "Error : Unable to create sub diretory (%s)";	//code to indicate invalid directory
	
	//public data
	public $type;
	public $currentUser = 'server';
	public $logDb = null;
	public $logTable = 'logs';    			// the directory you wish to save the file to
	public $filename; 									// default log file name
	public $fullpath;
	public $ext = '.txt';
	
	//protected data
	protected $msgClose = "End of Log Transaction";
	protected $msgNew = "Begining New Log";
	protected $msgApp = "Adding New Log Information";
	
	//private data
	private $handle;						// this will hold the resource for the open file
	private $logDir = "../runtime/logs/logger"; 
	
	public function init()
	{
		switch($this->type)
		{
			case self::LT_FILE:
			$this->prepareFile($this->filename, $this->logDir, $this->ext);
			break;
			
			case self::LT_DB:
			switch((@strlen($this->logDb) >= 1) && (@strlen($this->logTable) >= 1))
			{
				case true:
				$this->logDb = $this->logDb;
				$this->logTable = $this->logTable;
				break;
				
				default:
				$this->logDb = \Yii::$app->getModule('nitm')->logOptions['db'];
				$this->logTable = \Yii::$app->getModule('nitm')->logOptions['table'];
				break;
			}
			$this->prepareDb();
			break;
		}
		//$currentUser = Session::getVal(AUTH_DOMAIN.'.username');
		$this->logDir = is_dir($this->logDir) ? $this->logDir : $_SERVER['DOCUMENT_ROOT'].$this->logDir;
		$this->currentUser = (!\Yii::$app->hasProperty('user')) ? new User(['username' => 'console']) : \Yii::$app->user->identity;
	}
	
	public function __destruct()
	{
		unset($this->handle, $this);
	}
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	/**
	 * Function to change the log database and table
	 * @param string $db
	 * @param string $table
	 */
	public function changeLogDbt($db=null, $table=null)
	{
		if($db)
		{
			$this->logDb = $db;
		}
		if($table)
		{
			$this->logTable = $table;
		}
		$this->setDb($this->logDb, $this->logTable);
	}
	
	public function prepareFile($filename, $dir=null, $ext=null)
	{
		if($filename)
		{
			$this->ext = ($ext == null) ? $this->ext : $ext;
			$this->filename = $filename."-".date("Y-m-d")."-log".$this->ext;  //set filen name
			$dir = ($dir == null) ? null : (($dir[strlen($dir) -1] == self::SEP) ? $dir : $dir.self::SEP);
			$this->logDir = ($dir == null) ? $this->logDir.$filename.self::SEP : $dir.$filename.self::SEP;
			if(!is_dir($this->logDir))
			{
				if(!mkdir($this->logDir, 0775, true))
				{
					die(sprintf(self::ERR_DIRCREATE, $this->logDir)."\r\n");
				}
			}
			/*else
			{	
				die(sprintf(self::ERR_BADDIR, $this->logDir)."\r\n");
			}*/
		}
		else
		{
			//echo self::ERR_NOFNAME."\r\n";
		}
// 		parent::__construct();
// 		echo "<h1>currentUser == $this->currentUser sccess string == ".parent::securer.".username</h1>";
// 		exit;
	}
	
	public function prepareDb()
	{
		
		$this->setDb($this->logDb, $this->logTable);
	}
	
	public function changeFile($filename, $dir=null)
	{
		if($filename)
		{
			$this->filename = $filename."-".date("Y-m-d")."-log".$this->ext;  //set filename
			$this->logDir = ($dir == null) ? $this->logDir : $dir;
		}
		else
		{
			echo self::ERR_NOFNAME."\r\n";
		}
	}
	
	public function changeDir($dir)
	{
		if(is_dir($dir))
		{
			$this->logDir = ($dir == null) ? $this->logDir : $dir;
		}
	}
	
	public function changeExt($ext)
	{
		if($ext)
		{
			$this->ext = ($ext == null) ? $this->ext : $ext;
		}
	}
	
	private function open()
	{
		// open file
		$this->fullpath = $this->logDir.$this->filename;
		if(file_exists($this->fullpath))
		{
			$this->handle = fopen($this->fullpath,'a+');
			if(!$this->handle)
			{
				return false;
			}
			$msg = $this->msgApp;
		}
		else
		{
			$this->handle = fopen($this->fullpath,'w+b');
			if(!$this->handle)
			{
				return false;
			}
			$msg = $this->msgNew;
		}
		if(!is_resource($this->handle))
		{
			$errmsg = "Log error: Unable to open file: {$this->fullpath}\n
			<br>Suggestion: Check file path and folder permissions.\n
			<br>script exiting(2)\n\n";
			echo $errmsg;
			$this->endLog();
			exit(3);
		}
		@chmod($this->fullpath, 0775);
		$this->write("\n".str_repeat("-", 100), true); 
		$this->write(date("[M-d-Y H:i:s] ").$msg." user: ".$this->currentUser->username, 1);
		$this->write("\n".str_repeat("-", 100), true);	
		return $this->fullpath;
	}
	// end open
	
	public function endLog()
	{
		if(is_resource($this->handle))
		{
			$this->write("\n".str_repeat("-", 100), true); 
			$this->write(date("[M-d-Y H:i:s] ").$this->msgClose, 1);
			$this->write("\n".str_repeat("-", 100), true);
			fclose($this->handle);
			$this->handle = false;
		}
	}
	//-- end endLog
	
	
	public function read()
	{
		// open file
		// call file opening function
		$this->fullpath = $this->logDir.$this->filename;
		$this->handle = fopen($this->fullpath,'rb');
		if(!is_resource($this->handle))
		{
			$errmsg = "Log error: Unable to open file: {$this->fullpath}\n
			<br>Suggestion: Check file path and folder permissions.\n
			<br>script exiting(3)\n\n";
			echo $errmsg;
			$this->endLog();
			exit(3);
		}
		$contents = fread($this->handle);
		$this->endLog();
		return $contents;
	}
  
	// end open
	public function write($logtext="", $notime=false, $tag=false)
	{
		// call file opening function
		if(!$this->handle)
		{
            		$this->open();
		}
		$text = ($notime == false) ? date("D-M-d-Y [H:i:s]")." - $logtext\n" : $logtext."\n";
		// write log file heading
		// now add defined log content
		switch($this->ext)
		{
				case ".txt":
				$text = strip_tags($text);	
				break;
				
				case ".html":
				case ".xml":
				switch($tag == false)
				{
					case true:
					case 1:
					break;
					
					case false:
					case 0:
					$o = preg_replace("/($tag)/", "<\\1>", $tag);
					$c = preg_replace("/($tag)/", "</\\1>", $tag);
					$logtext = $o.$logtext.$c;
					break;
					
					default:
					break;
				}
				break;
				
				default: 
				$text = strip_tags($text);
				break;
		}
		if(Session::getVal("settings.globals.allow_log") == true)
		{
			if(fwrite($this->handle, wordwrap($text, 512, "\n\n")) === false)
			{
				// write faled issue error
				$errmsg = "Log error: Unable to write to file: {$this->fullpath}\n
				<br>Suggestion: Check file path and folder permissions.\n
				<br>script exiting(4)\n\n";
				echo $errmsg;
				$this->__destruct();
				exit(4);
			}
		}
		return true;
	}
	//- end write
	
	//function to add db transaction
	public function addTrans($db, $table, $action, $note)
	{
		if(empty($db) || empty($table) || empty($note) || empty($action))
		{
			die("<br>I won't add a transaction if you don't supply all arguments to me!!!<br>\n Logger::addTrans(\$db=>$db,\n<br>\$table=>$table,\n<br>\$action=>$action,\n<br>\$note=>$note)<br>");
		}
		else
		{
			switch(Session::getVal("settings.globals.allow_log"))
			{
				case true:
				case 'true':
				case 1:
				parent::changeDbt($this->logDb, $this->logTable);
				$this->prepareDb();
				$hostname = (empty($_SERVER['REMOTE_HOST'])) ? Network::getHost(@$_SERVER['REMOTE_ADDR']) : $_SERVER['REMOTE_HOST'];
				$hostname = (empty($hostname)) ? 'localhost' : $hostname;
				$ipaddr = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'localhost';
				parent::insert(array('added', 'user', 'action', 'notes', 'ip_addr', 'host', 'db_name', 'table_name'), array(strtotime('now'), $this->currentUser->username, $action, $note, $ipaddr, $hostname, $db, $table), null, null, true);
				parent::revertDbt();
				break;
			}
		}
	}
	//end fucntion
}
// end log class 
?>
