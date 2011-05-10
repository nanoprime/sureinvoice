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
require_once('includes/SI_PDFInvoice.php');

checkLogin();

if(intval($_REQUEST['id']) == 0){
  fatal_error("You must provide an invoice id to view!");
}

$invoice = new SI_Invoice();
if($invoice->get(intval($_REQUEST['id'])) === FALSE){
  $error_msg .= "Error retreiving invoice information!\n";
  debug_message($invoice->getLastError());
}

if(!$loggedin_user->hasRight('admin') && !$loggedin_user->hasRight('accounting')){
	if($loggedin_user->company_id != $invoice->company_id){
		fatal_error("You do not have rights to access this invoice!");	
	}	
}

if(!isset($_REQUEST['detail'])){
	$_REQUEST['detail'] = false;
}

$pdf_file = $invoice->getPDF($_REQUEST['detail']);
if($pdf_file === FALSE){
	fatal_error("Error creating PDF invoice: ".$invoice->getLastError());	
}

ob_end_clean();
header('Content-type: application/pdf');
header('Content-Disposition: attachment; filename="invoice.pdf"');
print($pdf_file);

?>