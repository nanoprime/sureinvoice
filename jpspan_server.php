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
// Including this sets up the JPSPAN constant
require_once('includes/JPSpan/JPSpan.php');

// Load the PostOffice server
require_once(JPSPAN . 'Server/PostOffice.php');

// Some class you've written...
require_once('includes/common.php');
require_once('includes/SureInvoice.php');
require_once('includes/SI_Task.php');
require_once('includes/SI_TaskActivity.php');

// Create the PostOffice server
$S = & new JPSpan_Server_PostOffice();

// Register the SI_Task class
$handle_desc = new JPSpan_HandleDescription();
$handle_desc->Class = 'SI_Task';
$handle_desc->methods = array('findTasks', 'getDefaultItemCode');
$S->addHandler(new SI_Task(), $handle_desc);

// Register the SI_TaskActivity class
$handle_desc = new JPSpan_HandleDescription();
$handle_desc->Class = 'SI_TaskActivity';
$handle_desc->methods = array('getActivityDetailHTML');
$S->addHandler(new SI_TaskActivity(), $handle_desc);

// Register the SureInvoice class
$handle_desc = new JPSpan_HandleDescription();
$handle_desc->Class = 'SureInvoice';
$handle_desc->methods = array('stayAlive', 'getUserSetting', 'saveUserSetting', 'getTimerData', 'pauseTimer', 'startTimer', 'addTimer', 'deleteTimer');
$S->addHandler(new SureInvoice(), $handle_desc);

define('JPSPAN_ERROR_DEBUG', true);
// This allows the JavaScript to be seen by
// just adding ?client to the end of the
// server's URL

if (isset($_SERVER['QUERY_STRING']) && strcasecmp($_SERVER['QUERY_STRING'], 'client')==0) {

		// Compress the output Javascript (e.g. strip whitespace)
		//define('JPSPAN_INCLUDE_COMPRESS',TRUE);

		// Display the Javascript client
		$S->displayClient();

} else {

		// This is where the real serving happens...
		// Include error handler
		// PHP errors, warnings and notices serialized to JS
		require_once JPSPAN . 'ErrorHandler.php';

		// Start serving requests...
		$S->serve();

}
?>
