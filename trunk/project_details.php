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
require_once('includes/SI_PaymentSchedule.php');

checkLogin();

$project = new SI_Project();
$tasks = array();

if(!empty($_REQUEST['id'])){
	$project->get($_REQUEST['id']);
	if(!$project->hasRights(PROJECT_RIGHT_VIEW)){
		fatal_error('Insufficent access rights for this project!');
	}

	$task = new SI_Task();
	if($_REQUEST['filter'] == "all"){
		$tasks = $task->retrieveSet("t.deleted = 'N' AND t.project_id = ".$project->id);
	}else{
		$tasks = $task->retrieveSet("t.deleted = 'N' AND t.project_id = ".$project->id." AND s.completed = 'N'");
	}
	if($tasks === FALSE){
		$error_msg .= "Error retrieving tasks for project!\n";
		debug_message($task->getLastError());
	}
}else{
	fatal_error("Project ID must be supplied!\n");
}

// Get this projects payment schedule
$ps = new SI_PaymentSchedule();
$items = $ps->getForProject($project->id);
if($items === FALSE){
	$error_msg .= "Error getting scheduled payments for project!\n";
	debug_message($ps->getLastError());
}

$title = "Tasks for ".$project->name;

require('header.php') ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Project Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell">
		<?= $project->name ?>&nbsp;&nbsp;
<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
		<a title="Edit Project" class="link1" href="project.php?mode=edit&id=<?= $project->id ?>">
		<img src="images/edit.png" width="16" height="16" alt="edit" border="0" align="MIDDLE"  /></a>&nbsp;
<?	}?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell"><a title="Company Detail Center" href="company_detail.php?id=<?= $project->company_id ?>"><?= $project->company_name ?></a></td>
</tr>
<tr>
	<td class="form_field_header_cell">Spec Total:</td>
	<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($project->getTotal(),2) ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Owner:</td>
	<td class="form_field_cell"><a title="Resouce Detail Center" href="home_resource.php?id=<?= $project->owner_id ?>"><?= $project->owner_name ?></a></td>
</tr>
<tr>
	<td class="form_field_header_cell">Due Date:</td>
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
	<td class="form_field_header_cell">Attachments:</td>
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
</table>
	</div>
</div>

<form action="batch_process.php" method="GET">
<input type="hidden" name="type" value="task"/>  
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Tasks</a><div>
	<div class="gridToolbar">
<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
		  <a href="project_task.php?mode=add&project_id=<?= $project->id ?>" style="background-image:url(images/new_invoice.png);">New Task</a>
<?	}?>		  
	&nbsp;&nbsp;Filter:&nbsp;
	<a class="<?= $_REQUEST['filter'] == "all" ? "link3" : "link1" ?>" HREF="project_details.php?id=<?= $_REQUEST['id'] ?>&filter=all" style="background-image:url(images/filter.gif);">All</a>
	<a class="<?= $_REQUEST['filter'] == "all" ? "link1" : "link3" ?>" HREF="project_details.php?id=<?= $_REQUEST['id'] ?>&filter=pending" style="background-image:url(images/filter.gif);">Pending</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">ID</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 1, false)">Name</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 1, false)">Status</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 1, false)">Due Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 1, false)">Priority</a></th>
		<th class="dg_header_cell">Select</th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($tasks); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $tasks[$i]->id ?></td>
		<td class="dg_data_cell_1"><a title="Task Activities" href="task_activities.php?task_id=<?= $tasks[$i]->id ?>"><?= $tasks[$i]->name ?></a></td>
		<td class="dg_data_cell_1"><?= $tasks[$i]->status ?></td>
		<td class="dg_data_cell_1"><?= $tasks[$i]->due_ts>0 ? date("n/j/y", $tasks[$i]->due_ts) :  "None" ?></td>
		<td class="dg_data_cell_1"><?= $tasks[$i]->priority ?></td>
		<td class="dg_data_cell_1"><input type="checkbox" name="ids[]" value="<?= $tasks[$i]->id ?>"/></td>
		<td class="dg_data_cell_1">&nbsp;
			<a class="link1" href="task_activities.php?task_id=<?= $tasks[$i]->id ?>"><img src="images/activity.gif" width="16" height="16" title="List Time" border="0" /></a>
<?	if($project->hasRights(PROJECT_RIGHT_EDIT)){?>
			&nbsp;|&nbsp;<a class="link1" href="task_activity.php?mode=add&task_id=<?= $tasks[$i]->id ?>"><img src="images/activity_add.gif" width="16" height="16" title="Add Time" border="0" /></a>
			&nbsp;|&nbsp;<a class="link1" href="project_task.php?mode=edit&id=<?= $tasks[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Task" border="0" /></a>
<?	}?>
<?	if($project->hasRights(PROJECT_RIGHT_FULL)){?>
			&nbsp;|&nbsp;<a class="link1" href="project_task.php?mode=delete&id=<?= $tasks[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Task" border="0" /></a>&nbsp;
<?	}?>
		</td>
	</tr>
<? }?>
</tbody>
	<tr>
		<td class="dg_data_cell_1" colspan="7" align="right">
			<select name="action">
				<option value="">Select Action...</option>
				<option value="delete">Delete Selected</option>
				<option value="move">Move Selected</option>
			</select>
			<input type="submit" name="submit" value="Perform">
		</td>
	</tr>
</table>
	</div>
</div>
<? if($project->billable == 'S' && $loggedin_user->hasRight('accounting')){	?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Scheduled Billings</a><div>
	<div class="gridToolbar">
		<a href="payment_schedule.php?mode=add&project_id=<?= $project->id ?>" style="background-image:url(images/new_invoice.png);">New Scheduled Billing</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 0, 1, false)">ID</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 1, 2, false)">Due Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 2, 1, false)">Desc</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 3, 1, false)">Amount</a></th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
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
			<a class="link1" href="payment_schedule.php?mode=delete&id=<?= $items[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Payment Schedule" border="0" /></a>
		</td>		
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>
</form>
<? } // if has accounting

require('footer.php') ?>