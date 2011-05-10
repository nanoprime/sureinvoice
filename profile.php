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
checkLogin();

require_once('includes/SI_User.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_UserType.php');
$title = '';
$user = new SI_User();

$title = "Update Profile";
if($loggedin_user->hasRight('admin') && isset($_REQUEST['id'])){
	if($user->get($_REQUEST['id']) === FALSE){
		$error_msg .= "Error getting user information!\n";
		debug_message($user->getLastError());
	}
}else{
	$user = &$loggedin_user;
}


if($_POST['save']){
	if(!empty($_POST['password_1']) || !empty($_POST['password_2'])){
		if($_POST['password_1'] != $_POST['password_2']){
			$error_msg .= "The passwords you enter do not match!";
		}else{
			$_POST['password'] = md5($_POST['password_1']);
		}
	}
	$user->updateFromAssocArray($_POST);
	if($user->update()){
		if(empty($error_msg)){
			goBack();
		}
	}else{
		$error_msg .= "Error updating your profile!\n";
		debug_message($user->getLastError());
	}
}

?>
<? require('header.php'); ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
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
	<td class="form_field_cell"><?= $user->company ?></td>
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
	<td class="form_field_cell"><input name="password_1" class="input_text" size="20" type="password" value=""></td>
</tr>
<tr>
	<td class="form_field_header_cell">Confirm Password:</td>
	<td class="form_field_cell"><input name="password_2" class="input_text" size="20" type="password" value=""></td>
</tr>
<tr>
	<td class="form_field_header_cell">Rate Type:</td>
	<td class="form_field_cell"><?= $user->rate_type ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Hourly Rate:</td>
	<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($user->hourly_rate, 2) ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Type of User:</td>
	<td class="form_field_cell"><?= $user->user_type ?></td>
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