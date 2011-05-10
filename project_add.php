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
$company = new SI_Company();
$project = new SI_Project();

$title = "Create Project";

if($_POST['save']){
	$project->owner_id = $loggedin_user->id;
	$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
	$project->updateFromAssocArray($_POST);
	if($project->add()){
		foreach($_POST['tasks'] as $task_data){
			if(!empty($task_data['name'])){
				$task = new SI_Task();
				$task->name = $task_data['name'];
				$task->billable = 'D';
				$task->task_status_id = $task_data['status'];
				$task->task_priority_id = $task_data['priority'];
				$task->project_id = $project->id;
				if(!$task->add()){
					$error_msg .= "Error adding task {$task_data['name']} to project\n";
					debug_message($task->getLastError());
				}
			}
		}
		if(empty($error_msg)){
			goBack();
		}
	}else{
		$error_msg .= "Error adding Project!\n";
		debug_message($project->getLastError());
	}
}

require('header.php'); 
?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Create New Project</a>
<div>
<form name="project" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell">
		<select name="company_id" class="input_text" tabindex="1">
			<option value="0">None</option>
			<?= SI_Company::getSelectTags($project->company_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Project Name:</td>
	<td class="form_field_cell"><input name="name" tabindex="2" class="input_text" size="45" type="text" value=""></td>
</tr>
<tr>
	<td class="form_field_header_cell">Project Status:</td>
	<td class="form_field_cell">
		<select name="project_status_id" class="input_text" tabindex="3">
			<?= SI_ProjectStatus::getSelectTags() ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Project Priority:</td>
	<td class="form_field_cell">
		<select name="project_priority_id" class="input_text" tabindex="4">
			<?= SI_ProjectPriority::getSelectTags() ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Default Item Code:</td>
	<td class="form_field_cell">
		<select name="default_item_code_id" class="input_text" tabindex="5">
			<option value="0">None</option>
			<?= SI_ItemCode::getSelectTags() ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Billable Type</td>
	<td class="form_field_cell">
		<input name="billable" type="radio" value="Y" tabindex="6" checked>Hourly&nbsp;
		<input name="billable" type="radio" value="N" tabindex="7">Non-billable&nbsp;
		<input name="billable" type="radio" value="S" tabindex="8">Flat Rate&nbsp;
	</td>
</tr>
<tr>
	<td colspan="2">
		<div class="tableContainer">
			<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
			<img src="images/arrow_down.jpg" alt="Hide table" />Add Tasks</a>
			<div>
			<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="100%">
				<tr>
					<th class="dg_header_cell">Name</th>
					<th class="dg_header_cell">Status</th>
					<th class="dg_header_cell">Priority</th>
				</tr>
				<tbody id="bodyId1">
			<? for($i = 0; $i < 5; $i++){ ?>
				<tr>
					<td class="dg_data_cell_1"><input name="tasks[<?= $i ?>][name]" tabindex="<?= 9 + $i?>" size="35" type="text" class="input_text"></td>
					<td class="dg_data_cell_1">
						<SELECT NAME="tasks[<?= $i ?>][status]" CLASS="input_text" tabindex="<?= 10 + $i?>">
							<?= SI_TaskStatus::getSelectTags() ?>
						</SELECT>
					</td>
					<td class="dg_data_cell_1">
						<SELECT NAME="tasks[<?= $i ?>][priority]" CLASS="input_text" tabindex="<?= 11 + $i?>">
							<?= SI_TaskPriority::getSelectTags() ?>
						</SELECT>
					</td>
				</tr>
			<? }?>
				</tbody>
			</table>
			</div>
		</div>
		
	</td>
</tr>
<tr>
	<td colspan="2"><input type="submit" class="button" name="save" value="Add Project"></td>
</tr>
</table>
</form>
</div>
</div>
<? require('footer.php') ?>
