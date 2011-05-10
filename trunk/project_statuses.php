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

checkLogin("admin");

$project_status = new SI_ProjectStatus();
$project_statuses = $project_status->retrieveSet();
if($project_statuses === FALSE){
	$error_msg .= "Error getting project statuses!\n";
	debug_message($project_status->getLastError());
}

$title = "Project Status Administration";

require('header.php') ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Project Statuses</a><div>
	<div class="gridToolbar">
		  <a href="project_status.php?mode=add" style="background-image:url(images/new_invoice.png);">New Project Status</a>
	</div>
		<table border="0" cellspacing="0" cellpadding="0">
			<tr>
				<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">ID</a></th>
				<th><a href="" onclick="return sortTable('bodyId', 1, 1, false)">Name</a></th>
				<th><a href="" onclick="return sortTable('bodyId', 2, 1, false)">Completed</a></th>
				<th>Edit</th>
				<th>Delete</th>		
			</tr>
			<tbody id="bodyId">
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

	</div>
</div>
<? require('footer.php') ?>