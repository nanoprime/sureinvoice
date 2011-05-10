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

checkLogin();
$project = new SI_Project();
if($loggedin_user->hasRight('accounting') && !empty($_REQUEST['id'])){
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

$invoice = new SI_Invoice();
$invoices = $invoice->getForCompany($company->id);
if($invoices === FALSE){
	$error_msg .= "Could not retrieve Invoice list!\n";
	debug_message($invoice->getLastError());
}

$title = $company->name." Invoices";
if($loggedin_user->hasRight('admin')){
	$update_url = "company.php?mode=edit&id=$id";
}else{
	$update_url = "company_profile.php?id=$id";
}
require('header.php'); ?>
<TABLE BORDER="0" CELLSPACING="4" CELLPADDING="0">
<TR>
	<TD VALIGN="top" ALIGN="LEFT" COLSPAN="2">
		<div class="tableContainer">
		<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Information</a><div>
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table" WIDTH="200">
			<TR>
				<TD CLASS="form_field_cell" COLSPAN="2">
					<B><?= $company->name ?></B><BR>
					<?= $company->address1.( !empty($company->address2) ? '<BR>'.$company->address2 : '' )?><BR>
					<?= $company->city.', '.$company->state.'   '.$company->zip ?>
					<DIV ALIGN="right"><A HREF="<?= $update_url ?>">Update</A></DIV>
				</TD>
			</TR>
		</TABLE>
		</div></div>
	</TD>
</TR>
<TR>
	<TD VALIGN="top" COLSPAN="2" ALIGN="LEFT">
		<div class="tableContainer">
		<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />All Invoices</a><div>
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table">
			<TR>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 0, 1, false)">Number</A></TD>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 2, 0, false)">Date</A></TD>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 3, 0, false)">Total</A></TD>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 4, 0, false)">Amount Due</A></TD>
				<TD CLASS="dg_header_cell">Options</TD>
			</TR>
			<TBODY ID="bodyId1">
<?	if(count($invoices) > 0){?>
<?	for($i=0; $i<count($invoices); $i++){
			$total += $invoices[$i]->getTotal(); 
			$amount_due_total +=  $invoices[$i]->getAmountDue(); ?>
			<TR>
				<TD CLASS="dg_data_cell_1"><A title="View Invoice" HREF="invoice_view.php?id=<?= $invoices[$i]->id ?>"><?= $invoices[$i]->id ?></A>&nbsp;</TD>
				<TD CLASS="dg_data_cell_1"><?= date("n/j/y", $invoices[$i]->timestamp) ?></TD>
				<TD ALIGN="right" CLASS="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($invoices[$i]->getTotal(),2) ?></TD>
				<TD ALIGN="right" CLASS="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($invoices[$i]->getAmountDue(),2) ?></TD>
				<TD CLASS="dg_data_cell_1">
					<? if($invoices[$i]->getAmountDue() > 0.00){ ?>
						<? if($loggedin_user->hasRight('admin')){ ?>
							<A CLASS="link1" HREF="payment.php?mode=add&company_id=<?= $invoices[$i]->company_id ?>"><img src="images/payment.png" border="0" width="16" height="16" title="Recieve Payment"></A>
						<? }else{ ?>
							<A HREF="cc_payment.php?invoice_id=<?= $invoices[$i]->id ?>"><img src="images/payment.png" border="0" width="16" height="16" title="Make Payment"></A>
						<? } ?>
					<? } ?>
					<A CLASS="link1" HREF="invoice_view.php?id=<?= $invoices[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="View Invoice" border="0" /></A>
					<A CLASS="link1" target="invoice_window" HREF="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=1"><img src="images/invoice_detail.png" width="16" height="16" title="View Detailed PDF Invoice" border="0" /></A>
					<a class="link1" target="invoice_window" href="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=0"><img src="images/invoice_simple.png" width="16" height="16" title="View Simple PDF Invoice" border="0" /></a>
					<? if($loggedin_user->hasRight('admin')){ ?>
						<A CLASS="link1" HREF="invoice_edit.php?id=<?= $invoices[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="View Invoice" border="0" /></A>
						<A CLASS="link1" HREF="invoice_email.php?id=<?= $invoices[$i]->id ?>&hide_url=true"><img src="images/email.png" width="16" height="16" title="Email Invoice" border="0" /></A>
					<? } ?>
				</TD>
			</TR>
<?	}?>
			<TR>
				<TD COLSPAN="2" CLASS="form_field_header_cell">Total:</TD>
				<TD CLASS="form_field_cell" ALIGN="right"><?= SureInvoice::getCurrencySymbol().number_format($total, 2) ?></TD>
				<TD CLASS="form_field_cell" ALIGN="right"><?= SureInvoice::getCurrencySymbol().number_format($amount_due_total, 2) ?></TD>
				<TD>&nbsp;</TD>
			</TR>
<?	}else{ // if invoices > 0 
?>
			<TR>
				<TD COLSPAN="4" CLASS="form_field_cell">None</TD>
			</TR>

<?	} // if invoices > 0  
?>
		</TABLE>
		</div></div>
	</TD>
</TR>
</TABLE>
<? require('footer.php') ?>
