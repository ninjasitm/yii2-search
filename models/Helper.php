<?php

namespace nitm\module\models;

use yii\db\ActiveRecord;
use yii\base\Model;

//class that sets up and retrieves, deletes and handles modifying of contact data
class Helper extends Model
{
	//setup public data
	public static $method;
	public static $readable_date = null;

	//setup protected data
	protected static $session = null;
	protected static $sess_name = "helper.";
	protected static $no_q = array('settings','securer','helper','batch', 'fields', 'configer', 'comparer');
	protected static $q = array('adder','deleter','updater','general');
	protected static $b_q = array('batch','deleter');

	//setup private data
	private $id;
	private static $csdm;				//Current Session Data Member
	private static $compare = false;
	private static $lock = 'locked_csdm';

	//define constant data
	
	//you can use a qualifier for these
	const batch = 'batch';
	const securer = 'securer';
	const helper = 'helper';
	const settings = 'settings';
	const fields = 'fields';
	const configer = 'configer';
	const comparer= 'comparer';
	
	//you don't have to use a qualifier for these but the csdm will be used
	const adder = 'adder';
	const deleter = 'deleter';
	const updater = 'updater';
	const general = 'general';
	
	//private class constants
	const variables = 'helper-variables';
	const reg_vars = "reg-vars";
	const name = "name";
	const object = 'oHelper';
	const csdm_var = 'csdm';
	
	public function __construct($dm=null, $db=null, $table=null, $compare=false, $driver=null)
	{
		$_SERVER['SERVER_NAME'] = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : $_SESSION['SERVER_NAME'];
		self::$session = (empty(self::$session)) ? self::$sess_name.$_SERVER['SERVER_NAME'] : self::$session;
// 		if(@is_object($_SESSION[self::$session][self::variables][self::reg_vars][self::object]))
// 		{
// 			return $_SESSION[self::$session][self::variables][self::reg_vars][self::object];
// 		}
		if(!isset($_SESSION[self::$session]))
		{
			$_SESSION[self::$session] = array();
			if(!isset($_SESSION[self::$session][self::variables]))
			{
				$_SESSION[self::$session][self::variables] = array();
			}
			if(!isset($_SESSION[self::$session][self::variables][self::reg_vars]))
			{
				$_SESSION[self::$session][self::variables][self::reg_vars] = array();
				$_SESSION[self::$session][self::variables][self::reg_vars][self::object] = NULL;
			}
		}
		if(!is_null($dm))
		{
			self::$compare = $compare;
			$_SESSION[self::$session][self::variables][self::csdm_var] = $dm;
			$_SESSION[self::$session][self::variables][self::$lock] = $dm;
			self::register($dm);
		}
		/*if(!@is_object($_SESSION[self::$session][self::variables][self::reg_vars][self::object]))
		{
// 			pr(var_dump($this));
			$_SESSION[self::$session][self::variables][self::reg_vars][self::object] = serialize($this);
		}*/
		if($compare == true)
		{
			self::register(self::comparer);
		}
	}
	
	public function behaviors()
	{
		$behaviors = array(
				"DB" => array(
					"class" => \nitm\module\models\DB::className(),
					"login" => [
						'localhost',
						\Yii::$app->params['components.db']['username'],
						\Yii::$app->params['components.db']['password'],
					]
				),
			);
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public function init()
	{
		self::$method = $_REQUEST;
	}
	
	//function to remove carriage returns and newlines from strings
	public static function stripNl($s)
	{
		switch(gettype($s))
		{
			case 'array':
			foreach($s as $k=>$v)
			{
				$s[$k] = self::stripNl($v);
			}
			return $s;
			break;
		
			default:
			return str_replace(array("\r", "\n", "\r\n"), '', $s);
			break;
		}
	}
	
	/*
		Function to return boolean value of a variable
		$var = value
	*/
	public static function boolVal($val)
	{
		$ret_val = 'nobool';
		switch(true)
		{
			case (true === $val):
			case (1 === $val) || ('1' === $val):
			case ($val && (strtolower($val) === 'true')):
			case ($val && (strtolower($val) === 'on')):
			case ($val && (strtolower($val) === 'yes')):
			case ($val && (strtolower($val) === 'y')):
			$ret_val = true;
			break;
			
			case (false === $val):
			case (0 === $val) || ('0' === $val):
			case ($val && (strtolower($val) === 'false')):
			case ($val && (strtolower($val) === 'off')):
			case ($val && (strtolower($val) === 'no')):
			case ($val && (strtolower($val) === 'n')):
			$ret_val = false;
			break;
		}
		return $ret_val;
	}
	
	/*
		Function to return an array for the given parameters:
		$string = The string code with all values
		$sep = The separator used in $string
		$sur = What to surround the values with
		$num = Should numeric values be left alone and not surround with $sur?
	*/
	public final function getArray($string, $sep=',', $sur="'", $num=true)
	{
		$ret_val = false;
		$sep = ($sep == null) ? ',' : $sep;
		$sur = ($sur == null) ? "'" : $sur;
		switch(is_string($string) || ($string == 0))
		{
			case true:
			$string = explode($sep, $string);
			$string = is_array($string) ? $string : array($string);
			$ret_val = DB::splitf($string, $sep, true, $sur, null, $num);
			eval("\$ret_val = array($ret_val);");
			break;
			
			default:
			$ret_val = $string;
			break;
		}
		return $ret_val;
	}
	
	/*static function to get safe string. Essentially replace all non string characters with underscores, 
	or unless specified by $s and $r
	$subject = string to be checked
	$s = what to search for
	$r = what to replace with
	*/
	public static function getSafeString($subject, $s=null, $r=null)
	{
		$s = (!empty($s)) ? $s : array("/([^a-zA-Z0-9\\+])/", "/([^a-zA-Z0-9]){1,}$/", "/([\s]){1,}/");
		$r = (!empty($r)) ? $r : array(" ", "", "_");
		return preg_replace($s, $r, $subject);
	}
	
	public static final function setCsdm($dm, $compare=false)
	{
// 		echo "Setting Helper csdm for $dm <br>";
		self::$compare = $compare;
		$_SESSION[self::$session][self::variables][self::csdm_var] = $dm;
		$_SESSION[self::$session][self::variables][self::$lock] = $dm;
		self::register($dm);
		return true;
	}
	
	public static final function getCsdm()
	{
		if(isset($_SESSION[self::$session][self::variables][self::csdm_var]))
		{
			return $_SESSION[self::$session][self::variables][self::csdm_var];
		}
		return null;
	}
	
	public static final function app($cIdx, $data, $compare=false)
	{
		return self::set($cIdx, $data, $compare, true);
	}
	
	public static function set($cIdx, $data, $compare=false, $array=false)
	{		
// 		echo "Setting $cIdx to $data<br>";
// 		pr(debug_backtrace());
		$ret_val = false;
		if(is_array($cIdx) && (sizeof($data) < sizeof($cIdx)))
		{
			return false;
		}
		$csdm = ($compare === true) ? self::comparer : @$_SESSION[self::$session][self::variables][self::csdm_var];
// 		pr($_SESSION[self::$session]);
// 		echo "End here<br>";
// 		pr($_SESSION[self::$session][self::variables]);
// 		echo "\nHelper string called by ".get_class($this)." == ".self::$session.".".self::variables.".".self::csdm_var."<br>";
//		var_dump(debug_backtrace());
//		exit;
		$cIdx = (is_null($cIdx)) ? $csdm : $cIdx;
		$cIdx = (is_array($cIdx)) ? $cIdx : array($cIdx);
		self::$compare = $compare;
		foreach($cIdx as $dx)
		{
			$hier = explode('.', $dx);
			switch($hier[0])
			{
				case in_array($hier[0], self::$no_q) === true:
				self::register($hier[0]);
				break;
				
				default:
				if($hier[0] != $csdm)
				{
					array_unshift($hier, $csdm);
				}
				break;
			}
			$hierarchy[] = $dx;
			self::register($dx);
		}
		foreach($hierarchy as $idx=>$member)
		{
			$member_str = $member;
			$member = explode('.', $member);
			switch($member[0])
			{
				case in_array($member[0], self::$q) === true:
				case in_array($member[0], self::$no_q) === true:
				case null;
				$csdm = $member[0];
				break;
				
				default:
				$csdm = @$_SESSION[self::$session][self::variables][self::csdm_var];
				array_unshift($member, $csdm);
				break;
			}
// 			echo "Member == $member && value == $data<br>";
			switch(isset($data[$idx]) && is_array($data[$idx]))
			{
				case true:
				foreach($data[$idx] as $jdx=>$jvalue)
				{
					if(self::inSession($member_str, $jvalue) === false)
					{
						switch($array)
						{
							case true:
							eval("\$_SESSION['".self::$session."']['".DB::splitf($member, "']['")."'][] = \$jvalue;");
							break;
							
							default:
							eval("\$_SESSION['".self::$session."']['".DB::splitf($member, "']['")."'] = \$jvalue;");
							break;
						}
					}
				}
				break;
				
				default:
// 				pr($data);
				if(self::inSession($member_str, $data) === false)
				{
					switch($array)
					{
						case true:
						eval("\$_SESSION['".self::$session."']['".DB::splitf($member, "']['")."'][] = \$data;");
						break;
						
						default:
// 						echo "Setting here $member to $data<br>";
						eval("\$_SESSION['".self::$session."']['".DB::splitf($member, "']['")."'] = \$data;");
						break;
					}
				}
				break;
			}
			
		}
// 		pr($_SESSION);
		$ret_val = $data;
// 		echo self::getVal($cIdx);
		return $ret_val;
	}
	
	//set batch ID's for misc use
	public function setBatch($cID)
	{
		self::setCsdm(self::batch);
		self::set(self::batch, $cID, true);
		return $cID;
	}
	//end section
	
	public function appBatch($cID, $cIdx=false, $clear=false)
	{
		$csdm = ($cIdx === false) ? self::batch : $cIdx;
		$cIdx = ($cIdx === false) ? $csdm : $cIdx;
		self::setCsdm($csdm);
		switch($clear)
		{
			case true:
			self::del($cIdx);
			break;
		}
		self::app($cIdx, $cID, true);
		return $cID;
	}

	//void section
	public final function voidBatch($cID, $cIdx=false)
	{
		$ret_val = $cID;
		switch($cIdx)
		{
			case false:
			$cIdx = self::batch;
			break;
		}
		if(!self::isRegistered($cIdx))
		{	
			$ret_val = false;
		}
		else
		{
			if(($key = @array_search($cID, self::getVal($cIdx))) !== false)
			{
				self::del("$cIdx.$key");
			}
		}
		return $ret_val;
	}

	public static final function del($cIdx)
	{
// 		echo "deleting $cIdx<br>";
		$value = self::getVal($cIdx);
		$ret_val = self::unregister($cIdx);
		return array("item"=> $cIdx , "value" => $value, "ret_val" => $ret_val);
	}
	
	public static final function pop($array, $index) 
	{
		if(is_array($array)) 
		{
			unset ($array[$index]);
			array_unshift($array, array_shift($array));
			return $array;
		}
		else 
		{
			return false;
		}
	}
	
	public static final function clear($cIdx)
	{
		switch($cIdx)
		{
			case in_array($cIdx, self::$no_q) === true:
			case in_array($cIdx, self::$q) === true:
			case null:
			$_SESSION[self::$session][$cIdx] = array();
			break;
			
			default:
			$_SESSION[self::$session][self::getCsdm()] = array();
			break;
		}
		return true;
// 		echo "Cleared $cIdx<br>";
	}
	
	public static final function cleanup()
	{
		self::close();
		self::destroy();
	}

	public static final function getVal($cIdx, $bool=false)
	{
// 		echo "Getting $cIdx<br>\n";
//		echo "<pre>";
//			print_r(debug_backtrace());
//		echo "</pre";
		$ret_val = null;
		if(self::isRegistered($cIdx) !== false)
		{
			if(($ret_val = self::get($cIdx)) !== false)
			{
// 				pr($ret_val);
// 				if(is_array($ret_val['value']))
// 				{
// 					ksort($ret_val['value']);
// 				}
				$value = ($bool == true) ? self::boolVal($ret_val['value']) : $ret_val['value'];
				$type = gettype($value);
				switch($type)
				{	
					case "boolean":
					case "integer":
					case "double":
					case "string":
					case "array":
					settype($value, $type);
					break;
				}
				$ret_val = $value;
			}
		}
		else
		{
//			echo "$cIdx is not regsitered\n";
		}
		$debug = debug_backtrace();
		$line = $debug[0];
 		//echo "Returning $cIdx value $ret_val called from ".$line['line']." ".$line['file']."<br>";
		return $ret_val;
	}

	public static final function size($item, $size_only=false)
	{
		if(!self::isRegistered($item))
		{	
			return 0;
		}	
		else
		{
			$ret_val = self::getVal($item);
			$hierarchy = explode('.', $item);
			$access_str = "['".DB::splitf($hierarchy, "']['")."']";
			$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];;
			$access_str = ($csdm != null) ? (($csdm == $hierarchy[0]) ? $access_str : ((!in_array($hierarchy[0], self::$no_q)) ? $csdm.$access_str : $access_str)) : $access_str;
			eval("\$size = sizeof(\$_SESSION['".self::$session."']".$access_str.");");
			switch($size_only)
			{
				case false:
				$ret_val = array('value' => self::getVal($item), 'size' => $size, 'idx' => $item);
				break;
				
				case  true:
				$ret_val = $size;
				break;
			}
			return $ret_val = (!$ret_val) ? 0 : $ret_val;
		}
	}/*
	 * Using dot notation see if this path exists
	 * @param string $cIdx
	 * @return bool
	 */
	public static final function isRegistered($cIdx)
	{
		$ret_val = false;
		switch($cIdx)
		{
			case in_array($cIdx, self::$no_q) === true:
			case in_array($cIdx, self::$q) === true:
			$ret_val = isset($_SESSION[self::$session][$cIdx]);
			break;
			
			default:
			$hierarchy = explode(".", $cIdx);
			switch($hierarchy[0])
			{	
				case in_array($hierarchy[0], self::$q) === true:
				case in_array($hierarchy[0], self::$no_q) === true:
				case ($hierarchy[0] == @$_SESSION[self::$session][self::variables][self::csdm_var]):
				break;
				
				default:
				array_unshift($hierarchy, @$_SESSION[self::$session][self::variables][self::csdm_var]);
				break;
			}
// 			if($csdm == 'securer')
// 			{
// 				echo "csdm == $csdm<br>";
// 				pr($_SESSION);
// 				pr($session);
// 			}
			eval("\$ret_val = isset(\$_SESSION['".self::$session."']['".DB::splitf($hierarchy, "']['")."']);");
// 			self::pr("['self::$session']['".DB::splitf($hierarchy, "']['")."']");
// 			eval("echo \$_SESSION['".self::$session."']['".DB::splitf($hierarchy, "']['")."'];");
			//print_r("Returning $ret_val for $cIdx<br>");
			break;
		}
// 		echo "Returning $ret_val for is_registrered($cIdx)<br>";
// 		pr($ret_val);
		return $ret_val;
	}
	
	/*
	 * Perform a replacement easily with preg_replace
	 * @param mixed $what
	 * @param mixed $with
	 * @param mixed $in
	 * @return bool
	 */
	public function replace($what='/[^a-zA-Z0-9 -]/', $with='_', $in)
	{
		return preg_replace($what, $with, $in);
	}
	
	/*---------------------
		Protected Functions
	---------------------*/
	
	/*
	 * Get a value
	 * @param string|int $cIdx
	 */
	protected static final function get($cIdx)
	{
		$val = "";
		self::$q = array('adder','deleter','updater','general');
		switch($cIdx)
		{
			case in_array($cIdx, self::$q) === true:
			case null;
			$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];
			$val = (self::isRegistered($csdm)) ? $_SESSION[self::$session][$csdm] : NULL;
// 			pr($ret_val);
			break;
			
			case in_array($cIdx, self::$no_q) === true:
			$val = (self::isRegistered($cIdx)) ? $_SESSION[self::$session][$cIdx] : NULL;
			break;
		
			default:
// 			echo "Session == ".self::$session;
			$csdm = @$_SESSION[self::$session][self::variables][self::csdm_var];
			$hierarchy = explode('.', $cIdx);
			switch($hierarchy[0])
			{
				case in_array($hierarchy[0], self::$no_q) === true:
				if(self::isRegistered($cIdx) !== false)
				{
					eval("\$val = \$_SESSION['".self::$session."']['".DB::splitf($hierarchy, "']['")."'];");
				}
				else
				{
					$val = false;
				}
				break;
				
				default:
				$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];
				if($hierarchy[0] != $csdm)
				{
					array_unshift($hierarchy, $csdm);
				}
				if(self::isRegistered($cIdx) !== false)
				{
					eval("\$val = \$_SESSION['".self::$session."']['".DB::splitf($hierarchy, "']['")."'];");
				}
				else
				{
					$val = false;
				}
				break;
			}
			break;		
		}
// 		return $ret_val = (($val === false) || sizeof($val) == 0 || empty($val)) ? false : array('idx' => $cIdx, 'value' => $val);
		$ret_val = (($val === false)) ? false : array('idx' => $cIdx, 'value' => $val);
// 		echo "Printing return val<br>";
// 		pr($ret_val);
		return $ret_val;
	}
	
	protected static function inSession($fields, $data, $array=false, $strict=false)
	{
		if($data == null)
		{
			return false;
		}
		$ret_val = false;
		switch($fields)
		{
			case in_array($fields, self::$b_q) === true:
			foreach($_SESSION[self::$session][$fields] as $idx=>$val)
			{
				if($data == $val)
				{
					$ret_val = true;
					break;
				}
			}
			break;
			
			default:
			if(self::isRegistered($fields))
			{
// 				echo "$fields is reistered<br/>";
				if(($search = self::reference($fields)) !== false)
				{
					if(!is_array($search) && ($search == $data))
					{
						$ret_val = true;
					}
					elseif(is_array($search))
					{
// 						echo "$fields returns an array for search";
						foreach($search as $idx=>$val)
						{
							if($data == $val)
							{
								$ret_val = true;
								break;
							}
						}
					}
				}
			}
			break;
		}
		return $ret_val;
	}

	/*---------------------
		Private Functions
	---------------------*/
	
	/*
	 * Destroy the session
	 */
	private static final function destroy()
	{
		if(isset($_SESSION[self::$session]))
		{
			foreach($_SESSION[self::$session] as $member=>$val)
			{
				switch($member)
				{
// 					case self::helper:
// 					continue;
// 					break;
					
					default:
					self::unregister($member);
					break;
				}
			}
			$_SESSION[self::$session] = array();
		}
	}
	
	/*
	 * Using dot notation register this path
	 * @param string $cIdx
	 */
	private static function register($cIdx)
	{
		if(is_null($cIdx))
		{
			return false;
		}
		if(self::isRegistered($cIdx) === false)
		{
// 			echo "Registering $cIdx<br>";
			switch($cIdx)
			{
				case in_array($cIdx, self::$no_q) === true:
				case in_array($cIdx, self::$q) === true:
				case in_array($cIdx, self::$b_q) === true:
// 				echo "Registering major csdm $cIdx<br>";
				$_SESSION[self::$session][$cIdx] = array();
// 				pr($_SESSION);
				break;
				
				default:
				$hierarchy = explode('.', $cIdx);
				switch($hierarchy[0])
				{
					case in_array($hierarchy[0], self::$no_q) === true:
					$csdm = array_shift($hierarchy);
					break;
					
					default:
					$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];
					if($hierarchy[0] == $csdm)
					{
						array_shift($hierarchy);
					}
					break;
				
				}
				$hierarchy = (sizeof($hierarchy) > 1) ? $hierarchy : @$hierarchy[0];
				eval("\$_SESSION['".self::$session."']['$csdm']['".DB::splitf($hierarchy, "']['")."'] = '';");
				break;
			}
			return true;
		}
		return false;
	}
	
	/*
	 * Using dot notation unregister this path
	 * @param string $cIdx
	 * @return bool
	 */
	private static final function unregister($cIdx)
	{
		$ret_val = false;
		if(self::isRegistered($cIdx) !== false)
		{
// 			$cIdx = implode('.', $hierarchy);
			switch($cIdx)
			{
				case in_array($cIdx, self::$q) === true:
				if($cIdx == $_SESSION[self::$session][self::variables][self::csdm_var])
				{
					unset($_SESSION[self::$session][$cIdx]);
					$ret_val = true;
				}
				break;
				
				case self::batch:
				case in_array($cIdx, self::$no_q) === true:
				unset($_SESSION[self::$session][$cIdx]);
				break;
				
				default:
				$hierarchy = explode('.', $cIdx);
				if($hierarchy[0] === @$_SESSION[self::$session][self::variables][self::csdm_var])
				{
					$csdm = $hierarchy[0];
					unset($hierarchy[0]);
				}
				else
				{
					switch(in_array($hierarchy[0], self::$no_q))
					{
						case true:
						$csdm = $hierarchy[0];
						unset($hierarchy[0]);
						break;
						
						case false:
						$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];
						break;
					}
				}
// 				echo "Unregistering $cIdx<br>";
				self::searchDel($_SESSION[self::$session][$csdm], array_values($hierarchy), self::getVal($cIdx));
// 				pr($_SESSION[self::$session]['fields']);
				$ret_val = true;
				break;		
			}
			return $ret_val;
		}
			
	}
	
	/*
	 * Using dot notation get a reference to this path
	 * @param string $cIdx
	 * @return objet
	 */
	private static function &reference($key)
	{
		$ret_val = false;
		if(!empty($key))
		{
			switch($key)
			{
				
				case in_array($key, self::$q) === true:
				case null:
				$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];
				$ret_val = $_SESSION[self::$session][$csdm];
				break;
				
				case in_array($key, self::$no_q) === true:
				$csdm = $key;
				$ret_val = $_SESSION[self::$session][$key];
				break; 
				
				default:
// 				pr($key);
// 				echo "Reference Key == $key<br>";
				$hierarchy = explode(".", $key);
				switch($hierarchy[0])
				{
					case in_array($hierarchy[0], self::$no_q) === true:
					$csdm = $hierarchy[0];
					array_shift($hierarchy);
					break; 
					
					default:
					$csdm = $_SESSION[self::$session][self::variables][self::csdm_var];
					if($hierarchy[0] == $csdm)
					{
						array_shift($hierarchy);
					}
					break;
				}
				$ret_val = $_SESSION[self::$session][$csdm];
// 				echo "csdm == $csdm<br>";
// 				pr($ret_val);
// 				print_r($hierarchy);
				foreach($hierarchy as $k)
				{
					if($k)
					{
						if(isset($ret_val[$k]))
						{
// 							echo "$key isset($k) for $csdm <br>";
							$ret_val = $ret_val[$k];
// 							pr(debug_backtrace());
						}
						else
						{
							$ret_val = false;
							break;
						}
					}
					else
					{
						$ret_val = false;
					}
				}
				break;
			}
		}
// 		pr($_SESSION);
// 		pr($key);
// 		pr($ret_val);
		return $ret_val;
	}	
	
	/*
	 * Search and delete values in $array
	 * @param mixed $array
	 * @param mixed $keys
	 * @param mixed $data
	 * @return bool
	 */
	private static function searchDel(&$array, $keys, $data)
	{
// 		pr($keys);
		$ret_val = false;
		if(is_array($array))
		{
			if(is_array($keys))
			{
				for($i = 0; $i < sizeof($keys); $i++)
				{
					$key = $keys[$i];
// 					echo "Key == $key i == $i<br>";
					if(isset($array[$key]))
					{
						if($array[$key] == $data)
						{
// 							echo "unsetting $key<br>";
// 							session_unregister($key);
							unset($array[$key]);
							unset($key);
// 							/*pr(*/$_SESSION);
							$ret_val = true;
							break;
						}
						elseif(is_array($array[$key]))
						{
// 							echo "unsetting array $key<br>";
							array_shift($keys);
							if(($ret_val = self::searchDel($array[$key], $keys, $data)) === true)
							{
// 								echo "ret_val 2<br>";
								break;
							}
							else
							{
// 								echo "ret_val 3<br>";
								$ret_val = false;
							}
						}
					}
					else
					{
// 						echo "ret_val 4<br>";
						$ret_val = false;
						break;
					}
				}
			}
			else
			{
				if(array_key_exists($keys, $array))
				{
					if($array[$keys] == $data)
					{
// 						echo "unsetting array $key<br>";
// 						session_unregister($key);
						unset($array[$keys]);
						unset($keys);
						$ret_val = true;
					}
				}
			}
		}
// 		echo "Returning $ret_val for searchDel(".implode('.', $keys).")<br>";
		return $ret_val;
	}
//------------end private session fucntions ection

//private functions--------------------------------------------|
}
?>
