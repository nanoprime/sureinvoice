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
require_once('includes/SI_UserType.php');

checkLogin("admin");

$user_type = new SI_UserType();
$user_types = SI_UserType::retrieveSet();
if($user_types === FALSE){
	$error_msg = "Error getting user types!\n";
	debug_message($user_type->getLastError());
}

$title = "User Type Administration";

require('header.php') ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />User types</a><div>
	<div class="gridToolbar">
		  <a href="user_type.php?mode=add" style="background-image:url(images/new_invoice.png);">New user type</a>
	</div>
		<table border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td><a href="" onclick="return sortTable('bodyId', 0, 1, false)">ID</a></td>
				<td><a href="" onclick="return sortTable('bodyId', 1, 1, false)">Name</a></td>
				<td><a href="" onclick="return sortTable('bodyId', 2, 1, false)">Resource</a></td>
				<td><a href="" onclick="return sortTable('bodyId', 3, 1, false)">Start Page</a></td>
				<td>Edit</td>
				<td>Delete</td>		
			</tr>
			<tbody id="bodyId">
		<? for($i = 0; $i < count($user_types); $i++){ ?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td><?= $user_types[$i]->id ?></td>
				<td><?= $user_types[$i]->name ?></td>
				<td><?= $user_types[$i]->resource ?></td>
				<td><?= $user_types[$i]->start_page ?></td>
				<td><a href="user_type.php?mode=edit&id=<?= $user_types[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" title="Edit" /></a></td>
				<td><a href="user_type.php?mode=delete&id=<?= $user_types[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" /></a></td>
			</tr>
		<? }?>
		</tbody>
		</table>
	</div>
</div>
<? require('footer.php') ?>