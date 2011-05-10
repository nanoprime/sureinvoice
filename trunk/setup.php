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
require_once('includes/SI_User.php');

checkLogin("admin");

require_once('includes/SI_Config.php');
require_once('includes/SI_Account.php');

$config = new SI_Config();

if($_REQUEST['mode'] == 'delete' && !empty($_REQUEST['name'])){
	if($config->delete($_REQUEST['name']) === FALSE){
		$error_msg = "Error removing configuration parameter\n!";
		debug_message($config->getLastError());
	}
}

if($_POST['save']){
	if(is_array($_POST['params'])){
		foreach($_POST['params'] as $param_name => $param_value){
			if(!empty($param_name)){
				if(!SI_Config::canEdit($param_name)){
					continue;
				}
				
				$modified_config = new SI_Config();
				$modified_config->name = $param_name;
				$modified_config->value = $param_value;
				if($modified_config->update() === FALSE){
					$error_msg .= "Error updating configuration paramenter: $param_name\n";
					debug_message($modified_config->getLastError());
					break;
				}
			}
		}
	}
	if(empty($error_msg)){
		header("Location: ".getCurrentURL()."\r\n");
		exit();
	}
}

$title = "Configuration";
require('header.php') ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />SureInvoice Configuration</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th colspan="3">General</th>
	</tr>
	<tr>
		<td class="dg_data_cell_1">My Company</td>
		<td class="dg_data_cell_1">Select the company entry for your company. The details from this company will be used on invoices.</td>
		<td class="dg_data_cell_1">
		<select name="params[my_company_id]" <?= !SI_Config::canEdit('my_company_id') ? 'DISABLED' : '' ?>>
			<option value="0">Select company...</option>
			<?= SI_Company::getSelectTags($GLOBALS['CONFIG']['my_company_id']) ?>
		</select>	
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Currency Symbol</td>
		<td class="dg_data_cell_1">The currency symbol to use in the application</td>
		<td class="dg_data_cell_1">
		<select name="params[currency_symbol]" class="input_text" <?= !SI_Config::canEdit('currency_symbol') ? 'DISABLED' : '' ?>>
			<?= SI_Config::getCurrencySymbolSelectTags(); ?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Currency Code</td>
		<td class="dg_data_cell_1">The currency code to use in the application</td>
		<td class="dg_data_cell_1">
		<select name="params[currency_code]" class="input_text" <?= !SI_Config::canEdit('currency_code') ? 'DISABLED' : '' ?>>
			<?= SI_Config::getCurrencyCodeSelectTags(); ?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Default Tax Rate</td>
		<td class="dg_data_cell_1">The default tax rate as a percentage, e.g. 7.8 for 7.8%</td>
		<td class="dg_data_cell_1">
		<input type="text" name="params[tax_rate]" class="input_text" size="4" value="<?= $GLOBALS['CONFIG']['tax_rate'] ?>" <?= !SI_Config::canEdit('tax_rate') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Invoice Logo</td>
		<td class="dg_data_cell_1">A fully qualified path to the logo that will be used on invoices. This logo must be in JPEG format. If left blank the SureInvoice logo will be used on invoices.</td>
		<td class="dg_data_cell_1">
		<input type="text" name="params[invoice_logo]" class="input_text" size="35" value="<?= $GLOBALS['CONFIG']['invoice_logo'] ?>" <?= !SI_Config::canEdit('invoice_logo') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Default Payment Terms</td>
		<td class="dg_data_cell_1">Select the payment terms you want to be the default entry when creating invoices, you can always change the payment terms when you create the invoice.</td>
		<td class="dg_data_cell_1">
		<select name="params[invoice_terms]" class="input_text" <?= !SI_Config::canEdit('invoice_terms') ? 'READONLY' : '' ?>>
			<?= SI_Invoice::getTermsSelectTags(); ?>
		</select>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Invoice Note</td>
		<td class="dg_data_cell_1">A small bit of text that is added to the footer of every invoice.</td>
		<td class="dg_data_cell_1">
		<textarea size="5" cols="20" name="params[invoice_note]" class="input_text" size="35" <?= !SI_Config::canEdit('invoice_note') ? 'READONLY' : '' ?>><?= $GLOBALS['CONFIG']['invoice_note'] ?></textarea>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Attachments Directory</td>
		<td class="dg_data_cell_1">A fully qualified path to a directory that will be used to store attachments. The webserver must have write access to this directory. If you change this and already have attachments stored then you will need to manually move the files to the new directory</td>
		<td class="dg_data_cell_1">
		<input type="text" name="params[attachment_dir]" class="input_text" size="35" value="<?= $GLOBALS['CONFIG']['attachment_dir'] ?>" <?= !SI_Config::canEdit('attachment_dir') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<td colspan="3"><div align="right"><input type="submit" class="button" name="save" value="Save"></div></td>
	</tr>
	<tr>
		<th colspan="3">Advanced</th>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Debug Mode</td>
		<td class="dg_data_cell_1">Select Yes to run in debug mode. While running in debug extra information will be provided with any errors that will help developers track down bugs.</td>
		<td class="dg_data_cell_1">
		<input type="radio" name="params[debug]" class="input_text" value="1" <?= checked($GLOBALS['CONFIG']['debug'], "1") ?> <?= !SI_Config::canEdit('debug') ? 'DISABLED' : '' ?>> Yes &nbsp;
		<input type="radio" name="params[debug]" class="input_text" value="0" <?= checked($GLOBALS['CONFIG']['debug'], "0") ?> <?= !SI_Config::canEdit('debug') ? 'DISABLED' : '' ?>> No
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Error Log</td>
		<td class="dg_data_cell_1">A fully qualified path to the file that will be used to log error messages. The webserver must have write permission to this file or the containing directory if the file does not exist.</td>
		<td class="dg_data_cell_1">
		<input type="text" name="params[error_log]" class="input_text" size="35" value="<?= $GLOBALS['CONFIG']['error_log'] ?>" <?= !SI_Config::canEdit('error_log') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Installation Path</td>
		<td class="dg_data_cell_1">A fully qualified path to the directory where SureInvoice is installed. e.g. /var/www/sureinvoice</td>
		<td class="dg_data_cell_1">
		<input type="text" name="params[path]" class="input_text" size="35" value="<?= $GLOBALS['CONFIG']['path'] ?>" <?= !SI_Config::canEdit('path') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Base URL</td>
		<td class="dg_data_cell_1">The base URL that is used to access SureInvoice, e.g. http://www.sureinvoice.com/</td>
		<td class="dg_data_cell_1">
		<input type="text" name="params[url]" class="input_text" size="35" value="<?= $GLOBALS['CONFIG']['url'] ?>" <?= !SI_Config::canEdit('url') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Bundled PEAR</td>
		<td class="dg_data_cell_1">Select Yes to use the version of PEAR bundled with SureInvoice otherwise SureInvoice will look in your include path for the PEAR libraries</td>
		<td class="dg_data_cell_1">
		<input type="radio" name="params[bundled_pear]" class="input_text" value="1" <?= checked($GLOBALS['CONFIG']['bundled_pear'], "1") ?> <?= !SI_Config::canEdit('bundled_pear') ? 'DISABLED' : '' ?>> Yes &nbsp;
		<input type="radio" name="params[bundled_pear]" class="input_text" value="0" <?= checked($GLOBALS['CONFIG']['bundled_pear'], "0") ?> <?= !SI_Config::canEdit('bundled_pear') ? 'DISABLED' : '' ?>> No
		</td>
	</tr>
	<tr>
		<td colspan="3"><div align="right"><input type="submit" class="button" name="save" value="Save"></div></td>
	</tr>
	<tr>
		<th colspan="3">Credit Card Processor Settings</th>
	</tr>
	<tr>
		<td colspan="3" align="center"><a href="setup_cc.php">Configure Credit Card Processor</a></td>
	</tr>
	<tr>
		<th colspan="3">Quickbooks Integration</th>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Payment Account</td>
		<td class="dg_data_cell_1">This account will be used for all payments in the Quickbooks export</td>
		<td class="dg_data_cell_1">
		<select name="params[account_payment]" <?= !SI_Config::canEdit('account_payment') ? 'DISABLED' : '' ?>>
			<option value="0">Select account...</option>
			<?= SI_Account::getSelectTags($GLOBALS['CONFIG']['account_payment']) ?>
		</select>	
		</td>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Receivables Account</td>
		<td class="dg_data_cell_1">This account will be used for all receivables in the Quickbooks export</td>
		<td class="dg_data_cell_1">
		<select name="params[account_rec]" <?= !SI_Config::canEdit('account_rec') ? 'DISABLED' : '' ?>>
			<option value="0">Select account...</option>
			<?= SI_Account::getSelectTags($GLOBALS['CONFIG']['account_rec']) ?>
		</select>	
		</td>
	</tr>
	<tr>
		<td colspan="3"><div align="right"><input type="submit" class="button" name="save" value="Save"></div></td>
	</tr>
</table>
	</div>
</div>

<? require('footer.php') ?>