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
require_once("jpgraph.php");
require_once("jpgraph_pie.php");
require_once("jpgraph_pie3d.php");

// Get the data
$activity = new SI_TaskActivity();
$activities = $activity->find($_REQUEST['start_ts'], $_REQUEST['end_ts'], $_REQUEST['resource_id'], $_REQUEST['company_id'], $_REQUEST['billable']);
if(count($activities) <= 0){
	$error_msg .= "Error getting report data!\n";
	debug_message($activity->getLastError());
}		

//verify this graph is for a project
if($_REQUEST['type'] == 'project'){
	// create array of project names to count how many projects have the same name
	$dupe_project_names = Array();
	//iterate through project names to see if there are any duplicates
	foreach($activities as $act) {
		//count($dupe_project_names[$act->project_name]) will be > 1 if there are more than 1 projects that have the same name
		$dupe_project_names[$act->project_name][$act->_task->project_id] +=1 ;
	}
}

$data = array();
$labels = array();
foreach($activities as $act){
	if($_REQUEST['type'] == 'company'){
		$title = "Time by Company";
		$data[$act->company_name] += ($act->end_ts - $act->start_ts);
		$labels[$act->company_name] = $act->company_name;
	}else if($_REQUEST['type'] == 'project'){
		$title = "Time by Project";
		$data[$act->_task->project_id] += ($act->end_ts - $act->start_ts);

		//determines if company name should be shown next to the project name
		if(array_key_exists($act->project_name,$dupe_project_names) && count($dupe_project_names[$act->project_name]) > 1) {
			//display company_name - project_name
			$labels[$act->_task->project_id] = $act->company_name .' - '. $act->project_name;
		} else {
			//display the project name only
			$labels[$act->_task->project_id] = $act->project_name;
		}
	}else{
		$error_msg .= "Invalid report type!\n";
	}
}
$data_array = array();
$label_array = array();
foreach($data as $key => $item){
	$data_array[] = $item;
	$label_array[] = $labels[$key];
}
	
debug_message(print_r($data_array, TRUE).print_r($label_array,TRUE));
// Create the Pie Graph.
$graph = new PieGraph(500,325);
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set($title);
#$graph->title->SetFont(FF_VERDANA,FS_BOLD,18); 
$graph->title->SetColor("darkblue");
//$graph->legend->Pos(0.1,0.2);

// Create pie plot
$p1 = new PiePlot3d($data_array);
$p1->SetTheme("earth");
$p1->SetCenter(0.35, 0.6);
$p1->SetAngle(30);
#$p1->SetFont(FF_ARIAL,FS_NORMAL,12);
$p1->SetLegends($label_array);

$graph->Add($p1);
$graph->Stroke();


?>