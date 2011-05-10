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
require_once('includes/SI_Expense.php');
require_once('includes/SI_ItemCode.php');

$title = '';
$task_activity = new SI_TaskActivity();
$task = new SI_Task();
$project = new SI_Project();
$user = new SI_User();
$item_code = new SI_ItemCode();
$disabled = false;

if($_REQUEST['mode'] == 'add'){
	$title = "Add Time Entry";
	if(empty($_REQUEST['task_id'])){
		fatal_error("No Task ID specified!\n");
	}else{
		$task_activity->task_id = $_REQUEST['task_id'];
		$task_activity->completed_ts = time();
		$task_activity->user_id = $loggedin_user->id;
		if($task->get($task_activity->task_id) === FALSE){
			fatal_error("Could not retreive task!");
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
		$task_activity->item_code_id = $project->default_item_code_id;
		$company = $project->getCompany();
		if($company === FALSE){
			fatal_error("Could not get company information!\n");
			debug_message($project->getLastError());			
		}
		$task_activity->hourly_rate = $company->hourly_rate;
		$sct = $task->getSalesCommissionType();
		$task_activity->sales_com_type_id = $sct->id;
	}

	if($_POST['save']){
		if(!isset($_POST['user_id'])) $_POST['user_id'] = $loggedin_user->id;
		if(isset($_POST['start_ts'])) $_POST['start_ts'] = getTSFromInput($_POST['start_ts']['date'], $_POST['start_ts']['time']);
		if(isset($_POST['end_ts'])) $_POST['end_ts'] = getTSFromInput($_POST['end_ts']['date'], $_POST['end_ts']['time']);
		if($user->get($_POST['user_id']) === FALSE){
			fatal_error("Could not get user information!\n");
			debug_message($user->getLastError());
		}

		$hourly_rate = $item_code->getCompanyPrice($company->id, $_POST['item_code_id']);
		if($hourly_rate === FALSE){
			$error_msg = "Error getting price for this item code!";
			debug_message($item_code->getLastError());
		}
		if(!isset($_POST['hourly_rate']) || empty($_POST['hourly_rate'])){
			$_POST['hourly_rate'] = $hourly_rate;
		}
		$task_activity->updateFromAssocArray($_POST);
		if($task_activity->add()){
			if(is_array($_POST['item_ids'])){
				if($task_activity->setItems($_POST['item_ids']) === FALSE){
					$error_msg .= "Error adding completed items to activity!\n";
					debug_message($task_activity->getLastError());
				}
			}
			if(is_array($_POST['expense']) && $_POST['expense']['item_code_id'] > 0){
				if(!empty($_POST['expense']['description']) && 
				   !empty($_POST['expense']['cost']) && 
				   !empty($_POST['expense']['price'])){
					$exp = new SI_Expense();
					$exp->updateFromAssocArray($_POST['expense']);
					$exp->task_id = $task_activity->task_id;
					$exp->created_ts = time();
					if($exp->add() === FALSE){
						$error_msg .= "Error adding new expense!\n";
						debug_message($exp->getLastError());	
					}
				}else{
					$error_msg .= "Not adding expense, description, cost and price must all be provided!";	
				}
			}elseif(is_array($_POST['expense']) && $_POST['expense']['item_code_id'] <= 0 && 
					(!empty($_POST['expense']['description']) ||
					!empty($_POST['expense']['cost']) ||
					!empty($_POST['expense']['price'])
					)){
				$error_msg .= "Not adding expense, item code must be selected!";	
			}
			if($project->sendUpdateNotification(array("Added new task activity ".$GLOBALS['CONFIG']['url'].'/task_activity.php?mode=edit&id='.$task_activity->id)) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}

			if(empty($error_msg)){
				goBack();
			}
		}else{
			$error_msg .= "Error adding Task Activity!\n";
			debug_message($task_activity->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Time Entry";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$task_activity->get($_REQUEST['id'])){
			fatal_error("Could not retreive task activity!");
			debug_message($task_activity->getLastError());
		}
		if($task->get($task_activity->task_id) === FALSE){
			fatal_error("Could not retreive task!");
			debug_message($task->getLastError());
		}
		if($project->get($task->project_id) === FALSE){
			fatal_error("Could not retreive project!");
			debug_message($project->getLastError());
		}
		if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
			fatal_error('Insufficent access rights for this project!');
		}
		
		$company =& $project->getCompany();
	}

	if($_POST['save']){
		if(!$task_activity->isPaid() && !$task_activity->isBilled()){
			if(isset($_POST['start_ts'])) $_POST['start_ts'] = getTSFromInput($_POST['start_ts']['date'], $_POST['start_ts']['time']);
			if(isset($_POST['end_ts'])) $_POST['end_ts'] = getTSFromInput($_POST['end_ts']['date'], $_POST['end_ts']['time']);
		}
		$hourly_rate = $item_code->getCompanyPrice($company->id, $_POST['item_code_id']);
		if($hourly_rate === FALSE){
			$error_msg = "Error getting price for this item code!";
			debug_message($item_code->getLastError());
		}
		$task_activity->hourly_rate = $hourly_rate;
		$task_activity->updateFromAssocArray($_POST);
		if($task_activity->update()){
			if(is_array($_POST['item_ids'])){
				if($task_activity->setItems($_POST['item_ids']) === FALSE){
					$error_msg .= "Error adding completed items to activity!\n";
					debug_message($task_activity->getLastError());
				}
			}
			if(is_array($_POST['expense']) && $_POST['expense']['item_code_id'] > 0){
				if(!empty($_POST['expense']['description']) && 
				   !empty($_POST['expense']['cost']) && 
				   !empty($_POST['expense']['price'])){
					$exp = new SI_Expense();
					$exp->updateFromAssocArray($_POST['expense']);
					$exp->task_id = $task_activity->task_id;
					$exp->created_ts = time();
					if($exp->add() === FALSE){
						$error_msg .= "Error adding new expense!\n";
						debug_message($exp->getLastError());	
					}
				}else{
					$error_msg .= "Not adding expense, description, cost and price must all be provided!";	
				}
			}elseif(is_array($_POST['expense']) && $_POST['expense']['item_code_id'] <= 0 && 
					(!empty($_POST['expense']['description']) ||
					!empty($_POST['expense']['cost']) ||
					!empty($_POST['expense']['price'])
					)){
				$error_msg .= "Not adding expense, item code must be selected!";	
			}
			if($project->sendUpdateNotification(array("Updated task activity ".$GLOBALS['CONFIG']['url'].'/task_activity.php?mode=edit&id='.$task_activity->id)) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}

			if(empty($error_msg)){
				goBack();
			}
		}else{
			$error_msg .= "Error updating Task Activity!\n";
			debug_message($task_activity->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Time Entry";
	if(!isset($_REQUEST['id'])){
		fatal_error("Task activity id must be supplied!");
	}
	if(!$task_activity->get($_REQUEST['id'])){
		fatal_error("Could not retreive task activity!");
		debug_message($task_activity->getLastError());
	}
	if($task_activity->isBilled() || $task_activity->isPaid()){
		fatal_error("Can not delete a task that is attached to an invoice or a check!");	
	}
	if($task->get($task_activity->task_id) === FALSE){
		fatal_error("Could not retreive task!");
		debug_message($task->getLastError());
	}
	if($project->get($task->project_id) === FALSE){
		fatal_error("Could not retreive project!");
		debug_message($project->getLastError());
	}
	if(!$project->hasRights(PROJECT_RIGHT_FULL)){
		fatal_error('Insufficent access rights for this project!');
	}else{
		if($task_activity->delete($_REQUEST['id'])){
			if($project->sendUpdateNotification(array("Deleted task activity ID ".$_REQUEST['id'])) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}else{
				goBack();
			}
		}else{
			fatal_error("Error deleting Task Activity!\n");
			debug_message($task_activity->getLastError());
		}
	}
}else{
	fatal_error("Error Invalid mode!\n");
}

$hourly_rates = $user->getHourlyRates();
if($hourly_rates === FALSE){
	$error_msg .= "Could not get hourly rates for all users!\n";
	debug_message($user->getLastError());	
}

$item_codes = $item_code->getCompanyPricedCodes($company->id);
if($item_codes === FALSE){
	$error_msg .= "Could not get item codes for company!\n";
	debug_message($item_code->getLastError());	
}
?>
<? require('header.php'); ?>
<script>
	function ItemCode(id, description, cost, price){
		this.id = id;
		this.description = description;
		this.cost = cost;
		this.price = price;	
	}
	
	var rates = new Array();
<?	foreach($hourly_rates as $id => $rate){?>
		<?= "rates[".$id."] = \"".$rate."\";" ?> 
<?	} ?>
	function updateHourlyCost(){
		var oHourlyCost = document.getElementById('hourly_cost');
		var oResource = document.getElementById('user_id');
		oHourlyCost.value = rates[oResource.options[oResource.selectedIndex].value];	
	}

	var item_prices = new Array();
<?	for($i = 0; $i <= count($item_codes); $i++){
		if(!empty($item_codes[$i]->id)){
			print("item_prices[".$item_codes[$i]->id."] = new ItemCode(\"".$item_codes[$i]->id."\",\"".$item_codes[$i]->description."\", \"".$item_codes[$i]->cost."\", \"".$item_codes[$i]->price."\");\n");
		} 
	} ?>

<? if($loggedin_user->hasRight('accounting')){ ?>	
	function updateHourlyRate(){
		var oHourlyRate = document.getElementById('hourly_rate');
		var oCode = document.getElementById('item_code_id');
		oHourlyRate.value = item_prices[oCode.options[oCode.selectedIndex].value].price;	
	}
<? }else{ ?>
	function updateHourlyRate(){}
<? } ?>

	function updateExpense(){
		var oPrice = document.getElementById('expense[price]');
		var oCost = document.getElementById('expense[cost]');
		var oDescription = document.getElementById('expense[description]');
		var oCode = document.getElementById('expense[item_code_id]');
		if(item_prices[oCode.options[oCode.selectedIndex].value]){
			oPrice.value = item_prices[oCode.options[oCode.selectedIndex].value].price;	
			oCost.value = item_prices[oCode.options[oCode.selectedIndex].value].cost;	
			oDescription.value = item_prices[oCode.options[oCode.selectedIndex].value].description;
		}else{
			oPrice.value = '';	
			oCost.value = '';	
			oDescription.value = '';			
		}	
	}
	
</script>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="task_id" type="hidden" value="<?= $task_activity->task_id ?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Task Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Project Name:</td>
	<td class="form_field_cell">
		<?= $project->name ?>&nbsp;&nbsp;
		<a class="link1" href="project_details.php?id=<?= $project->id ?>">
		<img src="images/properties.gif" width="16" height="16" title="Project Detail" border="0" align="MIDDLE" /></a>&nbsp;&nbsp;
		<a class="link1" href="project.php?mode=edit&id=<?= $project->id ?>">
		<img src="images/edit.png" width="16" height="16" title="Edit Project" border="0" align="MIDDLE"  /></a>&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell"><a title="Company Detail Center" href="company_detail.php?id=<?= $project->company_id ?>"><?= $project->company_name ?></a></td>
</tr>
<tr>
	<td class="form_field_header_cell">Project Due Date:</td>
	<td class="form_field_cell"><?= $project->due_ts>0 ? date("n/j/y", $project->due_ts) :  "None" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Status:</td>
	<td class="form_field_cell"><?= $project->status ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Priority:</td>
	<td class="form_field_cell"><?= $project->priority ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Task Name:</td>
	<td class="form_field_cell">
		<?= $task->name ?>&nbsp;
		<a class="link1" href="project_task.php?mode=edit&id=<?= $task->id ?>">
		<img src="images/edit.png" width="16" height="16" title="Edit Task" border="0" align="middle"  /></a>
		</td>
</tr>
<tr>
	<td class="form_field_header_cell">Task Due Date:</td>
	<td class="form_field_cell"><?= $task->due_ts>0 ? date("n/j/y", $task->due_ts) :  "None" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell" nowrap>Task Description:</td>
	<td class="form_field_cell"><?= nl2br($task->description) ?></td>
</tr>
</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Item Code:</td>
	<td class="form_field_cell">
		<select name="item_code_id" id="item_code_id" class="input_text" tabindex="1" onchange="updateHourlyRate()" <?= $task_activity->isBilled() ? 'DISABLED' : ''?>>
			<?= SI_ItemCode::getSelectTags($task_activity->item_code_id) ?>
		</select>	
	</td>
</tr>
<? if($loggedin_user->hasRight('accounting')){ ?>
<tr>
	<td class="form_field_header_cell">Hourly Rate:</td>
	<td class="form_field_cell"><input name="hourly_rate" id="hourly_rate" class="input_text" tabindex="2" size="7" type="text" <?= $task_activity->isBilled() ? 'DISABLED' : ''?> VALUE="<?= $task_activity->hourly_rate ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Hourly Cost:</td>
	<td class="form_field_cell"><input name="hourly_cost" id="hourly_cost" class="input_text" tabindex="3" size="7" type="text" <?= $task_activity->isPaid() ? 'DISABLED' : ''?> VALUE="<?= $task_activity->hourly_cost ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Cost Transaction ID:</td>
	<td class="form_field_cell"><?= $task_activity->cost_trans_id ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Sales Commission Type:</td>
	<td class="form_field_cell">
		<select name="sales_com_type_id"  tabindex="4" class="input_text" <?= $task_activity->isBilled() ? 'DISABLED' : ''?>>
			<option value="0">None</option>
			<?= SI_SalesCommissionType::getSelectTags($task_activity->sales_com_type_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Commission Transaction ID:</td>
	<td class="form_field_cell"><?= $task_activity->com_trans_id ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Resource:</td>
	<td class="form_field_cell">
		<select name="user_id" id="user_id" tabindex="5" class="input_text" onchange="updateHourlyCost()" <?= $task_activity->isPaid() ? 'DISABLED' : ''?>>
			<?= SI_User::getSelectTags($task_activity->user_id) ?>
		</select>
	</td>
</tr>
<? } //if accounting 
?>
<? if(!$task_activity->isPaid() && !$task_activity->isBilled()){ ?>
<tr>
	<td class="form_field_header_cell">Start:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="start_ts[date]" id="start_ts_date" SIZE="10" value="<?= $task_activity->start_ts > 0 ? date("n/j/Y", $task_activity->start_ts) : '' ?>" tabindex="6">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('start_ts_date', undefined, 'end_ts_date')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
		<input type="text" class="input_text" name="start_ts[time]" id="start_ts_time" SIZE="7" value="<?= $task_activity->start_ts > 0 ? date("H:i", $task_activity->start_ts) : '' ?>" tabindex="7">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.currentTime('start_ts_date', 'start_ts_time', 'end_ts_date', 'end_ts_time')"><img width="16" height="16" border="0" src="images/set_time.gif"/></a>&nbsp;<br/>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">End:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="end_ts[date]" id="end_ts_date" SIZE="10" value="<?= $task_activity->end_ts > 0 ? date("n/j/Y", $task_activity->end_ts) : '' ?>" tabindex="8">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('end_ts_date')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
		<input type="text" class="input_text" name="end_ts[time]" id="end_ts_time" SIZE="7" value="<?= $task_activity->end_ts > 0 ? date("H:i", $task_activity->end_ts) : '' ?>" tabindex="9">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.currentTime('end_ts_date', 'end_ts_time')"><img width="16" height="16" border="0" src="images/set_time.gif"/></a>&nbsp;<br/>
	</td>
</tr>
<? }else{ ?>
<tr>
	<td class="form_field_header_cell">Start:</td>
	<td class="form_field_cell">
		<?= date("n/j/y H:i", $task_activity->start_ts) ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">End:</td>
	<td class="form_field_cell">
		<?= date("n/j/y H:i", $task_activity->end_ts) ?>
	</td>
</tr>	
<? } ?>
<? if($task->type == 'FREEFORM'){ ?>
<tr>
	<td class="form_field_header_cell">Activity Details:</td>
	<td class="form_field_cell"><textarea name="text" class="input_text" tabindex="10" cols="70" rows="15"><?= $task_activity->text ?></textarea></td>
</tr>
<? }else{?>
<tr>
	<td class="form_field_header_cell">Activity Notes:</td>
	<td class="form_field_cell"><textarea name="text" class="input_text" tabindex="10" cols="70" rows="3"><?= $task_activity->text ?></textarea></td>
</tr>
<tr>
	<td class="form_field_header_cell">Items:</td>
	<td class="form_field_cell"><?= $task->getTaskItemsHTML($task_activity->id) ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">New Task:</td>
	<td class="form_field_cell">
		<select name="new_item_parent" class="input_text" tabindex="11">
			<option value="0">No Parent</option>
			<?= SI_TaskItem::getParentSelectTags($task->id) ?>
		</select>
		<input name="new_item" class="input_text" size="70" type="text">
	</td>
</tr>
<? } //if type 
?>
<tr>
	<td class="form_field_header_cell">Add Expense:</td>
	<td class="form_field_cell">
		<table cellpadding="2" cellspacing="0">
		<tr>
			<td><b>Item Code:</b></td>
			<td>
				<select name="expense[item_code_id]" id="expense[item_code_id]" tabindex="11" class="input_text" onchange="updateExpense()">
					<option value="0">No expense</option>
					<?= SI_ItemCode::getSelectTags() ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><b>Description:</b></td>
			<td><input type="text" name="expense[description]" id="expense[description]" tabindex="12" value="" size="40" maxlength="40"/></td>
		</tr>
		<tr>
			<td><b>Cost:</b></td>
			<td><input type="text" name="expense[cost]" id="expense[cost]" value="" tabindex="13" size="8" maxlength="10"/></td>
		</tr>
		<tr>
			<td><b>Price:</b></td>
			<td><input type="text" name="expense[price]" id="expense[price]" value="" tabindex="14" size="8" maxlength="10"/></td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" class="button" name="save" tabindex="15" value="Save"></div>
	</td>
</tr>
</table>
	</div>
</div>
</form>
<? if($_REQUEST['mode'] != 'edit'){ ?>
<script>
updateHourlyRate();
</script>
<? }  ?>
<? require('footer.php'); ?>