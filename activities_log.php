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
require_once('includes/SI_Task.php');
require_once('includes/SI_TaskActivity.php');

checkLogin();

$activity = new SI_TaskActivity();
$_REQUEST['detail'] = strtolower(substr($_REQUEST['detail'],0,1)) == "y" ? TRUE : FALSE;

if($_REQUEST['company_id']){
	if(!$loggedin_user->hasRight('admin') && !$loggedin_user->hasRight('accounting')){
		if($loggedin_user->company_id != $_REQUEST['company_id']){
			fatal_error('You do not have access to view this company!');
		}
	}
	$title = "for Company";
	$field_header = 'Billed';
	$field = 'invoice_id';
	$url = $_SERVER['PHP_SELF']."?company_id=".$_REQUEST['company_id']."&unbilled=".$_REQUEST['unbilled']."&";
	$_REQUEST['unbilled'] = strtolower(substr($_REQUEST['unbilled'],0,1)) == "y" ? TRUE : FALSE;
	$activities = $activity->getActivitiesForCompany($_REQUEST['company_id'], $_REQUEST['unbilled']);
	if($activities === FALSE){
		$error_msg .= "Could not retrieve Activity List for Company ID ".$_REQUEST['company_id']."!\n";
		debug_message($activity->getLastError());
	}
}else if($_REQUEST['invoice']){
	$title = "for Invoice Number ".$_REQUEST['invoice'];
	$field_header = 'Billed';
	$field = 'invoice';
	$url = $_SERVER['PHP_SELF']."?invoice=".$_REQUEST['invoice']."&";
	$invoice = new SI_Invoice();
	if($invoice->get($_REQUEST['invoice']) === FALSE){
		$error_msg .= "Could not find invoice!\n";
		debug_message($invoice->getLastError());
	}
	
	if(!$loggedin_user->hasRight('admin') && !$loggedin_user->hasRight('accounting')){
		if($loggedin_user->company_id != $invoice->company_id){
			fatal_error('You do not have access to view this invoice!');
		}
	}
	
	$activities = $activity->getActivitiesForCompany(0, TRUE, $_REQUEST['invoice']);
	if($activities === FALSE){
		$error_msg .= "Could not retrieve Activity List for Invoice ".$_REQUEST['invoice']."!\n";
		debug_message($activity->getLastError());
	}
}else if($_REQUEST['check']){
	$title = "for Check Number ".$_REQUEST['check'];
	$field_header = 'Paid';
	$field = 'check';
	$url = $_SERVER['PHP_SELF']."?check=".$_REQUEST['check']."&";
	$check = new SI_Check();
	if($check->getByNumber($_REQUEST['check']) === FALSE){
		$error_msg .= "Could not retrieve Activity List for Check ".$_REQUEST['check']."!\n";
		debug_message($check->getLastError());
	}
	if(!$loggedin_user->hasRight('admin') && !$loggedin_user->hasRight('accounting')){
		if($loggedin_user->id != $check->user_id){
			fatal_error('You do not have access to view this check!');
		}
	}
	
	$activities = $check->getActivities();
	if($activities === FALSE){
		$error_msg .= "Could not retrieve Activity List for Check ID ".$_REQUEST['check']."!\n";
		debug_message($check->getLastError());
	}
	$commissions = $check->getCommissions($_REQUEST['check']);
	if($commissions === FALSE){
		$error_msg .= "Could not retrieve Commission List for Check ID ".$check->id."!\n";
		debug_message($check->getLastError());
	}	
	
	
}elseif($_REQUEST['user_id']){
	$title = "for User";
	$field_header = 'Paid';
	$field = 'check';
	$url = $_SERVER['PHP_SELF']."?user_id=".$_REQUEST['user_id']."&unpaid=".$_REQUEST['unpaid']."&";
	$_REQUEST['unpaid'] = strtolower(substr($_REQUEST['unpaid'],0,1)) == "y" ? TRUE : FALSE;
	
	if(!$loggedin_user->hasRight('admin') && !$loggedin_user->hasRight('accounting')){
		if($loggedin_user->id != $_REQUEST['user_id']){
			fatal_error('You do not have access to view this users activities!');
		}
	}
	
	$user = new SI_User();
	$activities = $user->getActivities($_REQUEST['user_id'], $_REQUEST['unpaid']);
	if($activities === FALSE){
		$error_msg .= "Could not retrieve Activity List for User ID ".$_REQUEST['user_id']."!\n";
		debug_message($user->getLastError());
	}
	$commissions = $user->getCommissions($_REQUEST['user_id']);
	if($commissions === FALSE){
		$error_msg .= "Could not retrieve Commission List for User ID ".$user->id."!\n";
		debug_message($user->getLastError());
	}	
}else{
	$display_form = TRUE;
	$title = 'Activity Log';
}

$total_time = 0;

require('header.php') ?>
<link href="tooltips2.css" rel="stylesheet" type="text/css"/>
<script type='text/javascript' src='includes/javascript/tooltips2.js'></script>
<script type="text/javascript">
var tooltipData = function(id, tip){
	
	var handleSuccess = function(o){
		var response = o.responseText; 
		response = response.split("<!")[0]; 
		try{
			result = YAHOO.ext.util.JSON.decode(response);
			if(typeof result.error != 'undefined'){
				alert('Error getting tooltip data from server:\n' + result.error);
			}else{
				tip.innerHTML = result.result;
			}
		}catch(ex){
			alert("Could not decode response: \n" + response);
		}
		return false;		
	};
	YAHOO.util.Connect.asyncRequest('GET', 'json.php/getActivityDetailHTML/'+id, { 
		success: handleSuccess
	}); 
	return "Loading...";	
}

try{
	addLoadListener(function(){
		initTooltips(tooltipData);
		}
	);
}catch(err){
	for(var i in err){
		alert(i + ': ' + err[i]);
	}
}
</script>
<?
if($display_form){ ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="GET">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Invoice Number:</td>
	<td class="form_field_cell"><input type="text" class="input_text" name="invoice" size="10"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Check Number:</td>
	<td class="form_field_cell"><input type="text" class="input_text" name="check" size="10"></td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" name="save" class="button" value="View"></div>
	</td>
</tr>
</table>
	</div>
</div>
</form>
<?
}else{ //else if display_form 
?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" NAME="chk">
<input type="hidden" name="company_id" value="<?= $_REQUEST['company_id'] ?>">
<input type="hidden" name="user_id" value="<?= $_REQUEST['user_id'] ?>">
<input type="hidden" name="invoice" value="<?= $_REQUEST['invoice'] ?>">
<input type="hidden" name="detail" value="<?= $_REQUEST['detail'] ?>">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Activities <?= $title ?></a><div>
	<div class="gridToolbar">
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $url."detail=y" ?>" style="background-image:url(images/plus.png);">Show details</a>
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $url."detail=n" ?>" style="background-image:url(images/minus.png);">Hide details</a>		  
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Start</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">End</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">Time Spent</a></th>
<? if($loggedin_user->hasRight('admin') || $loggedin_user->hasRight('accounting')){ ?>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 5, 0, false)">Cost</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 5, 0, false)">Price</a></th>
<? } ?>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 6, 0, false)"><?= $field_header ?></a></th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($activities); $i++){
		$task = $activities[$i]->getTask();

		$act_time = ($activities[$i]->end_ts>0 &&  $activities[$i]->start_ts>0 ? $activities[$i]->end_ts - $activities[$i]->start_ts : 0);
		$total_time += $act_time;
		$total_cost += $activities[$i]->cost;
		$total_price += $activities[$i]->price;
		$activities[$i]->stripSlashes();
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1">
			<a class="link1 hastooltip" id="<?= $activities[$i]->id ?>" href="javascript: return false;"><img src="images/properties.gif" width="16" height="16" alt="Details" border="0" /></a>&nbsp;
			<a href="project_details.php?id=<?= $activities[$i]->project_id ?>"><?= $activities[$i]->project_name ?></a>
		</td>
		<td class="dg_data_cell_1"><a href="task_activities.php?task_id=<?= $activities[$i]->task_id ?>"><?= $activities[$i]->task_name ?></a></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->start_ts>0 ? date("Y-n-j H:i", $activities[$i]->start_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->end_ts>0 ? date("Y-n-j H:i", $activities[$i]->end_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></td>
<? if($loggedin_user->hasRight('admin') || $loggedin_user->hasRight('accounting')){ ?>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->cost, 2) ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->price, 2) ?></td>
<? } ?>
		<td class="dg_data_cell_1"><?= $activities[$i]->$field > 0  ?  $activities[$i]->$field :  "No" ?></td>
		<td class="dg_data_cell_1">
			<a class="link1" href="task_activity.php?mode=edit&id=<?= $activities[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
		<td class="dg_data_cell_1">
			<a class="link1" href="task_activity.php?mode=delete&id=<?= $activities[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="" border="0" /></a>
		</td>
	</tr>
<? if($_REQUEST['detail']){?>
	<tr>
		<td colspan="10" class="dg_data_cell_1"><?= nl2br($activities[$i]->text) ?></td>
	</tr>
<? 	if($i != count($activities)-1){?>
	<tr>
		<td colspan="10" class="dg_header_cell">&nbsp;</td>
	</tr>     
<? 	} //If not last 
  } //If detail 
}?>
</tbody>
	<tr>
		<td colspan="4" class="form_header_cell" align="right">Totals:</td>
		<td class="form_field_cell"><?= formatLengthOfTime($total_time) ?></td>
<? if($loggedin_user->hasRight('admin') || $loggedin_user->hasRight('accounting')){ ?>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_cost, 2) ?></td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price, 2) ?></td>
<? } ?>
		<td class="form_header_cell" align="right"></td>
		<td class="form_header_cell" align="right"></td>
	</tr>
</table>
	</div>
</div>
<? if(isset($commissions) && count($commissions) > 0) { ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Commissions <?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Start</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">End</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">Time Spent</a></th>
<? if($loggedin_user->hasRight('admin') || $loggedin_user->hasRight('accounting')){ ?>
		<th class="dg_header_cell">Amount?</th>
<? } ?>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId">
<? 
	$total_com = 0.00;
	for($i = 0; $i < count($commissions); $i++){
		$act_time = ($commissions[$i]->end_ts>0 &&  $commissions[$i]->start_ts>0 ? $commissions[$i]->end_ts - $commissions[$i]->start_ts : 0);
		$total_com += $commissions[$i]->com_amount;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $commissions[$i]->project_name ?></td>
		<td class="dg_data_cell_1"><?= $commissions[$i]->task_name ?></td>
		<td class="dg_data_cell_1"><?= $commissions[$i]->start_ts>0 ? date("n/j/y H:i", $commissions[$i]->start_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $commissions[$i]->end_ts>0 ? date("n/j/y H:i", $commissions[$i]->end_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></td>
<? if($loggedin_user->hasRight('admin') || $loggedin_user->hasRight('accounting')){ ?>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($commissions[$i]->com_amount, 2) ?></td>
<? } ?>
		<td class="dg_data_cell_1">
			<a class="link1" href="task_activity.php?mode=edit&id=<?= $commissions[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
		<td class="dg_data_cell_1">
			<a class="link1" href="task_activity.php?mode=delete&id=<?= $commissions[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="" border="0" /></a>
		</td>		
	</tr>
<? }?>
</tbody>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">Total:</td>
<? if($loggedin_user->hasRight('admin') || $loggedin_user->hasRight('accounting')){ ?>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_com, 2) ?></td>
<? } ?>
		<td class="form_header_cell" align="right"></td>
	</tr>
</table>

	</div>
</div>
<? } // end if commissions
?>
</form>
<? } //end if display_form 
?>
<div id="ad_holder" style="display: none"></div>
<? require('footer.php') ?>