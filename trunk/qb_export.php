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
require_once('includes/SI_Payment.php');
require_once('includes/SI_Invoice.php');

checkLogin();

if(!isset($_REQUEST['mode']) || empty($_REQUEST['mode'])){
	fatal_error("Export mode must be specified!");
}elseif(strtolower($_REQUEST['mode']) == 'company'){
	$company = new SI_Company();
	$output = $company->exportQB();
	if($output === FALSE){
		fatal_error("Error getting company export data!\n".$company->getLastError());
	}
}elseif(strtolower($_REQUEST['mode']) == 'item_code'){
	$code = new SI_ItemCode();
	$output = $code->exportQB();
	if($output === FALSE){
		fatal_error("Error getting item code export data!\n".$code->getLastError());
	}
}elseif(strtolower($_REQUEST['mode']) == 'account'){
	$account = new SI_Account();
	$output = $account->exportQB();
	if($output === FALSE){
		fatal_error("Error getting account export data!\n".$account->getLastError());
	}
}elseif(strtolower($_REQUEST['mode']) == 'payment'){
	$payment = new SI_Payment();
	$output = $payment->exportQB();
	if($output === FALSE){
		fatal_error("Error getting payment export data!\n".$payment->getLastError());
	}
}elseif(strtolower($_REQUEST['mode']) == 'invoice'){
	$invoice = new SI_Invoice();
	$output = $invoice->exportQB();
	if($output === FALSE){
		fatal_error("Error getting invoice export data!\n".$invoice->getLastError());
	}
}else{
	fatal_error("Unknown export mode!");
}

ob_end_clean();
header('Content-type: application/iif');
header('Content-Disposition: attachment; filename="'.$_REQUEST[mode].'.iif"');
print($output);