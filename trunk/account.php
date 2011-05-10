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
checkLogin('admin');

require_once('includes/SI_Account.php');
$title = '';
$account = new SI_Account();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Account";
	
	if($_POST['save']){
		$account->updateFromAssocArray($_POST);
		$account->id = $account->add();
		if($account->id !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Account!\n";
			debug_message($account->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Account";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$account->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving account information!\n";
			debug_message($account->getLastError());
		}
	}

	if($_POST['save']){
		$account->updateFromAssocArray($_POST);
		if($account->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Account!\n";
			debug_message($account->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Account";

	if(!$account->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving account information!\n";
		debug_message($account->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($account->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Account!\n";
			}
		}else{
			goBack();
		}
	}
}else{
	$title = "Invalid Mode";
	$error_msg .= "Error: Invalid mode!\n";
}

?>
<? require('header.php'); ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<?if($_REQUEST['mode'] == "delete"){?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the Account named <b><?= $account->name ?></b>?<br><br>
		<span class="error">CAUTION:</span>This action is irreversible.<br><br>
	</td>
</tr>
<tr>
	<td class="form_footer_cell">
		<div align="center">
			<input type="submit" class="button" name="confirm" value="Yes">&nbsp;&nbsp;
			<input type="submit" class="button" name="confirm" value="No">
		</div>
	</td>
</tr>
</table>
	</div>
</div>
<?}else{?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell"><input name="name" class="input_text" size="15" type="text" value="<?= $account->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Description:</td>
	<td class="form_field_cell"><input name="description" class="input_text" size="40" type="text" value="<?= $account->description ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Type:</td>
	<td class="form_field_cell"><input name="type" class="input_text" size="25" type="text" value="<?= $account->type ?>"></td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" class="button" name="save" value="Save"></div>
	</td>
</tr>	
</table>
	</div>
</div>
<?
} //if mode==delete
?>
</form>
<? require('footer.php'); ?>