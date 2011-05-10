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
require_once('includes/SI_Company.php');
require_once('includes/SI_TaskActivity.php');
require_once('includes/SI_Payment.php');

checkLogin('accounting');

if(intval($_REQUEST['id']) == 0){
  fatal_error("You must provide an invoice id to edit!");
}

$invoice = new SI_Invoice();
if($invoice->get(intval($_REQUEST['id'])) === FALSE){
  $error_msg .= "Error retreiving invoice information!\n";
  debug_message($invoice->getLastError());
}

$company = new SI_Company();
if($company->get($invoice->company_id) === FALSE){
  $error_msg .= "Error retreiving company information!\n";
  debug_message($company->getLastError());
}


if(isset($_POST['save_top'])){
	$old_invoice = $invoice;
	$timestamp = getTSFromInput($_POST['timestamp']);
	$_POST['timestamp'] = $timestamp;
	$invoice->updateFromAssocArray($_POST);
	if($invoice->update() === FALSE){
		$error_msg .= "Error updating invoice information!";
		debug_message($invoice->getLastError());
	}else{
		//Add discount line
		if($_POST['discount_line'] == 'Y'){
			if($invoice->addDiscountLine() === FALSE){
				$error_msg = "Error calculating discount line\n";
				debug_message($invoice->getLastError());	
			}
		}
	

		if($old_invoice->company_id != $invoice->company_id){
			$ct = new SI_CompanyTransaction();
			if($ct->get($invoice->trans_id) === FALSE){
				$error_msg .= "Error getting transaction for invoice!";
				debug_message($ct->getLastError());	
			}else{
				$ct->company_id = $invoice->company_id;
				if($ct->update() === FALSE){
					$error_msg .= "Error moving company transaction with new price";
					debug_message($ct->getLastError());	
				}else{
					goBack();
				}
			}	
		}	
	}
}	

$payment = new SI_Payment();
$payments = $payment->getForInvoice($invoice->id);
if($payments === FALSE){
	$error_msg .= "Error retreiving payment information!\n";
	debug_message($payment->getLastError());
}

$_REQUEST['detail'] = strtolower(substr($_REQUEST['detail'],0,1)) == "y" ? TRUE : FALSE;
$url = $_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'&';

$title = "Edit Invoice";

require('header.php') ?>
<form name="invoice_edit" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input type="hidden" name="id" value="<?= $invoice->id ?>"/>  
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell">
		<select name="company_id" class="input_text">
			<?= SI_Company::getSelectTags($invoice->company_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Date:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="timestamp" id="timestamp" SIZE="10" autocomplete="off" value="<?= date("n/j/Y", $invoice->timestamp)?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('timestamp')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 1:</td>
	<td class="form_field_cell"><input name="address1" class="input_text" size="35" type="text" value="<?= $invoice->address1 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 2:</td>
	<td class="form_field_cell"><input name="address2" class="input_text" size="35" type="text" value="<?= $invoice->address2 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">City:</td>
	<td class="form_field_cell"><input name="city" class="input_text" size="35" type="text" value="<?= $invoice->city ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">State:</td>
	<td class="form_field_cell"><input name="state" class="input_text" size="5" type="text" value="<?= $invoice->state ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Zip:</td>
	<td class="form_field_cell"><input name="zip" class="input_text" size="10" type="text" value="<?= $invoice->zip ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Terms:</td>
	<td class="form_field_cell">
		<select name="terms" class="input_text">
			<?= SI_Invoice::getTermsSelectTags($invoice->terms) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Rate Structure:</td>
	<td class="form_field_cell">
		<select name="discount_line" class="input_text">
			<option value="N">Do Not Recalculate Discount</option>
			<option value="Y">Recalculate Discount, if setup for company</option>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Amount Due:</td>
	<td class="form_field_cell"><b><?= SureInvoice::getCurrencySymbol().number_format($invoice->getAmountDue(), 2) ?></b></td>
</tr>
<tr>
	<td class="form_header_cell" colspan="2" align="right"><input type="submit" name="save_top" value="Save"/></td>
</tr>
</table>
	</div>
</div>
</form>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Line Items</a><div>
	<div class="gridToolbar">
		  <a href="invoice_line.php?mode=add&invoice_id=<?= $invoice->id ?>" style="background-image:url(images/new_invoice.png);">New Line</a>
						<a class="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $url."detail=y" ?>" style="background-image:url(images/plus.png);">Show details</a>
						<a class="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $url."detail=n" ?>" style="background-image:url(images/minus.png);">Hide details</a>		  
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Item Code</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Quantity</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">Unit Price</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">Tax Amount</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 5, 0, false)">Total</a></th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($invoice->_lines); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->getItemCodeCode() ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->quantity ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->unit_price ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->getTaxAmount() ?></td>
		<td class="dg_data_cell_1"><?= number_format($invoice->_lines[$i]->getTotal(), 2);  ?></td>
		<td class="gridActions">
			<a class="link1" href="invoice_line.php?mode=edit&id=<?= $invoice->_lines[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Line" border="0" /></a>
		</td>
		<td class="gridActions">
			<a class="link1" href="invoice_line.php?mode=delete&id=<?= $invoice->_lines[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Line" border="0" /></a>
		</td>		
	</tr>
<? if($_REQUEST['detail']){
	$links =& $invoice->_lines[$i]->_links; 
	for($x=0; $x<count($links); $x++){ 
		if($links[$x]->task_activity_id > 0){ 
			$ta = new SI_TaskActivity();
			$ta->get($links[$x]->task_activity_id); ?>
	<tr>
		<td colspan="11" class="dg_data_cell_1">
			<table cellpadding="2" cellspacing="0">
    		<tr>
      			<td valign="top" nowrap><b><?= date("n/j/y H:i", $ta->start_ts)."-".date("H:i", $ta->end_ts)?></b></td>
      			<td valign="top"><?= $ta->text ?></td>
			</tr>
			</table>
		</td>
	</tr>
<?		}
		if($links[$x]->payment_schedule_id > 0){
			$ps = new SI_PaymentSchedule();
			$ps->get($links[$x]->payment_schedule_id); ?>
	<tr>
		<td colspan="11" class="dg_data_cell_1"><?= $ps->description ?></td>
	</tr>
<?		} ?>
<? 	} // end for links
	if($i != count($activities)-1){?>
	<tr>
		<td colspan="11" class="dg_header_cell">&nbsp;</td>
	</tr>
<? 	} //If not last ?>
<? } //If detail ?>
<? }?>
</tbody>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($invoice->getTotal(), 2) ?></td>
	</tr>
</table>
	</div>
</div>
<? if(count($payments) > 0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Payments</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Type</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Amount</a></th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($payments); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= date("n/j/y H:i", $payments[$i]->timestamp) ?></td>
		<td class="dg_data_cell_1"><?= $payments[$i]->type ?></td>
		<td class="dg_data_cell_1"><?= number_format($payments[$i]->getAmountForInvoice($invoice->id), 2);  ?></td>
	</tr>
<? }?>
</tbody>
	<tr>
		<td colspan="2" class="form_header_cell" align="right">Total Paid:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format(($invoice->getTotal() - $invoice->getAmountDue()), 2) ?></td>
	</tr>
</table>

	</div>
</div>
<? } ?>

<? require('footer.php') ?>