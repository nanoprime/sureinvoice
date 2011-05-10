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
checkLogin('accounting');

require_once('includes/SI_UserTransaction.php');
require_once('includes/SI_CompanyTransaction.php');
$title = '';
$ut = new SI_UserTransaction();
$ct = new SI_CompanyTransaction();

if(!isset($_REQUEST['id'])){
	fatal_error('An id must be provided');
}

// Clean up amount
if(!empty($_POST['amount'])){
	$_POST['amount'] = preg_replace('/[^\-0-9\.]/','', $_POST['amount']);
}

if($_REQUEST['mode'] == 'user'){
	$title = "Add User Transaction";
	
	$ut->user_id = $_POST['id'];
	$ut->timestamp = time();
	if($_POST['save']){
		$ut->updateFromAssocArray($_POST);
		if($ut->add() !== FALSE){
			goBack();
		}else{
			$error_msg .= "Error adding Transaction!\n";
		}		
	}
}else if($_REQUEST['mode'] == 'company'){
	$title = "Add Company Transaction";
	
	$ct->company_id = $_POST['id'];
	$ct->timestamp = time();
	if($_POST['save']){
		$ct->updateFromAssocArray($_POST);
		if($ct->add() !== FALSE){
			goBack();
		}else{
			$error_msg .= "Error adding Transaction!\n";
		}		
	}
}else{
	fatal_error('Invalid mode specified');
}

?>
<? require('header.php'); ?>
<FORM ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST">
<INPUT NAME="id" TYPE="hidden" VALUE="<?= $_REQUEST['id'] ?>">
<INPUT NAME="mode" TYPE="hidden" VALUE="<?= $_REQUEST['mode'] ?>">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" CLASS="form_table">
<TR>
	<TD COLSPAN="2" CLASS="form_header_cell"><?= $title ?></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Description:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="description" CLASS="input_text" SIZE="35" TYPE="text"></TD>
</TR>
<TR>
	<TD CLASS="form_field_header_cell">Amount:</TD>
	<TD CLASS="form_field_cell"><INPUT NAME="amount" CLASS="input_text" SIZE="15" TYPE="text"></TD>
</TR>
<TR>
	<TD COLSPAN="2" CLASS="form_field_cell">
		<DIV ALIGN="right"><INPUT TYPE="submit" NAME="save" CLASS="button" VALUE="Save"></DIV>
	</TD>
</TR>
</TABLE>
</FORM>
<? require('footer.php'); ?>