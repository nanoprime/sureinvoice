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

require_once('includes/SI_Company.php');
require_once('includes/SI_ItemCode.php');
require_once('includes/SI_Account.php');
require_once('includes/QBExporter/QBImporter.php');

$title = 'Import From Quickbooks';

if($_POST['save']){
	$importer = new QBImporter();
	$dest_file = '/tmp/'.basename($_FILES['iif_file']['name']);
	if(!move_uploaded_file($_FILES['iif_file']['tmp_name'], $dest_file)){
		$error_msg .= "Could not move uploaded file!";	
	}else{
		$data = $importer->import($dest_file);
		if($data === FALSE){
			$error_msg .= "Error importing file!";
			debug_message($importer->getLastError());
		}
		
		$company = new SI_Company();
		if($company->importQB($data) === FALSE){
			$error_msg .= "Error importing company data!";
			debug_message($company->getLastError());	
		}

		$code = new SI_ItemCode();
		if($code->importQB($data) === FALSE){
			$error_msg .= "Error importing item code data!";
			debug_message($code->getLastError());	
		}

		$account = new SI_Account();
		if($account->importQB($data) === FALSE){
			$error_msg .= "Error importing account data!";
			debug_message($account->getLastError());	
		}
	}
}

?>
<? require('header.php'); ?>
<div class="box">
<div class="boxTitle"><h3><?= $title ?></h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span></div><div class="boxContent">
<form name="qb_import" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<table border="0" cellspacing="5" cellpadding="0" class="form_table">
<tr>
	<td><label for="iif_file">File</label></td>
	<td><input name="iif_file" type="file" id="iif_file"/></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="save" class="button" value="Import &raquo;" /></td>
</tr>
</table>
</form>
<p>
<?  if($data){var_dump($data);} ?>
</p>	
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
<? require('footer.php'); ?>