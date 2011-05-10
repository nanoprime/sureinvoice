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
require_once('includes/SI_Company.php');
checkLogin();

$title = '';
$company = new SI_Company();

$title = "Edit Company";
if($loggedin_user->isDeveloper() && !empty($_REQUEST['id'])){
	$id = $_REQUEST['id'];
}else{
	$id = $loggedin_user->company_id;
}

if(!$company->get($id)){
	$error_msg .= "Error retrieving company information!\n";
	debug_message($company->getLastError());
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
?>
<? require('header.php'); ?>
<FORM ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<INPUT NAME="id" TYPE="hidden" VALUE="<?= $id ?>">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD COLSPAN="2" CLASS="form_header_cell"><?= $title ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Name:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="name" CLASS="input_text" SIZE="25" TYPE="text" VALUE="<?= $company->name ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Address Line 1:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="address1" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $company->address1 ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Address Line 2:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="address2" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $company->address2 ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">City:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="city" CLASS="input_text" SIZE="35" TYPE="text" VALUE="<?= $company->city ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">State:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="state" CLASS="input_text" SIZE="5" TYPE="text" VALUE="<?= $company->state ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Zip:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="zip" CLASS="input_text" SIZE="10" TYPE="text" VALUE="<?= $company->zip ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Phone:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="phone" CLASS="input_text" SIZE="25" TYPE="text" VALUE="<?= $company->phone ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Fax:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="fax" CLASS="input_text" SIZE="25" TYPE="text" VALUE="<?= $company->fax ?>"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Created On:</TD>
	<TD CLASS="form_field_cell"><?= $company->created_ts ? date("D M jS, Y \a\\t h:i:s A", $company->created_ts) : "" ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Last Updated:</TD>
	<TD CLASS="form_field_cell"><?= $company->updated_ts ? date("D M jS, Y \a\\t h:i:s A", $company->updated_ts) : "" ?></TD>
</TR>
<TR>
	<TD COLSPAN="2" CLASS="form_field_cell">
		<DIV ALIGN="right"><INPUT TYPE="submit" CLASS="button" NAME="save" VALUE="Save"></DIV>
	</TD>
</TR>
</TABLE>
</FORM>
<? require('footer.php'); ?>