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

$title = 'Import Time - Select Columns for Import';

$importer = new SI_TimeImport();
if(isset($_SESSION['SureInvoice']['TimeImport'])){
	$importer =& $_SESSION['SureInvoice']['TimeImport'];
}else{
	fatal_error("Import is not in progress!");
}
$headers = $importer->getColumnHeaders();

if(isset($_POST['restart'])){
	unset($_SESSION['SureInvoice']['TimeImport']);
	header("Location: ".getCurrentURL('time_import_1.php'));
	exit();
}

if($_POST['save']){
	$importer->clearMappings();
	foreach ($_POST['column_mappings'] as $index => $type){
		$importer->setColumnMapping($index, $type);
	}
	$errors = $importer->validate();
	if(count($errors) == 0){
		$importer->parse(true);
		$importer->guessMappings();
		header("Location: ".getCurrentURL('time_import_3.php'));
		exit();
	}
}

?>
<? require('header.php'); ?>
<div class="box">
<div class="boxTitle"><h3><?= $title ?></h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span></div><div class="boxContent">
<form name="time_import" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<table border="0" cellspacing="5" cellpadding="0" class="form_table">
<tr>
	<td>
		
		<p>Please select the type of data that each of the columns below contain. 
		You don't need to import every column but you do need to one of each of 
		the following column types for this import.</p>
	</td>
</tr>
<tr>
	<td>
		<p><strong>
		Start Date/Time<br>
		End Date/Time OR Duration<br>
		Task<br>
		User<br>
		</strong></p>
		
	</td>
</tr>
<? if(count($errors) > 0){ ?>
<tr>
	<td>
		<p>You must correct the errors below before you can continue.</p>
<?		foreach ($errors as $code => $message){ ?>
		<span class="error"><?= $code ?></span> - <?= $message ?><br>
<?		} ?>
	</td>
</tr>
<? } ?>
<tr><td>
	<div class="tableContainer" style="overflow-x: scroll">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Columns</a><div>
	<table border="0" cellspacing="" cellpadding="0" class="dg_table">
	<tr>
<? foreach ($headers as $index => $header){ ?>
	<th><?= $header ?></th>
<? } ?>
	</tr>
	<tr>
<? foreach ($headers as $index => $header){ ?>
	<td>
		<select name='column_mappings[<?= $index ?>]'>
			<option value="0">Skip</option>
			<option value="<?= SI_IMPORT_COLUMN_START  ?>" <?= selected(SI_IMPORT_COLUMN_START, $importer->getColumnMapping($index))?>>Start Date/Time</option>
			<option value="<?= SI_IMPORT_COLUMN_END  ?>" <?= selected(SI_IMPORT_COLUMN_END, $importer->getColumnMapping($index))?>>End Date/Time</option>
			<option value="<?= SI_IMPORT_COLUMN_DURATION  ?>" <?= selected(SI_IMPORT_COLUMN_DURATION, $importer->getColumnMapping($index))?>>Duration</option>
			<option value="<?= SI_IMPORT_COLUMN_COMMENTS  ?>" <?= selected(SI_IMPORT_COLUMN_COMMENTS, $importer->getColumnMapping($index))?>>Comments</option>
			<option value="<?= SI_IMPORT_COLUMN_USER  ?>" <?= selected(SI_IMPORT_COLUMN_USER, $importer->getColumnMapping($index))?>>User</option>
			<option value="<?= SI_IMPORT_COLUMN_COMPANY  ?>" <?= selected(SI_IMPORT_COLUMN_COMPANY, $importer->getColumnMapping($index))?>>Company</option>
			<option value="<?= SI_IMPORT_COLUMN_PROJECT  ?>" <?= selected(SI_IMPORT_COLUMN_PROJECT, $importer->getColumnMapping($index))?>>Project</option>
			<option value="<?= SI_IMPORT_COLUMN_TASK  ?>" <?= selected(SI_IMPORT_COLUMN_TASK, $importer->getColumnMapping($index))?>>Task</option>
			<option value="<?= SI_IMPORT_COLUMN_ITEMCODE  ?>" <?= selected(SI_IMPORT_COLUMN_ITEMCODE, $importer->getColumnMapping($index))?>>Item Code</option>
		</select>
	</td>
<? } ?>
	</tr>	
	</table>
	</div></div>
</td></tr>
<tr>
  <td>
  	<input type="submit" name="restart" class="button" value="Restart" />
  	<input type="submit" name="save" class="button" value="Next &raquo;" />
  </td>
</tr>
</table>
</form>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
<? require('footer.php'); ?>