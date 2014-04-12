<?php

namespace nitm\helpers;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use nitm\models\DB;

if(!@isset($_SESSION))
{
	$_SESSION = array();
	$_SESSION['SERVER_NAME'] = empty($_SESSION['SERVER_NAME']) ? "_cli.mhdevnet.net" : $_SESSION['SERVER_NAME'];
}

//class that sets up and retrieves, deletes and handles modifying of contact data
class Network extends Behavior
{
	public $ip;
	public $host;
	public $coords;
	
	public function behaviors()
	{
		$behaviors = [
		];
		return array_merge(parent::behaviors(), $behaviors);
	}
	
	/*
		function to get hostname instead of gethostbyaddr
		$param string $ip = ip address to lookup
		@return string hostname
	*/
	public static function getHost($ip)
	{
		//Make sure the input is not going to do anything unexpected
		//IPs must be in the form x.x.x.x with each x as a number
		$testar = explode('.',$ip);
		if (count($testar)!=4)
		{
			return $ip;
		}
		for ($i=0;$i<4;++$i)
		{
			if (!is_numeric($testar[$i]))
			{
				return $ip;
			}
		}
		$host = `host -W 1 $ip`;
		$host = ($host) ? end(explode(' ', $host)) : $ip;
		$host = (strpos($host, "SERVFAIL") === false) ? $host : $ip;
		return $host;
	} 
	
	//xml request/curl related functions
	/**
	* Send a GET requst using cURL
	* @param string $url to request
	* @param array $get values to send
	* @param array $options for cURL
	* @return string
	*/ 
	public static function getCurlXml($url, array $get=NULL, array $options=array())
	{
		$ret_val = '';
		$response = static::getCurlData($url, $get, $options);
		$xml = simplexml_load_string(XML::convertEntities($response, true));
		if($xml)
		{
			$ret_val = XML::extractXml($xml);
		}
		else
		{
			pr(libxml_get_last_error());
		}
		return $ret_val;
	}
	
	//xml request/curl related functions
	/**
	* Send a GET requst using cURL
	* @param string $url to request
	* @param array $get values to send
	* @param array $options for cURL
	* @return string
	*/ 
	public static function getCurlData($url, array $get=NULL, array $options=array())
	{
		$ret_val = false;
		$defaults = [
			CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get),
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 4
		];
		
		$ch = curl_init();
		curl_setopt_array($ch, ($options + $defaults));
		$response = (!$result = curl_exec($ch)) ? $ret_val : preg_replace('/\<br \/>/', '', (utf8_encode($result)));
		switch($response)
		{
			case false:
			trigger_error(curl_error($ch));
			break;
			
			default:
			$ret_val = $response;
			break;
		}
		return $ret_val;
	}
}
?>