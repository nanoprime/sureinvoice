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

require_once('includes/SI_Account.php');

$account = new SI_Account();
$accounts = $account->retrieveSet("ORDER BY name");
if($accounts === FALSE){
	$error_msg .= "Error getting accounts!\n";
	debug_message($account->getLastError());
}

$title = "Account Administration";

require('header.php') ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Accounts</a><div>
	<div class="gridToolbar">
		  <a href="account.php?mode=add" style="background-image:url(images/new_invoice.png);">New Account</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th><a href="" onclick="return sortTable('bodyId', 0, 1, false)">Name</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 1, 1, false)">Description</a></th>
		<th><a href="" onclick="return sortTable('bodyId', 2, 1, false)">Type</a></th>
		<th>Edit</th>
		<th>Delete</th>		
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($accounts); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td><?= $accounts[$i]->name ?></td>
		<td><?= $accounts[$i]->description ?></td>
		<td><?= $accounts[$i]->type ?></td>
		<td>
			<a href="account.php?mode=edit&id=<?= $accounts[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" title="Edit" border="0" /></a>
		</td>
		<td>
			<a href="account.php?mode=delete&id=<?= $accounts[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" border="0" /></a>
		</td>		
	</tr>
<? }?>
</tbody>
</table>
	</div>
</div>
<? require('footer.php') ?>