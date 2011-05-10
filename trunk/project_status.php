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

require_once('includes/SI_ProjectStatus.php');
$title = '';
$project_status = new SI_ProjectStatus();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Project Status";
	
	if($_POST['save']){
		$project_status->updateFromAssocArray($_POST);
		if($project_status->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Project Status!\n";
		}		
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Project Status";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		$project_status->get($_REQUEST['id']);
	}

	if($_POST['save']){
		$project_status->updateFromAssocArray($_POST);
		if($project_status->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Project Status!\n";
		}	
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Project Status";
	if($project_status->delete($_REQUEST['id'])){
		goBack();
	}else{
		$error_msg .= "Error deleting Project Status!\n";
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
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="3" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell"><input name="name" class="input_text" size="25" type="text" value="<?= $project_status->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Completed Status?</td>
	<td class="form_field_cell">
		<input name="completed" type="radio" value="Y" <?= checked($project_status->completed, "Y") ?>>Yes&nbsp;
		<input name="completed" type="radio" value="N" <?= checked($project_status->completed, "N") ?>>No&nbsp;
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
</form>
<? require('footer.php'); ?>