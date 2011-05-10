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
require_once('includes/SI_Check.php');
require_once('includes/SI_User.php');
require_once('includes/SI_UserTransaction.php');
require_once('includes/SI_TaskActivity.php');

checkLogin();

$activity = new SI_TaskActivity();
$check = new SI_Check();

$user = new SI_User();
if($user->get($_REQUEST['user_id']) === FALSE){
	$error_msg .= "Could not retrieve information for User ID ".$_REQUEST['user_id']."!\n";
	debug_message($user->getLastError());
}

$activities = $user->getActivities($user->id);
if($activities === FALSE){
	$error_msg .= "Could not retrieve Activity List for User ID ".$user->id."!\n";
	debug_message($user->getLastError());
}

$commissions = $user->getCommissions($user->id);
if($commissions === FALSE){
	$error_msg .= "Could not retrieve Commission List for User ID ".$user->id."!\n";
	debug_message($user->getLastError());
}

if ($_POST['save']){
	$cost_trans_ids = $_POST['cost_trans_ids'];
	$comm_trans_ids = $_POST['comm_trans_ids'];
	$total_amount = 0.00;

	if(count($cost_trans_ids) == 0 && count($comm_trans_ids) == 0){
		$error_msg .= "You must select at least one line item!\n";
	}

	foreach($activities as $ta){
		if(in_array($ta->cost_trans_id, $cost_trans_ids)){
			$total_amount += $ta->cost;
		}
	}

	foreach($commissions as $com){
		if(in_array($com->com_trans_id, $comm_trans_ids)){
			$total_amount += $com->com_amount;
		}
	}

	if(empty($error_msg)){
		$check->updateFromAssocArray($_POST);
		$check->timestamp = time();
		$check->amount = $total_amount;
		if($check->add() !== FALSE){
			if($check->attachCostTransactions($cost_trans_ids) === FALSE){
				$error_msg .= "Error adding cost transactions to check!";
				debug_message($check->getLastError());
			}

			if($check->attachCommTransactions($comm_trans_ids) === FALSE){
				$error_msg .= "Error adding commission transactions to check!";
				debug_message($check->getLastError());
			}

			// Add user transaction
			if($check->type == 'CHECK'){
				$desc = "Check #".$check->number;
			}elseif($check->type == "EFT"){
				$desc = "Electronic Funds Transfer";
			}
			$ut = new SI_UserTransaction();
			$ut->amount = -$total_amount;
			$ut->description = $desc;
			$ut->timestamp = $check->timestamp;
			$ut->user_id = $user->id;
			if($ut->add() === FALSE){
				$error_msg .= "Error adding user transaction";
				debug_message($ut->getLastError());
			}else{
				$check->trans_id = $ut->id;
				if($check->update() === FALSE){
					$error_msg .= "Error updating transaction id for check";
					debug_message($check->getLastError());
				}
			}
			
			if(empty($error_msg)){
				goBack();
			}
		}else{
			$error_msg .= "Error adding Check!\n";
			debug_message($check->getLastError());
		}
	}
}

$url = $_SERVER['PHP_SELF']."?user_id=".$_REQUEST['user_id']."&";
$_REQUEST['detail'] = strtolower(substr($_REQUEST['detail'],0,1)) == "y" ? TRUE : FALSE;
$title = "Create Check";
$total_time = 0;

require('header.php') ?>
<FORM ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" NAME="chk">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Create Check</a><div>
<INPUT TYPE="hidden" NAME="user_id" VALUE="<?= $_REQUEST['user_id'] ?>">
<INPUT TYPE="hidden" NAME="detail" VALUE="<?= $_REQUEST['detail'] ?>">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TH COLSPAN="2" CLASS="form_header_cell">User Information</TH>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Name:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="name" CLASS="input_text" SIZE="25" TYPE="text" VALUE="<?= $user->first_name.' '.$user->last_name ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Address Line 1:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="address1" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $user->address1 ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Address Line 2:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="address2" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $user->address2 ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">City:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="city" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $user->city ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">State:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="state" CLASS="input_text" SIZE="5" TYPE="text" VALUE="<?= $user->state ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Zip:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="zip" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $user->zip ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Type:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="type" CLASS="input_text">
			<?= SI_Check::getTypeSelectTags() ?>
		</SELECT>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Check Number:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="number" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $check->number ?>"></TD>
</TR>
</TABLE>
</div>
</div>
<BR>
<? if(count($activities) > 0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Unpaid Time</a><div>
<div class="gridToolbar">
	<A CLASS="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $url."detail=y" ?>" style="background-image:url(images/plus.png);">Show Details</A>
	<A CLASS="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $url."detail=n" ?>" style="background-image:url(images/minus.png);">Hide Details</A>
</div>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table">
	<TR>
		<TH COLSPAN="8" CLASS="form_header_cell">Select Time</TH>
	</TR>
	<TR>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 0, 1, false)">Project</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 1, 0, false)">Task</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 2, 0, false)">Start</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 3, 0, false)">End</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 4, 0, false)">Time Spent</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 5, 0, false)">Cost</A></TH>
		<TH CLASS="dg_header_cell">Include Cost?</TH>
		<TH CLASS="dg_header_cell">Options</TH>
	</TR>
	<TBODY ID="bodyId">
<? for($i = 0; $i < count($activities); $i++){
		$act_time = ($activities[$i]->end_ts>0 &&  $activities[$i]->start_ts>0 ? $activities[$i]->end_ts - $activities[$i]->start_ts : 0);
		$total_time += $act_time;
		$total_cost += $activities[$i]->cost;
		$total_com += $activities[$i]->com_amount;
		$total_price += $activities[$i]->price;
?>
	<TR onMouseOver="this.style.backgroundColor ='#CCCCCC'" onMouseOut="this.style.backgroundColor ='#FFFFFF'">
		<TD CLASS="dg_data_cell_1"><?= $activities[$i]->project_name ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $activities[$i]->task_name ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $activities[$i]->start_ts>0 ? date("n/j/y H:i", $activities[$i]->start_ts) :  "" ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $activities[$i]->end_ts>0 ? date("n/j/y H:i", $activities[$i]->end_ts) :  "" ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></TD>
		<TD CLASS="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->cost, 2) ?></TD>
		<TD CLASS="dg_data_cell_1"><? if(!$activities[$i]->check_id > 0) echo '<INPUT TYPE="checkbox" NAME="cost_trans_ids[]" VALUE="'.$activities[$i]->cost_trans_id.'">'; ?></TD>
		<TD CLASS="dg_data_cell_1">&nbsp;
			<A CLASS="link1" HREF="task_activity.php?mode=edit&id=<?= $activities[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></A>&nbsp;|&nbsp;
			<A CLASS="link1" HREF="task_activity.php?mode=delete&id=<?= $activities[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="" border="0" /></A>&nbsp;
		</TD>
	</TR>
<? if($_REQUEST['detail']){?>
	<TR>
		<TD COLSPAN="8" CLASS="dg_data_cell_1"><?= nl2br($activities[$i]->text) ?></TD>
	</TR>
<? 	if($i != count($activities)-1){?>
	<TR>
		<TD COLSPAN="8" CLASS="dg_header_cell">&nbsp;</TD>
	</TR>
<? 	} //If not last ?>
<? } //If detail ?>
<? }?>
</TBODY>
	<TR>
		<TD COLSPAN="4" CLASS="form_header_cell" ALIGN="right">Totals:</TD>
		<TD CLASS="form_field_cell"><?= formatLengthOfTime($total_time) ?></TD>
		<TD CLASS="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_cost, 2) ?></TD>
		<TD CLASS="form_header_cell"><INPUT TYPE="checkbox" NAME="select_all" onClick='SelectAll("cost_trans_ids[]")'></TD>
		<TD CLASS="form_header_cell" ALIGN="right"></TD>
	</TR>
	<TR>
		<TD COLSPAN="8" CLASS="form_header_cell" ALIGN="right">
			<INPUT TYPE="submit" NAME="save" VALUE="Create Check"/>
		</TD>
	</TR>
</TABLE>
</div>
</div>
<? } ?>
<BR>
<? if(count($commissions) > 0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Unpaid Commissions</a><div>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="dg_table">
	<TR>
		<TH COLSPAN="8" CLASS="form_header_cell">Select Commissions</TH>
	</TR>
	<TR>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 0, 1, false)">Project</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 1, 0, false)">Task</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 2, 0, false)">Start</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 3, 0, false)">End</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 4, 0, false)">Time Spent</A></TH>
		<TH CLASS="dg_header_cell"><A CLASS="link1" HREF="" onClick="return sortTable('bodyId', 6, 0, false)">Commission</A></TH>
		<TH CLASS="dg_header_cell">Include Comm?</TH>
		<TH CLASS="dg_header_cell">Options</TH>
	</TR>
	<TBODY ID="bodyId">
<? 
	$total_com = 0.00;
	for($i = 0; $i < count($commissions); $i++){
		$act_time = ($commissions[$i]->end_ts>0 &&  $commissions[$i]->start_ts>0 ? $commissions[$i]->end_ts - $commissions[$i]->start_ts : 0);
		$total_com += $commissions[$i]->com_amount;
?>
	<TR onMouseOver="this.style.backgroundColor ='#CCCCCC'" onMouseOut="this.style.backgroundColor ='#FFFFFF'">
		<TD CLASS="dg_data_cell_1"><?= $commissions[$i]->project_name ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $commissions[$i]->task_name ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $commissions[$i]->start_ts>0 ? date("n/j/y H:i", $commissions[$i]->start_ts) :  "" ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $commissions[$i]->end_ts>0 ? date("n/j/y H:i", $commissions[$i]->end_ts) :  "" ?></TD>
		<TD CLASS="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></TD>
		<TD CLASS="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($commissions[$i]->com_amount, 2) ?></TD>
		<TD CLASS="dg_data_cell_1"><? if(!$commissions[$i]->check_id > 0) echo '<INPUT TYPE="checkbox" NAME="comm_trans_ids[]" VALUE="'.$commissions[$i]->com_trans_id.'">'; ?></TD>
		<TD CLASS="dg_data_cell_1">&nbsp;
			<A CLASS="link1" HREF="task_activity.php?mode=edit&id=<?= $commissions[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></A>&nbsp;|&nbsp;
			<A CLASS="link1" HREF="task_activity.php?mode=delete&id=<?= $commissions[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="" border="0" /></A>&nbsp;
		</TD>
	</TR>
<? }?>
</TBODY>
	<TR>
		<TD COLSPAN="5" CLASS="form_header_cell" ALIGN="right">Total:</TD>
		<TD CLASS="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_com, 2) ?></TD>
		<TD CLASS="form_header_cell"><INPUT TYPE="checkbox" NAME="select_all" onClick='SelectAll("comm_trans_ids[]")'></TD>
		<TD CLASS="form_header_cell" ALIGN="right"></TD>
	</TR>
	<TR>
		<TD COLSPAN=10" CLASS="form_header_cell" ALIGN="right">
			<INPUT TYPE="submit" NAME="save" VALUE="Create Check"/>
		</TD>
	</TR>
</TABLE>
</div>
</div>
<? } ?>
</FORM>
<? require('footer.php') ?>
