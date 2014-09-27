<?php

namespace nitm\helpers;

use yii\db\ActiveRecord;
use yii\base\Model;


class Helper extends Model
{
	//data and logic flags
	const FLAG_NULL = 'null:';
	const FLAG_ASIS = 'asis:';
	const FLAG_IGNORE = 'ignore:';
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	/**
	 * Print a pre formatted value
	 */
	public static function pr()
	{
		foreach(func_get_args() as $data)
		{
			if(!empty($data))
			{
				echo "<pre>".print_r($data)."</pre>";
			}
		}
	}
	
	/**
	 * Function to split data based on conditionals
	 * @param mixed $a1 first array to split, usually the keys
	 * @param mixed $a2 second array to be split, usually data
	 * @param mixed $c comparison operator to use
	 * @param mixed $xor glue to connect pieces of array conditionals
	 * @param boolean $esc escape data
	 * @param boolean $quote_fields should fields be quoted?
	 * @param boolean $quote_data should data be quoted?
	 * @return mixed
	*/
	public static function splitc($a1, $a2, $c='=', $xor='AND', $esc=true, $quote_fields=true, $quote_data=true)
	{
		$keys = (is_array($a1)) ? array_values($a1) : array($a1);
		$data = (is_array($a2)) ? array_values($a2) : array($a2);
// 		$c = (is_array($c) && (sizeof($c) == 1)) ? array_shift($c) : $c;
		if(($s = sizeof($keys)) == sizeof($data))
		{
			$ret_val = "";
			$c_arr = is_array($c);
			$xor_arr = is_array($xor);
			for($ci = 0; $ci < $s; $ci++)
			{
				$field = ($quote_fields === true) ? "`".$keys[$ci]."`" : $keys[$ci];
				$value = (($data[$ci] === self::FLAG_NULL) || ((is_null($data[$ci]) && !is_numeric($data[$ci])) === true)) ? null : (($esc === true) ? $data[$ci] : $data[$ci]);
				switch(substr($keys[$ci], 0, strlen(self::FLAG_IGNORE)) === self::FLAG_IGNORE)
				{
					case true:
					switch(isset($xor[$ci]))
					{
						case true:
						$field = '';
						$value = '';
						break;
						
						default:
						continue 2;
						break;
					}
					break;
				}
				switch(1)
				{
					case substr($field, 0, strlen(self::FLAG_ASIS)) === self::FLAG_ASIS:
					$value = null;
					$field = substr($field, strlen(self::FLAG_ASIS), strlen($field));
					$match = '';
					break;
					
					default:
					switch(1)
					{
						case $field == self::FLAG_NULL:
						case $field == null:
						$match = null;
						switch(is_array($data[$ci]))
						{
							case true:
							$value = "(".static::splitc($value['keys'], $value['data'], $value['operand'], $value['xor'], $esc, $quote_fields, $quote_data).")";
							break;
						}
						break;
						
						case is_numeric($c) && !empty($c):
						case is_string($c) && !empty($c):
						$match = $c;
						break;
						
						default:
						switch($c_arr == true)
						{
							case true:
							$match = isset($c[$ci]) ? (($c[$ci] == self::FLAG_NULL) ? null : $c[$ci]) : '=';
							break;
							
							default:
							$match = ($c == self::FLAG_NULL) ? null : (($c == null) ? '=' : $c);
							break;
						}
					break;
					}
				}
				$multi_cond = ($xor_arr === true && isset($xor[$ci])) ? (($xor[$ci] == null) ? " AND " : " $xor[$ci] ") : (($xor == null) ? " AND " : (is_array($xor) && !isset($xor[$ci])) ?  " AND " : " $xor ");
				switch(1)
				{
					case is_null($value):
					$ret_val .= $field.$match;
					break;
					
					case is_numeric($value):
					$ret_val .= $field.$match.$value;
					break;
					
					case is_string($value) && $quote_data:
					$quoter = ($quote_data === false) ? '' : ($quote_data === true) ? '"' : '';
					$ret_val .= $field.$match."$quoter".$value."$quoter";
					break;
					
					case is_array($value):
					$ret_val .= static::splitc(array_keys($value), array_values($value), $c, $xor, $esc, $quote_fields, $quote_data);
					break;
					
					default:
					$ret_val .= $field.$match.$value;
					break;
				}
				switch($ci == ($s-1))
				{
					case false:
					$ret_val .= $multi_cond;
					break;
				}
			}
		}
		else
		{
			static::generateError(-1, "You specified incorrect lengths for the keys and data to check Helper::splitc('".print_r($a1)."', '".print_r($a2)."');");
			return null;
		}
		return $ret_val;
	}
	
	/**
	 * function to split fields by a delimiter
	 * @param mixed $array array to split
	 * @param mixed $splitter comparison operator to use
	 * @param boolean $esc escape data
	 * @param string $sur Surround the data with?
	 * @param integer $max_len The maximum length 
	 * @param boolean $quote_data should data be quoted?
	 * @return mixed
	 */
	public static function splitf($array, $splitter=',', $esc = true, $sur='', $max_len=null, $num=true, $print=false)
	{
		$ret_val = array();
		switch(empty($array))
		{
			case false:
			$array = is_array($array) ? $array : array($array);
			$data = array_values($array);
			foreach($data as $d)
			{
				switch(1)
				{
					case substr($d, 0, strlen(self::FLAG_ASIS)) === self::FLAG_ASIS:
					$ret_val[] = substr($d, strlen(self::FLAG_ASIS), strlen($d));
					break;
					
					default:
					$ret_val[] = $sur.$d.$sur;
					break;
				}
			}
			break;
		}
		switch($print == true)
		{
			case true:
			self::pr($ret_val);
			break;
		}
		return implode($splitter, $ret_val);
	}
	
	/**
	 * function to remove carriage returns and newlines from strings
	 * @param string | mixed $s
	 * @return string | mixed
	 */
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
	
	/**
	 * Function to return boolean value of a variable
	 * @param string | int $var = value
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
		@param string $string = The string code with all values
		@param string $sep = The separator used in $string
		@param string $sur = What to surround the values with
		@param integer $num = Should numeric values be left alone and not surround with $sur?
		@return Array | boolean
	*/
	public static function getArray($string, $sep=',', $sur="'", $num=true)
	{
		$ret_val = false;
		$sep = ($sep == null) ? ',' : $sep;
		$sur = ($sur == null) ? "'" : $sur;
		switch(is_string($string) || ($string == 0))
		{
			case true:
			$string = explode($sep, $string);
			$string = is_array($string) ? $string : array($string);
			$ret_val = self::splitf($string, $sep, true, $sur, null, $num);
			eval("\$ret_val = array($ret_val);");
			break;
			
			default:
			$ret_val = $string;
			break;
		}
		return $ret_val;
	}
	
	/**
		Get safe a string. Essentially replace all non string characters with underscores, 
		or unless specified by $s and $r
		@param string $subject = string to be checked
		@param mixed $s = what to search for
		@param mixed $r = what to replace with
		@return string
	*/
	public static function getSafeString($subject, $s=null, $r=null)
	{
		$s = (!empty($s)) ? $s : array("/([^a-zA-Z0-9\\+])/", "/([^a-zA-Z0-9]){1,}$/", "/([\s]){1,}/");
		$r = (!empty($r)) ? $r : array(" ", "", "_");
		return preg_replace($s, $r, $subject);
	}
	
	public static function parseLinks($str)
	{
		return ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\">\\0</a>", $str); 
	}
	
	public static function printBacktrace($lines=10, $nl2br=false)
	{
		$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $lines);
		foreach($debug as $idx=>$line)
		{
 			$trace = var_export($line, true);
			echo ($nl2br === true) ? nl2br($trace)."<br>" : $trace."\n";
		}
		echo ($nl2br === true) ? '<br><br>' : "\n\n";
	}
	
	public function toList(array $what, $label)
	{
		$ret_val = [];
		foreach($what as $item)
		{
			$ret_val[] = $item->$label;
		}
		return $ret_val;
	}
	
	public static function getCallerName()
	{
		$callers = debug_backtrace(null, 3);
		return $callers[2]['function'];
	}
	
	/*---------------------
		Protected Functions
	---------------------*/
	
	
	/*---------------------
		Private Functions
	---------------------*/
}
?>
