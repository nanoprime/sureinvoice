<?
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

require_once('includes/SI_Project.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_TaskActivity.php');
require_once('includes/SI_Task.php');

$title = 'Reports';
$activity = new SI_TaskActivity();

if(!isset($_REQUEST['billable'])){
	$_REQUEST['billable'] = 'N';
}

	if(!$loggedin_user->hasRight('admin') && !$loggedin_user->isDeveloper()){
		$_REQUEST['billable'] = 'Y';
		$_REQUEST['company_id'] = $loggedin_user->company_id;
	}

	if(!$loggedin_user->hasRight('admin') && $loggedin_user->isDeveloper()){
		$_REQUEST['resource_id'] = $loggedin_user->id;
	}

	$activities = $activity->find($_REQUEST['start_ts'], $_REQUEST['end_ts']+86400, $_REQUEST['resource_id'], $_REQUEST['company_id'], $_REQUEST['billable']);
	if($activities === FALSE){
		$error_msg .= "Error getting report data!\n";
		debug_message($activity->getLastError());
	}

$csv_output = '';
for($i=0; $i<count($activities); $i++){
	if($i == 0){
		// Print the header
		$csv_output = "Company,Project,Task,Started,Completed,Time Spent\n";	
	}
	
	$csv_output .= '"'.$activities[$i]->company_name.
		'","'.$activities[$i]->project_name.
		'","'.$activities[$i]->task_name.
		'","'.($activities[$i]->start_ts>0 ? date("n/j/y H:i", $activities[$i]->start_ts) : "None").
		'","'.($activities[$i]->end_ts>0 ? date("n/j/y H:i", $activities[$i]->end_ts) : "None").
		'","'.($activities[$i]->start_ts>0 && $activities[$i]->end_ts>0 && $activities[$i]->end_ts > $activities[$i]->start_ts ? formatLengthOfTime($activities[$i]->end_ts - $activities[$i]->start_ts) : "").
		"\"\n";
}

if(!empty($csv_output)){
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="sureinvoice_report.csv"');
	print($csv_output);		
}
?>
