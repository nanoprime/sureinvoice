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
require_once('includes/SI_Expense.php');
require_once('includes/SI_CompanyTransaction.php');
require_once('includes/SI_Project.php');
require_once('includes/SI_Task.php');
require_once('includes/SI_ItemCode.php');

checkLogin('accounting');

$title = '';
$expense = new SI_Expense();
$company = new SI_Company();
$project = new SI_Project();
$task = new SI_Task();
$item_code = new SI_ItemCode();

// Clean up cost
if(!empty($_POST['cost'])){
	$_POST['cost'] = preg_replace('/[^0-9\.]/','', $_POST['cost']);
}

// Clean up price
if(!empty($_POST['price'])){
	$_POST['price'] = preg_replace('/[^0-9\.]/','', $_POST['price']);
}

if($_REQUEST['mode'] == 'add'){
	$title = "Add Expense";

	$project_id = intval($_REQUEST['project_id']);
	$task_id = intval($_REQUEST['task_id']);
	if($project_id == 0 && $task_id == 0){
		fatal_error("You must provide a task_id or project_id for this expense!\n");
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
		$expense->updateFromAssocArray($_POST);
		if($expense->add()){
			goBack();
		}else{
			$error_msg .= "Error adding Expense!\n";
			debug_message($expense->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Expense";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$expense->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving payment information!\n";
			debug_message($expense->getLastError());
		}
	}

	if($expense->task_id > 0){
		if($task->get($expense->task_id) === FALSE){
			$error_msg .= "Error getting task information!\n";
			debug_message($task->getLastError());
		}

		if($project->get($task->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}


		if($company->get($project->company_id) === FALSE){
			$error_msg .= "Error getting company information!\n";
			debug_message($company->getLastError());			
		}	
	}elseif($expense->project_id > 0){
		if($project->get($expense->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}

		if($company->get($project->company_id) === FALSE){
			$error_msg .= "Error getting company information!\n";
			debug_message($company->getLastError());			
		}	
	}else{
		if($company->get($expense->company_id) === FALSE){
			$error_msg .= "Error getting company information!\n";
			debug_message($company->getLastError());			
		}	
	}

	if($_POST['save']){
		$expense->updateFromAssocArray($_POST);
		if($expense->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Expense!\n";
			debug_message($expense->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Expense";

	if(!$expense->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving payment information!\n";
		debug_message($expense->getLastError());
	}

	if($expense->task_id > 0){
		if($task->get($expense->task_id) === FALSE){
			$error_msg .= "Error getting task information!\n";
			debug_message($task->getLastError());
		}

		if($project->get($task->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}


		if($company->get($project->company_id) === FALSE){
			$error_msg .= "Error getting company information!\n";
			debug_message($company->getLastError());			
		}	
	}elseif($expense->project_id > 0){
		if($project->get($expense->project_id) === FALSE){
			$error_msg .= "Error getting project information!\n";
			debug_message($project->getLastError());
		}

		if($company->get($project->company_id) === FALSE){
			$error_msg .= "Error getting company information!\n";
			debug_message($company->getLastError());			
		}	
	}else{
		if($company->get($expense->company_id) === FALSE){
			$error_msg .= "Error getting company information!\n";
			debug_message($company->getLastError());			
		}	
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($expense->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Expense!\n";
			}
		}else{
			goBack();
		}
	}
}else{
	fatal_error("Invalid mode ({$_REQUEST['mode']}) for this page!");
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
	
	var item_prices = new Array();
<?	for($i = 0; $i <= count($item_codes); $i++){
		if(!empty($item_codes[$i]->id)){
			print("item_prices[".$item_codes[$i]->id."] = new ItemCode(\"".$item_codes[$i]->id."\",\"".$item_codes[$i]->description."\", \"".$item_codes[$i]->cost."\", \"".$item_codes[$i]->price."\");\n");
		} 
	} ?>

	function updateExpense(){
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


</script>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="project_id" type="hidden" value="<?= $project->id ?>">
<input name="task_id" type="hidden" value="<?= $task->id?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<?if($_REQUEST['mode'] == "delete"){?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_header_cell"><?= $title ?></td>
</tr>
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete this expense?<br><br>
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
	</div>
</div>
<? }else{ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Project:</td>
	<td class="form_field_cell"><?= $project->name?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Task:</td>
	<td class="form_field_cell"><?= $task->name?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Item Code:</td>
	<td class="form_field_cell">
		<select name="item_code_id" id="item_code_id" class="input_text" onchange="updateExpense()">
			<?= SI_ItemCode::getSelectTags($expense->item_code_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Description:</td>
	<td class="form_field_cell"><input name="description" id="description" class="input_text" size="35" type="text" value="<?= $expense->description ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Cost:</td>
	<td class="form_field_cell"><input name="cost" id="cost" class="input_text" size="10" type="text" value="<?= $expense->cost ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Price:</td>
	<td class="form_field_cell"><input name="price" id="price" class="input_text" size="10" type="text" value="<?= $expense->price ?>"></td>
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