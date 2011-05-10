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
require_once('includes/SI_PaymentSchedule.php');
require_once('includes/SI_CompanyTransaction.php');
require_once('includes/SI_Project.php');
require_once('includes/SI_Task.php');
require_once('includes/SI_ItemCode.php');

checkLogin('accounting');

$title = '';
$ps = new SI_PaymentSchedule();
$project = new SI_Project();
$task = new SI_Task();
$item_code = new SI_ItemCode();

// Clean up amount
if(!empty($_POST['amount'])){
	$_POST['amount'] = preg_replace('/[^0-9\.]/','', $_POST['amount']);
}

if($_REQUEST['mode'] == 'add'){
	$title = "Add Scheduled Billing";

	$project_id = intval($_REQUEST['project_id']);
	$task_id = intval($_REQUEST['task_id']);
	if($project_id == 0 && $task_id == 0){
		fatal_error("You must provide a task_id or project_id for this scheduled payment!\n");
	}

	if($task_id > 0){
		if($task->get($task_id) === FALSE){
			$error_msg .= "Error getting task information!\n";
			debug_message($task->getLastError());
		}

		if($project->get($task->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}
	}else{
		if($project->get($project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}
	}


	if($_POST['save']){
		if($_POST['recurrence'] == 'Monthly'){
			$start_ts = getTSFromInput($_POST['start_ts']);
			$end_ts = getTSFromInput($_POST['end_ts']);
			$created_ps = SI_PaymentSchedule::generateScheduledPayments($_POST['project_id'], $_POST['task_id'], $_POST['recurrence'], $start_ts, $end_ts, $_POST['item_code_id'], $_POST['description'], $_POST['amount']);
			if($created_ps === FALSE){
				$error_msg .= "Error creating scheduled payments!\n";
			}else{
				//var_dump($created_ps);
				goBack();
			}
		}else{
			$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
			$ps->updateFromAssocArray($_POST);
			if($ps->add()){
				goBack();
			}else{
				$error_msg .= "Error adding Payment Schedule!\n";
				debug_message($ps->getLastError());
			}
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Payment Schedule";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$ps->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving payment information!\n";
			debug_message($ps->getLastError());
		}
	}

	if($ps->task_id > 0){
		if($task->get($ps->task_id) === FALSE){
			$error_msg .= "Error getting task information!\n";
			debug_message($task->getLastError());
		}

		if($project->get($task->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}
	}else{
		if($project->get($ps->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}
	}

	if($_POST['save']){
		$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
		$ps->updateFromAssocArray($_POST);
		if($ps->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Payment Schedule!\n";
			debug_message($ps->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Payment Schedule";

	if(!$ps->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving payment information!\n";
		debug_message($ps->getLastError());
	}

	if($ps->task_id > 0){
		if($task->get($ps->task_id) === FALSE){
			$error_msg .= "Error getting task information!\n";
			debug_message($task->getLastError());
		}

		if($project->get($task->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}
	}else{
		if($project->get($ps->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($ps->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Payment Schedule!\n";
			}
		}else{
			goBack();
		}
	}
}else{
	fatal_error("Invalid mode ({$_REQUEST['mode']}) for this page!");
}

$company =& $project->getCompany();
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
	
	var item_prices = new Array();
<?	for($i = 0; $i <= count($item_codes); $i++){
		if(!empty($item_codes[$i]->id)){
			print("item_prices[".$item_codes[$i]->id."] = new ItemCode(\"".$item_codes[$i]->id."\",\"".$item_codes[$i]->description."\", \"".$item_codes[$i]->cost."\", \"".$item_codes[$i]->price."\");\n");
		} 
	} ?>

	function updatePaymentSchedule(){
		var oPrice = document.getElementById('amount');
		var oDescription = document.getElementById('description');
		var oCode = document.getElementById('item_code_id');
		if(item_prices[oCode.options[oCode.selectedIndex].value]){
			oPrice.value = item_prices[oCode.options[oCode.selectedIndex].value].price;	
			oDescription.value = item_prices[oCode.options[oCode.selectedIndex].value].description;
		}else{
			oPrice.value = '';	
			oDescription.value = '';			
		}	
	}

	function switchRecurrence(value){
		singleEl = document.getElementById('single_entry');
		reccEl = document.getElementById('recurrence_settings');
		if(value == 'Single'){
			singleEl.style.display = 'block';
			reccEl.style.display = 'none';
		}else{
			singleEl.style.display = 'none';
			reccEl.style.display = 'block';
		}	
	}

</script>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="project_id" type="hidden" value="<?= $project->id ?>">
<input name="task_id" type="hidden" value="<?= $task->id?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<?if($_REQUEST['mode'] == "delete"){?>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_header_cell"><?= $title ?></td>
</tr>
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete this scheduled billing?<br><br>
		<span class="error">CAUTION:</span>This action is irreversible.<br><br>
	</td>
</tr>
<tr>
	<td class="form_footer_cell">
		<div align="center">
			<input type="submit" class="button" name="confirm" value="Yes">&nbsp;&nbsp;
			<input type="submit" class="button" name="confirm" value="No">
		</div>
	</td>
</tr>
</table>
<? }else{ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table" width="450">
<tr>
	<td class="form_field_header_cell" width="100">Project:</td>
	<td class="form_field_cell"><?= $project->name?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Task:</td>
	<td class="form_field_cell"><?= $task->name?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Item Code:</td>
	<td class="form_field_cell">
		<select name="item_code_id" id="item_code_id" class="input_text" onchange="updatePaymentSchedule()">
			<?= SI_ItemCode::getSelectTags($ps->item_code_id) ?>
		</select>
	</td>
</tr>
<? if($_REQUEST['mode'] == 'add'){ ?>
<tr>
	<td class="form_field_header_cell">Recurrence:</td>
	<td class="form_field_cell">
		<input type="radio" name="recurrence" onClick="switchRecurrence(this.value)" value="Single" CHECKED>&nbsp;Single
		<input type="radio" name="recurrence" onClick="switchRecurrence(this.value)" value="Monthly">&nbsp;Monthly
	</td>
</tr>
<? } ?>
</table>
<div id="recurrence_settings" style="display: none">
<table border="0" cellspacing="0" cellpadding="0" class="form_table" width="450">
<tr>
	<td class="form_field_header_cell" width="100">Start Date:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="start_ts" id="start_ts" SIZE="10" autocomplete="off" value="<?= date("n/j/Y")?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('start_ts')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">End Date:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="end_ts" id="end_ts" SIZE="10" autocomplete="off" value="<?= date("n/j/Y") ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('end_ts')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
</table>
</div>
<div id="single_entry">
<table border="0" cellspacing="0" cellpadding="0" class="form_table" width="450">
<tr>
	<td class="form_field_header_cell" width="100">Due Date:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="due_ts" id="due_ts" SIZE="10" autocomplete="off" value="<?= $ps->due_ts > 0 ? date("n/j/Y", $ps->due_ts) : '' ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('due_ts')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
</table>
</div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table" width="450">
<tr>
	<td class="form_field_header_cell" width="100">Amount:</td>
	<td class="form_field_cell"><input name="amount" id="amount" class="input_text" size="10" type="text" value="<?= $ps->amount ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Description:</td>
	<td class="form_field_cell"><input name="description" id="description" class="input_text" size="35" type="text" value="<?= $ps->description ?>"></td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" class="button" name="save" value="Save"></div>
	</td>
</tr>	
</table>
	</div>
</div>
<? } //if mode==delete
?>
</form>
<? require('footer.php'); ?>
