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
require_once('common.php');

// Required PEAR Classes
if($GLOBALS['CONFIG']['bundled_pear'] == 1){
	require_once('pear_bundle/PEAR.php');	
	require_once('pear_bundle/DB.php');	
	require_once('pear_bundle/Mail.php');	
	require_once('pear_bundle/Mail/mime.php');	
}else{
	require_once('PEAR.php');
	require_once('DB.php');
	require_once('Mail.php');
	require_once('Mail/mime.php');
}

// phpNotify Classes
require_once(realpath(dirname(__FILE__)).'/Notification.php');
require_once(realpath(dirname(__FILE__)).'/NotificationAddress.php');
require_once(realpath(dirname(__FILE__)).'/NotificationMacro.php');


$PHPNOTIFY_APP_CONFIG = array();
$PHPNOTIFY_APP_CONFIG['DSN'] = 'mysql://'.DB_USER.':'.DB_PASSWORD.'@'.DB_SERVER.'/'.DB_DATABASE;
$PHPNOTIFY_APP_CONFIG['URL'] = 'http://uversa.ws/notify/';
$PHPNOTIFY_APP_CONFIG['TABLE_PREFIX'] = 'phpnotify_';

// Setup a define for the table prefix
define('PHPNOTIFY_TABLE_PREFIX', $PHPNOTIFY_APP_CONFIG['TABLE_PREFIX']);

$PHPNOTIFY_DB = DB::connect($PHPNOTIFY_APP_CONFIG['DSN']);
if (DB::isError($PHPNOTIFY_DB)) {
		die($PHPNOTIFY_DB->getMessage());
}
?>
