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
require_once('includes/SI_PaymentSchedule.php');

checkLogin();

$project = new SI_Project();
$task = new SI_Task();
$ta = new SI_TaskActivity();
$activities = array();

if(!empty($_REQUEST['task_id'])){
	$task->get($_REQUEST['task_id']);
	$project->get($task->project_id);
	if(!$project->hasRights(PROJECT_RIGHT_VIEW)){
		fatal_error('Insufficent access rights for this project!');
	}
	$activities = $ta->retrieveSet("task_id = ".$task->id);
	if($activities === FALSE){
		$error_msg .= "Error getting list of task time!\n";
		debug_message($ta->getLastError());
	}
}else{
	fatal_error("Task ID must be supplied!\n");
}

$my_url = $_SERVER['PHP_SELF']."?task_id=".$task->id."&";

$_REQUEST['detail'] = strtolower(substr($_REQUEST['detail'],0,1)) == "y" ? TRUE : FALSE;

$title = "Time for ".$task->name;

$total_time = 0;

// Get this tasks payment schedule
$ps = new SI_PaymentSchedule();
$items = $ps->getForTask($task->id);
if($items === FALSE){
	$error_msg .= "Error getting scheduled billings for project!\n";
	debug_message($ps->getLastError());
}


require('header.php') ?>
<form action="batch_process.php" method="GET">
<input type="hidden" name="type" value="activity"/>  
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Task Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Project Name:</td>
	<td class="form_field_cell">
		<?= $project->name ?>&nbsp;&nbsp;
		<a class="link1" href="project_details.php?id=<?= $project->id ?>">
		<img src="images/properties.gif" width="16" height="16" title="Project Details" border="0" align="MIDDLE" /></a>&nbsp;&nbsp;
<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
		<a class="link1" href="project.php?mode=edit&id=<?= $project->id ?>">
		<img src="images/edit.png" width="16" height="16" title="Edit Project" border="0" align="MIDDLE"  /></a>&nbsp;
<?	}?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell"><a title="Company Detail Center" href="company_detail.php?id=<?= $project->company_id ?>"><?= $project->company_name ?></a></td>
</tr>
<tr>
	<td class="form_field_header_cell">Project Due Date:</td>
	<td class="form_field_cell"><?= $project->due_ts>0 ? date("n/j/y", $project->due_ts) :  "None" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Status:</td>
	<td class="form_field_cell"><?= $project->status ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Priority:</td>
	<td class="form_field_cell"><?= $project->priority ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Project Attachments:</td>
	<td class="form_field_cell">
<?  $attachments = $project->attachments;
		$attachment_count = count($attachments);
		if($attachment_count > 0){
			for($i=0; $i<$attachment_count; $i++){ ?>
			<a title="View Attachment" href="attachment.php?id=<?= $attachments[$i]->id ?>"><?= $attachments[$i]->path ?></a>&nbsp;-&nbsp;<?= $attachments[$i]->description ?><br>
<?  	}
		}else{?>
		<b>No Attachments</b>
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Expenses:</td>
	<td class="form_field_cell">
<?  $expenses = $project->getExpenses();
		$expense_count = count($expenses);
		if($expense_count > 0){
			for($i=0; $i<$expense_count; $i++){ ?>
			<?= $expenses[$i]->description . " - " . $expenses[$i]->price ?><br>
<?  	}
		}else{?>
		<b>No Expenses</b>
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Task Name:</td>
	<td class="form_field_cell">
		<?= $task->name ?>&nbsp;
<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
		<a class="link1" href="project_task.php?mode=edit&id=<?= $task->id ?>">
		<img src="images/edit.png" width="16" height="16" title="Edit Task" border="0" align="middle"  /></a>
<?	}?>
		</td>
</tr>
<tr>
	<td class="form_field_header_cell">Task Due Date:</td>
	<td class="form_field_cell"><?= $task->due_ts>0 ? date("n/j/y", $task->due_ts) :  "None" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Task Type:</td>
	<td class="form_field_cell"><?= $task->type ?></td>
</tr>
<tr>
	<td class="form_field_header_cell" nowrap>Task Description:</td>
	<td class="form_field_cell">
<? if($task->type == 'FREEFORM'){ ?>
		<?= nl2br($task->description) ?>
<? }else{ ?>
			<?= $task->getTaskItemsHTML(0, 'VIEW') ?>
<? } //end if task type ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Task Attachments:</td>
	<td class="form_field_cell">
<?  $attachments = $task->attachments;
		$attachment_count = count($attachments);
		if($attachment_count > 0){
			for($i=0; $i<$attachment_count; $i++){ ?>
			<a title="View Attachment" href="attachment.php?id=<?= $attachments[$i]->id ?>"><?= $attachments[$i]->path ?></a>&nbsp;-&nbsp;<?= $attachments[$i]->description ?><br>
<?  	}
		}else{?>
		<b>No Attachments</b>
<?	} ?>
	</td>
</tr>
</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Time</a><div>
	<div class="gridToolbar">
<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
			<a class="link1" href="task_activity.php?mode=add&task_id=<?= $task->id ?>" style="background-image:url(images/new_invoice.png);">Add Time</a>
<?	}?>		  
			<a class="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $my_url."detail=y" ?>" style="background-image:url(images/plus.png);">Show details</a>
			<a class="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $my_url."detail=n" ?>" style="background-image:url(images/minus.png);">Hide details</a>		  
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">ID</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Start</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">End</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">Duration</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">User</a></th>
		<th class="dg_header_cell">Select</th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($activities); $i++){
		$act_time = ($activities[$i]->end_ts>0 &&  $activities[$i]->start_ts>0 ? $activities[$i]->end_ts - $activities[$i]->start_ts : 0);
		$total_time += $act_time;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $activities[$i]->id ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->start_ts>0 ? date("n/j/y H:i", $activities[$i]->start_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->end_ts>0 ? date("n/j/y H:i", $activities[$i]->end_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->getUserName() ?></td>
		<td class="gridActions"><input type="checkbox" name="ids[]" value="<?= $activities[$i]->id ?>"/></td>
		<td class="gridActions">
			<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
						<a class="link1" href="task_activity.php?mode=edit&id=<?= $activities[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Time Entry" border="0" /></a>&nbsp;
			<?	}?>
		</td>
		<td class="gridActions">
			<?	if($project->hasRights(PROJECT_RIGHT_FULL)){?>
						<a class="link1" href="task_activity.php?mode=delete&id=<?= $activities[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Time Entry" border="0" /></a>&nbsp;
			<?	}?></td>			
		</tr>
<? if($_REQUEST['detail']){?>
	<tr>
		<td colspan="7" class="dg_data_cell_1">
		<?= nl2br(stripslashes($activities[$i]->text)) ?>
<?	if($task->type == 'ITEMIZED'){?>
		<?= "<br>".$activities[$i]->getCompletedItemsHTML() ?>
<?	} //end if itemized ?>		</td>
	</tr>
<? 	if($i != count($activities)-1){?>
	<tr>
		<td colspan="7" class="dg_header_cell">&nbsp;</td>
	</tr>
<? 	} //If not last ?>
<? } //If detail ?>
<? }?>
</tbody>
	<tr>
		<td colspan="3" class="form_header_cell" align="right">Time Spent:</td>
		<td class="form_field_cell"><?= formatLengthOfTime($total_time) ?></td>
		<td colspan="3" class="form_header_cell" align="right"></td>
	</tr>
	<tr>
		<td class="dg_data_cell_1" colspan="7" align="right">
			<select name="action">
				<option value="">Select Action...</option>
				<option value="delete">Delete Selected</option>
				<option value="move">Move Selected</option>
			</select>
			<input type="submit" name="submit" value="Perform">		</td>
	</tr>
</table>
	</div>
</div>
<? if($task->billable == 'S' && $loggedin_user->hasRight('accounting')){	?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Scheduled Payments</a><div>
	<div class="gridToolbar">
		  <a class="link1" href="payment_schedule.php?mode=add&task_id=<?= $task->id ?>" style="background-image:url(images/new_invoice.png);">New Scheduled Payment</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 0, 1, false)">ID</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 1, 2, false)">Due Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 2, 1, false)">Desc</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 3, 1, false)">Amount</a></th>
		<th class="dg_header_cell">Edit</td>
		<th class="dg_header_cell">Delete</td>		
	</tr>
	<tbody id="bodyId2">
<? for($i = 0; $i < count($items); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $items[$i]->id ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->due_ts>0 ? date("n/j/y", $items[$i]->due_ts) :  "None" ?></td>
		<td class="dg_data_cell_1"><a title="Edit Payment Schedule" href="payment_schedule.php?mode=edit&id=<?= $items[$i]->id ?>"><?= $items[$i]->description ?></a></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($items[$i]->amount, 2) ?></td>
		<td class="gridActions">
			<a class="link1" href="payment_schedule.php?mode=edit&id=<?= $items[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Payment Schedule" border="0" /></a>
		</td>
		<td class="gridActions">
			<a class="link1" href="payment_schedule.php?mode=delete&id=<?= $items[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Payment Schedule" border="0" /></a>&nbsp;
		</td>		
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>
<? } // if has accounting ?>
</form>
<? require('footer.php') ?>