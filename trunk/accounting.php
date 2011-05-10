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
require_once('includes/SI_Task.php');
require_once('includes/SI_Invoice.php');
require_once('includes/SI_Check.php');

checkLogin('accounting');

$project = new SI_Project();

$company = new SI_Company();
$companies = $company->getCompanysWithUnbilledAmount();
if($companies === FALSE){
	$error_msg .= "Could not retrieve Outstanding Hours list!\n";
	debug_message($company->getLastError());
}

$user = new SI_User();
$users = $user->getUnpaidUsers();
if($users === FALSE){
	$error_msg .= "Could not retrieve Unpaid Users's list!\n";
	debug_message($user->getLastError());
}

$invoice = new SI_Invoice();
$invoices = $invoice->getOutstanding();
if($invoices === FALSE){
	$error_msg .= "Could not retrieve Outstanding Invoice list!\n";
	debug_message($invoice->getLastError());
}

$check = new SI_Check();
$checks = $check->retrieveSet("ORDER BY timestamp DESC LIMIT 5");
if($checks === FALSE){
	$error_msg .= "Could not retrieve Check list!\n";
	debug_message($check->getLastError());
}

$ps = new SI_PaymentSchedule();
$time = time() + 30 * (24 * (60 * 60));
$ps_items = $ps->getUpcoming($time);
if($ps_items === FALSE){
	$error_msg .= "Could not retreive upcoming scheduled billings!\n";
	debug_message($ps->getLastError());
}

$expense = new SI_Expense();
$expenses = $expense->getUnbilled();
if($expenses === FALSE){
	$error_msg .= "Could not retreive unbilled expenses!\n";
	debug_message($expense->getLastError());
}
$title = "Accounting";

require('header.php'); ?>
<script>
function reloadPage(selObj){
	var user_id = selObj.options[selObj.selectedIndex].value;
	window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter=<?= $_REQUEST['filter'] ?>&user_id="+user_id;
}
</script>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Companies With Outstanding Hours</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table" >
		<?    if(count($companies) > 0){?>
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 0, 1, false)">Name</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 1, 0, false)">Time</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 2, 0, false)">Amount</a></th>
				<th class="dg_header_cell">Options</th>
			</tr>
			<tbody id="bodyId3">
		<? for($i = 0; $i < count($companies); $i++){
				$company_time_total += $companies[$i]->time_spent;
				$company_amount_total += $companies[$i]->amount;
		?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td class="dg_data_cell_1"><a title="Company Detail Center" href="company_detail.php?id=<?= $companies[$i]->id ?>"><?= $companies[$i]->name ?></a></td>
				<td class="dg_data_cell_1"><?= formatLengthOfTime($companies[$i]->time_spent) ?></td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($companies[$i]->amount, 2) ?></td>
				<td class="dg_data_cell_1" nowrap>&nbsp;
					<a class="link1" href="activities_log.php?company_id=<?= $companies[$i]->id ?>&unbilled=y"><img src="images/properties.gif" width="16" height="16" title="View Detail" border="0" /></a>
					<a class="link1" href="invoice.php?company_id=<?= $companies[$i]->id ?>&unbilled=y"><img src="images/invoice.gif" width="16" height="16" title="Create Invoice" border="0" /></a>
				</td>
			</tr>
		<? }?>
			</tbody>
			<tr>
				<td class="form_header_cell" align="right">Total:</td>
				<td class="dg_data_cell_1"><?= formatLengthOfTime($company_time_total) ?></td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($company_amount_total, 2) ?></td>
				<td class="form_header_cell">&nbsp;</td>
			</tr>
<?    }else{?>
			<tr>
				<td colspan="4" class="dg_data_cell_1">None</td>
			</tr>
<?    }?>
		</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Unpaid Resources</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table" >
<?   	if(count($users) > 0){?>
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId4', 0, 1, false)">Name</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId4', 1, 0, false)">Hours</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId4', 2, 0, false)">Amount</a></th>
				<th class="dg_header_cell">Options</th>
			</tr>
			<tbody id="bodyId4">
		<? for($i = 0; $i < count($users); $i++){
				$user_amount_total += $users[$i]->amount;
		?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td class="dg_data_cell_1"><a title="Resource Detail Center" href="home_resource.php?id=<?= $users[$i]->id ?>"><?= $users[$i]->first_name.' '.$users[$i]->last_name ?></a></td>
				<td class="dg_data_cell_1"><?= formatLengthOfTime($users[$i]->getUpaidHourCount()) ?></td>
				<td class="dg_data_cell_1"><?= $users[$i]->amount ?></td>
				<td class="dg_data_cell_1" nowrap>&nbsp;
					<a class="link1" href="activities_log.php?user_id=<?= $users[$i]->id ?>&unpaid=y"><img src="images/properties.gif" width="16" height="16" title="View Detail" border="0" /></a>
					<a class="link1" href="check.php?user_id=<?= $users[$i]->id ?>"><img src="images/check.gif" width="16" height="16" title="Create Check" border="0" /></a>
				</td>
			</tr>
		<? }?>
		</tbody>
			<tr>
				<td colspan="2" class="form_header_cell" align="right">Total:</td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($user_amount_total, 2) ?></td>
				<td class="form_header_cell">&nbsp;</td>
			</tr>
<?    }else{?>
			<tr>
				<td colspan="3" class="dg_data_cell_1">None</td>
			</tr>
<?    }?>
		</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Upcoming Scheduled Billings</a><div>
	<div class="gridToolbar">
		<a href="payment_schedules.php">View All</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
<?    if(count($ps_items) > 0){?>
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 0, 1, false)">Company</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 1, 0, false)">Project</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 2, 0, false)">Task</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 3, 0, false)">Due Date</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 4, 0, false)">Description</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 5, 0, false)">Amount</a></th>
				<th class="dg_header_cell">Options</th>
			</tr>
			<tbody id="bodyId5">
		<? for($i = 0; $i < count($ps_items); $i++){
				$ps_total += $ps_items[$i]->amount;
				$company =& $ps_items[$i]->getCompany();
				$cell_style = '';
				if($ps_items[$i]->due_ts > 0 && $ps_items[$i]->due_ts < time()){
					$cell_style = 'style="color: red; font-width: bold;"';
				}
		?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td class="dg_data_cell_1"><a title="Company Detail Center" href="company_detail.php?id=<?= $company->id ?>"><?= $ps_items[$i]->getCompanyName() ?></a></td>
				<td class="dg_data_cell_1">
<?			if($ps_items[$i]->getProjectName()){?>
					<a href="project_details.php?id=<?= $ps_items[$i]->getProjectId() ?>"><?= $ps_items[$i]->getProjectName() ?></a>
<?			}?>
				</td>
				<td class="dg_data_cell_1">
<?			if($ps_items[$i]->getTaskName()){?>
					<a href="task_activities.php?task_id=<?= $ps_items[$i]->getTaskId() ?>"><?= $ps_items[$i]->getTaskName() ?></a>
<?			}?>
				</td>
				<td class="dg_data_cell_1" <?= $cell_style ?>><?= $ps_items[$i]->due_ts>0 ? date("n/j/y", $ps_items[$i]->due_ts) :  "" ?></td>
				<td class="dg_data_cell_1"><?= $ps_items[$i]->description ?></td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($ps_items[$i]->amount, 2) ?></td>
				<td class="dg_data_cell_1">&nbsp;
					<a class="link1" href="invoice.php?company_id=<?= $company->id ?>"><img src="images/invoice.gif" width="16" height="16" title="Create Invoice" border="0" /></a>&nbsp;
					<a class="link1" href="payment_schedule.php?mode=edit&id=<?= $ps_items[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Payment Schedule" border="0" /></a>&nbsp;
					<a class="link1" href="payment_schedule.php?mode=delete&id=<?= $ps_items[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Payment Schedule" border="0" /></a>&nbsp;
				</td>
			</tr>
		<? }?>
			</tbody>
			<tr>
				<td colspan="5" class="form_header_cell" align="right">Total:</td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($ps_total, 2) ?></td>
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
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Unbilled Expenses</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
<?    if(count($expenses) > 0){?>
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 0, 1, false)">Company</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 1, 0, false)">Project</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 4, 0, false)">Description</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId5', 5, 0, false)">Amount</a></th>
				<th class="dg_header_cell">Options</th>
			</tr>
			<tbody id="bodyId5">
		<? for($i = 0; $i < count($expenses); $i++){
				$expense_total += $expenses[$i]->price;
				$company =& $expenses[$i]->getCompany();
		?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td class="dg_data_cell_1"><a title="Company Detail Center" href="company_detail.php?id=<?= $company->id ?>"><?= $company->name ?></a></td>
				<td class="dg_data_cell_1"><?= $expenses[$i]->getProjectName() ?></td>
				<td class="dg_data_cell_1"><?= $expenses[$i]->description ?></td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($expenses[$i]->price, 2) ?></td>
				<td class="dg_data_cell_1">&nbsp;
					<a class="link1" href="invoice.php?company_id=<?= $company->id ?>"><img src="images/invoice.gif" width="16" height="16" title="Create Invoice" border="0" /></a>&nbsp;
					<a class="link1" href="expense.php?mode=edit&id=<?= $expenses[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Expense" border="0" /></a>&nbsp;
					<a class="link1" href="expense.php?mode=delete&id=<?= $expenses[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Expense" border="0" /></a>&nbsp;
				</td>
			</tr>
		<? }?>
			</tbody>
			<tr>
				<td colspan="3" class="form_header_cell" align="right">Total:</td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($expense_total, 2) ?></td>
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
<div class="tableContainer" STYLE="clear: both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Outstanding Invoices</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
<?    if(count($invoices) > 0){?>
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 1, false)">Number</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 1, 0, false)">Date</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Company</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 3, 0, false)">Due Date</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Amount</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 5, 0, false)">Emailed</a></th>
				<th class="dg_header_cell">Options</th>
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
				<td class="dg_data_cell_1"><?= $invoices[$i]->id ?></td>
				<td class="dg_data_cell_1"><?= date("n/j/y", $invoices[$i]->timestamp) ?></td>
				<td class="dg_data_cell_1"><a title="Company Detail Center" href="company_detail.php?id=<?= $invoices[$i]->company_id ?>"><?= $invoices[$i]->getName(); ?></a></td>
				<td class="dg_data_cell_1" <?= $cell_style ?>><?= date("n/j/y", $invoices[$i]->getDueDate()) ?></td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($invoices[$i]->getAmountDue(), 2) ?></td>
				<td class="dg_data_cell_1"><?= $invoices[$i]->sent_ts > 0 ? date("n/j/y", $invoices[$i]->sent_ts) : '<span style="color: red;">Not Sent</span>' ?></td>
				<td class="dg_data_cell_1" nowrap>&nbsp;
<?				if($invoices[$i]->isPastDue()){ ?>
					<a class="link1" href="invoice_email.php?id=<?= $invoices[$i]->id ?>&notification=InvoicePastDue&hide_url=true"><img src="images/overdue-invoice.png" width="16" height="16" title="Send Past Due Notification" border="0" /></a>
<?				} ?>
					<a class="link1" href="invoice_view.php?id=<?= $invoices[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="View Invoice" border="0" /></a>
					<a class="link1" target="invoice_window" href="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=1"><img src="images/invoice_detail.png" width="16" height="16" title="View Detailed Invoice PDF" border="0" /></a>
					<a class="link1" target="invoice_window" href="invoice_pdf.php?id=<?= $invoices[$i]->id ?>&hide_url=true&detail=0"><img src="images/invoice_simple.png" width="16" height="16" title="View Simple Invoice PDF" border="0" /></a>
					<a class="link1" href="invoice_edit.php?id=<?= $invoices[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Invoice" border="0" /></a>
					<a class="link1" href="invoice_email.php?id=<?= $invoices[$i]->id ?>&hide_url=true"><img src="images/email.png" width="16" height="16" title="Email Invoice" border="0" /></a>
					<a class="link1" href="payment.php?mode=add&company_id=<?= $invoices[$i]->company_id ?>"><img src="images/payment.png" border="0" width="16" height="16" title="Receive Payment"></a>
					<a class="link1" href="cc_payment.php?company_id=<?= $invoices[$i]->company_id ?>&invoice_id=<?= $invoices[$i]->id ?>"><img src="images/creditcards.png" border="0" width="16" height="16" title="CC Payment"></a>
				</td>
			</tr>
		<? }?>
			</tbody>
			<tr>
				<td colspan="4" class="form_header_cell" align="right">Total:</td>
				<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($invoice_total, 2) ?></td>
				<td colspan="2" class="form_header_cell">&nbsp;</td>
			</tr>
<?    }else{?>
			<tr>
				<td colspan="6" class="dg_data_cell_1">None</td>
			</tr>
<?    }?>
		</table>
	</div>
</div>
<? require('footer.php') ?>
