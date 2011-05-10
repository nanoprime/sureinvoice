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

$title = 'Import Time - Upload File';

if(isset($_SESSION['SureInvoice']['TimeImport'])){
	$importer =& $_SESSION['SureInvoice']['TimeImport'];
	if($importer->hasMappings()){
		header("Location: ".getCurrentURL('time_import_3.php'));
		exit();			
	}
	if($importer->hasFile()){
		header("Location: ".getCurrentURL('time_import_2.php'));
		exit();	
	}
}

if($_POST['save']){
	$importer = new SI_TimeImport();
	$importer->processUploadedFile($_FILES['csv_file']);
	$_SESSION['SureInvoice']['TimeImport'] =& $importer;
	header("Location: ".getCurrentURL('time_import_2.php'));
	exit();
}

?>
<? require('header.php'); ?>
<div class="box">
<div class="boxTitle"><h3><?= $title ?></h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span></div><div class="boxContent">
<form name="time_import" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<table border="0" cellspacing="5" cellpadding="0" class="form_table">
<tr>
	<td><label for="csv_file">File</label></td>
	<td><input name="csv_file" type="file" id="csv_file"/></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="save" class="button" value="Next &raquo;" /></td>
</tr>
</table>
</form>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
<? require('footer.php'); ?>