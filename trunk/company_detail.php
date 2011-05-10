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
require_once('includes/SI_Project.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_Invoice.php');
require_once('includes/SI_ItemCode.php');

checkLogin();
$item_code = new SI_ItemCode();

$project = new SI_Project();
if($loggedin_user->isDeveloper() && !empty($_REQUEST['id'])){
	$id = $_REQUEST['id'];
}else{
	$id = $loggedin_user->company_id;
}

$company = new SI_Company();
if($company->get($id) === FALSE){
	$error_msg .= "Could not retrieve data for company!\n";
	debug_message($company->getLastError());
}

$balance = $company->getBalance();
if($balance === FALSE){
	$error_msg .= "Error getting your outstanding balance!";
	debug_message($company->getLastError());
}

$transactions = $company->getTransactions(NULL, 5);
if($transactions === FALSE){
	$error_msg .= "Error getting transactions for company!\n";
	debug_message($company->getLastError());
}

$invoice = new SI_Invoice();
$invoices = $invoice->getOutstanding($company->id);
if($invoices === FALSE){
	$error_msg .= "Could not retrieve Outstanding Invoice list!\n";
	debug_message($invoice->getLastError());
}

$project = new SI_Project();
$active_projects = TRUE;
if(isset($_REQUEST['show_all']) && $_REQUEST['show_all'] == 'Y'){
	$active_projects = FALSE;
}
$projects = $project->getCompanyProjects($company->id, $active_projects);
if($projects === FALSE){
	$error_msg .= "Could not retrieve active project list!\n";
	debug_message($project->getLastError());
}

if(isset($_POST['save'])){
	if(is_array($_POST['expense']) && !empty($_POST['expense']['description'])){
		$_POST['expense']['cost'] = preg_replace('/[^0-9\.]/','', $_POST['expense']['cost']);
		$_POST['expense']['price'] = preg_replace('/[^0-9\.]/','', $_POST['expense']['price']);
		if($company->addExpense($_POST['expense']['item_code_id'], $_POST['expense']['description'], $_POST['expense']['cost'], $_POST['expense']['price']) === FALSE){
			$error_msg .= "Error adding new expense!";
			debug_message($company->getLastError());	
		}else{
			$error_msg .= "Added new expense!";	
		}
	}else{
		$error_msg .= "Invalid entry for new expense, Expense not added!";
	}	
}

$title = $company->name." Detail Center";
if($loggedin_user->hasRight('admin')){
	$update_url = "company.php?mode=edit&id=$id";
}else{
	$update_url = "company_profile.php?id=$id";
}

$item_codes = $item_code->getCompanyPricedCodes($company->id);
if($item_codes === FALSE){
	$error_msg .= "Could not get item codes for company!\n";
	debug_message($item_code->getLastError());	
}
require('header.php'); ?>
<script>
function reloadPage(selObj){
	var user_id = selObj.options[selObj.selectedIndex].value;
	window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter=<?= $_REQUEST['filter'] ?>&user_id="+user_id;
}

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
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="4" cellpadding="0">
<tr>
	<td valign="top" align="LEFT" colspan="2">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Information</a><div>
		<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="200">
			<tr>
				<td class="form_field_cell" colspan="2">
					<b><?= $company->name ?></b><br>
					<?= $company->address1.( !empty($company->address2) ? '<br>'.$company->address2 : '' )?><br>
					<?= $company->city.', '.$company->state.'   '.$company->zip ?>
					<div align="right"><a href="<?= $update_url ?>">Update</a></div>
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Outstanding Invoices</a><div>
	<div class="gridToolbar">
		  <a href="company_invoices.php?id=<?= $id ?>" >View all</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
<?	if(count($invoices) > 0){?>
			<tr>
				<td class="form_field_cell" colspan="2">
					<table border="0" cellspacing="0" cellpadding="0">
<?	for($i=0; $i<count($invoices); $i++){
			$total += $invoices[$i]->getAmountDue(); ?>
					<tr>
						<td><a title="View Invoice" href="invoice_view.php?id=<?= $invoices[$i]->id ?>"><?= $invoices[$i]->id ?></a>&nbsp;</td>
						<td align="right">&nbsp;&nbsp;<?= date("n/j/y", $invoices[$i]->timestamp) ?></td>
						<td align="right">&nbsp;&nbsp;<?= SureInvoice::getCurrencySymbol().number_format($invoices[$i]->getAmountDue(),2) ?></td>
						<td align="right">&nbsp;&nbsp;
							<a class="link1" href="invoice_view.php?id=<?= $invoices[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="View Invoice" border="0" /></a>
							<a class="link1" target="invoice_window" href="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=1"><img src="images/invoice_detail.png" width="16" height="16" title="View Detailed PDF Invoice" border="0" /></a>
							<a class="link1" target="invoice_window" href="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=0"><img src="images/invoice_simple.png" width="16" height="16" title="View Simple PDF Invoice" border="0" /></a>
							<a href="cc_payment.php?invoice_id=<?= $invoices[$i]->id ?>"><img src="images/payment.png" border="0" width="16" height="16" title="Make Payment"></a>
						</td>
					</tr>
<?	}?>
					</table>
				</td>
			</tr>
			<tr>
				<td class="form_field_header_cell">Total:</td>
				<td class="form_field_cell" align="right"><?= SureInvoice::getCurrencySymbol().number_format($total, 2) ?></td>
			</tr>
<?	}else{ // if invoices > 0 ?>
			<tr>
				<td colspan="2" class="form_field_cell">None</td>
			</tr>

<?	} // if invoices > 0  
?>
		</table>
	</div>
</div>
	</td>
</tr>
<tr>
	<td valign="top" colspan="2" align="LEFT">
		<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Active Projects</a><div>
<?				if($loggedin_user->isDeveloper()){ ?>
	<div class="gridToolbar">
		  <a href="project.php?mode=add&company_id=<?= $id ?>" style="background-image:url(images/new_invoice.png);">New Project</a>
		  <a href="company_detail.php?id=<?= $id ?>&show_all=Y">View all</a>		  
	</div>
<? } ?>	
<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="100%">
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 1, false)">Project</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Status</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 3, 0, false)">Due Date</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Priority</a></th>
				<th class="dg_header_cell">Options</th>
			</tr>
			<tbody id="bodyId1">
		<? for($i = 0; $i < count($projects); $i++){ ?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td class="dg_data_cell_1"><a title="Project Details" href="project_details.php?id=<?= $projects[$i]->id ?>"><?= $projects[$i]->name ?></a></td>
				<td class="dg_data_cell_1"><?= $projects[$i]->status ?></td>
				<td class="dg_data_cell_1"><?=  $projects[$i]->due_ts>0 ? date("n/j/y", $projects[$i]->due_ts) : "None" ?></td>
				<td class="dg_data_cell_1"><?= $projects[$i]->priority ?></td>
				<td class="dg_data_cell_1">&nbsp;
					<a class="link1" href="project_details.php?id=<?= $projects[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="Project Details" border="0" /></a>
		<?	if($projects[$i]->hasRights(PROJECT_RIGHT_EDIT)){?>
					&nbsp;|&nbsp;<a class="link1" href="project.php?mode=edit&id=<?= $projects[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Project" border="0" /></a>
		<?	}?>
		<?	if($projects[$i]->hasRights(PROJECT_RIGHT_FULL)){?>
					&nbsp;|&nbsp;<a class="link1" href="project.php?mode=delete&id=<?= $projects[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Project" border="0" /></a>&nbsp;
		<?	}?>
				</td>
			</tr>
		<? }?>
		</tbody>
		</table>
	</div>
</div>
	</td>
</tr>
<? if($loggedin_user->isDeveloper()){ ?>
<tr>
	<td valign="top" colspan="2" align="LEFT">
		<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
		<input type="hidden" name="id" value="<?= $id ?>">
		<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Add New Expense</a><div>
<table class="dg_table" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th class="dg_header_cell">Item Code</th>
			<th class="dg_header_cell">Description</th>
			<th class="dg_header_cell">Cost</th>
			<th class="dg_header_cell">Price</th>
		</tr>
		<tr>
			<td class="dg_data_cell_1">
				<select name="expense[item_code_id]" id="expense[item_code_id]" tabindex="11" class="input_text" onchange="updateExpense()">
					<option value="0">No expense</option>
					<?= SI_ItemCode::getSelectTags() ?>
				</select>
			</td>
			<td class="dg_data_cell_1"><input type="text" name="expense[description]" id="expense[description]" size="40" maxlength="255"></td>
			<td class="dg_data_cell_1"><input type="text" name="expense[cost]" id="expense[cost]" size="10" maxlength="15"></td>
			<td class="dg_data_cell_1"><input type="text" name="expense[price]" id="expense[price]" size="10" maxlength="15"></td>
		</tr>
		<tr>
			<td colspan="4" class="dg_data_cell_1" align="right"><input type="submit" name="save" value="Add Expense"/></td>
		</tr>
		</table>
	</div>
</div>
		</form>
	</td>
</tr>
<? } ?>
</table>

	</div>
</div>
<? require('footer.php') ?>
