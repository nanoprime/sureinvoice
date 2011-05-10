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
require_once('includes/SI_Task.php');
require_once('includes/SI_TaskActivity.php');
require_once('includes/SI_ItemCode.php');

$title = '';
$task_activity = new SI_TaskActivity();
$task = new SI_Task();
$project = new SI_Project();
$item_code = new SI_ItemCode();
$num_entries = 5;

$title = "Add Time";
if($_POST['save']){

	for($i=0; $i<$num_entries; $i++){
		$task_activity = new si_taskactivity();
		$task_activity->start_ts = getTSFromInput($_POST[start_ts][$i]['date'], $_POST[start_ts][$i]['time']);
		$task_activity->end_ts = getTSFromInput($_POST[end_ts][$i]['date'], $_POST[end_ts][$i]['time']);
		$task_activity->task_id = intval($_POST['task_id'][$i]);
		$task_activity->user_id = $loggedin_user->id;
		$task_activity->text = $_POST['text'][$i];
		$task_activity->item_code_id = $_POST['item_code_id'][$i];

//		var_dump($task_activity);
		if(($task_activity->task_id > 0 || $task_activity->start_ts > 0 || $task_activity->end_ts > 0)){
			if(($task_activity->task_id <= 0 || $task_activity->start_ts <= 0 || $task_activity->end_ts <= 0)){
				$error_msg .= "Skipping incomplete entry #".($i + 1)."\n";
				continue;
			}
		}else{
			continue;
		}

		if($task->get($task_activity->task_id) === FALSE){
			fatal_error("Could not retreive task!\n");
			debug_message($task->getLastError());
		}
		if($project->get($task->project_id) === FALSE){
			fatal_error("Could not retreive project!");
			debug_message($project->getLastError());
		}
		if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
			fatal_error('Insufficent access rights for this project!');
		}

		$task_activity->hourly_cost = $loggedin_user->hourly_rate;
		$company = $project->getCompany();
		if($company === FALSE){
			fatal_error("Could not get company information!\n");
			debug_message($project->getLastError());			
		}
		$task_activity->hourly_rate = $item_code->getCompanyPrice($company->id, $_POST['item_code_id'][$i]);
		if($task_activity->hourly_rate === FALSE){
			$error_msg = "Error getting price for this item code!";
			debug_message($item_code->getLastError());
		}
		$sct = $task->getSalesCommissionType();
		$task_activity->sales_com_type_id = $sct->id;

		if($task_activity->add()){
			if($project->sendUpdateNotification(array("Added new task activity ".$GLOBALS['CONFIG']['url'].'/task_activity.php?mode=edit&id='.$task_activity->id)) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}

		}else{
			$error_msg .= "Error adding Task Activity!\n";
			debug_message($task_activity->getLastError());
		}
	}
}



require('header.php'); ?>
<script language="javascript">
YAHOO.util.Event.addListener(window, 'load', setupAutoComplete);

function setupAutoComplete(){
	for(i = 0; i < <?= $num_entries ?>; i++){
		
		YAHOO.util.Event.onContentReady(
			'main', 
			Uversa.SureInvoice.AutoComplete.init, 
			{ 
				inputId: 'task_name_'+i,
				containerId: 'task_ac_container_'+i,
				input2Id: 'task_id_'+i,
				itemCodeId: 'item_code_id_'+i
			}
		);		
	}
	setTimeout('sendKeepAlive()', 600000);
}

function sendKeepAlive(){
	var handleSuccess = function(o){
		setTimeout('sendKeepAlive()', 600000);
	}
	YAHOO.util.Connect.asyncRequest('GET', 'json.php/stayAlive', {success: handleSuccess});
}
</script>
<div class="box">
<div class="boxTitle"><h3><?= $title ?></h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span></div><div class="boxContent">
	<form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
<table border="0" cellspacing="10" cellpadding="0" class="form_table">
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="center"><input type="submit" class="button" name="save" value="Save" tabindex="<?= $num_entries * 8 ?>"></div>
	</td>
</tr>
<? for($i = 0; $i < $num_entries; $i++){?>
<tr>
	<td colspan="2">
	<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Time Entry <?= $i+1 ?></a><div>
<table>
<tr>
	<th>Activity <?= $i + 1 ?></th>
	<th>Comments</th>
</tr>
	<tr>
	<td valign="top">
		<input type="hidden" name="task_id[<?= $i ?>]"  id="task_id_<?= $i ?>" value="<?= $_REQUEST['task_id'][$i] ?>">
		<input type="text" class="input_text" name="task_name[<?= $i ?>]" id="task_name_<?= $i ?>" SIZE="50" autocomplete="off" tabindex="<?= 1 + ($i * 8) ?>"  value="<?= $_REQUEST['task_name'][$i] ?>"><br />
		<div id="task_ac_container_<?= $i ?>" class="siACContainer"></div>
		<select name="item_code_id[<?= $i ?>]" id="item_code_id_<?= $i ?>" CLASS="input_text" tabindex="<?= 2 + ($i * 8) ?>">
			<?= SI_ItemCode::getSelectTags($_REQUEST['item_code_id'][$i]) ?>
		</select><br>
		<input type="text" class="input_text" name="start_ts[<?= $i ?>][date]" id="start_ts_date_<?= $i ?>" SIZE="10" autocomplete="off" tabindex="<?= 3 + ($i * 8) ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('start_ts_date_<?= $i ?>', undefined, 'end_ts_date_<?= $i ?>')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
		<input type="text" class="input_text" name="start_ts[<?= $i ?>][time]" id="start_ts_time_<?= $i ?>" SIZE="7" autocomplete="off" tabindex="<?= 4 + ($i * 8) ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.currentTime('start_ts_date_<?= $i ?>', 'start_ts_time_<?= $i ?>', 'end_ts_date_<?= $i ?>', 'end_ts_time_<?= $i ?>')"><img width="16" height="16" border="0" src="images/set_time.gif"/></a>&nbsp;<br/>
		<input type="text" class="input_text" name="end_ts[<?= $i ?>][date]" id="end_ts_date_<?= $i ?>" SIZE="10" autocomplete="off" tabindex="<?= 5 + ($i * 8) ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('end_ts_date_<?= $i ?>')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
		<input type="text" class="input_text" name="end_ts[<?= $i ?>][time]" id="end_ts_time_<?= $i ?>" SIZE="7" autocomplete="off" tabindex="<?= 6 + ($i * 8) ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.currentTime('end_ts_date_<?= $i ?>', 'end_ts_time_<?= $i ?>')"><img width="16" height="16" border="0" src="images/set_time.gif"/></a>&nbsp;<br/>
	</td>
	<td valign="top" class="form_field_cell"><textarea name="text[<?= $i ?>]" CLASS="input_text" COLS="45" ROWS="5"  tabindex="<?= 7 + ($i * 8) ?>"></textarea></td>
</tr>
</table>
	</div>
</div>
	</td>
</tr>
<? } ?>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="center"><input type="submit" class="button" name="save" value="Save" tabindex="<?= $num_entries * 8 ?>"></div>
	</td>
</tr>
</table>
</form>
<div id="acDiv" style="border: 1px solid black; background-color: white; z-index: 1; visibility: hidden;"><div class="AutoCompleteBackground"></div></div>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
<? require('footer.php'); ?>
