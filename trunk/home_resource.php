<?php
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

checkLogin();
$project = new SI_Project();

if($loggedin_user->hasRight('admin') && isset($_REQUEST['id'])){
	$user_id = $_REQUEST['id'];
}else{
	$user_id = $loggedin_user->id;
}

$user = new SI_User();
if($user->get($user_id) === FALSE){
	$error_msg .= "Error getting user information!\n";
	debug_message($user->getLastError());
}

$balance = $user->getBalance();
if($balance === FALSE){
	$error_msg .= "Error getting your outstanding balance!";
	debug_message($loggedin_user->getLastError());
}

$transactions = $user->getTransactions(NULL, 5);
if($transactions === FALSE){
	$error_msg .= "Error getting your last 5 transactions!";
	debug_message($user->getLastError());
}

$task = new SI_Task();
$tasks = $task->getUpcoming($user->id, 0, 0, 10);
if($tasks === FALSE){
	$error_msg .= "Could not retrieve Upcoming Tasks!\n";
	debug_message($task->getLastError());
}

$title = "Home";

require('header.php'); 
?>
<script>
function reloadPage(selObj){
	var user_id = selObj.options[selObj.selectedIndex].value;
	window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter=<?=$_REQUEST['filter'] ?>&user_id="+user_id;
}
</script>
<div class="tableContainer">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Resource Detail</a><div>
		<table border="0" cellspacing="4" cellpadding="0">
		<tr>
			<td valign="top" align="LEFT">
				<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="200">
					<tr>
						<th colspan="2" class="form_header_cell">Your Information</th>
					</tr>
					<tr>
						<td class="form_field_cell" colspan="2">
							<b><?= $user->first_name.' '.$user->last_name ?></b><br>
							<?= $user->address1.( !empty($user->address2) ? '<br>'.$user->address2 : '' )?><br>
							<?= $user->city.', '.$user->state.'   '.$user->zip ?>
							<div align="right"><a href="profile.php?id=<?= $user->id ?>">Update</a></div>
						</td>
					</tr>
				</table>
			</td>
			<td valign="top" align="LEFT">
				<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
					<tr>
						<th colspan="2" class="form_header_cell">Recent Account Activity</th>
					</tr>
					<tr>
						<td class="form_field_cell" colspan="2">
							<table border="0" cellspacing="0" cellpadding="0">
		<?	for($i=0; $i<count($transactions); $i++){ ?>
							<tr>
								<td><?= $transactions[$i]->description ?>&nbsp;</td>
								<td align="right">&nbsp;&nbsp;<?= SureInvoice::getCurrencySymbol().$transactions[$i]->amount ?></td>
							</tr>
		<?	}?>
							</table><br>
							<div align="left"><a href="user_account.php?id=<?= $user_id ?>">View All</a></div>
						</td>
					</tr>
					<tr>
						<td class="form_field_header_cell">Outstanding Balance:</td>
						<td class="form_field_cell" align="right"><?= SureInvoice::getCurrencySymbol().number_format($balance, 2) ?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td valign="top" colspan="2" align="LEFT">
				<div class="tableContainer">
					<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Upcoming Tasks</a>
					<div>
					<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="100%">
						<tr>
							<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 1, false)">Project</a></th>
							<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 1, 0, false)">Task</a></th>
							<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Status</a></th>
							<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 3, 0, false)">Due Date</a></th>
							<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Priority</a></th>
						</tr>
						<tbody id="bodyId1">
					<? for($i = 0; $i < count($tasks); $i++){ ?>
						<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
							<td class="dg_data_cell_1"><a title="Project Details" href="project_details.php?id=<?= $tasks[$i]->project_id ?>"><?= $tasks[$i]->project ?></a></td>
							<td class="dg_data_cell_1"><a title="Task Activities" href="task_activities.php?task_id=<?= $tasks[$i]->id ?>"><?= $tasks[$i]->name ?></a></td>
							<td class="dg_data_cell_1"><?= $tasks[$i]->status ?></td>
							<td class="dg_data_cell_1"><?=  $tasks[$i]->due_ts>0 ? date("n/j/y", $tasks[$i]->due_ts) : "None" ?></td>
							<td class="dg_data_cell_1"><?= $tasks[$i]->priority ?></td>
						</tr>
					<? }?>
						</tbody>
					</table>
					</div>
				</div>
			</td>
		</tr>
		</table>
	</div>
</div>
<? require('footer.php') ?>
