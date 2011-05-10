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

define('SI_API', true);

require_once('includes/DBConn.php');
require_once('includes/global_config.php');
require_once('includes/html.php');
require_once('includes/SI_User.php');
require_once('includes/SI_Config.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_Project.php');
require_once('includes/SI_Task.php');
require_once('includes/SI_Invoice.php');

$db_conn = new DBConn(DB_SERVER, DB_DATABASE, DB_USER, DB_PASSWORD, TRUE);
if($db_conn->connect() == FALSE){
	trigger_error("Could not connect to database!\n".$db_conn->getLastError(), E_USER_ERROR);
}

$GLOBALS['CONFIG'] = SI_Config::getAppConfig();

$loggedin_user = null;

?>
