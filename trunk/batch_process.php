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
require_once('includes/SI_Company.php');
require_once('includes/SI_TaskActivity.php');

$title = '';
$project = new SI_Project();
$task = new SI_Task();
$company = new SI_Company();
$ta = new SI_TaskActivity();
$fields = array();
$can_continue = TRUE;

if(!isset($_REQUEST['ids']) || !is_array($_REQUEST['ids'])){
	$_REQUEST['ids'] = split(',', $_REQUEST['id_list']);	
}

if($_REQUEST['type'] == 'task'){
	$tasks = $task->retrieveSet('WHERE t.id IN ('.join(',', $_REQUEST['ids']).')');
	if($tasks === FALSE){
		$error_msg = "Error getting task information!";
		debug_message($task->getLastError());	
	}
	$fields['Tasks:'] = '';
	$billed_activities = FALSE;
	$billed_tasks = '';
	$same_company = TRUE;
	$company_id = 0;
	foreach($tasks as $task){
		$project =& $task->getProject();
		if($company_id == 0){
			$company_id =  $project->company_id;
		}elseif($company_id != $project->company_id){
			$same_company = FALSE;
		}
		
		if($task->hasBilledActivities()){
			$billed_activities = TRUE;
			$billed_tasks .= $task->id." - ".$project->name.' - '.$task->name."<BR>\n";
		}
		$fields['Tasks:'] .= $task->id." - ".$project->name.' - '.$task->name."<BR>\n";
	}

	if($company->get($company_id) === FALSE){
		$error_msg .= "Error looking up company associated with these tasks!";
		debug_message($company->getLastError());	
	}
	 
	if($billed_activities){
		$fields[] = "<DIV CLASS='error'>These tasks contain time entries that are on an invoice<BR>$billed_tasks</DIV>";	
	}
	
	if($_REQUEST['action'] == 'move'){
		$title = "Move Tasks";

		if($billed_activities){
			if($same_company){
				$fields[] = "<DIV class='error'>You can only move tasks with billed time entries to a project that is owned by the same company as the source project. So, the following lists of projects only includes the projects associated with {$company->name}</DIV>";
				$fields['Destination Project:'] = "<SELECT NAME='dest_project_id'>\n<OPTION VALUE='0'>Select Project...</OPTION>\n".SI_Project::getSelectTags(0, $company_id)."</SELECT>\n";
			}else{
				$fields[] = "<DIV class='error'>You can only move tasks with billed time entries to a project that is owned by the same company and the tasks you have selected are not all owned by the same company so you can not move the tasks.</DIV>";
				$can_continue = FALSE;
			}
		}else{
			$fields['Destination Project:'] = "<SELECT NAME='dest_project_id'>\n<OPTION VALUE='0'>Select Project...</OPTION>\n".SI_Project::getSelectTags()."</SELECT>\n";
		}
				
		if($_POST['save']){
			if($_REQUEST['dest_project_id'] > 0){
				foreach($tasks as $task){
					$task->project_id = $_REQUEST['dest_project_id'];
					if($task->update() === FALSE){
						$error_msg .= "Error moving task id {$task->id} to project id {$_REQUEST['dest_project_id']}\n";
						debug_message($task->getLastError());	
					}
				}
				
				if(empty($error_msg)){
					goBack();
				}
			}
		}
	}elseif($_REQUEST['action'] == 'delete'){
		$title = "Delete Tasks";

		if($billed_activities){
			$fields[] = "<DIV class='error'>You can not delete tasks that have time entries attached to invoices.</DIV>";
			$can_continue = FALSE;
		}else{
			$fields[] = "<DIV class='error'>Are you sure you want to delete these tasks? This action is irreversible!</DIV>";
		}
		
		if($_POST['save']){
			foreach($tasks as $task){
				if($task->delete() === FALSE){
					$error_msg .= "Error removing task id {$task->id}\n";
					debug_message($task->getLastError());	
				}
			}
			
			if(empty($error_msg)){
				goBack();
			}
		}
	}else{
		fatal_error("You must select an action to perform on the selected items!");	
	}

}else if($_REQUEST['type'] == 'activity'){
	$tas = $ta->retrieveSet('WHERE a.id IN ('.join(',', $_REQUEST['ids']).')');
	if($tas === FALSE){
		$error_msg = "Error getting task activity information!";
		debug_message($ta->getLastError());	
	}
	$fields['Activities:'] = '';
	$billed_activities = FALSE;
	$billed_ta_text = '';
	$same_company = TRUE;
	$company_id = 0;
	foreach($tas as $ta){
		$project =& $ta->getProject();
		if($company_id == 0){
			$company_id =  $project->company_id;
		}elseif($company_id != $project->company_id){
			$same_company = FALSE;
		}
		
		if($ta->isBilled()){
			$billed_activities = TRUE;
			$billed_ta_text .= $ta->id." - ".$ta->getUserName()." - ".($ta->start_ts >0 ? date("n/j/y H:i", $ta->start_ts) : '').' - '.($ta->end_ts >0 ? date("n/j/y H:i", $ta->end_ts) : '')."<BR>\n";
		}
		$fields['Activities:'] .= $ta->id." - ".$ta->getUserName()." - ".($ta->start_ts >0 ? date("n/j/y H:i", $ta->start_ts) : '').' - '.($ta->end_ts >0 ? date("n/j/y H:i", $ta->end_ts) : '')."<BR>\n";
	}

	if($company->get($company_id) === FALSE){
		$error_msg .= "Error looking up company associated with these tasks!";
		debug_message($company->getLastError());	
	}
	 
	if($billed_activities){
		$fields[] = "<DIV CLASS='error'>Some of these time entries are on an invoice<BR>$billed_ta_text</DIV>";	
	}
	
	if($_REQUEST['action'] == 'move'){
		$title = "Move Time";

		if($billed_activities){
			if($same_company){
				$fields[] = "<DIV class='error'>You can only move time entries that are billed to a task that is owned by the same company as the source project. So, the following lists of tasks only includes the tasks associated with {$company->name}</DIV>";
				$fields['Destination Task:'] = "<SELECT NAME='dest_task_id'>\n<OPTION VALUE='0'>Select Task...</OPTION>\n".SI_Task::getSelectTags(0, $company_id)."</SELECT>\n";
			}else{
				$fields[] = "<DIV class='error'>You can only move time entries with billed time entries to a task that is owned by the same company and the time entries you have selected are not all owned by the same company so you can not move the tasks.</DIV>";
				$can_continue = FALSE;
			}
		}else{
			$fields['Destination Task:'] = "<SELECT NAME='dest_task_id'>\n<OPTION VALUE='0'>Select Task...</OPTION>\n".SI_Task::getSelectTags()."</SELECT>\n";
		}
				
		if($_POST['save']){
			if($_REQUEST['dest_task_id'] > 0){
				foreach($tas as $ta){
					$ta->task_id = $_REQUEST['dest_task_id'];
					if($ta->update() === FALSE){
						$error_msg .= "Error moving task id {$ta->id} to project id {$_REQUEST['dest_task_id']}\n";
						debug_message($ta->getLastError());	
					}
				}
				
				if(empty($error_msg)){
					goBack();
				}
			}else{
				$error_msg .= "You must select a destination Task!";	
			}
		}
	}elseif($_REQUEST['action'] == 'delete'){
		$title = "Delete Time";

		if($billed_activities){
			$fields[] = "<DIV class='error'>You can not delete time entries that are attached to an invoice.</DIV>";
			$can_continue = FALSE;
		}else{
			$fields[] = "<DIV class='error'>Are you sure you want to delete these time entries? This action is irreversible!</DIV>";
		}
		
		if($_POST['save']){
			foreach($tas as $ta){
				if($ta->delete() === FALSE){
					$error_msg .= "Error removing activitiy id {$ta->id}\n";
					debug_message($ta->getLastError());	
				}
			}
			
			if(empty($error_msg)){
				goBack();
			}
		}
	}else{
		fatal_error("You must select an action to perform on the selected items!");
	}


}else{
	fatal_error("Unknown object type!");
}

?>
<? require('header.php'); ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<FORM NAME="batch_process" ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<INPUT NAME="action" TYPE="hidden" VALUE="<?= $_REQUEST['action'] ?>">
<INPUT NAME="type" TYPE="hidden" VALUE="<?= $_REQUEST['type'] ?>">
<INPUT NAME="id_list" TYPE="hidden" VALUE="<?= join(',', $_REQUEST['ids']) ?>">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD COLSPAN="2" CLASS="form_header_cell"><?= $title ?></TD>
</TR>
<? foreach($fields as $label => $field){ 
	if(is_numeric($label) || empty($label)){ ?>
<TR>
	<TD CLASS="form_field_cell" COLSPAN="2">
		<?= $field ?>
	</TD>
</TR>
<?	}else{ ?>
<TR>
	<TD CLASS="form_field_header_cell"><?= $label ?></TD>
	<TD CLASS="form_field_cell">
		<?= $field ?>
	</TD>
</TR>	
<?	} 
} 
if($can_continue){ ?>
<TR>
	<TD COLSPAN="2" CLASS="form_field_cell">
		<DIV ALIGN="right"><INPUT TYPE="submit" CLASS="button" NAME="save" VALUE="Continue"></DIV>
	</TD>
</TR>
<? } ?>
</TABLE>
</FORM>
</div></div>
<? require('footer.php'); ?>