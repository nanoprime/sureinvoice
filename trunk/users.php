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
require_once('includes/SI_User.php');

checkLogin("admin");

$user = new SI_User();
$_REQUEST['show_all'] = strtolower(substr($_REQUEST['show_all'],0,1)) == "y" ? TRUE : FALSE;

if($_REQUEST['show_all']){
	$clause = "WHERE u.deleted = 'N'";
}else{
	$clause = "WHERE u.active = 'Y' AND u.deleted = 'N'";
}
$users = $user->getAll("$clause ORDER BY first_name, last_name");
if($users === FALSE){
	$error_msg .= "Error getting users!\n";
	debug_message($user->getLastError());
}

$title = "User Administration";

require('header.php') ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Users</a>
<div>
	<div class="gridToolbar">
		<a href="user.php?mode=add" style="background-image:url(images/new_invoice.png);">New user</a>
		<a href="<?= $_SERVER['PHP_SELF']."?show_all=y" ?>" style="background-image:url(images/plus.png);">Show inactive</a>
		<a href="<?= $_SERVER['PHP_SELF']."?show_all=n" ?>" style="background-image:url(images/minus.png);">Hide inactive</a>
	</div>
	<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<th><a href="" onclick="return sortTable('bodyId', 1, 1, false)">Name</a></th>
			<th><a href="" onclick="return sortTable('bodyId', 2, 1, false)">Company</a></th>
			<th><a href="" onclick="return sortTable('bodyId', 3, 1, false)">Type</a></th>
			<th>Edit</th>
		    <th>Delete</th>
		</tr>
		<tbody id="bodyId">
	<? for($i = 0; $i < count($users); $i++){ ?>
		<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
			<td><a title="Resource Detail Center" href="home_resource.php?id=<?= $users[$i]->id ?>"><?= $users[$i]->first_name." ".$users[$i]->last_name ?></a></td>
			<td><?= $users[$i]->company ?></td>
			<td><?= $users[$i]->user_type ?></td>
			<td class="gridActions"><a href="user.php?mode=edit&id=<?= $users[$i]->id ?>"><img src="images/edit.png"  alt="Edit" title="Edit" border="0" /></a></td>
		    <td class="gridActions"><a href="user.php?mode=delete&id=<?= $users[$i]->id ?>" onclick="return(confirm('are you sure you want to delete user <?= $users[$i]->first_name." ".$users[$i]->last_name ?>?'));"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" border="0" /></a></td>
		</tr>
	<? }?>
	</tbody>
	</table>
	</div>
</div>
<? require('footer.php') ?>