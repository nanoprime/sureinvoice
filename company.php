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

require_once('includes/SI_Company.php');
require_once('includes/SI_RateStructure.php');
$title = '';
$company = new SI_Company();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Company";
	
	if($_POST['save']){
		$company->updateFromAssocArray($_POST);
		if($company->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Company!\n";
			debug_message($company->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Company";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$company->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving company information!\n";
			debug_message($company->getLastError());
		}
	}

	if($_POST['save']){
		$company->updateFromAssocArray($_POST);
		if($company->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Company!\n";
			debug_message($company->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Company";

	if(!$company->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving company information!\n";
		debug_message($company->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($company->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Company!\n";
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
<? if($_REQUEST['mode'] == "delete"){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the Company named <b><?= $company->name ?></b>?<br><br>
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
<? }else{?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell"><input name="name" class="input_text" size="25" type="text" value="<?= $company->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 1:</td>
	<td class="form_field_cell"><input name="address1" class="input_text" size="35" type="text" value="<?= $company->address1 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 2:</td>
	<td class="form_field_cell"><input name="address2" class="input_text" size="35" type="text" value="<?= $company->address2 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">City:</td>
	<td class="form_field_cell"><input name="city" class="input_text" size="35" type="text" value="<?= $company->city ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">State:</td>
	<td class="form_field_cell"><input name="state" class="input_text" size="5" type="text" value="<?= $company->state ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Zip:</td>
	<td class="form_field_cell"><input name="zip" class="input_text" size="10" type="text" value="<?= $company->zip ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Phone:</td>
	<td class="form_field_cell"><input name="phone" class="input_text" size="25" type="text" value="<?= $company->phone ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Fax:</td>
	<td class="form_field_cell"><input name="fax" class="input_text" size="25" type="text" value="<?= $company->fax ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Rate Structure:</td>
	<td class="form_field_cell">
		<select name="rate_structure_id" class="input_text">
			<option value="0">No Rate Structure</option>
			<?= SI_RateStructure::getSelectTags($company->rate_structure_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Created On:</td>
	<td class="form_field_cell"><?= $company->created_ts ? date("D M jS, Y \a\\t h:i:s A", $company->created_ts) : "" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Last Updated:</td>
	<td class="form_field_cell"><?= $company->updated_ts ? date("D M jS, Y \a\\t h:i:s A", $company->updated_ts) : "" ?></td>
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