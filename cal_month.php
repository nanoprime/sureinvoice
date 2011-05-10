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
if ( !isset ( $_GET['y'] ) ) $_GET['y']=date('Y');
if ( !isset ( $_GET['m'] ) ) $_GET['m']=date('m');
if ( !isset ( $_GET['d'] ) ) $_GET['d']=date('d');
if($loggedin_user->hasRight('admin') && isset($_REQUEST['user_id'])){
	$user_id = $_REQUEST['user_id'];
}else{
	$user_id = $loggedin_user->id;
}

// Instantiate the Month class
$month=new Month($_GET['y'],$_GET['m'], "Sunday");

// Get the details of the months as timestamps
$last=$month->lastMonth(true);
$next=$month->nextMonth(true);
$thisMonth=$month->thisMonth(true);

// Get the task data
$task = new SI_Task();
$tasks = $task->getCalendarTasks($user_id, $thisMonth, $next, 'day');
if($tasks === FALSE){
	$error_msg .= "Could not retrieve Tasks!\n";
	debug_message($task->getLastError());
}

// Get the activity data
$ta = new SI_TaskActivity();
$activities = $ta->getCalendarActivities($user_id, $thisMonth, $next, 'day');
if($activities === FALSE){
	$error_msg .= "Could not retrieve activities!\n";
	debug_message($ta->getLastError());
}

// Make sure the current date is selected
$sDays = array (
    new Day(date('Y'),date('m'),date('d'))
    );

// Build the days of the month
$month->buildWeekDays($sDays);

// Define the days of the week for column headings
$daysOfWeek= array('Sunday', 'Monday','Tuesday','Wednesday',
    'Thursday','Friday','Saturday');

$title = "Calendar - Month View";
require('header.php');
?>
<?
if($loggedin_user->hasRight('admin')){?>
<SCRIPT>
function reloadPage(selObj){
	var user_id = selObj.options[selObj.selectedIndex].value;
	window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter=<?= $_REQUEST['filter'] ?>&y=<?= $_GET['y'] ?>&m=<?= $_GET['m'] ?>&d=<?= $_GET['d'] ?>&user_id="+user_id;
}
</SCRIPT>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Calendar</a><div>
<B>Select User:&nbsp;</B>
<SELECT NAME="user_id" onChange="javascript:reloadPage(this)" CLASS="input_text">
	<?= SI_User::getSelectTags($user_id) ?>
</SELECT>
<?
} //if admin 
?>
<table BORDER="0" CELLSPACING="0" CELLPADDING="0" class="dg_table">
<tr>
	<td colspan="7" class="form_header_cell">
		<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" WIDTH="100%">
		<TR>
			<TD WIDTH="20%">
				<a class="link1" href="<?= $_SERVER['PHP_SELF']."?y=".date('Y',$last)."&m=".date('m',$last)."&d=1" ?>&user_id=<?= $user_id ?>"> <?= date('F',$last) ?></a>
			</TD>
			<TD WIDTH="60%" ALIGN="center">
				<?= date('F',$thisMonth)." ".date('Y',$thisMonth) ?>
			</TD>
			<TD WIDTH="20%" ALIGN="right">
				<a class="link1" href="<?= $_SERVER['PHP_SELF']."?y=".date('Y',$next)."&m=".date('m',$next)."&d=1" ?>&user_id=<?= $user_id ?>"> <?= date('F',$next) ?></a>
			</TD>
		</TR>
		</TABLE>
	</TD>
</TR>
<TR>
<? // Display the days of the week
foreach($daysOfWeek as $dayOfWeek ) {?>
	<TH CLASS="dg_header_cell"><?= $dayOfWeek?></TH>
<?
}
?>
</TR>
<?
// Loop through the day entries
while ( ($day = $month->fetch()) != false ) {

    // If its the start of a week, start a new row
    if ( $day->isFirst() ) {
        echo "<tr class=\"dg_data_cell_1\">\n";
    }

    // Check to see if day is an "empty" day
    if ( ! $day->isEmpty() ) {

        // If it's the current day, highlight it
        if ( !$day->isSelected() )
            echo "<td class=\"cal_day\">";
        else
            echo "<td class=\"cal_day_current\">";

				// Display the day inside a link
        echo "<DIV CLASS=\"cal_date\">
					<a class=\"link3\" href=\"cal_day.php?y=".$day->thisYear().
					"&m=".$day->thisMonth()."&d=".$day->thisDay()."&user_id=".$user_id."\">".
					date("jS", $day->getTimestamp())."</a></div>";
				if(isset($tasks[$day->getTimestamp()])){
					foreach($tasks[$day->getTimestamp()] as $task){
						echo "<A CLASS=\"link1\" HREF=\"task_activities.php?task_id=".$task->id."\">".$task->name."</A><BR><BR>";
					}
				}
				if(isset($activities[$day->getTimestamp()])){
					foreach($activities[$day->getTimestamp()] as $ta){
						echo "<A CLASS=\"link1\" HREF=\"task_activity.php?id=".$ta->id."&mode=edit\">".$ta->project_name.":".$ta->task_name." ".formatLengthOfTime($ta->total_interval_time)."</A><BR><BR>";
					}
				}
				echo "</td>";
    // Display an empty cell for empty days
    } else {
        echo "<td class=\"cal_day_empty\">&nbsp;</td>";
    }

    // If its the end of a week, close the row
    if ( $day->isLast() ) {
        echo "\n</tr>\n";
    }
}?>
</TABLE>
</div></div>
<? require('footer.php'); ?>
