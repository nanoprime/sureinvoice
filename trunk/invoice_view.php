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

checkLogin();

if(intval($_REQUEST['id']) == 0){
  fatal_error("You must provide an invoice id to view!");
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

if(!$loggedin_user->hasRight('admin') && !$loggedin_user->hasRight('admin')){
	if($loggedin_user->company_id != $invoice->company_id){
		fatal_error("You do not have rights to access this invoice!");	
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

$title = "View Invoice";

require('header.php') ?>
<div class="tableContainer" style="clear:both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell"><a href="company_detail.php?id=<?= $company->id?>"><?= $company->name?></a></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 1:</td>
	<td class="form_field_cell"><?= $invoice->address1 ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 2:</td>
	<td class="form_field_cell"><?= $invoice->address2 ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">City:</td>
	<td class="form_field_cell"><?= $invoice->city ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">State:</td>
	<td class="form_field_cell"><?= $invoice->state ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Zip:</td>
	<td class="form_field_cell"><?= $invoice->zip ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Terms:</td>
	<td class="form_field_cell"><?= $invoice->terms ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Amount Due:</td>
	<td class="form_field_cell"><b><?= SureInvoice::getCurrencySymbol().number_format($invoice->getAmountDue(), 2) ?></b></td>
</tr>
</table>
	</div>
</div>
<div class="tableContainer" style="clear:both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Line items</a><div>
	<div class="gridToolbar">
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $url."detail=y" ?>" style="background-image:url(images/plus.png);">Show details</a>
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $url."detail=n" ?>" style="background-image:url(images/minus.png);">Hide details</a>		  
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<thead>
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Item Code</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Quantity</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">Unit Price</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">Tax</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 5, 0, false)">Total</a></th>
	</tr>
	</thead>
	<tbody id="bodyId">
<? for($i = 0; $i < count($invoice->_lines); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->getItemCodeCode() ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->quantity ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= $invoice->_lines[$i]->unit_price ?></td>
		<td class="dg_data_cell_1"><?= number_format($invoice->_lines[$i]->getTaxAmount(), 2);  ?></td>
		<td class="dg_data_cell_1"><?= number_format($invoice->_lines[$i]->getSubTotal(), 2);  ?></td>
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
		<td colspan="5" class="form_header_cell" align="right">Sub-Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($invoice->getSubTotal(), 2) ?></td>
	</tr>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">Tax:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($invoice->getTaxAmount(), 2) ?></td>
	</tr>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($invoice->getTotal(), 2) ?></td>
	</tr>
</table>
	</div>
</div><BR>
<? if(count($payments) > 0){ ?>
<div class="tableContainer" style="clear:both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Payments</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<td class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Date</a></td>
		<td class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Type</a></td>
		<td class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Ref Num</a></td>
		<td class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">Amount</a></td>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($payments); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= date("n/j/y H:i", $payments[$i]->timestamp) ?></td>
		<td class="dg_data_cell_1"><?= $payments[$i]->type ?></td>
		<td class="dg_data_cell_1">
<?		if($payments[$i]->type == 'CHECK'){ ?>
			<?= $payments[$i]->check_no ?>
<?		}elseif($payments[$i]->type == 'CC'){ ?>		
			<?= $payments[$i]->auth_code ?>
<?		} ?>
		</td>
		<td class="dg_data_cell_1"><?= number_format($payments[$i]->getAmountForInvoice($invoice->id), 2);  ?></td>
	</tr>
<? }?>
</tbody>
	<tr>
		<td colspan="3" class="form_header_cell" align="right">Total Paid:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format(($invoice->getTotal() - $invoice->getAmountDue()), 2) ?></td>
	</tr>
</table>
</div></div>
<? } ?>
<? require('footer.php') ?>