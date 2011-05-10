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
require_once('includes/SI_PaymentSchedule.php');

checkLogin('accounting');

$ps = new SI_PaymentSchedule();
$ps_items = $ps->getUpcoming();
if($ps_items === FALSE){
	$error_msg .= "Could not retreive upcoming scheduled billings!\n";
	debug_message($ps->getLastError());
}
$title = "Scheduled Billings";

require('header.php'); ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />All Scheduled Billings</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
<?    if(count($ps_items) > 0){?>
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 0, 1, false)">Company</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 1, 0, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 2, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 3, 0, false)">Due Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 4, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 5, 0, false)">Amount</a></th>
		<th class="dg_header_cell">Create Invoice</th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>				
	</tr>
	<tbody id="bodyId1">
<? for($i = 0; $i < count($ps_items); $i++){
		$ps_total += $ps_items[$i]->amount;
		$company =& $ps_items[$i]->getCompany();
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><a title="Company Detail Center" href="company_detail.php?id=<?= $company->id ?>"><?= $ps_items[$i]->getCompanyName() ?></a></td>
		<td class="dg_data_cell_1"><?= $ps_items[$i]->getProjectName() ?></td>
		<td class="dg_data_cell_1"><?= $ps_items[$i]->getTaskName() ?></td>
		<td class="dg_data_cell_1"><?= $ps_items[$i]->due_ts>0 ? date("n/j/y", $ps_items[$i]->due_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $ps_items[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($ps_items[$i]->amount, 2) ?></td>
		<td class="gridActions">&nbsp;
			<a class="link1" href="invoice.php?company_id=<?= $company->id ?>"><img src="images/invoice.gif" width="16" height="16" title="Create Invoice" border="0" /></a>&nbsp;
		</td>
		<td class="gridActions">&nbsp;
			<a class="link1" href="payment_schedule.php?mode=edit&id=<?= $ps_items[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Payment Schedule" border="0" /></a>&nbsp;
		</td>
		<td class="gridActions">&nbsp;
			<a class="link1" href="payment_schedule.php?mode=delete&id=<?= $ps_items[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Payment Schedule" border="0" /></a>&nbsp;
		</td>				
	</tr>
<? }?>
	</tbody>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">Total:</td>
		<th class="dg_data_cell_1"><strong><?= SureInvoice::getCurrencySymbol().number_format($ps_total, 2) ?></strong></th>
		<td class="form_header_cell">&nbsp;</td>
	</tr>
<?    }else{?>
	<tr>
		<td colspan="7" class="dg_data_cell_1">None</td>
	</tr>
<?    }?>
</table>
	</div>
</div>
<? require('footer.php') ?>
