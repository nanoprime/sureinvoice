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
require_once('includes/SI_CCProcessor.php');
checkLogin();
if($loggedin_user->hasRight('accounting') && !empty($_REQUEST['company_id'])){
	$company_id = $_REQUEST['company_id'];
}else{
	$company_id = $loggedin_user->company_id;
}

$company = new SI_Company();
if($company->get($company_id) === FALSE){
	fatal_error("Could not get company for id $company_id");	
}

$invoice = new SI_Invoice();
if($invoice->get($_REQUEST['invoice_id']) === FALSE){
	fatal_error("Could not get invoice {$_REQUEST['invoice_id']}\n");	
}

$payment = new SI_Payment();
$payment->company_id = $company_id;
$title = "Add Payment";

if($_POST['save']){
	$payment->amount = preg_replace('/[^0-9\.]/','', $_POST['amount']);
	if($payment->amount > $invoice->getTotal()){
		fatal_error("Amount can not be more than amount due on the invoice!\n");	
	}
	$payment->timestamp = time();
	$payment->type = 'CC';
	
	// Process the card
	$cc_processor = SI_CCProcessor::getInstance();
	$params = array(
		'id' => $company_id,
		'first_name' => $_POST['card_first_name'],
		'last_name' => $_POST['card_last_name'],
		'address' => $_POST['card_address'],
		'city' => $_POST['card_city'],
		'state' => $_POST['card_state'],
		'zip' => $_POST['card_zip'],
		'cc_number' => $_POST['card_number'],
		'cc_expiration_month' => $_POST['card_exp_month'],
		'cc_expiration_year' => $_POST['card_exp_year'],
		'cc_type' => $_POST['card_type'],
		'cc_cvv' => $_POST['card_cvv'],
		'amount' => $payment->amount
	);
	$cc_result = $cc_processor->process($params);
	if($cc_result === false){
		fatal_error('Error processing card: '.$cc_processor->getLastError());	
	}
	
	if($payment->add()){
		//TODO: Attach to oldest invoices
		if($payment->attachInvoices(array($_POST['invoice_id'] => $payment->amount)) === FALSE){
			$error_msg .= "Error applying payment to invoice!\n";
			debug_message($payment->getLastError());
		}

		// Add the company transaction
		$ct = new SI_CompanyTransaction();
		$ct->amount = -$payment->amount;
		$ct->company_id = $payment->company_id;
		$ct->description = "Credit card payment of ".$payment->amount." on ".date("n/j/y", $payment->timestamp);
		$ct->timestamp = time();
		if($ct->add() === FALSE){
			$error_msg .= "Error adding transaction to company account!\n";
			debug_message($ct->getLastError());
		}

		// Update the payment with the company transaction id and auth code
		$payment->trans_id = $ct->id;
		$payment->auth_code = $cc_processor->getAuthCode();
		if($payment->update() === FALSE){
			$error_msg .= "Error updating payment with company transaction id!\n";
			debug_message($payment->getLastError());
		}

		if(empty($error_msg)){
			if(isset($_POST['email_invoice']) && $_POST['email_invoice'] == 'Y'){
				$invoice = new SI_Invoice();
				if($invoice->get(intval($_POST['invoice_id'])) === FALSE){
					$error_msg .= "Error retreiving invoice!\n";
					debug_message($invoice->getLastError());
				}
				
				if($invoice->sendEmail('InvoiceEmail') === FALSE){
					$error_msg .= "Error sending invoice notification!\n";
					debug_message($invoice->getLastError());
				}
			}
			if(empty($error_msg)){
				goBack();
			}
		}
	}else{
		$error_msg .= "Error adding Payment!\n";
		debug_message($payment->getLastError());
	}
}

?>
<? require('header.php'); ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<FORM ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" NAME="chk">
<input type="hidden" name="invoice_id" value="<?= $_REQUEST['invoice_id'] ?>">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD COLSPAN="2" CLASS="form_header_cell"><?= $title ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Date:</TD>
	<TD CLASS="form_field_cell"><?= date('n/j/y'); ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Company:</TD>
	<TD CLASS="form_field_cell"><?= SI_Company::getName($payment->company_id) ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Invoice:</TD>
	<TD CLASS="form_field_cell"><?= $invoice->id ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Invoice Amount Due:</TD>
	<TD CLASS="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($invoice->getAmountDue(), 2) ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Amount:</TD>
	<TD CLASS="form_field_cell"><?= SureInvoice::getCurrencySymbol() ?><INPUT NAME="amount" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= number_format($invoice->getAmountDue(),2) ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">First Name:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_first_name" CLASS="input_text" SIZE="20" TYPE="text" VALUE="<?= $loggedin_user->first_name ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Last Name:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_last_name" CLASS="input_text" SIZE="20" TYPE="text" VALUE="<?= $loggedin_user->last_name ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Billing Address:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_address" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $company->address1 ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Billing City:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_city" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $company->city ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Billing State:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_state" CLASS="input_text" SIZE="5" TYPE="text" VALUE="<?= $company->state ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Billing Zip:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_zip" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $company->zip ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Card Type:</TD>
	<TD CLASS="form_field_cell">
		<select name="card_type">
<?		$types = explode(',', $GLOBALS['CONFIG']['cc_types']);
		foreach($types as $type){?>
			<option value="<?= trim($type) ?>"><?= trim($type) ?></option>
<?		}?>
		</select>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Card Number:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_number" CLASS="input_text" SIZE="25" TYPE="text"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Security Code:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="card_cvv" CLASS="input_text" SIZE="7" TYPE="text"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Expiration:</TD>
	<TD CLASS="form_field_cell">
		<select name="card_exp_month">
			<option value="01">01 (Jan)</option>
			<option value="02">02 (Feb)</option>
			<option value="03">03 (Mar)</option>
			<option value="04">04 (Apr)</option>
			<option value="05">05 (May)</option>
			<option value="06">06 (Jun)</option>
			<option value="07">07 (Jul)</option>
			<option value="08">08 (Aug)</option>
			<option value="09">09 (Sep)</option>
			<option value="10">10 (Oct)</option>
			<option value="11">11 (Nov)</option>
			<option value="12">12 (Dec)</option>
		</select>
		<select name="card_exp_year">
<?		$year = date('Y');
		for($i=$year; $i<$year+11; $i++ ){?>
			<option value="<?= $i ?>"><?= $i ?></option>
<?		}?>
		</select>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Email Invoice?</TD>
	<TD CLASS="form_field_cell">
		<INPUT NAME="email_invoice" CLASS="input_text" TYPE="radio" value="Y" checked>&nbsp;Yes&nbsp;
		<INPUT NAME="email_invoice" CLASS="input_text" TYPE="radio" value="N">&nbsp;No
	</TD>
</TR>
<TR>
	<TD COLSPAN="2" CLASS="form_field_cell">
		<DIV ALIGN="right"><INPUT TYPE="submit" CLASS="button" NAME="save" VALUE="Process Payment"></DIV>
	</TD>
</TR>	
</TABLE>
</FORM>
	</div>
</div>

<? require('footer.php'); ?>
