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

require_once('includes/SI_ItemCode.php');
require_once('includes/SI_Account.php');
$title = '';
$item_code = new SI_ItemCode();

// Clean up price
if(!empty($_POST['price'])){
	$_POST['price'] = preg_replace('/[^0-9\.]/','', $_POST['price']);
}

// Clean up cost
if(!empty($_POST['cost'])){
	$_POST['cost'] = preg_replace('/[^0-9\.]/','', $_POST['cost']);
}

if($_REQUEST['mode'] == 'add'){
	$title = "Add Item Code";
	
	if($_POST['save']){
		$item_code->updateFromAssocArray($_POST);
		if($item_code->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Item Code!\n";
			debug_message($item_code->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Item Code";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$item_code->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving item_code information!\n";
			debug_message($item_code->getLastError());
		}
	}

	if($_POST['save']){
		$item_code->updateFromAssocArray($_POST);
		if($item_code->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Item Code!\n";
			debug_message($item_code->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Item Code";

	if(!$item_code->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving item_code information!\n";
		debug_message($item_code->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($item_code->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Item Code!\n";
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
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_header_cell"><?= $title ?></td>
</tr>
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the Item Code named <b><?= $item_code->code ?></b>?<br><br>
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
<?}else{?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Code:</td>
	<td class="form_field_cell"><input name="code" class="input_text" size="15" type="text" value="<?= $item_code->code ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Description:</td>
	<td class="form_field_cell"><input name="description" class="input_text" size="40" type="text" value="<?= $item_code->description ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Income Account:</td>
	<td class="form_field_cell">
		<select name="income_account_id">
			<option value="0">Select account...</option>
			<?= SI_Account::getSelectTags($item_code->income_account_id) ?>
		</select>	
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Expense Account:</td>
	<td class="form_field_cell">
		<select name="expense_account_id">
			<option value="0">Select account...</option>
			<?= SI_Account::getSelectTags($item_code->expense_account_id) ?>
		</select>	
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Cost:</td>
	<td class="form_field_cell"><input name="cost" class="input_text" size="10" type="text" value="<?= $item_code->cost ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Default Price:</td>
	<td class="form_field_cell"><input name="price" class="input_text" size="10" type="text" value="<?= $item_code->price ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Taxable:</td>
	<td class="form_field_cell">
		<input name="taxable" type="radio" value="Y" <?= checked($item_code->taxable, "Y") ?>>Yes&nbsp;
		<input name="taxable" type="radio" value="N" <?= checked($item_code->taxable, "N") ?>>No&nbsp;
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
