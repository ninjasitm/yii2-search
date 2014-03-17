<?php
define("AUTH_IDLED",       -1001);
define("AUTH_EXPIRED",     -1002);
define("AUTH_WRONG_LOGIN", -1003);
define("AUTH_LOGOUT", -1004);
define("AUTH_USER_NOBODY", "nobody");
class Auth extends CUserIdentity
{
	protected static $persistent = array("expire" => 'cookie_expire',
	 "idled" => 'cookie_idled',
	 "expired" => 'expired',
	 "avatar" => 'avatar',
	 "timestamp" => 'access',
	 "idle" => 'idle',
	 "isidle" => 'isidle',
	 "lasthere" => 'lasthere',
	 "loginpage" => 'loginpage',
	 "status" => "status",
	 "fullname" => 'fullname',
	 "username" => "username",
	 "joined" => 'joined',
	 "email" => 'email',
	 "type" => 'type');
	protected static $non_persistent = array("isadmin" => "isadmin",
	"twostep" => "two_step_auth",
	"showLogin" => true,
	"registered" => 'registered',
	"access" => 'access',
	"password" => "password",
	"token" => 'token');
	protected static $auth = array('three_step' => array('url' => 							'https://admin.callcentric.com/api/yubiaccess.php', 
	'params' => array('key' => ''), 
	'auth' => array('enabled' => false, 'type' => 'basic', 'user' => 'fogDqFD0fF', 'password' => 'lUev11rgHutrkETivunFdih'), 
	'enabled' => false));

	protected $enc = "";
	protected $token_opts = array('omit' => -32, 'db' => 'lefteyecc', 'table' => 'auth_tokens');

	private $enc_type = array('md5', 'sha1', 'hash_mac');
	private $domain = '';
	private $obj = null;
	private $creds = array('db' => null, 
	'table' => null);

	const dm = 'securer';

	public function __construct($db=null, $table=null, $loginpage="login.php", $enc=false, $token_opts=null)
	{	
	 $this->creds['db'] = (is_null($db)) ? AUTH_DB : $db;
	 $this->creds['table'] = (is_null($table)) ? AUTH_TABLE : $table;
	 $this->obj = new Helper(self::dm, $this->creds['db'], $this->creds['table']);
	 $this->setDomain();
	 switch($enc)
	 {
	  case "md5":
	  case "sha1":
	  case 'hash_mac':
	  $this->enc = $enc;
	  break;

	  default:
	  $this->enc = !defined(AUTH_ENC) ? 'md5' : AUTH_ENC;
	  break;
  }
	 $this->token_opts = is_array($token_opts) ? array_merge($this->token_opts, $token_opts) : $this->token_opts;
	 //set Yii persistent attribute keys
	 $this->setPersistentStates(array_keys(self::$persistent));
 }

	public function __destruct()
	{
	 $this->obj->close();
 }

	//change the current domain this process is being used in
	public function setDomain($domain=null)
	{
	 $domain = (is_null($domain)) ? (($_SERVER['HTTP_HOST'] == '') ? 'authdomain' : str_replace('.', '', $_SERVER['HTTP_HOST'])) : str_replace('.', '', $domain);
	 $this->domain = self::dm.'.'.$domain;
	 if(!defined('AUTH_DOMAIN'))
	 {
	  define('AUTH_DOMAIN', $this->domain);
  }
 }

	public function start($u, $p, $fields, $loginpage='login.php', $token=null)
	{
	 //set persistent info
	 $this->setState(AUTH_DOMAIN.".".self::$persistent['username'], $u);
	 $this->setState(AUTH_DOMAIN.".".self::$persistent['timestamp'], time());
	 $this->setState(AUTH_DOMAIN.".".self::$persistent['idle'], time());
	 //set non persistent info
	 $this->setState(AUTH_DOMAIN.".".self::$non_persistent['registered'], false);
	 $this->setState(AUTH_DOMAIN.".".self::$non_persistent['password'], self::encrypt($p, $this->enc, AUTH_SALT));
	 $this->setState(AUTH_DOMAIN.".".self::$non_persistent['token'], $token);
	 if(is_array($fields))
	 {
	  foreach($fields as $key=>$data)
	  {
	   switch (isset(self::$persistent[$key]) || isset($this->non_persistent[$key]))
	   {
		case true:
		$this->setState(AUTH_DOMAIN.'.'.$key, $data);
		break;
	}
   }
  }
	 $this->setState(AUTH_DOMAIN.".".self::$persistent['loginpage'], $loginpage);
 }

	public function login()
	{
	 if(!(($this->getState(AUTH_DOMAIN.".".self::$persistent['username']) == null) && !($this->getState(AUTH_DOMAIN.".".self::$non_persistent['password']) == null)) || ($this->getState(self::$persistent['remember']) == 1)) 
	 {
	  $password = $this->getState(AUTH_DOMAIN.".".self::$persistent['password']);
	  $this->obj->set_db($this->creds['db'], $this->creds['table']);
	  $pri = $this->obj->get_primary_key();
	  $c = array('key' => array(), 'data' => array(), 'xor' => array());
	  switch(!is_null($this->getState(AUTH_DOMAIN.".".self::$persistent['username'])) && !is_null($password))
	   {
		case true:
		array_push($c['key'], 'uname', 'passwd', 'active');
	  array_push($c['data'], $this->getState(AUTH_DOMAIN.".".self::$persistent['username']), $password, '1');
	   array_push($c['xor'], 'AND', 'AND');
	   break;
	 }
	   switch($this->getState(self::$persistent['remember']))
		{
	  case 1:
	  case true;
	  array_push($c['key'], DB::FLAG_ASIS.'((session_id="'.$this->getState(self::$persistent['sessionid']).'") AND (session_secret=MD5(CONCAT(session_id,session_key))) AND active=1)');
	   array_push($c['data'], DB::FLAG_NULL);
	   array_push($c['xor'], 'OR');
	   break;
	 }
	   $this->obj->select(array($pri, 'uname', 'admin', 'avatar', 'email', 'f_name', 'l_name', 'two_step_auth', 'last_here'), true, $c);
		if(!$this->obj->rows())
	  {
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['status'], AUTH_WRONG_LOGIN);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['registered'], false);
	   return false;
   }
	  else
	  {
	   $user = $this->obj->result(DB::R_ASS);
	   switch($user['two_step_auth'])
	   {
		case 1:
		switch($this->login_2nd_step($user[$pri]))
		{
	  case false:
	  $this->obj->set(AUTH_DOMAIN.".".self::$persistent['status'], AUTH_WRONG_LOGIN);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['registered'], false);
		return false;
		break;
	  }
		break;
	  }
		$this->obj->set(AUTH_DOMAIN.".".self::$persistent['registered'], true);
	  	$this->obj->set(AUTH_DOMAIN.".".self::$persistent['avatar'], @$user['avatar']);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['email'], @$user['email']);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['twostep'], @$user['two_step_auth']);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['timestamp'], time());
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['idle'], time());
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['lasthere'], $user['last_here']);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['isadmin'], $user['admin']);
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['fullname'], array('first' => @$user['f_name'], 'last' => @$user['l_name']));
	   $s = $this->session_secret($password, $user['uname']);
	   $this->setState(self::$persistent['sessionid'],  $s['id']);
	   $this->obj->update(array(DB::PDO_NOBIND.'last_here', 'loggedin', 'session_id', 'session_key', 'session_secret'), array('NOW()', '1', $s['id'], $s['key'], $s['secret']), array('key' => $pri, 'data' => $user[$pri]), $this->creds['table']);
	   Yii::app()->user->login($this->_identity,$duration);
	   return true;
	 }
	 }
	   return false;
	 // 		else
	 // 		{
	// 			self::redirectTo();
	// 		}
	 }

	public function setExpire($time, $add=false)
	{
	 if ($add) 
	 {
	  $this->obj->set(AUTH_DOMAIN.".".self::$persistent['expire'], $this->getState(self::dm.".".self::$persistent['expire']) + $time);
  } 
	 else 
	 {
	  $this->obj->set(AUTH_DOMAIN.".".self::$persistent['expire'], $time);
  }
 }

	public function setSessionName($name = "PHPSESSID")
	{
	 @session_name($name);
 }

	public function setShowLogin($showLogin = true)
	{
	 $this->obj->set(AUTH_DOMAIN.".".self::$persistent['showLogin'], $showLogin);
 }

	public function authenticate()
	{
	 $ret_val = false;
	 switch(1)
	 {
	  case ($this->getState(AUTH_DOMAIN.".".self::$persistent['registered']) != 1):
	  $condition = AUTH_EXPIRED;
	  break;

	  case (($this->getState(AUTH_DOMAIN.".".self::$persistent['idle']) + $this->getState("settings.security.".self::$persistent['idled'])) < time()): 
	  $condition = AUTH_IDLED;
	  break;
  }
	 switch($condition)
	 {
	  case AUTH_EXPIRED:
	  case AUTH_IDLED:
	  $ret_val = $condition;
	  switch($this->getState(self::$persistent['remember']))
	  {
	   case true:
	   case 1:
	   switch($this->login())
	   {
		case true:
		$ret_val = true;
		$this->updateIdle();
		$this->redirectTo($this->getState(AUTH_DOMAIN.".lastpage"), 5000);
	  break;
	}
	  break;

	   default:
	  switch($condition)
	  {
	   case AUTH_IDLED:
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['isidle'], 1);
		$this->obj->set(AUTH_DOMAIN.".".self::$persistent['status'], AUTH_IDLED);
	  $this->obj->set(AUTH_DOMAIN.".".self::$persistent['registered'], 0);
	   break;

	   case AUTH_EXPIRED:
	   $this->obj->set(AUTH_DOMAIN.".".self::$persistent['expired'], 1);
		$this->obj->set(AUTH_DOMAIN.".".self::$persistent['status'], AUTH_EXPIRED);
	  $this->obj->set(AUTH_DOMAIN.".".self::$persistent['registered'], 0);
	   break;
	 }
	   break;
	 }
	   break;

	  default:
	   if(($this->getState(AUTH_DOMAIN.".".self::$persistent['registered']) == 1) && ($this->getState(AUTH_DOMAIN.".".self::$persistent['username']) != "")) 
	   {
		$this->updateIdle();
	}
	   $ret_val = true;
	   break;
	 }
	   return $ret_val;
	 }

	public function getAuth()
	{
	 if($this->getState(AUTH_DOMAIN.".".self::$persistent['registered']) != false)
	 {
	  return true;
  } 
 }

	public function redirectTo($page=null, $time=1)
	{
	 $page = urldecode((empty($page)) ? $this->getState(AUTH_DOMAIN.".".self::$persistent['loginpage']) : $page);
	 if(!headers_sent())
	 {	
	  $https = ($_SERVER['HTTPS'] == '') ? "http" : "https";
	  header("Location: $https://".preg_replace("([/]{2,})", "/",  $_SERVER['SERVER_NAME']."/".$page));
  }
	 else
	 {
	  $time = $time*1000;
	  echo "<iframe style='display:none;' onload=\"setTimeout(window.location.href='$page', '$time');\"></iframe>";
  }
 }

	public function logout()
	{
	 $this->obj->set_db($this->creds['db'], $this->creds['table']);
	 $pri = $this->obj->get_primary_key();
	 $this->obj->update(array('loggedin'), array('0'), array('key' => array('sessionid'), 'data' => array($this->getState(self::$persistent['sessionid']))));
	 $this->del(AUTH_DOMAIN.".".self::$persistent['registered']);
 }

	public function updateIdle()
	{
	 $this->obj->set(AUTH_DOMAIN.".".self::$persistent['idle'], time());
 }

	public function getUsername()
	{
	 return $this->getState(AUTH_DOMAIN.".".self::$persistent['username']);
 }

	public function getStatus()
	{
	 return $this->getState(AUTH_DOMAIN.".".self::$persistent['status']);
 }

	public function sessionValidThru()
	{
	 return $this->getState(AUTH_DOMAIN.".".self::$persistent['idle']) + 3600;
 }

	public static function encrypt($data, $enc=null, $key=null)
	{
	 $ret_val = $data;
	 $key = is_null($key) ? AUTH_SALT : (string)$key;
	 $enc = is_null($key) ? AUTH_ENC : (string)$enc;
	 $enc = 'hash_mac';
	 switch($enc)
	 {
	  case "md5":
	  $ret_val = md5($data);
	  break;

	  case "sha1":
	  $ret_val = sha1($data);
	  break;

	  case 'hash_mac':
	  $ret_val = hash_hmac('sha512', $data, $key);
	  break;
  }
	 return $ret_val;
 }

	//--------------Private functions----------//
	/*
	Get unique key and identifier
	 */
	private function session_secret($password, $username)
	{
	 $ret_val = array();
	 $id = @md5($password.md5($username.$password));
	 $key = @md5(uniqid(rand(), true));
	 $secret = md5($id.$key);
	 return array('id' => $id, 'key' => $key, 'secret' => $secret);
 }

	/*
	Do second step of authentication here
	At this time authenticate token against Sergey's Auth DB
	 */
	private function login_2nd_step($id)
	{
	 $ret_val = false;
	 switch(isset($id) || !is_null($id))
	 {
	  case true:
	  $token = substr($this->getState(AUTH_DOMAIN.".".self::$non_persistent['token']), 0, $this->token_opts['omit']);
	  switch(!empty($token))
	  {
	   case true:
	   $this->obj->set_db($this->token_opts['db'], $this->token_opts['table']);
	   $this->obj->select('token_cc', true, array("key" => array('userid', 'token'), "data" => array($id, $token)), null, null, 1);
	   switch($this->obj->rows())
	   {
		case true:
		/*
		Three step auth here
		 */
		error_reporting(E_ALL);
		$cur_token = $this->getState(AUTH_DOMAIN.".".self::$non_persistent['token']);
	  switch(!self::$auth['three_step']['enabled'])
	   {
		case true:
		case 'true':
		case 1:
		self::$auth['three_step']['params']['key'] = $cur_token;
	  $options = array(CURLOPT_URL, self::$auth['three_step']['url']);
	   switch(!self::$auth['three_step']['auth']['enabled'])
		{
	  case true:
	  case 'true':
	  case 1:
	  switch(self::$auth['three_step']['auth']['type'])
	   {
		case 'basic';
		$options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
		break;
	}
	   $options[CURLOPT_USERPWD] = self::$auth['three_step']['auth']['user'].':'.self::$auth['three_step']['auth']['password'];
		break;
	  }
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = self::$auth['three_step']['params'];
	  $options[CURLOPT_RETURNTRANSFER] = true;
	  $session = curl_init(self::$auth['three_step']['url']);
	   curl_setopt_array($session, $options);
	   $response = curl_exec($session);
	   var_dump($response);
	   switch(1)
		{
	  case strpos($response, 'OK') !== false:
	   $ret_val = true;
	   break;

	  default:
	   $ret_val = false;
	   break;
	 }
	   break;

		default:
	   $ret_val = true;
	   break;
	 }
	   break;
	 }
	   break;
	 }
	   $this->revert_dbt();
	   break;
	 }
	   return $ret_val;
	 }

}
	 ?>
