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
require_once('includes/SI_Invoice.php');
require_once('includes/SI_InvoiceLine.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_TaskActivity.php');
require_once('includes/SI_Payment.php');
require_once('includes/SI_ItemCode.php');

checkLogin('accounting');

$line = new SI_InvoiceLine();
$invoice = new SI_Invoice();
$company = new SI_Company();
$item_code = new SI_ItemCode();

if(!isset($_REQUEST['mode'])) $_REQUEST['mode'] = 'add';

// Clean up unit_price
if(!empty($_POST['unit_price'])){
	$_POST['unit_price'] = preg_replace('/[^0-9\.-]/','',$_POST['unit_price']);
}

// Prepare our variables
$line_added = false;
if($_REQUEST['mode'] == 'add_ta_link' || $_REQUEST['mode'] == 'add_sp_link' ||
   $_REQUEST['mode'] == 'add_ex_link' || $_REQUEST['mode'] == 'delete_link' ||
   $_REQUEST['mode'] == 'edit' || $_REQUEST['mode'] == 'delete'){
	if($_REQUEST['mode'] != 'delete' && intval($_REQUEST['id']) == 0 && intval($_REQUEST['invoice_id']) != 0){
		// We must be adding
		$line->invoice_id = $_REQUEST['invoice_id'];
		if($line->add() === FALSE){
			$error_msg .= "Error adding new line item to invoice!";
			debug_message($line->getLastError());
		}
		$_REQUEST['id'] = $line->id;
		$line_added = true;
	}elseif(intval($_REQUEST['id']) == 0 && intval($_REQUEST['invoice_id']) == 0){
		fatal_error('You must provide an invoice id when adding a new line!');	
	}

	if($line->get($_REQUEST['id']) === FALSE){
		$error_msg .= "Error getting invoice line";
		debug_message($line->getLastError());	
	}

	$invoice = $line->getInvoice();
	if($invoice === FALSE){
	  $error_msg .= "Error retreiving invoice information!\n";
	  debug_message($line->getLastError());
	}

	if($company->get($invoice->company_id) === FALSE){
	  $error_msg .= "Error retreiving company information!\n";
	  debug_message($company->getLastError());
	}	
}

// Handle preliminary actions
if($_REQUEST['mode'] == 'add_ta_link'){
	if(intval($_REQUEST['ta_id']) == 0){
		fatal_error("You must provide a task activity id to add a link!");
	}
	
	$ill = new SI_InvoiceLineLink();
	$ill->task_activity_id = $_REQUEST['ta_id'];
	$ill->invoice_line_id = $line->id;
	if($line->unit_price > 0 && $ill->getUnitPrice() != $line->unit_price){
		fatal_error("You can not add this task activity to this line because it has a different unit price!");	
	}else{
		if($line->unit_price <= 0) $line->unit_price = $ill->getUnitPrice();
		if($line->description == '') $line->description = $ill->getDescription();
	}
	
	if($ill->add() === FALSE){
		$error_msg .= "Could not add task activity to invoice!";
		debug_message($ill->getLastError());	
	}else{
		$line->quantity += $ill->getQuantity();
		$line->addTax();
		if($line->update() === FALSE){
			$error_msg .= "Could not update quantity on line item!";
			debug_message($line->getLastError());	
		}else{
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->amount += $ill->getPrice();
				if($ct->update() === FALSE){
					$error_msg .= "Error updating company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					header('Location: '.getCurrentURL(null, false).'?mode=edit&id='.$line->id."\r\n");
					die();
				}	
			}
		}
	}
	
	$_REQUEST['mode'] = 'edit';
}elseif($_REQUEST['mode'] == 'add_sp_link'){
	if(intval($_REQUEST['sp_id']) == 0){
		fatal_error("You must provide a scheduled payment id to add a link!");
	}

	$ill = new SI_InvoiceLineLink();
	$ill->payment_schedule_id = $_REQUEST['sp_id'];
	$ill->invoice_line_id = $line->id;
	if($line->unit_price > 0 && $ill->getUnitPrice() != $line->unit_price){
		fatal_error("You can not add this scheduled payment to this line because it has a different unit price!");	
	}else{
		if($line->unit_price <= 0) $line->unit_price = $ill->getUnitPrice();
		if($line->description == '') $line->description = $ill->getDescription();
	}
	
	if($ill->add() === FALSE){
		$error_msg .= "Could not add scheduled payment to invoice!";
		debug_message($ill->getLastError());	
	}else{
		$line->quantity += $ill->getQuantity();
		$line->addTax();
		if($line->update() === FALSE){
			$error_msg .= "Could not update quantity on line item!";
			debug_message($line->getLastError());	
		}else{
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->amount += $ill->getPrice();
				if($ct->update() === FALSE){
					$error_msg .= "Error updating company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					header('Location: '.getCurrentURL(null, false).'?mode=edit&id='.$line->id."\r\n");
					die();
				}	
			}
		}
	}

	$_REQUEST['mode'] = 'edit';
}elseif($_REQUEST['mode'] == 'add_ex_link'){
	if(intval($_REQUEST['ex_id']) == 0){
		fatal_error("You must provide a expense id to add a link!");
	}

	$ill = new SI_InvoiceLineLink();
	$ill->expense_id = $_REQUEST['ex_id'];
	$ill->invoice_line_id = $line->id;
	if($line->unit_price > 0 && $ill->getUnitPrice() != $line->unit_price){
		fatal_error("You can not add this expense to this line because it has a different unit price!");	
	}else{
		if($line->unit_price <= 0) $line->unit_price = $ill->getUnitPrice();
		if($line->description == '') $line->description = $ill->getDescription();
	}
	
	if($ill->add() === FALSE){
		$error_msg .= "Could not add expense to invoice!";
		debug_message($ill->getLastError());	
	}else{
		$line->quantity += $ill->getQuantity();
		$line->addTax();
		if($line->update() === FALSE){
			$error_msg .= "Could not update quantity on line item!";
			debug_message($line->getLastError());	
		}else{
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->amount += $ill->getPrice();
				if($ct->update() === FALSE){
					$error_msg .= "Error updating company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					header('Location: '.getCurrentURL(null, false).'?mode=edit&id='.$line->id."\r\n");
					die();
				}	
			}
		}
	}

	$_REQUEST['mode'] = 'edit';
}elseif($_REQUEST['mode'] == 'delete_link'){
	if(intval($_REQUEST['link_id']) == 0){
		fatal_error("You must provide a link id to delete a link!");
	}

	$ill = new SI_InvoiceLineLink();
	if($ill->get($_REQUEST['link_id']) === FALSE){
		$error_msg .= "Could not get invoice line!";
		debug_message($ill->getLastError());	
	}else{
		$line->quantity -= $ill->getQuantity();
		$line->addTax();
		if($line->update() === FALSE){
			$error_msg .= "Could not update quantity on line item!";
			debug_message($line->getLastError());	
		}else{
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->amount -= $ill->getPrice();
				if($ct->update() === FALSE){
					$error_msg .= "Error updating company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					if($ill->delete() === FALSE){
						$error_msg .= "Error deleting line link";
						debug_message($ill->getLastError());
					}else{
						header('Location: '.getCurrentURL(null, false).'?mode=edit&id='.$line->id."\r\n");
						die();
					}
				}
			}
		}
	}
	
	$_REQUEST['mode'] = 'edit';
}
if($line->id > 0){
	$line->get($line->id);
} 

// Handle main actions
if($_REQUEST['mode'] == 'delete'){
	if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) <= 0){
		fatal_error('you must specify a line id to delete!');
	}
	
	if($line->get($_REQUEST['id']) === FALSE){
		$error_msg .= "Could not get line item!";
		debug_message($line->getLastError());	
	}else{
		$ct = new SI_CompanyTransaction();
		if($ct->get($invoice->trans_id) === FALSE){
			$error_msg .= "Error getting transaction for invoice!";
			debug_message($ct->getLastError());	
		}else{
			$ct->amount -= $line->getTotal();
			if($ct->update() === FALSE){
				$error_msg .= "Error updating company transaction with new price";
				debug_message($ct->getLastError());	
			}else{
				if($line->delete() === FALSE){
					$error_msg .= "Error deleting line";
					debug_message($line->getLastError());
				}else{
					goBack();
				}
			}	
		}
	}
	
	$_REQUEST['mode'] = 'edit';
}elseif($_REQUEST['mode'] == 'add'){
	if(intval($_REQUEST['invoice_id']) == 0){
		fatal_error("You must provide an invoice id to add a line item!");
	}

	if($invoice->get($_REQUEST['invoice_id']) === FALSE){
	  $error_msg .= "Error retreiving invoice information!\n";
	  debug_message($line->getLastError());
	}
	
	if($company->get($invoice->company_id) === FALSE){
	  $error_msg .= "Error retreiving company information!\n";
	  debug_message($company->getLastError());
	}
	
	if(isset($_POST['save_line'])){	
		$line->updateFromAssocArray($_POST);
		$line->addTax();
		if($line->add() === FALSE){
			$error_msg .= "Error adding new line!\n";
	  		debug_message($line->getLastError());
		}else{
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->amount += $line->getTotal();
				if($ct->update() === FALSE){
					$error_msg .= "Error updating company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					header('Location: '.getCurrentURL('invoice_edit.php').'?id='.$invoice->id."\r\n");
					die();
				}	
			}
			
		}
	}	

}elseif($_REQUEST['mode'] == 'edit'){
	
	if(isset($_POST['save_line'])){	
		$old_line = $line;
		$line->updateFromAssocArray($_POST);
		$line->addTax();
		if($line->update() === FALSE){
			$error_msg .= "Error updating line information!\n";
	  		debug_message($line->getLastError());
		}else{
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->amount -= $old_line->getTotal();
				$ct->amount += $line->getTotal();
				if($ct->update() === FALSE){
					$error_msg .= "Error updating company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					header('Location: '.getCurrentURL('invoice_edit.php').'?id='.$invoice->id."\r\n");
					die();
				}	
			}
			
		}
	}	
}

$activity = new SI_TaskActivity();
$ps = new SI_PaymentSchedule();
$activities = $activity->getActivitiesForCompany($invoice->company_id, true, 0, true);
if($activities === FALSE){
	$error_msg .= "Could not retrieve Activity List for Company!\n";
	debug_message($activity->getLastError());
}

$items = $ps->getForCompany($invoice->company_id);
if($items === FALSE){
	$error_msg .= "Could not retrieve scheduled payments for Company!\n";
	debug_message($ps->getLastError());
}

$expenses = $company->getExpenses(TRUE);
if($expenses === FALSE){
	$error_msg .= "Could not retrieve expenses for Company!\n";
	debug_message($company->getLastError());
}

$base_url = $_SERVER['PHP_SELF'].'?id='.$line->id.'&invoice_id='.$invoice->id.'&hide_url=true&';

require('header.php') ?>
<form name="line_edit" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input type="hidden" name="mode" value="<?= $_REQUEST['mode'] ?>"/>  
<input type="hidden" name="id" value="<?= $line->id ?>"/>  
<input type="hidden" name="invoice_id" value="<?= $invoice->id ?>"/>  
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Current Line</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell">Item Code</th>
		<th class="dg_header_cell">Quantity</th>
		<th class="dg_header_cell">Unit Price</th>
	</tr>
	<tr>
		<th class="dg_header_cell">Description</th>
		<th class="dg_header_cell">Tax Amount</th>
		<th class="dg_header_cell">Total</th>
	</tr>
	<tr>
		<td class="dg_data_cell_1">
			<select name="item_code_id" id="item_code_id" class="input_text">
				<?= SI_ItemCode::getSelectTags($line->item_code_id) ?>
			</select>
		</td>
		<td class="dg_data_cell_1"><input type="text" size="8" name="quantity" value="<?= $line->quantity ?>"></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol() ?><input type="text" size="10" name="unit_price" value="<?= number_format($line->unit_price,2) ?>"></td>
	</tr>
	<tr>
		<td class="dg_data_cell_1"><input type="text" size="50" name="description" value="<?= $line->description ?>"></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol() ?><input readonly type="text" size="10" name="tax_amount" value="<?= number_format($line->getTaxAmount(),2) ?>"></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol() ?><input readonly  size="12" type="text" name="line_total" value="<?= number_format($line->getTotal(),2) ?>"></td>
	</tr>
	<tr>
		<td colspan="7" class="form_header_cell" align="right"><input type="submit" name="save_line" value="Save"/></td>
	</tr>
</table>

	</div>
</div>
</form>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Current Links</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell">Type</th>
		<th class="dg_header_cell">Quantity</th>
		<th class="dg_header_cell">Description</th>
		<th class="dg_header_cell">Unit Price</th>
		<th class="dg_header_cell">Total</th>
		<th class="dg_header_cell">Options</th>
	</tr>
<?
$links =& $line->getLinks(); 
$line_total = 0.00;
$quantity_total = 0.00;
for($i = 0; $i < count($links); $i++){
	$quantity_total += $links[$i]->getQuantity();
	$line_total += $links[$i]->getPrice(); 
	?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $links[$i]->getType() ?></td>
		<td class="dg_data_cell_1"><?= $links[$i]->getQuantity() ?></td>
		<td class="dg_data_cell_1"><?= $links[$i]->getDescription() ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($links[$i]->getUnitPrice(),2) ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($links[$i]->getPrice(),2) ?></td>
		<td class="dg_data_cell_1">
			<a class="link1" href="<?= $base_url ?>mode=delete_link&link_id=<?= $links[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="" border="0" title="Remove Link"/></a>
		</td>
	</tr>
<? } ?>
	<tr>
		<td class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= number_format($quantity_total, 2) ?></td>
		<td colspan="2" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($line_total, 2) ?></td>
		<td></td>
	</tr>
</table>
	</div>
</div>
<? if(count($activities)>0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Select Activities</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 1, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Start</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 3, 0, false)">End</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Time Spent</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 5, 0, false)">Hourly Rate</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 6, 0, false)">Price</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId1">
<? for($i = 0; $i < count($activities); $i++){
		if($activities[$i]->hourly_rate <= 0)
			continue;

		$activities[$i]->_calcPrice();
		$act_time = ($activities[$i]->end_ts>0 &&  $activities[$i]->start_ts>0 ? $activities[$i]->end_ts - $activities[$i]->start_ts : 0);
		$total_time += $act_time;
		$total_cost += $activities[$i]->cost;
		$total_com += $activities[$i]->com_amount;
		$total_price += $activities[$i]->price;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $activities[$i]->project_name ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->task_name ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->start_ts>0 ? date("n/j/y H:i", $activities[$i]->start_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->end_ts>0 ? date("n/j/y H:i", $activities[$i]->end_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->hourly_rate, 2) ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->price, 2) ?></td>
		<td class="gridActions">
			<a class="link1" href="<?= $base_url ?>mode=add_ta_link&ta_id=<?= $activities[$i]->id ?>"><img src="images/add.png" width="16" height="16" alt="" border="0" title="Add Activity To Line"/></a>
		</td>
	</tr>
<?
}?>
	</tbody>
	<tr>
		<td colspan="4" class="form_header_cell" align="right">Totals:</td>
		<td class="form_field_cell"><?= formatLengthOfTime($total_time) ?></td>
		<td class="form_header_cell"></td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price, 2) ?></td>
		<td></td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<? if(count($items)>0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Select Scheduled Payments</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId_ps', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId_ps', 1, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId_ps', 2, 0, false)">Due Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId_ps', 3, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId_ps', 4, 0, false)">Amount</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId_ps">
<? for($i = 0; $i < count($items); $i++){
		$total_price2 += $items[$i]->amount;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $items[$i]->getProjectName() ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->getTaskName() ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->due_ts>0 ? date("n/j/y", $items[$i]->due_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($items[$i]->amount, 2) ?></td>
		<td class="dg_data_cell_1">
			<a class="link1" href="<?= $base_url ?>mode=add_sp_link&sp_id=<?= $items[$i]->id ?>"><img src="images/add.png" width="16" height="16" alt="" border="0" title="Add Schedule Payment To Line"/></a>
		</td>
	</tr>
<? }?>
	</tbody>
	<tr>
		<td colspan="4" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price2, 2) ?></td>
		<td class="form_header_cell" align="right"></td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<? if(count($expenses)>0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Select Expenses</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 1, 0, false)">Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 3, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 4, 0, false)">Amount</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId3">
<? for($i = 0; $i < count($expenses); $i++){
		$total_price3 += $expenses[$i]->price;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $expenses[$i]->getProjectName() == "" ? 'Not linked to a project' : $expenses[$i]->getProjectName() ?></td>
		<td class="dg_data_cell_1"><?= $expenses[$i]->created_ts>0 ? date("n/j/y", $expenses[$i]->created_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $expenses[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($expenses[$i]->price, 2) ?></td>
		<td class="dg_data_cell_1">
			<a class="link1" href="<?= $base_url ?>mode=add_ex_link&ex_id=<?= $expenses[$i]->id ?>"><img src="images/add.png" width="16" height="16" alt="" border="0" title="Add Expense To Line"/></a>
		</td>
	</tr>
<? }?>
	</tbody>
	<tr>
		<td colspan="3" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price3, 2) ?></td>
		<td class="form_header_cell" align="right"></td>
	</tr>
</table>
	</div>
</div>
<? } ?>

<? require('footer.php') ?>
