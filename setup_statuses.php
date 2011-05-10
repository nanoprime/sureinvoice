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
require_once('includes/SI_ProjectStatus.php');
require_once('includes/SI_ProjectPriority.php');
require_once('includes/SI_TaskStatus.php');
require_once('includes/SI_TaskPriority.php');

checkLogin("admin");

$project_status = new SI_ProjectStatus();
$project_statuses = $project_status->retrieveSet();
if($project_statuses === FALSE){
	$error_msg .= "Error getting project statuses!\n";
	debug_message($project_status->getLastError());
}

$project_priority = new SI_ProjectPriority();
$project_prioirities = SI_ProjectPriority::retrieveSet();
if($project_prioirities === FALSE){
	$error_msg .= "Error getting project priorities!\n";
	debug_message($project_priority->getLastError());
}

$task_priority = new SI_TaskPriority();
$task_prioirities = $task_priority->retrieveSet();
if($task_prioirities === FALSE){
	$error_msg .= "Error getting list of task priorities!\n";
	debug_message($task_priority->getLastError());
}

$task_status = new SI_TaskStatus();
$task_statuses = SI_TaskStatus::retrieveSet();
if($task_statuses === FALSE){
	$error_msg .= "Error getting task statuses!\n";
	debug_message($task_status->getLastError());
}


$title = "Priority & Status Configuration";

if($_POST['save']){
	if(is_array($_POST['params'])){
		foreach($_POST['params'] as $param_name => $param_value){
			if(!empty($param_name)){
				$modified_config = new SI_Config();
				$modified_config->name = $param_name;
				$modified_config->value = $param_value;
				if($modified_config->update() === FALSE){
					$error_msg .= "Error updating configuration paramenter: $param_name\n";
					debug_message($modified_config->getLastError());
					break;
				}
			}
		}
	}
	if(empty($error_msg)){
		header("Location: ".getCurrentURL()."\r\n");
		exit();
	}
}

require('header.php') ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<div class="box">
<div class="boxTitle"><h3>Projects</h3>
<span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span>
</div><div class="boxContent">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td valign="top">
		<div class="tableContainer">
		<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
		<img src="images/arrow_down.jpg" alt="Hide table" />Statuses</a><div>
		<div class="gridToolbar">
			  <a href="project_status.php?mode=add" style="background-image:url(images/new_invoice.png);">New Status</a>
		</div>
		<table border="0" cellspacing="0" cellpadding="0">
			<tr>
				<th><a href="" onclick="return sortTable('bodyId1', 0, 1, false)">ID</a></th>
				<th><a href="" onclick="return sortTable('bodyId1', 1, 1, false)">Name</a></th>
				<th><a href="" onclick="return sortTable('bodyId1', 2, 1, false)">Completed</a></th>
				<th>Edit</th>
				<th>Delete</th>		
			</tr>
			<tbody id="bodyId1">
		<? for($i = 0; $i < count($project_statuses); $i++){ ?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td><?= $project_statuses[$i]->id ?></td>
				<td><?= $project_statuses[$i]->name ?></td>
				<td><?= $project_statuses[$i]->completed ?></td>
				<td class="gridActions">
					<a href="project_status.php?mode=edit&id=<?= $project_statuses[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" title="Edit" border="0" /></a>
				</td>
				<td class="gridActions">
					<a href="project_status.php?mode=delete&id=<?= $project_statuses[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" border="0" /></a>
				</td>		
			</tr>
		<? }?>
		</tbody>
		</table>
		<div>
		<b>Default Status:</b> 
		<select name="params[default_project_status_id]">
			<?= SI_ProjectStatus::getSelectTags($GLOBALS['CONFIG']['default_project_status_id']); ?>
		</select>
		</div>
		</div></div>	
	</td>
	<td valign="top">
		<div class="tableContainer">
		<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
		<img src="images/arrow_down.jpg" alt="Hide table" />Priorities</a><div>
		<div class="gridToolbar">
			  <a href="project_priority.php?mode=add" style="background-image:url(images/new_invoice.png);">New Priority</a>
		</div>
		<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th><a href="" onclick="return sortTable('bodyId2', 0, 1, false)">ID</a></th>
			<th><a href="" onclick="return sortTable('bodyId2', 1, 1, false)">Name</a></th>
			<th><a href="" onclick="return sortTable('bodyId2', 2, 1, false)">Level</a></th>
			<th>Edit</th>
			<th>Delete</th>		
		</tr>
		<tbody id="bodyId2">
		<? for($i = 0; $i < count($project_prioirities); $i++){ ?>
		<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
			<td><?= $project_prioirities[$i]->id ?></td>
			<td><?= $project_prioirities[$i]->name ?></td>
			<td><?= $project_prioirities[$i]->priority_level ?></td>
			<td class="gridActions">
				<a href="project_priority.php?mode=edit&id=<?= $project_prioirities[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" title="Edit" border="0" /></a>
			</td>
			<td class="gridActions">
				<a href="project_priority.php?mode=delete&id=<?= $project_prioirities[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" border="0" /></a>
			</td>		
		</tr>
		<? }?>
		</tbody>
		</table>
		<div>
		<b>Default Priority:</b> 
		<select name="params[default_project_priority_id]">
			<?= SI_ProjectPriority::getSelectTags($GLOBALS['CONFIG']['default_project_priority_id']); ?>
		</select>
		</div>
		</div></div>	
	</td>
</tr>
<tr>
	<td colspan="2"><div align="right"><input type="submit" class="button" name="save" value="Save Defaults"></div></td>
</tr>
</table>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>

<div class="box">
<div class="boxTitle"><h3>Tasks</h3>
<span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span>
</div><div class="boxContent">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td valign="top">
		<div class="tableContainer"><a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table"/>Statuses</a><div>
		<div class="gridToolbar">
			<a href="task_status.php?mode=add" style="background-image:url(images/new_invoice.png);">New Status</a>
		</div>
		<table border="0" cellspacing="0" cellpadding="0" >
			<tr>
				<th><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">ID</a></th>
				<th><a class="link1" href="" onclick="return sortTable('bodyId', 1, 1, false)">Name</a></th>
				<th><a class="link1" href="" onclick="return sortTable('bodyId', 2, 1, false)">Completed</a></th>
				<th>Edit</th>
				<th>Delete</th>			
			</tr>
			<tbody id="bodyId">
		<? for($i = 0; $i < count($task_statuses); $i++){ ?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td><?= $task_statuses[$i]->id ?></td>
				<td><?= $task_statuses[$i]->name ?></td>
				<td><?= $task_statuses[$i]->completed ?></td>
				<td class="gridActions">
					<a class="link1" href="task_status.php?mode=edit&id=<?= $task_statuses[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" title="Edit" border="0" /></a>
				</td>
				<td class="gridActions">
					<a class="link1" href="task_status.php?mode=delete&id=<?= $task_statuses[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" border="0" /></a>
				</td>			
			</tr>
		<? }?>
		</tbody>
		</table>
		<div>
		<b>Default Status:</b> 
		<select name="params[default_task_status_id]">
			<?= SI_TaskStatus::getSelectTags($GLOBALS['CONFIG']['default_task_status_id']); ?>
		</select>
		</div>
		</div></div>	
	</td>
	<td valign="top">
		<div class="tableContainer">
		<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Priorities</a><div>
		<div class="gridToolbar">
			  <a href="task_priority.php?mode=add" style="background-image:url(images/new_invoice.png);">New Priority</a>
		</div>
		<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">ID</a></th>
			<th><a href="" onclick="return sortTable('bodyId', 1, 1, false)">Name</a></th>
			<th><a href="" onclick="return sortTable('bodyId', 2, 1, false)">Level</a></th>
			<th>Edit</th>
			<th>Delete</th>		
		</tr>
		<tbody id="bodyId">
		<? for($i = 0; $i < count($task_prioirities); $i++){ ?>
		<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
			<td><?= $task_prioirities[$i]->id ?></td>
			<td><?= $task_prioirities[$i]->name ?></td>
			<td><?= $task_prioirities[$i]->priority_level ?></td>
			<td class="gridActions">
				<a href="task_priority.php?mode=edit&id=<?= $task_prioirities[$i]->id ?>"><img src="images/edit.png"  alt="Edit" title="Edit" border="0" /></a>
			</td>
			<td class="gridActions">
				<a href="task_priority.php?mode=delete&id=<?= $task_prioirities[$i]->id ?>"><img src="images/delete.png" alt="Delete" title="Delete" border="0" /></a>
			</td>		
		</tr>
		<? }?>
		</tbody>
		</table>
		<div>
		<b>Default Priority:</b> 
		<select name="params[default_task_priority_id]">
			<?= SI_TaskPriority::getSelectTags($GLOBALS['CONFIG']['default_task_priority_id']); ?>
		</select>
		</div>
		</div></div>	
	</td>
</tr>
<tr>
	<td colspan="2"><div align="right"><input type="submit" class="button" name="save" value="Save Defaults"></div></td>
</tr>
</table>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
</form>
<? require('footer.php') ?>