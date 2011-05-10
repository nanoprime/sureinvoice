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
require_once('includes/SI_Company.php');
require_once('includes/SI_ItemCode.php');
require_once('includes/SI_Account.php');
require_once('includes/SI_Invoice.php');
require_once('includes/SI_Payment.php');


checkLogin();
if(isset($_REQUEST['last_update_ts'])){
	$_REQUEST['update_ts'] = getTSFromInput($_REQUEST['last_update_ts']);	
}

if(isset($_REQUEST['update_ts'])){
	$last_update_ts = $_REQUEST['update_ts'];
}else{
	$last_update_ts = time() - (60 * 60 * 24 * 30);	
}

$company = new SI_Company();
$companies = $company->retrieveSet("WHERE updated_ts > $last_update_ts");
if($companies === FALSE){
	$error_msg .= "Error getting companies updated since ".date('m-d-Y', $last_update_ts);
	debug_message($company->getLastError());
}

$item_code = new SI_ItemCode();
$item_codes = $item_code->retrieveSet("WHERE updated_ts > $last_update_ts");
if($item_codes === FALSE){
	$error_msg .= "Error getting item codes updated since ".date('m-d-Y', $last_update_ts);
	debug_message($item_code->getLastError());
}

$account = new SI_Account();
$accounts = $account->retrieveSet("WHERE updated_ts > $last_update_ts");
if($accounts === FALSE){
	$error_msg .= "Error getting accounts updated since ".date('m-d-Y', $last_update_ts);
	debug_message($account->getLastError());
}

$invoice = new SI_Invoice();
$invoices = $invoice->retrieveSet("WHERE updated_ts > $last_update_ts");
if($invoices === FALSE){
	$error_msg .= "Error getting invoices updated since ".date('m-d-Y', $last_update_ts);
	debug_message($invoice->getLastError());
}

$payment = new SI_Payment();
$payments = $payment->retrieveSet("WHERE updated_ts > $last_update_ts");
if($payments === FALSE){
	$error_msg .= "Error getting invoices updated since ".date('m-d-Y', $last_update_ts);
	debug_message($payment->getLastError());
}

if(isset($_POST['save2'])){
	$output = '';
	if(isset($_POST['export_companies']) && is_array($_POST['export_companies'])){
		$company = new SI_Company();
		$company_output = $company->exportQB("WHERE id IN (".join(',', $_POST['export_companies']).")");
		if($company_output === FALSE){
			fatal_error("Error getting company export data!\n".$company->getLastError());
		}else{
			$output .= $company_output;
		}
	}
	
	if(isset($_POST['export_item_codes']) && is_array($_POST['export_item_codes'])){
		$item_code = new SI_ItemCode();
		$item_code_output = $item_code->exportQB("WHERE id IN (".join(',', $_POST['export_item_codes']).")");
		if($item_code_output === FALSE){
			fatal_error("Error getting item_code export data!\n".$item_code->getLastError());
		}else{
			$output .= $item_code_output;
		}
	}

	if(isset($_POST['export_accounts']) && is_array($_POST['export_accounts'])){
		$account = new SI_Account();
		$account_output = $account->exportQB("WHERE id IN (".join(',', $_POST['export_accounts']).")");
		if($account_output === FALSE){
			fatal_error("Error getting account export data!\n".$account->getLastError());
		}else{
			$output .= $account_output;
		}
	}

	if(isset($_POST['export_invoices']) && is_array($_POST['export_invoices'])){
		$invoice = new SI_Invoice();
		$invoice_output = $invoice->exportQB("WHERE id IN (".join(',', $_POST['export_invoices']).")");
		if($invoice_output === FALSE){
			fatal_error("Error getting invoice export data!\n".$invoice->getLastError());
		}else{
			$output .= $invoice_output;
		}
	}
	
	if(isset($_POST['export_payments']) && is_array($_POST['export_payments'])){
		$payment = new SI_Payment();
		$payment_output = $payment->exportQB("WHERE id IN (".join(',', $_POST['export_payments']).")");
		if($payment_output === FALSE){
			fatal_error("Error getting payment export data!\n".$payment->getLastError());
		}else{
			$output .= $payment_output;
		}
	}

	ob_end_clean();
	header('Content-type: application/iif');
	header('Content-Disposition: attachment; filename="si_export.iif"');
	print($output);
	exit();	
}

require('header.php'); ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="GET" NAME="qb_export">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Last Update Date</a><div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>Date:</td>
		<td>
			<input type="text" class="input_text" name="last_update_ts" id="last_update_ts" SIZE="10" value="<?= $last_update_ts > 0 ? date("n/j/y", $last_update_ts) :  "" ?>">&nbsp;
			<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('last_update_ts')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<div align="right"><input type="submit" name="save" value="Update"></div>
		</td>
	</tr>
</table>
</form>
</div></div>
<br><br>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" NAME="qb_export">
<div class="tableContainer" style="clear:both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Companies</a><div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">Name</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 1, 0, false)">Last Update</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 2, 0, false)">Export</a></th>
		<th>Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($companies); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><?= $companies[$i]->name ?></td>
		<td><?= $companies[$i]->updated_ts>0 ? date("n/j/y H:i", $companies[$i]->updated_ts) :  "" ?></td>
		<td><input type="checkbox" name="export_companies[]" value="<?= $companies[$i]->id ?>" CHECKED/></td>
		<td>&nbsp;
			<a href="company.php?mode=edit&id=<?= $companies[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Item Codes</a><div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">Code</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 1, 0, false)">Description</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 2, 0, false)">Last Update</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 3, 0, false)">Export</a></th>
		<th>Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($item_codes); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><?= $item_codes[$i]->code ?></td>
		<td><?= $item_codes[$i]->description ?></td>
		<td><?= $item_codes[$i]->updated_ts>0 ? date("n/j/y H:i", $item_codes[$i]->updated_ts) :  "" ?></td>
		<td><input type="checkbox" name="export_item_codes[]" value="<?= $item_codes[$i]->id ?>" CHECKED/></td>
		<td>&nbsp;
			<a href="item_code.php?mode=edit&id=<?= $item_codes[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Accounts</a><div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">Name</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 1, 0, false)">Description</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 2, 0, false)">Last Update</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 3, 0, false)">Export</a></th>
		<th>Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($accounts); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><?= $accounts[$i]->name ?></td>
		<td><?= $accounts[$i]->description ?></td>
		<td><?= $accounts[$i]->updated_ts>0 ? date("n/j/y H:i", $accounts[$i]->updated_ts) :  "" ?></td>
		<td><input type="checkbox" name="export_accounts[]" value="<?= $accounts[$i]->id ?>" CHECKED/></td>
		<td>&nbsp;
			<a href="account.php?mode=edit&id=<?= $accounts[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>

<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Invoices</a><div>
<table border="0" cellspacing="0" cellpadding="0">
<?    if(count($invoices) > 0){?>
	<tr>
		<th><a href="" onclick="return sortTable('bodyId1', 0, 1, false)">Number</a></th>
		<th><a href="" onclick="return sortTable('bodyId1', 1, 0, false)">Date</a></th>
		<th><a href="" onclick="return sortTable('bodyId1', 2, 0, false)">Company</a></th>
		<th><a href="" onclick="return sortTable('bodyId1', 3, 0, false)">Due Date</a></th>
		<th><a href="" onclick="return sortTable('bodyId1', 4, 0, false)">Amount Due</a></th>
		<th><a href="" onclick="return sortTable('bodyId1', 5, 0, false)">Last Update</a></th>
		<th><a href="" onclick="return sortTable('bodyId1', 6, 0, false)">Export</a></th>
		<th>Options</th>
	</tr>
	<tbody id="bodyId1">
<? for($i = 0; $i < count($invoices); $i++){
		$invoice_total += $invoices[$i]->getTotal();
		$cell_style = '';
		if($invoices[$i]->getDueDate() < time()){
			$cell_style = 'style="color: red; font-width: bold;"';
		}
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><?= $invoices[$i]->id ?></td>
		<td><?= date("n/j/y", $invoices[$i]->timestamp) ?></td>
		<td><a title="Company Detail Center" href="company_detail.php?id=<?= $invoices[$i]->company_id ?>"><?= $invoices[$i]->getName(); ?></a></td>
		<td <?= $cell_style ?>><?= date("n/j/y", $invoices[$i]->getDueDate()) ?></td>
		<td><?= SureInvoice::getCurrencySymbol().number_format($invoices[$i]->getAmountDue(), 2) ?></td>
		<td><?= $invoices[$i]->updated_ts>0 ? date("n/j/y H:i", $invoices[$i]->updated_ts) :  "" ?></td>
		<td><input type="checkbox" name="export_invoices[]" value="<?= $invoices[$i]->id ?>" CHECKED/></td>
		<td nowrap>
			<a href="invoice_view.php?id=<?= $invoices[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="View Invoice" border="0" /></a>
			<a href="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true"><img target="invoice_window" src="images/invoice_detail.png" width="15" height="16" title="View Invoice PDF" border="0" /></a>
			<a href="invoice_edit.php?id=<?= $invoices[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="View Invoice" border="0" /></a>
			<a href="invoice_email.php?id=<?= $invoices[$i]->id ?>"><img src="images/email.png" width="16" height="16" title="Email Invoice" border="0" /></a>
			<a href="payment.php?mode=add&company_id=<?= $invoices[$i]->company_id ?>"><img src="images/payment.png" border="0" width="16" height="16" title="Recieve Payment"></a>
			<a href="cc_payment.php?company_id=<?= $invoices[$i]->company_id ?>&invoice_id=<?= $invoices[$i]->id ?>"><img src="images/creditcards.png" border="0" width="16" height="16" title="CC Payment"></a>
		</td>
	</tr>
<? }?>
	</tbody>
	<tr>
		<td colspan="4" align="right">Total:</td>
		<td><?= SureInvoice::getCurrencySymbol().number_format($invoice_total, 2) ?></td>
		<td colspan="3">&nbsp;</td>
	</tr>
<?    }else{?>
	<tr>
		<td colspan="8">None</td>
	</tr>
<?    }?>
</table>
	</div>
</div>

<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Payments</a><div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">Name</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 1, 0, false)">Last Update</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 2, 0, false)">Amount</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 3, 0, false)">Export</a></th>
		<th>Options</td>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($payments); $i++){ 
	$company =& $payments[$i]->getCompany(); ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><?= $company->name ?></td>
		<td><?= $payments[$i]->amount ?></td>
		<td><?= $payments[$i]->updated_ts>0 ? date("n/j/y H:i", $payments[$i]->updated_ts) :  "" ?></td>
		<td><input type="checkbox" name="export_payments[]" value="<?= $payments[$i]->id ?>" CHECKED/></td>
		<td>
			<a href="payment.php?mode=edit&id=<?= $payments[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>

<table border="0" cellspacing="5" cellpadding="0" style="clear:both;">
	<tr>
		<td>Export Transactions</td>
	    <td><input type="submit" name="save2" value="Export" /></td>
	</tr>
</table>
</form>
<? require('footer.php') ?>