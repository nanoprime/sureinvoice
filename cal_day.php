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
require_once('includes/common.php');
checkLogin();

// Include DateMath class
require_once ('includes/DateTime/DateMath.php');
// Include the TimeUnitValidator
require_once ('includes/DateTime/TimeUnitValidator.php');

// Include Calendar and subclasses
require_once ('includes/DateTime/Calendar.php');
require_once ('includes/DateTime/Month.php');
require_once ('includes/DateTime/Day.php');
require_once ('includes/DateTime/Hour.php');

// Data classses
require_once('includes/SI_Task.php');

// Set up initial variables
if ( !isset($_GET['y']) ) $_GET['y']=date('Y');
if ( !isset($_GET['m']) ) $_GET['m']=date('m');
if ( !isset($_GET['d']) ) $_GET['d']=date('d');
if ( !isset($_GET['h']) ) $_GET['h'] = date('H');
if($loggedin_user->hasRight('admin') && isset($_REQUEST['user_id'])){
	$user_id = $_REQUEST['user_id'];
}else{
	$user_id = $loggedin_user->id;
}

// Create a new day
$day = new Day($_GET['y'],$_GET['m'],$_GET['d']);

// Get the task data
$task = new SI_Task();
$tasks = $task->getCalendarTasks($user_id, $day->getTimestamp(), ($day->getTimestamp() + (24 * 60 * 60)), 'hour');
if($tasks === FALSE){
  $error_msg .= "Could not retrieve Tasks!\n";
	debug_message($task->getLastError());
}

// Get the activity data
$ta = new SI_TaskActivity();
$activities = $ta->getCalendarActivities($user_id, $day->getTimestamp(), ($day->getTimestamp() + (24 * 60 * 60)), 'hour');
if($activities === FALSE){
	$error_msg .= "Could not retrieve activities!\n";
	debug_message($ta->getLastError());
}

// Make sure the current date is selected
$sHours = array (
    new Hour(date('Y'),date('m'),date('d'), date('H'))
    );

// Build the hour list for that day
$day->build($sHours);
$title = "Calendar - Month View";
require('header.php');
?>
<table class="dg_table" width="450">
	<TR>
		<TD COLSPAN="2" CLASS="dg_header_cell"><?= date('l F jS',$day->thisDay(true)) ?></TD>
	</TR>
<?
$alt='';

// Loop through the hours
while ( ($hour = $day->fetch()) != false ) {
    // Set a range for the hours; only between 8am and 6pm
    //if ( $hour->thisHour() < 6 || $hour->thisHour() > 20 )
    //    continue;

    // For alternating row colors
    $alt= $alt=="dg_data_cell_1" ? "dg_data_cell_2" : "dg_data_cell_1";

    // If it's the current day, highlight it
    if ( !$hour->isSelected() )
        echo "<tr>\n";
    else
        echo "<tr class=\"cal_hour_current\">\n";

    echo "<td class=\"cal_hour\">".date('g A',$hour->thisHour(true))."</td>";

    echo "<td class=\"".$alt."\">";
				if(isset($tasks[$hour->getTimestamp()])){
					foreach($tasks[$hour->getTimestamp()] as $task){
						echo "<A CLASS=\"link1\" HREF=\"task_activities.php?task_id=".$task->id."\">".$task->name."</A><BR><BR>";
					}
				}
				if(isset($activities[$hour->getTimestamp()]) && count($activities[$hour->getTimestamp()]) > 0){
					foreach($activities[$hour->getTimestamp()] as $ta){
						echo "<A CLASS=\"link1\" HREF=\"task_activity.php?id=".$ta->id."&mode=edit\">".$ta->project_name.":".$ta->task_name." ".formatLengthOfTime($ta->total_interval_time)."</A><BR><BR>";
					}
				}
				if(!isset($tasks[$hour->getTimestamp()]) && !isset($activities[$hour->getTimestamp()])){
					echo "&nbsp;";
				}
		echo "</td>";

    echo "</tr>\n";
}
?>
</TABLE>
<? require('footer.php'); ?>
