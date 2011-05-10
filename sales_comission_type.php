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

require_once('includes/SI_SalesCommissionType.php');
$title = '';
$com_type = new SI_SalesCommissionType();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Sales Commission Type";
	
	if($_POST['save']){
		$com_type->updateFromAssocArray($_POST);
		if($com_type->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Sales Commission Type!\n";
			debug_message($com_type->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Sales Commission Type";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$com_type->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving Commission Type information!\n";
			debug_message($com_type->getLastError());
		}
	}

	if($_POST['save']){
		$com_type->updateFromAssocArray($_POST);
		if($com_type->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Sales Commission Type!\n";
			debug_message($com_type->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Sales Commission Type";

	if(!$com_type->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving Commission Type information!\n";
		debug_message($com_type->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($com_type->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Sales Commission Type!\n";
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
	<td class="form_header_cell"><?= $title ?></td>
</tr>
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the SalesCommissionType named <b><?= $com_type->name ?></b>?<br><br>
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
	<td class="form_field_cell"><input name="name" class="input_text" size="25" type="text" value="<?= $com_type->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Commission Type:</td>
	<td class="form_field_cell">
		<select name="type">
		<?= SI_SalesCommissionType::getTypeSelectTags($com_type->type) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Rate:</td>
	<td class="form_field_cell"><input name="rate" class="input_text" size="35" type="text" value="<?= $com_type->rate ?>"></td>
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