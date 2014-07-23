<?php

namespace nitm\models\security;

use \yii\helpers\Security;

/**
 * This class helps with security and creates a unique fingerprint based on the user's browser
 */

class Fingerprint extends Security
{
	
	public static $logtext = [];
	private static $_cookieName = '__ufc';
	private static $_hashKey = '__ufch';
	private static $_hashNonce = '__ufcn';
	private static $_hashFingerprint = '__ufcf';
	
	/**
	* Process the Javascript supplied challenge and verify the browser fingerprint
	*
	* @return bool
	*/
	public static function isChallengeReceived() 
	{
		array_push(static::$logtext, 'isChallengeReceived');
		/* Check whether all form fields have been provided */
		if (isset($_POST[static::$_hashKey]) && isset($_POST[static::$_hashNonce]) && isset($_POST[static::$_hashFingerprint])) 
		{
			$_hash = $_POST[static::$_hashKey];
			$nonce = $_POST[static::$_hashNonce];
			$fingerprint = $_POST[static::$_hashFingerprint];
			array_push(static::$logtext, 'nonce: ' . $nonce);
			array_push(static::$logtext, 'received hash: ' . $_hash);
			array_push(static::$logtext, 'calculated hash: ' . hash('sha256', $nonce));
			array_push(static::$logtext, 'received fingerprint: ' . $fingerprint);
			/* Check the form supplied fingerprint with the calculated fingerprint */
			if (static::isFingerprintValid($fingerprint)) 
			{
				static::setSessionCookie($nonce, $fingerprint);
				return TRUE;
			} else {
				array_push(static::$logtext, 'invalid fingerprint');
				exit;
			}
		} else {
			array_push(static::$logtext, 'not all form fields are provided');
		}
		return FALSE;
	}/* Fingerprint checks out ok, set the session cookie */
	
	/**
	* Verifies whether the session cookie is present
	*
	* @return bool
	*/
	public static function isSessionCookiePresent() {
		array_push(static::$logtext, 'isSessionCookiePresent');
		/* Check if the cookie is present */
		return isset($_COOKIE[static::$_cookieName]);
	}
	
	/**
	* Verifies whether the session cookie is valid
	*
	* @return string|bool
	*/
	public static function isSessionCookieValid() 
	{
		array_push(static::$logtext, 'isSessionCookieValid');
		/* Get the encrypted cookie data */
		$cookie = $_COOKIE[static::$_cookieName];
		array_push(static::$logtext, 'encrypted session cookie: ' . $cookie);
		/* The encryption key is based on the browser fingerprint, so calculate it */
		$key = static::getBrowserFingerprint();
		/* Result of the decryption is the start timestamp as supplied to the form */
		$time_start = static::decrypt($cookie, $key);
		array_push(static::$logtext, 'decrypted session cookie: ' . $time_start);
		if (is_numeric($time_start)) {
			/* What's the current timestamp */
			$time_end = microtime(true);
			/* Calculate the difference between start timestamp and end timestamp */
			$time = $time_end - $time_start;
			array_push(static::$logtext, 'time difference: ' . $time);
			/* Check if the outcome is a time value */
			if (is_float($time)) {
				array_push(static::$logtext, 'SessionCookie = Valid');
				return $key;
			} else {
				array_push(static::$logtext, 'SessionCookie invalid, time not a float');
				exit;
			}
		} else {
			array_push(static::$logtext, 'SessionCookie invalid, returned value is not in seconds');
			exit;
		}
		return FALSE;
	}
	
	/**
	* Creates the session cookie with encrypted nonce
	*
	* param string $nonce
	* param string $fingerprint
	*/
	public static function setSessionCookie($nonce='', $fingerprint='') 
	{
		array_push(static::$logtext, 'setSessionCookie');
		array_push(static::$logtext, 'nonce: ' . $nonce);
		array_push(static::$logtext, 'fingerprint: ' . $fingerprint);
		/* Encrypt the payload for the session cookie */
		$payload = static::encrypt($nonce, $fingerprint);
		array_push(static::$logtext, 'encrypted session cookie payload: ' . $payload);
		/* Set the session cookie, validity of 5 minutes, secure, httponly */
		$cookie = 
		\Yii::$app->request->cookies->set(new \yii\web\Cookie([
				'name' => static::$_cookieName, 
				'value' => $payload, 
				'expire' => time() + 300,
				'domain' => "", 
				'secure' => TRUE, 
				'httpOnly' => TRUE
			])
		);
	}
	
	/**
	* Gets the session cookie with encrypted nonce
	* @return string Cookie
	*/
	public static function getSessionCookie($nonce='', $fingerprint='') 
	{
		return \Yii::$app->request->cookies->get(static::$_cookieName);
	}
	
	/**
	* Verifies whether the browser fingerprint is valid when compared to the cookie stored fingerprint
	*
	* param string $fingerprint
	* @return bool
	*/
	public static function isFingerprintValid($fingerprint="") 
	{
		array_push(static::$logtext, 'isFingerprintValid');
		array_push(static::$logtext, 'fingerprint: ' . $fingerprint);
		/* Compare the provided browser fingerprint with the actual fingerprint */
		if ($fingerprint === static::getBrowserFingerprint()) {
			array_push(static::$logtext, 'Fingerprints match');
			return TRUE;
		} else {
			array_push(static::$logtext, 'Fingerprints DONT match');
			exit;
		}
		return FALSE;
	}
	
	/**
	* Creates the unique browser fingerprint
	*
	* @return string
	*/
	public static function getBrowserFingerprint() 
	{
		array_push(static::$logtext, 'getBrowserFingerprint');
		$client_ip = \Yii::$app->request->getUserIp();
		$useragent = @$_SERVER['HTTP_USER_AGENT'];
		$accept  = @$_SERVER['HTTP_ACCEPT'];
		$charset = @$_SERVER['HTTP_ACCEPT_CHARSET'];
		$encoding = @$_SERVER['HTTP_ACCEPT_ENCODING'];
		$language = @$_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$data = '';
		$data .= $client_ip;
		$data .= $useragent;
		$data .= $accept;
		$data .= $charset;
		$data .= $encoding;
		$data .= $language;
		/* Apply SHA256 hash to the browser fingerprint */
		$_hash = hash('sha256', $data);
		array_push(static::$logtext, 'getBrowserFingerprint: ' . $_hash);
		return $_hash;
	}
	
	/**
	* Encrypts string $plaintext with AES 256 algorithm with key $key and returns the cipher text in base64 encoding
	*
	* param string $plaintext
	* param string $key
	* @return string
	*/
	public static function encrypt($plaintext='', $key='') 
	{
		array_push(static::$logtext, 'encrypt');
		array_push(static::$logtext, 'payload: ' . $plaintext);
		array_push(static::$logtext, 'key: ' . $key);
		return parent::encrypt($plaintext, $key);
	}
	
	/**
	* Decrypts string $ciphertext with AES 256 with key $key and returns the plain text
	*
	* param string $plaintext
	* param string $key
	* @return string
	*/
	public static function decrypt($ciphertext, $key) 
	{
		array_push(static::$logtext, 'decrypt');
		array_push(static::$logtext, 'payload: ' . base64_encode($ciphertext));
		array_push(static::$logtext, 'key: ' . $key);
		return parent::decrypt($ciphertext, $key);
	}
	
	/**
	* Logs actions to file
	*
	* param string $log_description
	*/
	private static function logEvent() 
	{
		$log = new \nitm\models\Logger();
		/* Log events to a text file for troubleshooting analysis */
		$header = date("Y.m.d H:i:s (l)") . ': START\n';
		$footer = date("Y.m.d H:i:s (l)") . ': END\n';
		static::$logtext[] = 'END';
		$log->addTrans(static::$_hashFingerprint, static::$_hashFingerprint, static::$_hashFingerprint, $header.implode('\n\t', static::$logtext).$footer);
		static::$logtext = [];
	}
}
?>