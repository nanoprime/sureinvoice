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
require_once('includes/SI_Payment.php');
require_once('includes/SI_CompanyTransaction.php');
checkLogin('accounting');

$title = '';
$payment = new SI_Payment();
if(isset($_REQUEST['company_id'])) $payment->company_id = $_REQUEST['company_id'];

if($_REQUEST['mode'] == 'add'){
	$title = "Add Payment";

	$invoice = new SI_Invoice();
	$invoices = $invoice->getOutstanding($payment->company_id);
	if($invoices === FALSE){
		$error_msg .= "Could not retrieve Outstanding Invoice list!\n";
		debug_message($invoice->getLastError());
	}


	if($_POST['save']){
		$_POST['timestamp'] = getTSFromInput($_POST['timestamp']);
		$_POST['amount'] = preg_replace('/[^0-9\.]/','', $_POST['amount']);
		$payment->updateFromAssocArray($_POST);
		if($payment->add()){
			
			// Prepare the invoice ids and amounts
			$invoice_amounts = array();
			if(is_array($_POST['invoice_ids'])){
				foreach($_POST['invoice_ids'] as $index => $id){
					if(isset($_POST['invoice_amounts'][$index])){
						$invoice_amounts[$id] = preg_replace('/[^0-9\.]/','', $_POST['invoice_amounts'][$index]);
					}else{
						$invoice_amounts[$id] = 0.00;
					}
				}	
			}
			
			if($payment->attachInvoices($invoice_amounts) === FALSE){
				$error_msg .= "Error attaching invoices to payment!\n";
				debug_message($payment->getLastError());
			}

			// Add the company transaction
			$ct = new SI_CompanyTransaction();
			$ct->amount = -$payment->amount;
			$ct->company_id = $payment->company_id;
			if($payment->type == 'CASH'){
				$ct->description = "Cash payment of ".$payment->amount." on ".date("n/j/y", $payment->timestamp);
			}elseif($payment->type == 'CHECK'){
				$ct->description = "Check number ".$payment->check_no." for ".$payment->amount." on ".date("n/j/y", $payment->timestamp);
			}elseif($payment->type == 'CC'){
				$ct->description = "Credit card payment of ".$payment->amount." on ".date("n/j/y", $payment->timestamp);
			}else{
				$ct->description = "Payment of ".$payment->amount." on ".date("n/j/y", $payment->timestamp);
			}
			$ct->timestamp = time();
			if($ct->add() === FALSE){
				$error_msg .= "Error adding transaction to company account!\n";
				debug_message($ct->getLastError());
			}

			// Update the payment with the company transaction id
			$payment->trans_id = $ct->id;
			if($payment->update() === FALSE){
				$error_msg .= "Error updating payment with company transaction id!\n";
				debug_message($payment->getLastError());
			}

			if(empty($error_msg)){
				goBack();
			}
		}else{
			$error_msg .= "Error adding Payment!\n";
			debug_message($payment->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Payment";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$payment->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving payment information!\n";
			debug_message($payment->getLastError());
		}
	}

	if($_POST['save']){
		$payment->updateFromAssocArray($_POST);
		if($payment->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Payment!\n";
			debug_message($payment->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Payment";

	if(!$payment->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving payment information!\n";
		debug_message($payment->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($payment->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Payment!\n";
			}
		}else{
			goBack();
		}
	}
}else{
	$title = "Invalid Mode";
	$error_msg .= "Error: Invalid mode!\n";
}

?>
<? require('header.php'); ?>
<SCRIPT>
var invoice_count = <?= count($invoices) ?>;

function SelectAllAmounts() {
	for (var i = 0; i < invoice_count; i++) {
		var oAmount = document.getElementById('invoice_amount_' + i);
		var oAmountDue = document.getElementById('invoice_due_' + i);
		var oId = document.getElementById('invoice_id_' + i);
		if(!oId.checked){
			oId.checked = true;
			oAmount.value = oAmountDue.value;
		}else{
			oId.checked = false;
			oAmount.value = '';
		}
	}
	CalculateTotal();
}

function CalculateTotal() {
	var formTotal = 0.00;
	for (var i = 0; i < invoice_count; i++) {
		var oAmount = document.getElementById('invoice_amount_' + i);
		var oId = document.getElementById('invoice_id_' + i);
		if(oId.checked){
			var tempAmount = parseFloat(oAmount.value);
			if(!isNaN(tempAmount)){
				formTotal += tempAmount;
			}
		}
	}
	oTotalAmount = document.getElementById('amount');
	oTotalAmount.value = formTotal;
}

function ApplyDefault(index){
	var oId = document.getElementById('invoice_id_' + index);
	var oAmount = document.getElementById('invoice_amount_' + index);
	var oAmountDue = document.getElementById('invoice_due_' + index);

	if(oId.checked){
		oAmount.value = oAmountDue.value;
	}else{
		oAmount.value = '';
	}
	CalculateTotal();
}
</SCRIPT>
<FORM ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" NAME="invoices">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<INPUT NAME="id" TYPE="hidden" VALUE="<?= $_REQUEST['id'] ?>">
<INPUT NAME="company_id" TYPE="hidden" VALUE="<?= $_REQUEST['company_id'] ?>">
<INPUT NAME="mode" TYPE="hidden" VALUE="<?= $_REQUEST['mode'] ?>">
<?if($_REQUEST['mode'] == "delete"){?>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD CLASS="form_field_cell">
		<BR>Are you sure you want to delete this payment?<BR><BR>
		<SPAN CLASS="error">CAUTION:</SPAN>This action is irreversible.<BR><BR>
	</TD>
</TR>
<TR>
	<TD CLASS="form_footer_cell">
		<DIV ALIGN="center">
			<INPUT TYPE="submit" CLASS="button" NAME="confirm" VALUE="Yes">&nbsp;&nbsp;
			<INPUT TYPE="submit" CLASS="button" NAME="confirm" VALUE="No">
		</DIV>
	</TD>
</TR>
<?}else{?>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD CLASS="form_field_header_cell">Date:</TD>
	<TD CLASS="form_field_cell">
		<input type="text" class="input_text" name="timestamp" id="timestamp" SIZE="10" value="<?= $payment->timestamp > 0 ? date("n/j/Y", $payment->timestamp) : '' ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('timestamp')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Company:</TD>
	<TD CLASS="form_field_cell"><?= SI_Company::getName($payment->company_id) ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Payment Type:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="type" CLASS="input_text">
			<?= SI_Payment::getTypeSelectTags($payment->type) ?>
		</SELECT>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Amount:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="amount" ID="amount" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $payment->amount ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Auth Code:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="auth_code" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $payment->auth_code ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Check No.:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="check_no" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $payment->check_no ?>"></TD>
</TR>
<TR>
	<TD COLSPAN="2" CLASS="form_field_cell">
		<DIV ALIGN="right"><INPUT TYPE="submit" CLASS="button" NAME="save" VALUE="Save"></DIV>
	</TD>
</TR>	
</TABLE>
</div>
</div>
<BR style="clear: both;">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Outstanding Invoices</a><div>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table">
<?    if(count($invoices) > 0){?>
<TR>
	<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 0, 1, false)">Number</A></TD>
	<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 1, 0, false)">Date</A></TD>
	<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 2, 0, false)">Company</A></TD>
	<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 3, 0, false)">Unpaid Amount</A></TD>
	<TD CLASS="dg_header_cell">Apply?</TD>
	<TD CLASS="dg_header_cell">Amount</TD>
	<TD CLASS="dg_header_cell">Options</TD>
</TR>
<TBODY ID="bodyId1">
		<? for($i = 0; $i < count($invoices); $i++){
				$invoice_total += $invoices[$i]->getAmountDue();
		?>
<TR onMouseOver="this.style.backgroundColor ='#CCCCCC'" onMouseOut="this.style.backgroundColor ='#FFFFFF'">
	<TD CLASS="dg_data_cell_1"><?= $invoices[$i]->id ?></TD>
	<TD CLASS="dg_data_cell_1"><?= date("n/j/y", $invoices[$i]->timestamp) ?></TD>
	<TD CLASS="dg_data_cell_1"><A HREF="company_detail.php?id=<?= $invoices[$i]->company_id ?>"><?= $invoices[$i]->getName(); ?></A></TD>
	<TD ALIGN="right" CLASS="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($invoices[$i]->getAmountDue(), 2) ?></TD>
	<TD CLASS="dg_data_cell_1">
		<INPUT TYPE="checkbox" ID="invoice_id_<?= $i ?>" NAME="invoice_ids[<?= $i ?>]" VALUE="<?= $invoices[$i]->id ?>"  onChange="ApplyDefault(<?= $i ?>)" <?= $payment->onInvoice($invoices[$i]->id) ? 'CHECKED' : '' ?>>
		<INPUT TYPE="hidden" ID="invoice_due_<?= $i ?>" NAME="invoice_due[<?= $i ?>]" VALUE="<?= $invoices[$i]->getAmountDue() ?>">
	</TD>
	<TD CLASS="dg_data_cell_1"><INPUT TYPE="text" ID="invoice_amount_<?= $i ?>" NAME="invoice_amounts[<?= $i ?>]" SIZE="6" onChange="CalculateTotal()" ></TD>
	<TD CLASS="dg_data_cell_1">&nbsp;
		<A CLASS="link1" HREF="invoice_view.php?id=<?= $invoices[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="View Invoice" border="0" /></A>
		<A CLASS="link1" HREF="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=1"><img target="invoice_window" src="images/invoice_detail.png" width="16" height="16" title="View Detailed Invoice PDF" border="0" /></A>
		<A CLASS="link1" HREF="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=0"><img target="invoice_window" src="images/invoice_simple.png" width="16" height="16" title="View Simple Invoice PDF" border="0" /></A>
	</TD>
</TR>
		<? }?>
</TBODY>
<TR>
	<TD COLSPAN="3" CLASS="form_header_cell" ALIGN="right">Total:</TD>
	<TD ALIGN="right" CLASS="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($invoice_total, 2) ?></TD>
	<TD CLASS="form_header_cell"><INPUT TYPE="checkbox" NAME="select_all" onClick='SelectAllAmounts()'></TD>
	<TD COLSPAN="2" CLASS="form_header_cell">&nbsp;</TD>
</TR>
<?    }else{?>
<TR>
	<TD COLSPAN="7" CLASS="dg_data_cell_1">None</TD>
</TR>
<?    }?>
</TABLE>
</div>
</div>
<?} //if mode==delete?>
</FORM>
<? require('footer.php'); ?>