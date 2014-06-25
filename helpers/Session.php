<?php

namespace nitm\helpers;

use yii\db\ActiveRecord;
use yii\base\Model;

//class that sets up and retrieves, deletes and handles modifying of contact data
class Session extends Model
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
	const comparer = 'comparer';
	const current = 'active';
	
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
		if(!isset($_SESSION[static::sessionName()]))
		{
			$_SESSION[static::sessionName()] = array();
			if(!isset($_SESSION[static::sessionName()][self::variables]))
			{
				$_SESSION[static::sessionName()][self::variables] = array();
			}
			if(!isset($_SESSION[static::sessionName()][self::variables][self::reg_vars]))
			{
				$_SESSION[static::sessionName()][self::variables][self::reg_vars] = array();
				$_SESSION[static::sessionName()][self::variables][self::reg_vars][self::object] = NULL;
			}
		}
		if(!is_null($dm))
		{
			self::$compare = $compare;
			$_SESSION[static::sessionName()][self::variables][self::csdm_var] = $dm;
			$_SESSION[static::sessionName()][self::variables][self::$lock] = $dm;
			self::register($dm);
		}
		/*if(!@is_object($_SESSION[static::sessionName()][self::variables][self::reg_vars][self::object]))
		{
// 			pr(var_dump($this));
			$_SESSION[static::sessionName()][self::variables][self::reg_vars][self::object] = serialize($this);
		}*/
		if($compare == true)
		{
			self::register(self::comparer);
		}
	}
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	public function init()
	{
		self::$method = $_REQUEST;
	}
	
	public static function sessionName()
	{
		static::$session = (empty(static::$session)) ? static::$sess_name.$_SERVER['SERVER_NAME'] : static::$session;
		return static::$session;
	}
	
	public static final function setCsdm($dm, $compare=false)
	{
// 		echo "Setting Helper csdm for $dm <br>";
		self::$compare = $compare;
		$_SESSION[static::sessionName()][self::variables][self::csdm_var] = $dm;
		$_SESSION[static::sessionName()][self::variables][self::$lock] = $dm;
		self::register($dm);
		return true;
	}
	
	public static final function getCsdm()
	{
		if(isset($_SESSION[static::sessionName()][self::variables][self::csdm_var]))
		{
			return $_SESSION[static::sessionName()][self::variables][self::csdm_var];
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
		$csdm = ($compare === true) ? self::comparer : @static::getCsdm();
// 		pr($_SESSION[static::sessionName()]);
// 		echo "End here<br>";
// 		pr($_SESSION[static::sessionName()][self::variables]);
// 		echo "\nHelper string called by ".get_class($this)." == ".static::sessionName().".".self::variables.".".self::csdm_var."<br>";
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
				$csdm = @static::getCsdm();
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
							eval("\$_SESSION['".static::sessionName()."']['".Helper::splitf($member, "']['")."'][] = \$jvalue;");
							break;
							
							default:
							eval("\$_SESSION['".static::sessionName()."']['".Helper::splitf($member, "']['")."'] = \$jvalue;");
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
						eval("\$_SESSION['".static::sessionName()."']['".Helper::splitf($member, "']['")."'][] = \$data;");
						break;
						
						default:
// 						echo "Setting here $member to $data<br>";
						eval("\$_SESSION['".static::sessionName()."']['".Helper::splitf($member, "']['")."'] = \$data;");
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
 		//echo "deleting $cIdx<br>";
		$value = self::getVal($cIdx);
		$ret_val = self::unregister($cIdx);
		$debug = debug_backtrace();
		$line = $debug[0];
 		//echo "\ndel: Returning ".$cIdx/*." value ".print_r($ret_val)*/." called from ".$line['line']." ".$line['file']."<br>\n";
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
			$_SESSION[static::sessionName()][$cIdx] = array();
			break;
			
			default:
			$_SESSION[static::sessionName()][self::getCsdm()] = array();
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
 		//echo "\nReturning ".$cIdx/*." value ".print_r($ret_val)*/." called from ".$line['line']." ".$line['file']."<br>\n";
		return $ret_val;
	}

	public static final function size($item, $size_only=true)
	{
		if(!self::isRegistered($item))
		{	
			return 0;
		}	
		else
		{
			//$ret_val = self::getVal($item);
			$hierarchy = explode('.', $item);
			$access_str = "['".Helper::splitf($hierarchy, "']['")."']";
			$csdm = @static::getCsdm();;
			$access_str = ($csdm != null) ? (($csdm == $hierarchy[0]) ? $access_str : ((!in_array($hierarchy[0], self::$no_q)) ? $csdm.$access_str : $access_str)) : $access_str;
			eval("\$size = sizeof(\$_SESSION['".static::sessionName()."']".$access_str.");");
			switch($size_only)
			{
				case false:
				$ret_val = array('value' => self::getVal($item), 'size' => $size, 'idx' => $item);
				break;
				
				default:
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
			$ret_val = isset($_SESSION[static::sessionName()][$cIdx]);
			break;
			
			default:
			$hierarchy = explode(".", $cIdx);
			switch($hierarchy[0])
			{	
				case in_array($hierarchy[0], self::$q) === true:
				case in_array($hierarchy[0], self::$no_q) === true:
				case ($hierarchy[0] == @static::getCsdm()):
				break;
				
				default:
				array_unshift($hierarchy, @static::getCsdm());
				break;
			}
// 			if($csdm == 'securer')
// 			{
// 				echo "csdm == $csdm<br>";
// 				pr($_SESSION);
// 				pr($session);
// 			}
			eval("\$ret_val = isset(\$_SESSION['".static::sessionName()."']['".Helper::splitf($hierarchy, "']['")."']);");
// 			self::pr("['static::sessionName()']['".Helper::splitf($hierarchy, "']['")."']");
// 			eval("echo \$_SESSION['".static::sessionName()."']['".Helper::splitf($hierarchy, "']['")."'];");
			//print_r("Returning $ret_val for $cIdx<br>");
			break;
		}
// 		echo "Returning $ret_val for is_registrered($cIdx)<br>";
// 		pr($ret_val);
		return $ret_val;
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
			$csdm = static::getCsdm();
			$val = (self::isRegistered($csdm)) ? $_SESSION[static::sessionName()][$csdm] : NULL;
// 			pr($ret_val);
			break;
			
			case in_array($cIdx, self::$no_q) === true:
			$val = (self::isRegistered($cIdx)) ? $_SESSION[static::sessionName()][$cIdx] : NULL;
			break;
		
			default:
// 			echo "Session == ".static::sessionName();
			$csdm = @static::getCsdm();
			$hierarchy = explode('.', $cIdx);
			switch($hierarchy[0])
			{
				case in_array($hierarchy[0], self::$no_q) === true:
				if(self::isRegistered($cIdx) !== false)
				{
					eval("\$val = \$_SESSION['".static::sessionName()."']['".Helper::splitf($hierarchy, "']['")."'];");
				}
				else
				{
					$val = false;
				}
				break;
				
				default:
				$csdm = static::getCsdm();
				if($hierarchy[0] != $csdm)
				{
					array_unshift($hierarchy, $csdm);
				}
				if(self::isRegistered($cIdx) !== false)
				{
					eval("\$val = \$_SESSION['".static::sessionName()."']['".Helper::splitf($hierarchy, "']['")."'];");
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
			foreach($_SESSION[static::sessionName()][$fields] as $idx=>$val)
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
		if(isset($_SESSION[static::sessionName()]))
		{
			foreach($_SESSION[static::sessionName()] as $member=>$val)
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
			$_SESSION[static::sessionName()] = array();
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
				$_SESSION[static::sessionName()][$cIdx] = array();
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
					$csdm = @static::getCsdm();
					if($hierarchy[0] == $csdm)
					{
						array_shift($hierarchy);
					}
					break;
				
				}
				$hierarchy = (sizeof($hierarchy) > 1) ? $hierarchy : @$hierarchy[0];
				eval("\$_SESSION['".static::sessionName()."']['$csdm']['".Helper::splitf($hierarchy, "']['")."'] = '';");
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
				if($cIdx == static::getCsdm())
				{
					unset($_SESSION[static::sessionName()][$cIdx]);
					$ret_val = true;
				}
				break;
				
				case self::batch:
				case in_array($cIdx, self::$no_q) === true:
				unset($_SESSION[static::sessionName()][$cIdx]);
				break;
				
				default:
				$hierarchy = explode('.', $cIdx);
				if($hierarchy[0] === @static::getCsdm())
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
						$csdm = static::getCsdm();
						break;
					}
				}
// 				echo "Unregistering $cIdx<br>";
				self::searchDel($_SESSION[static::sessionName()][$csdm], array_values($hierarchy), self::getVal($cIdx));
// 				pr($_SESSION[static::sessionName()]['fields']);
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
				$csdm = static::getCsdm();
				$ret_val = $_SESSION[static::sessionName()][$csdm];
				break;
				
				case in_array($key, self::$no_q) === true:
				$csdm = $key;
				$ret_val = $_SESSION[static::sessionName()][$key];
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
					$csdm = static::getCsdm();
					if($hierarchy[0] == $csdm)
					{
						array_shift($hierarchy);
					}
					break;
				}
				$ret_val = $_SESSION[static::sessionName()][$csdm];
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
}
?>
