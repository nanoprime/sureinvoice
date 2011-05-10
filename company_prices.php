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

if(!isset($_REQUEST['company_id']) || $_REQUEST['company_id'] <= 0){
	fatal_error("a company_id must be provided to view company prices");	
}

require_once('includes/SI_Company.php');
require_once('includes/SI_CompanyPrice.php');

$company = new si_company();
if($company->get($_REQUEST['company_id']) === FALSE){
	$error_msg .= "Error getting company!\n";
	debug_message($company->getLastError());	
}

$company_price = new SI_CompanyPrice();
$prices = $company_price->retrieveSet('WHERE cp.company_id = '.$_REQUEST['company_id']);
if($prices === FALSE){
	$error_msg .= "Error getting company prices!\n";
	debug_message($company_price->getLastError());
}

$title = "Company Price Administration";

require('header.php') ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="200">
	<tr>
		<td class="form_field_cell" colspan="2">
			<b><?= $company->name ?></b><br>
			<?= $company->address1.( !empty($company->address2) ? '<br>'.$company->address2 : '' )?><br>
			<?= $company->city.', '.$company->state.'   '.$company->zip ?>
			<div align="right"><a href="company.php?mode=edit&id=<?= $company->id ?>">Update</a></div>
		</td>
	</tr>
</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Prices</a><div>
	<div class="gridToolbar">
		  <a style="background-image:url(images/new_invoice.png);" href="company_price.php?mode=add&company_id=<?= $company->id ?>">New Price</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Code</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Price</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">Tax Rate</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($prices); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $prices[$i]->code ?></td>
		<td class="dg_data_cell_1"><?= $prices[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().$prices[$i]->price ?></td>
		<td class="dg_data_cell_1">
<?		if($prices[$i]->taxable == 'Y'){ ?>		
		<?= $prices[$i]->tax_rate ?>%
<?		}else{?>
			N/A
<?		}?>
		</td>
		<td class="dg_data_cell_1">&nbsp;
			<a class="link1" href="company_price.php?mode=edit&company_id=<?= $prices[$i]->company_id ?>&item_code_id=<?= $prices[$i]->item_code_id ?>"><img src="images/goldbar.png" width="16" height="16" title="Edit" border="0" /></a>&nbsp;|&nbsp;
			<a class="link1" href="company_price.php?mode=delete&company_id=<?= $prices[$i]->company_id ?>&item_code_id=<?= $prices[$i]->item_code_id ?>"><img src="images/goldbar_delete.png" width="16" height="16" title="Delete" border="0" /></a>&nbsp;
		</td>
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>

<? require('footer.php') ?>
