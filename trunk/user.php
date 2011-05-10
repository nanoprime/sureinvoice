<?php
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

require_once('includes/SI_User.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_UserType.php');
$title = '';
$user = new SI_User();

// Clean up hourly_rate
if(!empty($_POST['hourly_rate'])){
	$_POST['hourly_rate'] = preg_replace('/[^0-9\.]/','', $_POST['hourly_rate']);
}

// Clean up salary
if(!empty($_POST['salary'])){
	$_POST['salary'] = preg_replace('/[^0-9\.]/','', $_POST['salary']);
}

if($_REQUEST['mode'] == 'add'){
	$title = "Add User";
	
	if($_POST['save']){
		$_POST['password'] = md5($_POST['password']);
		$user->updateFromAssocArray($_POST);
		if($user->add()){
			if($user->updateRights($_POST['rights'])){
				goBack();
			}else{
				$error_msg .= "Error updating user rights!\n";
				debug_message($user->getLastError());	
			}
		}else{
			$error_msg .= "Error adding User!\n";
			debug_message($user->getLastError());
		}		
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit User";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$user->get($_REQUEST['id'])){
			$error_msg .= "Error retrieving user information!\n";
			debug_message($user->getLastError());
		}
	}

	if($_POST['save']){
		if(!empty($_POST['password']))
			$_POST['password'] = md5($_POST['password']);
		else
			unset($_POST['password']);
			
		$user->updateFromAssocArray($_POST);
		if($user->update()){
			if($user->updateRights($_POST['rights'])){
				goBack();
			}else{
				$error_msg .= "Error updating user rights!\n";
				debug_message($user->getLastError());	
			}
		}else{
			$error_msg .= "Error updating User!\n";
			debug_message($user->getLastError());
		}	
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete User";
	if($user->delete($_REQUEST['id'])){
		goBack();
	}else{
		$error_msg .= "Error deleting User!\n";
		debug_message($user->getLastError());
	}
}else{
	$title = "Invalid Mode";
	$error_msg .= "Error: Invalid mode!\n";
}

$js_onLoad = "setRateType()";
?>
<?php require('header.php'); ?>
<script>
function setRateType(){
	var rate_type = document.user.rate_type.options[document.user.rate_type.selectedIndex].value;
	if(rate_type == 'SALARY'){
		document.user.salary.disabled = false;
		document.user.hourly_rate.disabled = true;
	}else if(rate_type == 'HALF_CUST_RATE'){
		document.user.salary.disabled = true;
		document.user.hourly_rate.disabled = true;
	}else{
		document.user.salary.disabled = true;
		document.user.hourly_rate.disabled = false;
	}
}
</script>
<form name="user" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">First Name:</td>
	<td class="form_field_cell"><input name="first_name" class="input_text" size="25" type="text" value="<?= $user->first_name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Last Name:</td>
	<td class="form_field_cell"><input name="last_name" class="input_text" size="25" type="text" value="<?= $user->last_name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell">
		<select name="company_id" class="input_text">
			<?= SI_Company::getSelectTags($user->company_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 1:</td>
	<td class="form_field_cell"><input name="address1" class="input_text" size="35" type="text" value="<?= $user->address1 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 2:</td>
	<td class="form_field_cell"><input name="address2" class="input_text" size="35" type="text" value="<?= $user->address2 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">City:</td>
	<td class="form_field_cell"><input name="city" class="input_text" size="35" type="text" value="<?= $user->city ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">State:</td>
	<td class="form_field_cell"><input name="state" class="input_text" size="5" type="text" value="<?= $user->state ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Zip:</td>
	<td class="form_field_cell"><input name="zip" class="input_text" size="10" type="text" value="<?= $user->zip ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">E-Mail:</td>
	<td class="form_field_cell"><input name="email" class="input_text" size="35" type="text" value="<?= $user->email ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Password:</td>
	<td class="form_field_cell"><input name="password" class="input_text" size="20" type="text" value=""></td>
</tr>
<tr>
	<td class="form_field_header_cell">Rate Type:</td>
	<td class="form_field_cell">
		<select name="rate_type" class="input_text" onchange="setRateType()">
			<?= SI_User::getRateTypeSelectTags($user->rate_type) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Annual Salary:</td>
	<td class="form_field_cell"><input name="salary" class="input_text" size="10" type="text" value="<?= $user->salary ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Hourly Rate:</td>
	<td class="form_field_cell"><input name="hourly_rate" class="input_text" size="10" type="text" value="<?= $user->hourly_rate ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Receive Invoices?</td>
	<td class="form_field_cell">
		<input name="invoiced" type="radio" value="Y" <?= checked($user->invoiced, "Y") ?>>Yes&nbsp;
		<input name="invoiced" type="radio" value="N" <?= checked($user->invoiced, "N") ?>>No&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Active:</td>
	<td class="form_field_cell">
		<input name="active" type="radio" value="Y" <?= checked($user->active, "Y") ?>>Yes&nbsp;
		<input name="active" type="radio" value="N" <?= checked($user->active, "N") ?>>No&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Type of User:</td>
	<td class="form_field_cell">
		<select name="user_type_id" class="input_text">
			<?= SI_UserType::getSelectTags($user->user_type_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">User Rights:</td>
	<td class="form_field_cell">
		<input type="checkbox" name="rights[admin]" value="1" <?= $user->hasRight('admin') ? 'CHECKED' : '' ?>/>&nbsp;Administrative Access<br>
		<input type="checkbox" name="rights[accounting]" value="1" <?= $user->hasRight('accounting') ? 'CHECKED' : '' ?>/>&nbsp;Accounting Access<br>  
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Created On:</td>
	<td class="form_field_cell"><?= $user->created_ts ? date("D M jS, Y \a\\t h:i:s A", $user->created_ts) : "" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Last Updated:</td>
	<td class="form_field_cell"><?= $user->updated_ts ? date("D M jS, Y \a\\t h:i:s A", $user->updated_ts) : "" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Last Login:</td>
	<td class="form_field_cell"><?= $user->last_login_ts ? date("D M jS, Y \a\\t h:i:s A", $user->last_login_ts) : "" ?></td>
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