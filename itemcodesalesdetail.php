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

require_once('includes/SI_Reports.php');

$title = 'Item Code Sales';
$reports = new SI_Reports();

$start_ts = 0;
$end_ts = 0;
if(isset($_REQUEST['start'])){
	$start_ts = getTSFromInput($_REQUEST['start']);
}elseif(isset($_REQUEST['start_ts'])){
	$start_ts = $_REQUEST['start_ts'];
}else{
	$start_ts = mktime(0, 0, 0, date("n"), 1, date("Y"));
}
$next_start_ts = mktime(0, 0, 0, date("n", $start_ts) + 1, 1, date("Y", $start_ts));
$prev_start_ts = mktime(0, 0, 0, date("n", $start_ts) - 1, 1, date("Y", $start_ts));

if(isset($_REQUEST['end'])){
	$end_ts = getTSFromInput($_REQUEST['end']) + 86399;
}elseif(isset($_REQUEST['end_ts'])){
	$end_ts = $_REQUEST['end_ts'];
}else{
	$end_ts = mktime(23, 59, 59, date("n") + 1, 1, date("Y")) - (24 * 60 * 60);
}
$next_end_ts = mktime(0, 0, 0, date("n", $next_start_ts) + 1, 1, date("Y", $next_start_ts)) - (24 * 60 * 60);
$prev_end_ts = mktime(0, 0, 0, date("n", $prev_start_ts) + 1, 1, date("Y", $prev_start_ts)) - (24 * 60 * 60);


$next_month_url = $_SERVER['PHP_SELF']."?start_ts=$next_start_ts&end_ts=$next_end_ts&";
$prev_month_url = $_SERVER['PHP_SELF']."?start_ts=$prev_start_ts&end_ts=$prev_end_ts&";

$item_code_id = 0;
if(isset($_REQUEST['item_code_id'])){
	$item_code_id = $_REQUEST['item_code_id'];
}

$rows = $reports->salesByItemCodeDetail($item_code_id, $start_ts, $end_ts);
if($rows === FALSE){
	$error_msg .= "Error getting report data!\n";
	debug_message($reports->getLastError());
}

?>
<? require('header.php'); ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="GET">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Item Code:</td>
	<td class="form_field_cell">
		<select name="item_code_id" id="item_code_id" class="input_text">
			<option value="0">All Item Codes</option>
			<?= SI_ItemCode::getSelectTags($item_code_id) ?>
		</select>	
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Start:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="start" id="start" SIZE="10" value="<?= date("n/j/Y", $start_ts) ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('start')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">End:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="end" id="end" SIZE="10" value="<?= date("n/j/Y", $end_ts) ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('end')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<a href="<?= $prev_month_url ?>">Previous Month</a><br>
		<a href="<?= $next_month_url ?>">Next Month</a>
	</td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" name="save" class="button" value="Report"></div>
	</td>
</tr>
</table>
	</div>
</div>
</form>
<?
if(count($rows) > 0){
	$total = 0.00;
?>
<div class="tableContainer" style="clear: both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Item Code Sales</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 1, false)">Item Code</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 1, 0, false)">Company</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Amount</a></th>
	</tr>
	<tbody id="bodyId1">
<?	$i = 0;
	foreach($rows as $row){
		$i++;
		$total += $row['sales'];
	?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $row['code'] ?></td>
		<td class="dg_data_cell_1"><?= $row['name'] ?></td>
		<td class="dg_data_cell_1" align="right"><?= SureInvoice::getCurrencySymbol().number_format($row['sales'],2) ?></td>
	</tr>
<?	}?>
	</tbody>
	<tr>
		<td colspan="2" class="form_header_cell" align="right">Totals:</td>
		<td class="form_field_cell" align="right"><?= SureInvoice::getCurrencySymbol().number_format($total,2) ?></td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<? require('footer.php'); ?>