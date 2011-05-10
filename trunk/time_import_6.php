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

$title = 'Import Time - Preview Import';

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

if(isset($_POST['back'])){
	header("Location: ".getCurrentURL('time_import_4.php'));
	exit();
}

if(isset($_POST['save'])){
	header("Location: ".getCurrentURL('time_import_7.php'));
	exit();
}

if($_POST['preview']){
	$results = $importer->run();
}

//var_dump($importer);
$task = new SI_Task();
$user = new SI_User();
$ic = new SI_ItemCode();
?>
<? require('header.php'); ?>
<div class="box">
<div class="boxTitle"><h3><?= $title ?></h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span></div><div class="boxContent">
<form name="time_import" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<table border="0" cellspacing="5" cellpadding="0" class="form_table">
<tr>
	<td>
		<p>Now we are going to run a test of the import. Please press the Preview button below to start. The results will be displayed below.</p>
	</td>
</tr>
<tr><td>
<? if(isset($_POST['preview'])){ ?>
	<div class="tableContainer">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Results</a><div>
	<table border="0" cellspacing="" cellpadding="0" class="dg_table">
	<tr id="tasklist_header">
		<th>Action</th>
		<th>Project & Task</th>
		<th>Item Code</th>
		<th>Time</th>
		<th>User</th>
		<th>Message</th>
	</tr>
<? foreach($results as $result){ ?>
	<tr>
		<td><?= $result['action'] == 'Skip' ? "<span style='color: red'>Skip</span>" : "<span style='color: green'>Import</span>"?></td>
		<td><?= $task->getLongName($result['task_id']) ?></td>
		<td><?= $ic->getCodeName($result['item_code_id']) ?></td>
		<td><?= date('Y-m-d H:i', $result['start_ts']).' - '.date('H:i',$result['end_ts'])?></td>
		<td><?= $user->getUserName($result['user_id']) ?></td>
		<td><?= $result['message'] ?></td>
	</tr>
<? } ?>
	</table>
	</div></div>
<? } ?>
</td></tr>
<tr>
  <td>
  	<input type="submit" name="restart" class="button" value="Restart" />
  	<input type="submit" name="back" class="button" value="&laquo; Back" />
  	<input type="submit" name="preview" class="button" value="Run Preview" />
  	<input type="submit" name="save" class="button" value="Next &raquo;" />
  </td>
</tr>
</table>
</form>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
<? require('footer.php'); ?>