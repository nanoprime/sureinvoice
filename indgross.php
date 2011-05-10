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

$title = 'Individual Gross';
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


$use_salaries = true;
if(!isset($_REQUEST['use_salaries'])){
	$_REQUEST['use_salaries'] = 'Y';
}else{
	if($_REQUEST['use_salaries'] == 'N'){
		$use_salaries = false;
	}
}

$next_month_url = $_SERVER['PHP_SELF']."?start_ts=$next_start_ts&end_ts=$next_end_ts&use_salaries=".$_REQUEST['use_salaries'].'&';
$prev_month_url = $_SERVER['PHP_SELF']."?start_ts=$prev_start_ts&end_ts=$prev_end_ts&use_salaries=".$_REQUEST['use_salaries'].'&';

$rows = $reports->individualGross($start_ts, $end_ts, $use_salaries);
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
	<td class="form_field_header_cell">Use Salaries?</td>
	<td class="form_field_cell">
		<input name="use_salaries" type="radio" value="Y" <?= checked($_REQUEST['use_salaries'], "Y") ?>>Yes&nbsp;
		<input name="use_salaries" type="radio" value="N" <?= checked($_REQUEST['use_salaries'], "N") ?>>No&nbsp;
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
	$cost_total = 0.00;
	$price_total = 0.00;
	$cost_hours_total = 0;
	$price_hours_total = 0;
?>
<div class="tableContainer" style="clear: both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Employee Time</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell" colspan="2">&nbsp;</th>
		<th class="dg_header_cell" colspan="2">Worked</th>
		<th class="dg_header_cell" colspan="2">Billed</th>
		<th class="dg_header_cell">&nbsp;</th>
	</tr>
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 0, false)">First Name</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 1, 1, false)">Last Name</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Hours</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Amount</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Hours</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Amount</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 5, 0, false)">Diff</a></th>
	</tr>
	<tbody id="bodyId1">
<?	$i = 0;
	foreach($rows as $row){
		$i++;
		$cost_total += $row['cost'];
		$price_total += $row['price'];
		$cost_hours_total += $row['hours_worked'];
		$price_hours_total += $row['hours_billed'];
	?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $row['first_name'] ?></td>
		<td class="dg_data_cell_1"><?= $row['last_name'] ?></td>
		<td class="dg_data_cell_1" align="right"><?= formatLengthOfTime($row['hours_worked']) ?></td>
		<td class="dg_data_cell_1" align="right"><?= SureInvoice::getCurrencySymbol().number_format($row['cost'], 2) ?></td>
		<td class="dg_data_cell_1" align="right"><?= formatLengthOfTime($row['hours_billed']) ?></td>
		<td class="dg_data_cell_1" align="right"><?= SureInvoice::getCurrencySymbol().number_format($row['price'], 2) ?></td>
		<td class="dg_data_cell_1" align="right" <?= ($row['price'] - $row['cost']) < 0 ? 'style="color:red"' : '' ?>><?= number_format($row['price'] - $row['cost'], 2) ?></td>
	</tr>
<?	}?>
	</tbody>
	<tr>
		<td colspan="2" class="form_header_cell" align="right">Totals:</td>
		<td class="form_field_cell" align="right"><?= formatLengthOfTime($cost_hours_total) ?></td>
		<td class="form_field_cell" align="right"><?= SureInvoice::getCurrencySymbol().number_format($cost_total,2) ?></td>
		<td class="form_field_cell" align="right"><?= formatLengthOfTime($price_hours_total) ?></td>
		<td class="form_field_cell" align="right"><?= SureInvoice::getCurrencySymbol().number_format($price_total,2) ?></td>
		<td class="form_field_cell" align="right" <?= ($price_total - $cost_total) < 0 ? 'style="color:red"' : '' ?>><?= number_format($price_total - $cost_total, 2) ?></td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<? require('footer.php'); ?>