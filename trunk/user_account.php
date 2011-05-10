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

$trans_per_page = 30;
if(!isset($_REQUEST['page'])) $_REQUEST['page'] = 0;
$url = $_SERVER['PHP_SELF'].'?';

checkLogin();
$project = new SI_Project();

if($loggedin_user->hasRight('admin') && isset($_REQUEST['id'])){
	$user_id = $_REQUEST['id'];
	$url .= 'id='.$_REQUEST['id'].'&';
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

$transactions = $user->getTransactions(NULL, $trans_per_page, $_REQUEST['page'] * $trans_per_page);
if($transactions === FALSE){
	$error_msg .= "Error getting your transactions!";
	debug_message($user->getLastError());
}

$total_transactions = $user->getTransactionCount();
if($total_transactions === FALSE){
	$error_msg .= "Error getting transaction count!";
	debug_message($user->getLastError());
}

$checks = $user->getChecks(NULL, 5);
if($checks === FALSE){
	$error_msg .= "Error getting your last 5 checks!";
	debug_message($user->getLastError());
}

$total_pages = ceil($total_transactions / $trans_per_page);

$title = "Home";

require('header.php'); 
?>
<SCRIPT>
function reloadPage(selObj){
	var user_id = selObj.options[selObj.selectedIndex].value;
	window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter=<?=$_REQUEST['filter'] ?>&user_id="+user_id;
}
</SCRIPT>
<div class="tableContainer">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Account Detail</a><div>
<TABLE BORDER="0" CELLSPACING="4" CELLPADDING="0">
<TR>
	<TD VALIGN="top" ALIGN="LEFT">
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table" WIDTH="200">
			<TR>
				<th COLSPAN="2" CLASS="form_header_cell">Your Information</th>
			</TR>
			<TR>
				<TD CLASS="form_field_cell" COLSPAN="2">
					<B><?= $user->first_name.' '.$user->last_name ?></B><BR>
					<?= $user->address1.( !empty($user->address2) ? '<BR>'.$user->address2 : '' )?><BR>
					<?= $user->city.', '.$user->state.'   '.$user->zip ?><BR>
					<B>Account Balance:</B> <?= SureInvoice::getCurrencySymbol().number_format($balance, 2) ?>
					<DIV ALIGN="right"><A HREF="profile.php?id=<?= $user->id ?>">Update</A></DIV>
				</TD>
			</TR>
		</TABLE>
	</TD>
	<TD VALIGN="top" ALIGN="LEFT">
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table">
			<TR>
				<th COLSPAN="2" CLASS="form_header_cell">Most Recent Checks</th>
			</TR>
			<TR>
				<TD CLASS="form_field_cell" COLSPAN="2">
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0">
<?	for($i=0; $i<count($checks); $i++){ ?>
					<TR>
						<TD><?= date("n/j/y", $checks[$i]->timestamp) ?>&nbsp;<?= $checks[$i]->getDescription() ?></TD>
						<TD ALIGN="right">&nbsp;&nbsp;<?= $checks[$i]->amount ?></TD>
					</TR>
<?	}?>
					</TABLE><BR>
				</TD>
			</TR>
		</TABLE>
	</TD>
</TR>
<TR>
	<TD VALIGN="top" COLSPAN="2" ALIGN="LEFT">
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table" WIDTH="100%">
			<TR>
				<TD COLSPAN="5" CLASS="form_header_cell">
					<table width="100%">
					<tr>
						<th>
							Account Transactions
							<A CLASS="link1" HREF="modify_account.php?mode=user&id=<?= $user->id ?>">
							<img src="images/new.gif" width="16" height="16" title="New Transaction" border="0" /></A>
						</th>
						<th align="right">
						<?	if($_REQUEST['page'] > 0){ ?>
							<A HREF="<?= $url."page=".($_REQUEST['page'] - 1) ?>">&lt;</A>
						<?	} ?>
							Page <?= ($_REQUEST['page'] + 1) ?> of <?= $total_pages ?>
						<?	if($_REQUEST['page'] < ($total_pages - 1)){ ?>
							<A HREF="<?= $url."page=".($_REQUEST['page'] + 1) ?>">&gt;</A>
						<?	} ?>
						</th>
					</tr>
					</table>
				</TD>
			</TR>
			<TR>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 0, 1, false)">Date</A></TD>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 1, 0, false)">Description</A></TD>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 2, 0, false)">Type</A></TD>
				<TD CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId1', 3, 0, false)">Amount</A></TD>
			</TR>
			<TBODY ID="bodyId1">
		<? for($i = 0; $i < count($transactions); $i++){ ?>
			<TR onMouseOver="this.style.backgroundColor ='#CCCCCC'" onMouseOut="this.style.backgroundColor ='#FFFFFF'">
				<TD CLASS="dg_data_cell_1"><?= date("n/j/y", $transactions[$i]->timestamp) ?></TD>
				<TD CLASS="dg_data_cell_1"><?= $transactions[$i]->description ?></TD>
				<TD CLASS="dg_data_cell_1"><?= $transactions[$i]->getType() ?></TD>
				<TD CLASS="dg_data_cell_1">
				<?	if($transactions[$i]->amount < 0){ ?>
					<SPAN STYLE="color: red"><?= SureInvoice::getCurrencySymbol().number_format($transactions[$i]->amount, 2) ?></SPAN>
				<?	}else{ ?>
					<?= SureInvoice::getCurrencySymbol().number_format($transactions[$i]->amount, 2) ?>					
				<?	} ?>
					
				</TD>
			</TR>
		<? }?>
		</TBODY>
		</TABLE>
	</TD>
</TR>
</TABLE>
</div>
</div>

<? require('footer.php') ?>
