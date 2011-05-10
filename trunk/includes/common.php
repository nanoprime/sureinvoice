<?php
/**
 *
 * Copyright (C) 2003-2011 Cory Powers
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

$wr = substr($_SERVER['PHP_SELF'],0,strpos(strtolower($_SERVER['PHP_SELF']),basename($_SERVER['SCRIPT_NAME'])));
if ($wr == "") $wr = "/";
define('WEB_ROOT',$wr);


// Installer checks
$include_path = realpath(dirname(__FILE__));
if(!file_exists($include_path.'/global_config.php') || filesize($include_path.'/global_config.php') < 10){
	if(file_exists(realpath(dirname(__FILE__).'/../installer').'/index.php')){
		$url = WEB_ROOT.'installer/';
		header("Location: ".$url."\r\n");
		exit();
	}else{
		print("No configuration file found: ".$include_path.'/global_config.php'."<BR>\n");
		exit();
	}
}
 

require_once('DBConn.php');
require_once('global_config.php');

$db_conn = new DBConn(DB_SERVER, DB_DATABASE, DB_USER, DB_PASSWORD, TRUE);
if($db_conn->connect() == FALSE){
	trigger_error("Could not connect to database!\n".$db_conn->getLastError(), E_USER_ERROR);
}

$GLOBALS['CONFIG'] = SI_Config::getAppConfig();

require_once('html.php');
require_once('SI_User.php');
require_once('SI_Config.php');
require_once('SI_TimeImport.php');
require_once('version.php');
require_once('SureInvoice.php');
session_start();

ob_start();


// Error handling functions
function site_error_handler($errno, $errstr, $errfile, $errline){
	$today = date("D M j G:i:s T Y");

	if(isset($GLOBALS['CONFIG']['error_log']) && is_file($GLOBALS['CONFIG']['error_log']) && is_writeable($GLOBALS['CONFIG']['error_log'])){
		$error_log_avail = TRUE;
	}else{
		error_log("sure_invoice: Could not write to error log: ".$GLOBALS['CONFIG']['error_log'], 0);
		$error_log_avail = FALSE;
	}

	// Log the error
	if($error_log_avail && $errno != E_NOTICE)
		error_log("$today: PHP Error ($errno): $errstr in $errfile at $errline.\n", 3, $GLOBALS['CONFIG']['error_log']);

	// Email error
	if(isset($GLOBALS['CONFIG']['error_email']) && $errno != E_NOTICE && $errno != E_USER_NOTICE)
		error_log("{$_SERVER['SERVER_NAME']} Server Error\n\n$today: PHP Error ($errno): $errstr in $errfile at $errline.\n", 1, $APP_GLOBALS['ERROR_EMAIL']);

	// If it is an error redirect to error page or print message
	if($errno == E_COMPILE_ERROR || $errno == E_CORE_ERROR || $errno == E_USER_ERROR || $errno == E_ERROR){
		if($GLOBALS['CONFIG']['debug']){
			$error_msg = "<PRE>PHP Error ($errno): $errfile at $errline.\n\n$errstr\n\n\n".format_backtrace(debug_backtrace())."\n\n</PRE>";
		}else{
			$error_msg = "We are sorry, an error has occured while processing your request, please try again later.\n".
				"The system administrator has been notified of the problem.\n";
		}
		fatal_error($error_msg);
	}
}

function log_message($message){
	if(!isset($GLOBALS['CONFIG']['debug']) || $GLOBALS['CONFIG']['debug'] != 1){
		return;
	}
	$date = '['.date('Y-m-d H:i:s').'] ';
	if(isset($GLOBALS['CONFIG']['error_log']) && is_file($GLOBALS['CONFIG']['error_log']) && is_writeable($GLOBALS['CONFIG']['error_log'])){
		$error_log_avail = TRUE;
	}else{
		error_log("sure_invoice: Could not write to error log: ".$GLOBALS['CONFIG']['error_log'], 0);
		$error_log_avail = FALSE;
	}
	
	$message = trim($message);
	if($error_log_avail){
		if(strpos($message, "\n") !== false){
			$lines = explode("\n", $message);
			foreach($lines as $line){
				$line = trim($line);
				error_log("$date $line\n", 3, $GLOBALS['CONFIG']['error_log']);
			}
		}else{
			error_log("$date $message\n", 3, $GLOBALS['CONFIG']['error_log']);
		}
	}
}

function fatal_error($message){

//	print($message);
	header("Location: ".$GLOBALS['CONFIG']['url']."error.php?error=".urlencode($message)."\r\n");
	exit();

}

function format_backtrace($backtrace){
	$output = "<b>Debug Backtrace:\n</b>";
	if(is_array($backtrace)){
		foreach($backtrace as $item){
			if(isset($item['file']))
				$output .= $item['file'];
			if(isset($item['line']))
				$output .= " at ".$item['line'];
			$output .= "\n";
		}
	}
	return $output;
}

set_error_handler('site_error_handler');


// Authentication functions
$loggedin_user =& $_SESSION['userObj'];

function loginUser($email, $password){
	$user = new SI_User();
	$login_user = $user->getUserByLogin($email, $password);
	if($login_user === FALSE){
		debug_message($user->getLastError());
		unset($_SESSION['userObj']);
		return FALSE;
	}else{
		$user->hasRight("admin");
		$_SESSION['userObj'] = $login_user;
		return TRUE;
	}
}

function isLoggedIn(){
	if(!isset($_SESSION['userObj'])){
		return FALSE;
	}

	if(is_a($_SESSION['userObj'], 'SI_User')){
		return TRUE;
	}else{
		return FALSE;
	}
}


function checkLogin($section = ''){
	global $loggedin_user;

	if(!isLoggedIn()){
		if($_SERVER['PHP_SELF'] == WEB_ROOT.'index.php'){
			header("Location: ".getCurrentURL('login.php'));
		}else{
			header("Location: ".getCurrentURL('login.php')."?referrer=".urlencode(getCurrentURL()));			
		}
		exit();
	}else{
		if($section == "")
			return TRUE;
		else{
			if($loggedin_user->hasRight($section))
				return TRUE;
			else{
				trigger_error("Insufficent Access Rights, you must have the $section right to access this page!\n", E_USER_ERROR);
				exit();
			}
		}
	}
}

function debug_message($message){
	global $error_msg;
	$today = date("D M j G:i:s T Y");
	
	if($GLOBALS['CONFIG']['debug'] > 0){
		$error_msg .= "DEBUG: ".$message."\n";
		//trigger_error($message, E_USER_ERROR);
	}
	
	if(isset($GLOBALS['CONFIG']['error_log']) && is_file($GLOBALS['CONFIG']['error_log']) && is_writeable($GLOBALS['CONFIG']['error_log'])){
		$error_log_avail = TRUE;
	}else{
		error_log("sure_invoice: Could not write to error log: ".$GLOBALS['CONFIG']['error_log'], 0);
		$error_log_avail = FALSE;		
	}	
	
	if($error_log_avail == TRUE){
		error_log("[$today]:[".$_SERVER['PHP_SELF']."]:\n".$message, 3, $GLOBALS['CONFIG']['error_log']);
	}
}

// Make sure we have some key vars setup
if(!is_dir($GLOBALS['CONFIG']['attachment_dir']) && !empty($GLOBALS['CONFIG']['attachment_dir'])) {
	fatal_error("attachment_dir {$GLOBALS['CONFIG']['attachment_dir']} is not directory!");
}

if(substr($GLOBALS['CONFIG']['attachment_dir'], -1, 1) != '/'){
	$GLOBALS['CONFIG']['attachment_dir'] .= '/';
}

function goBack($num = 1){
	$num = $num + 1; //Pad number to avoid redirecting to self
	if(count($_SESSION['history'] >= $num)){
		$location = '';
		for($i = 0; $i < $num; $i++){
			$location = array_pop($_SESSION['history']);
		}
		header("Location: ".$location."\r\n");
	}elseif(isLoggedIn()){
		header("Location: ".getCurrentURL($_SESSION['userObj']->start_page)."\r\n");
	}else{
		header("Location: ".getCurrentURL('login.php')."\r\n");
	}
	exit();
}

function getCurrentURL($page = null, $params = true){
	$url = '';
	
	// Get protocol
	if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off'){
		$url .= 'https://';
	}else{
		$url .= 'http://';
	}
	
	// Get Host
	if(isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])){
		$url .= $_SERVER['HTTP_HOST'];
	}elseif(isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])){
		$url .= $_SERVER['SERVER_NAME'];
	}else{
		$url .= $_SERVER['SERVER_ADDR'];
	}

	if($page == null){
		$url .= $_SERVER['PHP_SELF'];
		if($params){
			$url .= '?'.$_SERVER['QUERY_STRING'];
		}
	}else{
		$url .= WEB_ROOT.$page;
	}
	
	return $url;
}

// Use the session to determine redirects correctly
// Add current URL to history if it is a GET and not a duplicate of the last URL
if($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['hide_url']) &&
	$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'] != $_SESSION['history'][count($_SESSION['history'])-1] &&
	substr($_SERVER['PHP_SELF'], 0, 9) != '/json.php' &&
	substr($_SERVER['PHP_SELF'], 0, 16) != '/javascript.php' 
	){
	$_SESSION['history'][] = getCurrentURL();
}

// Keep history array small
while(count($_SESSION['history'])>10){
	array_shift($_SESSION['history']);
}
?>
