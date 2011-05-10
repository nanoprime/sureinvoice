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

require_once('includes/SI_RateStructure.php');
require_once('includes/SI_ItemCode.php');
$title = '';
$rate_structure = new SI_RateStructure();

// Clean up price
if(!empty($_POST['price'])){
	$_POST['price'] = preg_replace('/[^0-9\.]/','', $_POST['price']);
}

// Clean up cost
if(!empty($_POST['cost'])){
	$_POST['cost'] = preg_replace('/[^0-9\.]/','', $_POST['cost']);
}

// Handle minor requests
if($_REQUEST['mode'] == 'delete_item_code'){
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$rate_structure->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving rate_structure information!\n";
			debug_message($rate_structure->getLastError());
		}
	}

	if($rate_structure->deleteItemCode($_REQUEST['item_code_id']) === FALSE){
		$error_msg .= "Error removing item code!\n";
		debug_message($rate_structure->getLastError());
	}

	$_REQUEST['mode'] = 'edit';	
}elseif($_REQUEST['mode'] == 'delete_line'){
	$line = new SI_RateStructureLine();
	if($line->delete($_REQUEST['line_id']) === FALSE){
		$error_msg .= "Error removing line!\n";
		debug_message($line->getLastError());	
	}
	
	$_REQUEST['mode'] = 'edit';
}

if($_REQUEST['mode'] == 'add'){
	$title = "Add Rate Structure";
	
	if($_POST['save']){
		$rate_structure->updateFromAssocArray($_POST);
		if($rate_structure->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Rate Structure!\n";
			debug_message($rate_structure->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Rate Structure";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$rate_structure->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving rate_structure information!\n";
			debug_message($rate_structure->getLastError());
		}
	}
	$item_codes = $rate_structure->getItemCodes();
	$lines = $rate_structure->getLines();
	
	if($_POST['save']){
		$rate_structure->updateFromAssocArray($_POST);
		if($rate_structure->update()){
			if($_POST['new_item_code_id'] != 0){
				if($rate_structure->addItemCode($_POST['new_item_code_id']) === FALSE){
					$error_msg .= "Error adding new item code!\n";
					debug_message($rate_structure->getLastError());
				}
				$item_codes = $rate_structure->getItemCodes();
			}
			
			if(!empty($_POST['new_high']) || !empty($_POST['new_low']) || !empty($_POST['new_discount'])){
				if(empty($_POST['new_discount']) || (empty($_POST['new_low']) && empty($_POST['new_high']))){
					$error_msg .= "You must enter a value for high, low and discount to add a new structure line!\n";	
				}else{
					if(empty($_POST['new_low'])) $_POST['new_low'] = 0;
					if(empty($_POST['new_high']) || trim($_POST['new_high']) == '+') $_POST['new_high'] = 0;
					$_POST['new_discount'] = trim($_POST['new_discount']);
					if($_POST['new_discount'][0] != '-') $_POST['new_discount'] = '-'.$_POST['new_discount']; 
					$line = new SI_RateStructureLine();
					$line->high = (int)$_POST['new_high'];	
					$line->low = (int)$_POST['new_low'];	
					$line->discount = (float)$_POST['new_discount'];
					$line->rate_structure_id = $rate_structure->id;
					if($rate_structure->validateNewLine($line) === FALSE){
						$error_msg .= $rate_structure->getLastError();
					}else{
						if($line->add() === FALSE){
							$error_msg .= "Error adding new structure line!\n";
							debug_message($line->getLastError());	
						}
						$lines = $rate_structure->getLines();
					}
				}
			}
			
			if(empty($error_msg) && $_POST['save'] == 'Save'){
				goBack();
			}
		}else{
			$error_msg .= "Error updating Rate Structure!\n";
			debug_message($rate_structure->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Rate Structure";

	if(!$rate_structure->get($_REQUEST['id'])){
		$error_msg .= "Error retrieving rate_structure information!\n";
		debug_message($rate_structure->getLastError());
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($rate_structure->delete($_REQUEST['id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Rate Structure!\n";
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
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_header_cell"><?= $title ?></td>
</tr>
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the Rate Structure named <b><?= $rate_structure->name ?></b>?<br><br>
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
<?}else{?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell"><input name="name" class="input_text" size="50" type="text" value="<?= $rate_structure->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Type:</td>
	<td class="form_field_cell">
		<select name="type">
			<option value="0">Select Type...</option>
			<?= SI_RateStructure::getTypeSelectTags($rate_structure->type) ?>
		</select>	
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Discount Item Code:</td>
	<td class="form_field_cell">
		<select name="discount_item_code_id">
			<option value="0">Select Item Code...</option>
			<?= SI_ItemCode::getSelectTags($rate_structure->discount_item_code_id) ?>
		</select>	
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
<?	if($_REQUEST['mode'] == 'edit'){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Associated Item Codes</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Code</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Description</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($item_codes); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $item_codes[$i]->code ?></td>
		<td class="dg_data_cell_1"><?= $item_codes[$i]->description ?></td>
		<td class="gridActions">
			<a class="link1" href="rate_structure.php?mode=delete_item_code&id=<?= $_REQUEST['id'] ?>&item_code_id=<?= $item_codes[$i]->id ?>"><img src="images/delete.gif" width="16" height="16" alt="Delete Item Code" title="Delete Item Code" border="0" /></a>
		</td>
	</tr>
<? }?>
</tbody>
	<tr>
		<td colspan="3" class="dg_data_cell_1">
			<select name="new_item_code_id">
				<option value="0">Select Item Code...</option>
				<?= SI_ItemCode::getSelectTags(NULL, $rate_structure->item_code_ids) ?>
			</select>
			<input type="submit" class="button" name="save" value="Add">
		</td>
	</tr>
</table>
	</div>
</div>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Structure Lines</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Low</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">High</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Discount/Hour</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($lines); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $lines[$i]->low ?></td>
		<td class="dg_data_cell_1"><?= $lines[$i]->high == 0 ? '+' : $lines[$i]->high ?></td>
		<td class="dg_data_cell_1"><?= $lines[$i]->discount ?></td>
		<td class="gridActions">
			<a class="link1" href="rate_structure.php?mode=delete_line&id=<?= $_REQUEST['id'] ?>&line_id=<?= $lines[$i]->id ?>"><img src="images/delete.gif" width="16" height="16" alt="Delete Item Code" title="Delete Item Code" border="0" /></a>
		</td>
	</tr>
<? }?>
</tbody>
	<tr>
		<td class="dg_data_cell_1"><input type="text" name="new_low" size="10"/></td>
		<td class="dg_data_cell_1"><input type="text" name="new_high" size="10"/></td>
		<td class="dg_data_cell_1"><input type="text" name="new_discount" size="10"/></td>
		<td class="dg_data_cell_1"><input type="submit" class="button" name="save" value="Add"></td>
	</tr>
</table>
	</div>
</div>
<?	} ?>
<?} ?>
</form>
<? require('footer.php'); ?>