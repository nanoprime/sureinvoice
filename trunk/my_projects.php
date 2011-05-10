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
require_once('includes/SI_Company.php');
require_once('includes/SI_Task.php');

checkLogin();

$project = new SI_Project();

if($loggedin_user->hasRight('admin') && isset($_REQUEST['user_id'])){
	$user_id = $_REQUEST['user_id'];
}else{
	$user_id = $loggedin_user->id;
}

if($_REQUEST['filter'] == "all"){
	$projects = $project->getMyProjects($user_id, FALSE);
}else{
	$_REQUEST['filter'] = "pending";
	$projects = $project->getMyProjects($user_id, TRUE);
}

if($projects === FALSE){
	$error_msg .= "Could not retrieve your project list!\n";
	debug_message($project->getLastError());
}

$task = new SI_Task();
$tasks = $task->getUpcoming($user_id);
if($tasks === FALSE){
	$error_msg .= "Could not retrieve Upcoming Tasks!\n";
	debug_message($task->getLastError());
}

$company = new SI_Company();
$companies = $company->getCompanysWithBalance();
if($companies === FALSE){
	$error_msg .= "Could not retrieve Outstanding Hours list!\n";
	debug_message($company->getLastError());
}

$user = new SI_User();
$users = $user->getUnpaidUsers();
if($users === FALSE){
	$error_msg .= "Could not retrieve Unpaid Users's list!\n";
	debug_message($user->getLastError());
}

$title = "My Projects";

require('header.php'); ?>
<script>
function reloadPage(selObj){
	var user_id = selObj.options[selObj.selectedIndex].value;
	window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter=<?= $_REQUEST['filter'] ?>&user_id="+user_id;
}
</script>
<div class="tableContainer">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />My projects</a>
	<div>
	<div class="gridToolbar">
		<a href="project.php?mode=add" style="background-image:url(images/new_invoice.png);">New Project</a>
<?	if($loggedin_user->hasRight('admin')){?>
		<select name="user_id" onchange="javascript:reloadPage(this)">
		<?= SI_User::getSelectTags($user_id) ?>
		</select>
<?	} //if admin 
?>		  
		<strong>&nbsp;&nbsp;Filter:&nbsp;</strong>
		<a style="background-image:url(images/filter.gif);" href="my_projects.php?filter=all<?= $loggedin_user->hasRight('admin') ? '&user_id='.$user_id : '' ?>">All</a>&nbsp;|&nbsp;
		<a style="background-image:url(images/filter.gif);" href="my_projects.php?filter=pending<?= $loggedin_user->hasRight('admin') ? '&user_id='.$user_id : '' ?>">Pending</a>
	</div>
	<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">Name</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 1, 0, false)">Company</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 2, 0, false)">Status</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 3, 0, false)">Due Date</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 4, 0, false)">Priority</a></th>
		<th>Options</td>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($projects); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><a title="Project Details" href="project_details.php?id=<?= $projects[$i]->id ?>"><?= $projects[$i]->name ?></a></td>
		<td><a title="Company Detail Center" href="company_detail.php?id=<?= $projects[$i]->company_id ?>"><?= $projects[$i]->company_name ?></a></td>
		<td><?= $projects[$i]->status ?></td>
		<td><?=  $projects[$i]->due_ts>0 ? date("n/j/y", $projects[$i]->due_ts) : "None" ?></td>
		<td><?= $projects[$i]->priority ?></td>
		<td>&nbsp;
			<a href="project_details.php?id=<?= $projects[$i]->id ?>"><img src="images/properties.gif" width="16" height="16" title="Project Details" border="0" /></a>
<?	if($projects[$i]->hasRights(PROJECT_RIGHT_EDIT)){?>
			&nbsp;|&nbsp;<a href="project.php?mode=edit&id=<?= $projects[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Project" border="0" /></a>
<?	}?>
<?	if($projects[$i]->hasRights(PROJECT_RIGHT_FULL)){?>
			&nbsp;|&nbsp;<a href="project.php?mode=delete&id=<?= $projects[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Project" border="0" /></a>&nbsp;
<?	}?>
		</td>
	</tr>
<? }?>
	</tbody>
	</table>
	</div>
</div>
<br><br>
<? if($loggedin_user->isDeveloper()){ ?>
<div class="tableContainer" style="clear:both;">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Upcoming Tasks</a>
	<div>
		<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th><a href="" onclick="return sortTable('bodyId1', 0, 1, false)">Project</a></th>
			<th><a href="" onclick="return sortTable('bodyId1', 1, 0, false)">Task</a></th>
			<th><a href="" onclick="return sortTable('bodyId1', 2, 0, false)">Status</a></th>
			<th><a href="" onclick="return sortTable('bodyId1', 3, 0, false)">Due Date</a></th>
			<th><a href="" onclick="return sortTable('bodyId1', 4, 0, false)">Priority</a></th>
			<th>Options</th>
		</tr>
		<tbody id="bodyId1">
<? for($i = 0; $i < count($tasks); $i++){ ?>
		<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
			<td><a title="Project Details" href="project_details.php?id=<?= $tasks[$i]->project_id ?>"><?= $tasks[$i]->project ?></a></td>
			<td><a title="Task Activities" href="task_activity.php?mode=add&task_id=<?= $tasks[$i]->id ?>"><?= $tasks[$i]->name ?></a></td>
			<td><?= $tasks[$i]->status ?></td>
			<td><?= $tasks[$i]->due_ts>0 ? date("n/j/y", $tasks[$i]->due_ts) : "None" ?></td>
			<td><?= $tasks[$i]->priority ?></td>
			<td>&nbsp;
				<a href="task_activity.php?mode=add&task_id=<?= $tasks[$i]->id ?>"><img src="images/activity_add.gif" width="16" height="16" title="Add Activity" border="0" /></a>&nbsp;|&nbsp;
				<a href="task_activities.php?task_id=<?= $tasks[$i]->id ?>"><img src="images/activity.gif" width="16" height="16" title="Task Activities" border="0" /></a>&nbsp;|&nbsp;
				<a href="project_task.php?mode=edit&id=<?= $tasks[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit Task" border="0" /></a>&nbsp;|&nbsp;
				<a href="project_task.php?mode=delete&id=<?= $tasks[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete Task" border="0" /></a>&nbsp;
			</td>
		</tr>
<? }?>
		</tbody>
		</table>
	</div>
</div>
<? } // end if developer ?>
<? require('footer.php') ?>
