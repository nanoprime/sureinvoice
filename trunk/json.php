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
require_once('includes/SureInvoice.php');
require_once('includes/SI_Task.php');
require_once('includes/SI_TaskActivity.php');

if(!isLoggedIn()){
	echo json_encode(array('error' => 'User is not logged in!'));
	exit();
}
/*
list($path,$query_string) = explode('?', $_SERVER['REQUEST_URI']);
if("/json.php" == substr($path, 0, strlen("/json.php"))){
	if(strlen($path) == strlen("/json.php")){
		$path = '';
	}else{
		$path = substr($path, strlen("/json.php"));
	}
}
*/
$path = $_SERVER['PATH_INFO'];
if($path[0] == '/'){
	$path = substr($path, 1);
}
$params = explode('/',$path);
$action = '';
if(isset($params[0]) && !empty($params[0])){
	$action = array_shift($params);
}

for($i = 0; $i < count($params); $i++){
	$params[$i] = urldecode($params[$i]);
}
//var_dump($action, $path_parts);
$action_map = array(
	'stayAlive' => array('SureInvoice', 'stayAlive'),
	'getUserSetting' => array('SureInvoice', 'getUserSetting'),
	'saveUserSetting' => array('SureInvoice', 'saveUserSetting'),
	'getRecentTime' => array('SureInvoice', 'getRecentTime'),
	'getTimerData' => array('SureInvoice', 'getTimerData'),
	'pauseTimer' => array('SureInvoice', 'pauseTimer'),
	'startTimer' => array('SureInvoice', 'startTimer'),
	'addTimer' => array('SureInvoice', 'addTimer'),
	'deleteTimer' => array('SureInvoice', 'deleteTimer'),
	'getDefaultItemCode' => array('SI_Task', 'getDefaultItemCode'),
	'getActivityDetailHTML' => array('SI_TaskActivity', 'getActivityDetailHTML'),
	'importGetUsers' => array('SureInvoice', 'importGetUsers'),
	'importGetTasks' => array('SureInvoice', 'importGetTasks'),
	'importGetItemCodes' => array('SureInvoice', 'importGetItemCodes')
);
$output = array();
switch ($action){
	case 'findTasks':
		$task = new SI_Task();
		$tasks = array();
		if(!isset($params[0]) || empty($params[0])){
			if(isset($_GET['query'])){
				$tasks = $task->findTasks($_GET['query']);
			}else{
				$output['error'] = 'Invalid parameters for findTasks action';
				break;
			}
		}else{
			$tasks = $task->findTasks($params[0]);
		}
		if($tasks === false){
			$output['error'] = $task->getLastError();
		}else{
			$output['tasks'] = $tasks;
		}
		break;
	case 'addTaskActivity':
		$project = new SI_Project();
		$company = new SI_Company();
		$task = new SI_Task();
		$item_code = new SI_ItemCode();
		$task_activity = new SI_TaskActivity();
		$task_activity->start_ts = getTSFromInput($_POST['ta_popup_start_ts']['date'],$_POST['ta_popup_start_ts']['time']);
		$task_activity->end_ts = getTSFromInput($_POST['ta_popup_end_ts']['date'],$_POST['ta_popup_end_ts']['time']);
		$task_activity->task_id = intval($_POST['ta_popup_task_id']);
		$task_activity->user_id = $loggedin_user->id;
		$task_activity->text = $_POST['ta_popup_text'];
		$task_activity->item_code_id = $_POST['ta_popup_item_code_id'];

		$debug_info = "
			POST = ".print_r($_POST, true)."\n
			start_ts = {$task_activity->start_ts}\n
			end_ts = {$task_activity->end_ts}\n
			task_id = {$task_activity->task_id}\n
			user_id = {$task_activity->user_id}\n
			item_code_id = {$task_activity->item_code_id}\n
			text = {$task_activity->text}\n
		";
		$output['debug'] = $debug_info;
		if(($task_activity->task_id > 0 || $task_activity->start_ts > 0 || $task_activity->end_ts > 0)){
			if(($task_activity->task_id <= 0 || $task_activity->start_ts <= 0 || $task_activity->end_ts <= 0)){
				$output['error'] = "Skipping incomplete entry\n";
				break;
			}
		}else{
			$output['error'] = "Skipping incomplete entry\n";
			break;
		}

		if($task->get($task_activity->task_id) === FALSE){
			$output['error'] = "Could not retreive task:\n".$task->getLastError();
			break;
		}
		if($project->get($task->project_id) === FALSE){
			$output['error'] = "Could not retreive project:\n".$project->getLastError();
			break;
		}
		if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
			$output['error'] = "Insufficent access rights for this project!\n";
			break;
		}

		$task_activity->hourly_cost = $loggedin_user->hourly_rate;
		$company = $project->getCompany();
		if($company === FALSE){
			$output['error'] = "Could not get company information:\n".$project->getLastError();
			break;
		}
		$task_activity->hourly_rate = $item_code->getCompanyPrice($company->id, $task_activity->item_code_id);
		if($task_activity->hourly_rate === FALSE){
			$output['error'] = "Error getting price for this item code:\n".$item_code->getLastError();
			break;
		}
		$sct = $task->getSalesCommissionType();
		$task_activity->sales_com_type_id = $sct->id;

		if($task_activity->add()){
			if($project->sendUpdateNotification(array("Added new task activity ".$GLOBALS['CONFIG']['url'].'/task_activity.php?mode=edit&id='.$task_activity->id)) === FALSE){
				$output['error'] = "Error sending update notification:\n".$project->getLastError();
				break;
			}
		}else{
			$output['error'] = "Error adding Task Activity:\n".$task_activity->getLastError();
			break;
		}
		break;
	default:
		if(isset($action_map[$action])){
			if(is_array($action_map[$action])){
				if(class_exists($action_map[$action][0])){
					$class = new $action_map[$action][0];
					if(method_exists($class, $action_map[$action][1])){
						$result = call_user_func_array(array($class, $action_map[$action][1]), $params);
						if($result === false){
							$output['error'] = "{$action_map[$action][0]}::{$action_map[$action][1]}: ".$class->getLastError();
						}else{
							$output['result'] = $result;
						}
					}else{
						$output['error'] = "Method {$action_map[$action][1]} does not exist in class {$action_map[$action][0]} for action $action";
					}
				}else{
					$output['error'] = "Could not load class {$action_map[$action][0]} for action $action";
				}
			}else{
				//TODO Deal with global methods later
			}
		}else{
			$output['error'] = 'Invalid action provided';
		}
}

echo json_encode($output);
?>
