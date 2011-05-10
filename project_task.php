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
checkLogin();

require_once('includes/SI_Project.php');
require_once('includes/SI_Task.php');
require_once('includes/SI_TaskStatus.php');
require_once('includes/SI_TaskItem.php');
require_once('includes/SI_TaskPriority.php');
require_once('includes/SI_SalesCommissionType.php');


$title = '';
$task = new SI_Task();
$project = new SI_Project();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Task";
	$task->due_ts = '';
	if(empty($_REQUEST['project_id'])){
		fatal_error("Error: No Project ID specified!\n");
	}else{
		$task->project_id = $_REQUEST['project_id'];
		if($project->get($task->project_id) === FALSE){
			fatal_error("Could not retreive project!");
			debug_message($project->getLastError());
		}
		if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
			fatal_error('Insufficent access rights for this project!');
		}
	}

	if($_POST['save']){
		$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
		$task->updateFromAssocArray($_POST);
		if($task->add() !== false){
			if($project->sendUpdateNotification(array("Added task ".$_POST['name'])) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}

			if($_POST['save'] != "Add"){
				goBack();
			}
		}else{
			$error_msg .= "Error adding Task!\n";
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Task";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$task->get($_REQUEST['id'])){
			debug_message($task->getLastError());
			fatal_error("Could not retreive task!");
		}
		if($project->get($task->project_id) === FALSE){
			debug_message($project->getLastError());
			fatal_error("Could not retreive project!");
		}
		if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
			fatal_error('Insufficent access rights for this project!');
		}
	}

	if($_POST['save']){
		$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
		$task->updateFromAssocArray($_POST);
		if($task->update()){
			if(!empty($_FILES['attachment_file']['tmp_name'])){
				if($task->addAttachment($_FILES['attachment_file']['tmp_name'], $_FILES['attachment_file']['name'], $_POST['attachment_description']) === FALSE){
					$error_msg .= "Error adding attachment!\n";
					debug_message($task->getLastError());
				}
			}
			if(!empty($_POST['new_item'])){
				if($task->addTaskItem($_POST['new_item'], $_POST['new_item_parent']) === FALSE){
					$error_msg .= "Error adding new task item!\n";
				}
			}
		}else{
			$error_msg .= "Error updating Task!\n";
		}
		if(empty($error_msg) && $_POST['save'] != "Add"){
			if($project->sendUpdateNotification(array("Updated task ".$_POST['name'])) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}

			goBack();
		}else{
			$task->stripSlashes();
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Task";
	if(!isset($_REQUEST['id'])){
		fatal_error("Task id must be supplied!");
	}
	if(!$task->get($_REQUEST['id'])){
		fatal_error("Could not retreive task!");
		debug_message($task->getLastError());
	}
	if($project->get($task->project_id) === FALSE){
		fatal_error("Could not retreive project!");
		debug_message($project->getLastError());
	}
	if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
		fatal_error('Insufficent access rights for this project!');
	}else{
		if($task->delete($_REQUEST['id'])){
			goBack();
		}else{
			fatal_error("Could not delete task!");
			debug_message($task->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'move_item'){
	$title = "Move Task Item";
	if(!$task->get($_REQUEST['id'])){
		fatal_error("Could not retreive task!");
		debug_message($task->getLastError());
	}
	if($project->get($task->project_id) === FALSE){
		fatal_error("Could not retreive project!");
		debug_message($project->getLastError());
	}
	if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
		fatal_error('Insufficent access rights for this project!');
	}

	$ti = new SI_TaskItem();
	$ti->get($_REQUEST['item_id']);
	if(strtolower($_REQUEST['direction']) == 'up'){
		$result = $ti->switchOrderNumber(TI_ORDER_UP);
	}else if(strtolower($_REQUEST['direction']) == 'down'){
		$result = $ti->switchOrderNumber(TI_ORDER_DOWN);
	}else{
		$error_msg .= "Invalid direction provided for moving item!\n";
	}
	if($result){
		header("Location: ".getCurrentURL(null, false)."?mode=edit&id=".$_REQUEST['id']."\r\n");
		exit();
	}else{
		$error_msg .= "Error moving Task Item!\n";
	}
}else if($_REQUEST['mode'] == 'delete_item'){
	$title = "Delete Task Item";
	if(!$task->get($_REQUEST['id'])){
		fatal_error("Could not retreive task!");
		debug_message($task->getLastError());
	}
	if($project->get($task->project_id) === FALSE){
		fatal_error("Could not retreive project!");
		debug_message($project->getLastError());
	}
	if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
		fatal_error('Insufficent access rights for this project!');
	}

	$ti = new SI_TaskItem();
	if($ti->delete($_REQUEST['item_id'])){
		header("Location: ".getCurrentURL(null, false)."?mode=edit&id=".$_REQUEST['id']."\r\n");
		exit();
	}else{
		$error_msg .= "Error deleting Task Item!\n";
	}
}else if($_REQUEST['mode'] == 'delete_attachment'){
	$title = "Delete Attachment";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$task->get($_REQUEST['id'])){
			fatal_error("Could not retreive task!");
			debug_message($task->getLastError());
		}
		if($project->get($task->project_id) === FALSE){
			fatal_error("Could not retreive project!");
			debug_message($project->getLastError());
		}
		if(!$project->hasRights(PROJECT_RIGHT_FULL)){
			fatal_error('Insufficent access rights for this project!');
		}
	}


	if($task->deleteAttachment($_REQUEST['attachment_id']) === FALSE){
		$error_msg .= "Error deleting attachment from project!\n";
		debug_message($task->getLastError());
	}
	if($project->sendUpdateNotification(array("Removed attachment ID ".$_REQUEST['attachment_id'])) === FALSE){
		$error_msg .= "Error sending update notification!\n";
		debug_message($project->getLastError());
	}
	$_REQUEST['mode'] = 'edit';

}else{
	fatal_error("Error: Invalid mode!\n");
}

?>
<? require('header.php'); ?>
<SCRIPT>
function disableCom(disableIt){
	document.task.sales_com_user_id.disabled = disableIt;
	document.task.sales_com_type_id.disabled = disableIt;
}
</SCRIPT>
<FORM NAME="task" ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Project Information</a><div>
<INPUT NAME="id" TYPE="hidden" VALUE="<?= $_REQUEST['id'] ?>">
<INPUT NAME="project_id" TYPE="hidden" VALUE="<?= $task->project_id ?>">
<INPUT NAME="mode" TYPE="hidden" VALUE="<?= $_REQUEST['mode'] ?>">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD CLASS="form_field_header_cell">Project Name:</TD>
	<TD CLASS="form_field_cell">
		<?= $project->name ?>&nbsp;&nbsp;
		<A CLASS="link1" HREF="project_details.php?id=<?= $project->id ?>">
		<img src="images/properties.gif" width="16" height="16" alt="Detail" border="0" ALIGN="MIDDLE" /></A>&nbsp;&nbsp;
		<A CLASS="link1" HREF="project.php?mode=edit&id=<?= $project->id ?>">
		<img src="images/edit.png" width="16" height="16" alt="Edit" border="0" ALIGN="MIDDLE"  /></A>&nbsp;
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Company:</TD>
	<TD CLASS="form_field_cell"><?= $project->company ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Project Due Date:</TD>
	<TD CLASS="form_field_cell"><?= $project->due_ts>0 ? date("n/j/y", $project->due_ts) :  "None" ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Status:</TD>
	<TD CLASS="form_field_cell"><?= $project->status ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Priority:</TD>
	<TD CLASS="form_field_cell"><?= $project->priority ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Attachments:</TD>
	<TD CLASS="form_field_cell">
<?  $attachments = $project->attachments;
		$attachment_count = count($attachments);
		if($attachment_count > 0){
			for($i=0; $i<$attachment_count; $i++){ ?>
			<A HREF="attachment.php?id=<?= $attachments[$i]->id ?>"><?= $attachments[$i]->path ?></A>&nbsp;-&nbsp;<?= $attachments[$i]->description ?><BR>
<?  	}
		}else{?>
		<b>No Attachments</b>
<?	} ?>
	</TD>
</TR>
</TABLE><BR>
</DIV></DIV>

<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD CLASS="form_field_header_cell">Name:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="name" CLASS="input_text" SIZE="45" TYPE="text" VALUE="<?= $task->name ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Task Type:</TD>
	<TD CLASS="form_field_cell">
<?	if($_REQUEST['mode'] == 'edit'){
			echo $task->type;
		}else{?>
		<SELECT NAME="type" CLASS="input_text">
			<?= SI_Task::getTypeTags($task->type); ?>
		</SELECT>
<?	}
?>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Status:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="task_status_id" CLASS="input_text">
			<?= SI_TaskStatus::getSelectTags($task->task_status_id) ?>
		</SELECT>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Priority:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="task_priority_id" CLASS="input_text">
			<?= SI_TaskPriority::getSelectTags($task->task_priority_id) ?>
		</SELECT>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Due:</TD>
	<TD CLASS="form_field_cell">
		<input type="text" class="input_text" name="due_ts" id="due_ts" SIZE="10"  value="<?= $task->due_ts > 0 ? date("n/j/Y", $task->due_ts) : ''?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('due_ts')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Sales Commission?</TD>
	<TD CLASS="form_field_cell">
		<INPUT NAME="sales_com" TYPE="radio" VALUE="D" <?= checked($task->sales_com, "D") ?> onClick="disableCom(true)">Default&nbsp;
		<INPUT NAME="sales_com" TYPE="radio" VALUE="Y" <?= checked($task->sales_com, "Y") ?> onClick="disableCom(false)">Hourly&nbsp;
		<INPUT NAME="sales_com" TYPE="radio" VALUE="N" <?= checked($task->sales_com, "N") ?> onClick="disableCom(true)">Non-Billable&nbsp;
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Commission User:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="sales_com_user_id" CLASS="input_text" DISABLED>
			<OPTION VALUE="0">None</OPTION>
			<?= SI_User::getSelectTags($task->sales_com_user_id) ?>
		</SELECT>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Commission Type:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="sales_com_type_id" CLASS="input_text" DISABLED>
			<OPTION VALUE="0">None</OPTION>
			<?= SI_SalesCommissionType::getSelectTags($task->sales_com_type_id) ?>
		</SELECT>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Task Billable?</TD>
	<TD CLASS="form_field_cell">
		<INPUT NAME="billable" TYPE="radio" VALUE="D" <?= checked($task->billable, "D") ?>>Default&nbsp;
		<INPUT NAME="billable" TYPE="radio" VALUE="Y" <?= checked($task->billable, "Y") ?>>Yes&nbsp;
		<INPUT NAME="billable" TYPE="radio" VALUE="N" <?= checked($task->billable, "N") ?>>No&nbsp;
		<INPUT NAME="billable" TYPE="radio" VALUE="S" <?= checked($task->billable, "S") ?>>Spec&nbsp;
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Task Spec Amount:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="amount" CLASS="input_text" SIZE="15" TYPE="text" VALUE="<?= $task->amount ?>"> * Only used for billable type of spec</TD>
</TR>
<? if($task->type == 'FREEFORM'){ ?>
<TR>
	<TD CLASS="form_field_header_cell">Description:</TD>
	<TD CLASS="form_field_cell"><TEXTAREA NAME="description" CLASS="input_text" COLS="70" ROWS="15"><?= $task->description ?></TEXTAREA></TD>
</TR>
<? }else{?>
<TR>
	<TD CLASS="form_field_header_cell">Description:</TD>
	<TD CLASS="form_field_cell"><TEXTAREA NAME="description" CLASS="input_text" COLS="70" ROWS="5"><?= $task->description ?></TEXTAREA></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Items:</TD>
	<TD CLASS="form_field_cell">
		<UL CLASS="taskitems">
<?	for($i=0; $i<count($task->items); $i++){?>
		<LI CLASS="taskitem"><?= $task->items[$i]->item ?>&nbsp;
			<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=move_item&direction=up&item_id=".$task->items[$i]->id ?>"><IMG SRC="images/arrow_up.gif" BORDER="0" WIDTH="16" HEIGHT="16" ALIGN="middle"/></A>&nbsp;
			<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=move_item&direction=down&item_id=".$task->items[$i]->id ?>"><IMG SRC="images/arrow_down.gif" BORDER="0" WIDTH="16" HEIGHT="16" ALIGN="middle"/></A>&nbsp;
			<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=delete_item&item_id=".$task->items[$i]->id ?>"><IMG SRC="images/delete_small.gif" BORDER="0" WIDTH="13" HEIGHT="13" ALIGN="middle"/></A>&nbsp;
		</LI>
<?		if($task->items[$i]->hasChildren()){?>
			<UL CLASS="taskitems">
<?			foreach($task->items[$i]->children as $child){?>
				<LI CLASS="taskitem"><?= $child->item ?>
					<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=move_item&direction=up&item_id=".$child->id ?>"><IMG SRC="images/arrow_up.gif" BORDER="0" WIDTH="16" HEIGHT="16" ALIGN="middle"/></A>&nbsp;
					<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=move_item&direction=down&item_id=".$child->id ?>"><IMG SRC="images/arrow_down.gif" BORDER="0" WIDTH="16" HEIGHT="16" ALIGN="middle"/></A>
					<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=delete_item&item_id=".$child->id ?>"><IMG SRC="images/delete_small.gif" BORDER="0" WIDTH="13" HEIGHT="13" ALIGN="middle"/></A>&nbsp;
				</LI>
<?			} //foreach child?>
			</UL>
<?		} //if hasChildren
		} //for items
?>	</UL>
	</TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">New Task:</TD>
	<TD CLASS="form_field_cell">
		<SELECT NAME="new_item_parent" CLASS="input_text">
			<OPTION VALUE="0">No Parent</OPTION>
			<?= SI_TaskItem::getParentSelectTags($task->id, $_POST['new_item_parent']) ?>
		</SELECT>
		<INPUT NAME="new_item" CLASS="input_text" SIZE="70" TYPE="text">
		<INPUT TYPE="submit" CLASS="button" NAME="save" VALUE="Add">
	</TD>
</TR>
<? } //if type?>
<TR>
	<TD CLASS="form_field_header_cell">Attachments:</TD>
	<TD CLASS="form_field_cell">
<?  $attachments = $task->attachments;
		$attachment_count = count($attachments);
		if($attachment_count > 0){
			for($i=0; $i<$attachment_count; $i++){ ?>
		<LI CLASS="taskitem"><A HREF="attachment.php?id=<?= $attachments[$i]->id ?>"><?= $attachments[$i]->path ?></A>&nbsp;-&nbsp;<?= $attachments[$i]->description ?>&nbsp;&nbsp;
			<A HREF="<?= $_SERVER['PHP_SELF']."?id=".$task->id."&mode=delete_attachment&attachment_id=".$attachments[$i]->id ?>">
			<IMG SRC="images/delete_small.gif" BORDER="0" WIDTH="13" HEIGHT="13" ALIGN="middle"/></A>&nbsp;
		</LI>
<?  	}
		}else{?>
		<b>No Attachments</b>
<?	} ?>
	</TD>
</TR>
<?if($_REQUEST['mode'] == 'edit' && $project->hasRights(PROJECT_RIGHT_EDIT)){?>
<TR>
	<TD CLASS="form_field_header_cell">Add Attachment:</TD>
	<TD CLASS="form_field_cell">
		<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" WIDTH="100%">
		<TR>
			<TD><b>File:</b></TD>
			<TD><INPUT TYPE="file" CLASS="input_text" SIZE="30" name="attachment_file" value="<?= $_REQUEST['attachment_file'] ?>"></TD>
		</TR>
		<TR>
			<TD><b>Description:</b></TD>
			<TD><INPUT TYPE="TEXT" CLASS="input_text" SIZE="50" name="attachment_description" value="<?= $_REQUEST['attachment_description'] ?>" maxlength="255"></TD>
		</TR>
		<TR>
			<TD COLSPAN="2"><INPUT TYPE="submit" CLASS="button" NAME="save" VALUE="Add Attachment"></TD>
		</TR>
		</TABLE>
	</TD>
</TR>
<?}?>
<TR>
	<TD CLASS="form_field_header_cell">Created On:</TD>
	<TD CLASS="form_field_cell"><?= $task->created_ts ? date("D M jS, Y \a\\t h:i:s A", $task->created_ts) : "" ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Last Updated:</TD>
	<TD CLASS="form_field_cell"><?= $task->updated_ts ? date("D M jS, Y \a\\t h:i:s A", $task->updated_ts) : "" ?></TD>
</TR>
<TR>
	<TD COLSPAN="2" CLASS="form_field_cell">
		<DIV ALIGN="right"><INPUT TYPE="submit" NAME="save" CLASS="button" VALUE="Save"></DIV>
	</TD>
</TR>
</TABLE>
</DIV></DIV>
</FORM>
<?
if($task->sales_com == 'Y')
	print('<SCRIPT>disableCom(false)</SCRIPT>');

require('footer.php'); ?>