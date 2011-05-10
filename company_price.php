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
require_once('includes/SI_ItemCode.php');
require_once('includes/SI_CompanyPrice.php');
$title = '';
$company_price = new SI_CompanyPrice();

$company = new SI_Company();

// Clean up price
if(!empty($_POST['price'])){
	$_POST['price'] = preg_replace('/[^0-9\.]/','', $_POST['price']);
}

// Clean up tax_rate
if(!empty($_POST['tax_rate'])){
	$_POST['tax_rate'] = preg_replace('/[^0-9\.]/','', $_POST['tax_rate']);
}

if($_REQUEST['mode'] == 'add'){
	if(!isset($_REQUEST['company_id']) || $_REQUEST['company_id'] <= 0){
		fatal_error("a company_id must be provided to add company price");	
	}

	if($company->get($_REQUEST['company_id']) === FALSE){
		$error_msg .= "Error getting company!\n";
		debug_message($company->getLastError());	
	}

	$company_price->company_id = $company->id;
	$title = "Add Company Price";
	
	if($_POST['save']){
		$company_price->updateFromAssocArray($_POST);
		if($company_price->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Company Price!\n";
			debug_message($company_price->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	if(!isset($_REQUEST['company_id']) || $_REQUEST['company_id'] <= 0 ||
	   !isset($_REQUEST['item_code_id']) || $_REQUEST['item_code_id'] <= 0){
		fatal_error("A company_id and item_code_id must be provided to edit company price");	
	}

	if($company_price->get($_REQUEST['company_id'], $_REQUEST['item_code_id']) === FALSE){
		$error_msg .= "Error getting company price!\n";
		debug_message($company_price->getLastError());	
	}

	if($company->get($company_price->company_id) === FALSE){
		$error_msg .= "Error getting company!\n";
		debug_message($company->getLastError());	
	}

	$title = "Edit Company Price";
	if($_POST['save']){
		$company_price->updateFromAssocArray($_POST);
		if($company_price->update()){
			goBack();
		}else{
			$error_msg .= "Error updating Company Price!\n";
			debug_message($company_price->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Company Price";

	if(!isset($_REQUEST['company_id']) || $_REQUEST['company_id'] <= 0 ||
	   !isset($_REQUEST['item_code_id']) || $_REQUEST['item_code_id'] <= 0){
		fatal_error("A company_id and item_code_id must be provided to delete a company price");	
	}

	if(!$company_price->get($_REQUEST['company_id'], $_REQUEST['item_code_id'])){
		$error_msg .= "Error retrieving company_price information!\n";
		debug_message($company_price->getLastError());
	}

	if($company->get($company_price->company_id) === FALSE){
		$error_msg .= "Error getting company!\n";
		debug_message($company->getLastError());	
	}

	if($_POST['confirm']){
		if($_POST['confirm'] == "Yes"){
			if($company_price->delete($_REQUEST['company_id'], $_REQUEST['item_code_id'])){
				goBack();
			}else{
				$error_msg .= "Error deleting Company Price!\n";
				debug_message($company_price->getLastError());
			}
		}else{
			goBack();
		}
	}
}else{
	$title = "Invalid Mode";
	$error_msg .= "Error: Invalid mode!\n";
}

$item_code = new SI_ItemCode();
$all_item_codes = $item_code->retrieveSet();
if($all_item_codes === FALSE){
	$error_msg .= "Could not retreive item code list";
	debug_message($item_code->getLastError());
}
?>
<? require('header.php'); ?>
<script>
	var taxable = new Array();
<?	for($i = 0; $i <= count($all_item_codes); $i++){
		if(!empty($all_item_codes[$i]->id)){
			print("taxable[".$all_item_codes[$i]->id."] = \"".$all_item_codes[$i]->taxable."\";\n");
		} 
	} ?>
	
	function updateTaxRate(){
		var oTaxRate = document.getElementById('tax_rate');
		var oCode = document.getElementById('item_code_id');
		var oMsg = document.getElementById('non_taxable_message');
		var oField = document.getElementById('tax_field');
		if(taxable[oCode.options[oCode.selectedIndex].value] == 'Y'){
			oTaxRate.disabled = false;
			oMsg.style.display = 'none';
			oField.style.display = 'inline';
		}else{
			oTaxRate.disabled = true;
			oMsg.style.display = 'inline';
			oField.style.display = 'none';
		} 	
	}
</script>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="company_id" type="hidden" value="<?= $company_price->company_id ?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table" width="200">
	<tr>
		<td class="form_field_cell" colspan="2">
			<b><?= $company->name ?></b><br>
			<?= $company->address1.( !empty($company->address2) ? '<br>'.$company->address2 : '' )?><br>
			<?= $company->city.', '.$company->state.'   '.$company->zip ?>
			<div align="right"><a href="company.php?mode=edit&id=<?= $company->id ?>">Update</a></div>
		</td>
	</tr>
</table>
	</div>
</div>

<?if($_REQUEST['mode'] == "delete"){?>
<input name="item_code_id" type="hidden" value="<?= $company_price->item_code_id ?>">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_cell">
		<br>Are you sure you want to delete the Company Price for item code <strong><?= $company_price->code ?></strong>?<br /><br />
		<span class="error">CAUTION:</span>This action is irreversible.<br /><br />
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
	<td class="form_field_header_cell">Item Code:</td>
	<td class="form_field_cell">
		<select name="item_code_id" id="item_code_id" class="input_text" onchange="updateTaxRate()">
			<?= SI_ItemCode::getSelectTags($company_price->item_code_id) ?>
		</select>	
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Price:</td>
	<td class="form_field_cell"><input name="price" class="input_text" size="10" type="text" value="<?= $company_price->price ?>"></td>
</tr>
<tr id="tax_rate_row">
	<td class="form_field_header_cell">Tax Rate:</td>
	<td class="form_field_cell">
		<span id="tax_field" style="display: none;"><input name="tax_rate" id="tax_rate" class="input_text" size="10" type="text" value="<?= $company_price->tax_rate ?>">%</span>
		<span id="non_taxable_message" style="display: none;" class="error">Non Taxable</span>
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
<script>
updateTaxRate();
</script>
<?} //if mode==delete
?>
</form>
<? require('footer.php'); ?>
