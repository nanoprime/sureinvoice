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

require_once('includes/Notification.php');
$title = '';
$notification = new Notification();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Notification";

	if($_POST['save']){
		$notification->updateFromAssocArray($_POST);
		if($notification->add()){
			if(!empty($_POST['new_address'])){
				if($notification->addAddress($_POST['new_address']) === FALSE){
					$error_msg .= "Error adding new address to notification!";
					debug_message($notification->getLastError());
				}
			}

			if(!empty($_POST['new_macro_name'])){
				if($notification->addMacro($_POST['new_macro_name'], $_POST['new_macro_description']) === FALSE){
					$error_msg .= "Error adding new macro to notification!";
					debug_message($notification->getLastError());
				}
			}

			if(empty($error_msg)){
				goBack();
			}

		}else{
			$error_msg .= "Error adding Notification!\n";
			debug_message($notification->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Notification";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$notification->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving notification information!\n";
			debug_message($notification->getLastError());
		}
	}

	if($_POST['save']){
		$notification->updateFromAssocArray($_POST);
		if($notification->update()){
			if(!empty($_POST['new_address'])){
				if($notification->addAddress($_POST['new_address']) === FALSE){
					$error_msg .= "Error adding new address to notification!";
					debug_message($notification->getLastError());
				}
			}

			if(!empty($_POST['new_macro_name'])){
				if($notification->addMacro($_POST['new_macro_name'], $_POST['new_macro_description']) === FALSE){
					$error_msg .= "Error adding new macro to notification!";
					debug_message($notification->getLastError());
				}
			}

			if(empty($error_msg)){
				goBack();
			}
		}else{
			$error_msg .= "Error updating Notification!\n";
			debug_message($notification->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Notification";

	if(!$notification->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving notification information!\n";
		debug_message($notification->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($notification->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Notification!\n";
			}
		}else{
			goBack();
		}
	}
}else if($_REQUEST['mode'] == 'delete_address'){
	$title = "Delete Email Address";

	$na = new NotificationAddress();
	if($na->delete($_REQUEST['address_id'])){
		header("Location: ".getCurrentURL(null, false)."?mode=edit&id=".$_REQUEST['id']."\r\n");
		exit();
	}else{
		$error_msg .= "Error deleting email address!\n";
		debug_message($na->getLastError());
	}
}else if($_REQUEST['mode'] == 'delete_macro'){
	$title = "Delete Notification Macro";

	$nm = new NotificationMacro();
	if($nm->delete($_REQUEST['macro_id'])){
		header("Location: ".getCurrentURL(null, false)."?mode=edit&id=".$_REQUEST['id']."\r\n");
		exit();
	}else{
		$error_msg .= "Error deleting macro!\n";
		debug_message($nm->getLastError());
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
<table border="0" cellspacing="0" cellpadding="0" class="form_table" style="width:100%;">
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the Notification named <b><?= $notification->name ?></b>?<br><br>
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
	<td class="form_field_cell"><input name="name" class="input_text" size="25" type="text" value="<?= $notification->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Description:</td>
	<td class="form_field_cell"><textarea name="description" cols="50" class="input_text"><?= $notification->description ?></textarea></td>
</tr>
<tr>
	<td class="form_field_header_cell">From Address:</td>
	<td class="form_field_cell"><input name="from_address" class="input_text" size="25" type="text" value="<?= $notification->from_address ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Subject:</td>
	<td class="form_field_cell"><input name="subject" class="input_text" size="45" type="text" value="<?= $notification->subject ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Email:</td>
	<td class="form_field_cell"><textarea name="email" rows="20" cols="50" class="input_text"><?= $notification->email ?></textarea></td>
</tr>
<tr>
	<td class="form_field_header_cell">Active:</td>
	<td class="form_field_cell">
		<input name="active" type="radio" value="Y" <?= checked($notification->active, "Y") ?>>Yes&nbsp;
		<input name="active" type="radio" value="N" <?= checked($notification->active, "N") ?>>No&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">E-Mail Addresses:</td>
	<td class="form_field_cell">
<?  $nas = $notification->addresses;
		$na_count = count($nas);
		if($na_count > 0){
			for($i=0; $i<$na_count; $i++){ ?>
		<li class="taskitem"><?= $nas[$i]->address ?>&nbsp;&nbsp;
			<a href="<?= $_SERVER['PHP_SELF']."?id=".$notification->id."&mode=delete_address&address_id=".$nas[$i]->id ?>">
			<img src="images/delete_small.gif" border="0" width="13" height="13" align="middle"/></a>&nbsp;
		</li>
<?  	}
		}else{?>
		<b>No Addresses Configured</b>
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Add Email Address:</td>
	<td class="form_field_cell">
		<input name="new_address" class="input_text" size="35" type="text">
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Available Macros:</td>
	<td class="form_field_cell">
<?  $nms = $notification->macros;
		$nm_count = count($nms);
		if($nm_count > 0){
			for($i=0; $i<$nm_count; $i++){ ?>
		<li class="taskitem"><?= $nms[$i]->name ?>&nbsp;&nbsp;-&nbsp;&nbsp;<?= $nms[$i]->description ?>
			<a href="<?= $_SERVER['PHP_SELF']."?id=".$notification->id."&mode=delete_macro&macro_id=".$nms[$i]->id ?>">
			<img src="images/delete_small.gif" border="0" width="13" height="13" align="middle"/></a>&nbsp;
		</li>
<?  	}
		}else{?>
		<b>No Macros Configured</b>
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Add Macro:</td>
	<td class="form_field_cell">
		<input name="new_macro_name" class="input_text" size="20" type="text"><br>
		<input name="new_macro_description" class="input_text" size="45" type="text">
	</td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" class="button" name="save" value="Save"></div>
	</td>
</tr>
</table>
	</div>
</div>
<?} //if mode==delete?>
</form>
<? require('footer.php'); ?>