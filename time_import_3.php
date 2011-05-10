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

$title = 'Import Time - Map Users';

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
	header("Location: ".getCurrentURL('time_import_2.php'));
	exit();
}


if($_POST['save']){
	foreach ($_POST['actions'] as $normalizedName => $type){
		$importer->addUserMapping($normalizedName, $type, $_POST['params'][$normalizedName]);
	}
	header("Location: ".getCurrentURL('time_import_4.php'));
	exit();
}

?>
<? require('header.php'); ?>
<script language="javascript">
YAHOO.util.Event.addListener(window, 'load', getUserList);
function getUserList(){
	var handleSuccess = function(o){
		result = Uversa.SureInvoice.Timers.parseResponse(o);
		
		var html= "<tr><td>{0}</td><td><select name='action[{0}]'></td><td></td></tr>";
		var tplUser = new YAHOO.ext.DomHelper.Template(html);
		var userList = document.getElementById('userlist');
		userList.innerHTML = result;
	}
	YAHOO.util.Connect.asyncRequest('GET', 'json.php/importGetUsers', {success: handleSuccess});
}
</script>
<div class="box">
<div class="boxTitle"><h3><?= $title ?></h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span></div><div class="boxContent">
<form name="time_import" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<table border="0" cellspacing="5" cellpadding="0" class="form_table">
<tr>
	<td>
		<p>Below is a list of users that do not yet exist in SureInvoice. Please select what action you would like to take with each.</p>
	</td>
</tr>
<tr><td>
	<div class="tableContainer">
	<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Users</a><div>
	<table border="0" cellspacing="" cellpadding="0" class="dg_table">
	<tr id="userlist_header">
		<th>User's Name</th>
		<th>Import Action</th>
		<th>Map To...</th>
	</tr>
	<tbody id="userlist">
	<tr><td colspan="3"><img src="/images/spinner.gif" title="Loading Users"> Loading Users...</td></tr>
	</tbody>
	</table>
	</div></div>
</td></tr>
<tr>
  <td>
  	<input type="submit" name="restart" class="button" value="Restart" />
  	<input type="submit" name="back" class="button" value="&laquo; Back" />
  	<input type="submit" name="save" class="button" value="Next &raquo;" />
  </td>
</tr>
</table>
</form>
</div><div class="boxBottom"><span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span></div>
</div>
<? require('footer.php'); ?>