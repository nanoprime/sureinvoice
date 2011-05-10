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

require_once('includes/SI_Project.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_TaskActivity.php');
require_once('includes/SI_Task.php');

$title = 'Reports';
$activity = new SI_TaskActivity();
$_REQUEST['detail'] = strtolower(substr($_REQUEST['detail'],0,1)) == "y" ? TRUE : FALSE;

if(!isset($_REQUEST['billable'])){
	$_REQUEST['billable'] = 'N';
}

if($_REQUEST['save']){
	if(!$loggedin_user->hasRight('admin') && !$loggedin_user->isDeveloper()){
		$_REQUEST['billable'] = 'Y';
		$_REQUEST['company_id'] = $loggedin_user->company_id;
	}

	if(!$loggedin_user->hasRight('admin') && $loggedin_user->isDeveloper()){
		$_REQUEST['resource_id'] = $loggedin_user->id;
	}

	if(empty($_REQUEST['start_ts'])) $_REQUEST['start_ts'] = getTSFromInput($_REQUEST['start']);
	if(empty($_REQUEST['end_ts'])) $_REQUEST['end_ts'] = getTSFromInput($_REQUEST['end']) + 86399;
	$url = $_SERVER['PHP_SELF']."?start_ts=".$_REQUEST['start_ts']."&end_ts=".$_REQUEST['end_ts']."&resource_id=".$_REQUEST['resource_id']."&company_id=".$_REQUEST['company_id']."&billable=".$_REQUEST['billable']."&save=Report&graph_company=".$_REQUEST['graph_company']."&graph_project=".$_REQUEST['graph_project']."&";
	
	$activities = $activity->find($_REQUEST['start_ts'], $_REQUEST['end_ts'], $_REQUEST['resource_id'], $_REQUEST['company_id'], $_REQUEST['billable']);
	if($activities === FALSE){
		$error_msg .= "Error getting report data!\n";
		debug_message($activity->getLastError());
	}
}

?>
<? require('header.php'); ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="GET">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell">
<?	if($loggedin_user->hasRight('admin') || $loggedin_user->isDeveloper()){?>
		<select name="company_id" class="input_text">
			<option value="0">All</option>
			<?= SI_Company::getSelectTags($_REQUEST['company_id']) ?>
		</select>
<?	}else{ ?>
		<input name="company_id" type="hidden" value="<?= $loggedin_user->company_id ?>">
		<?= $loggedin_user->company ?>
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Resource:</td>
	<td class="form_field_cell">
<?	if($loggedin_user->hasRight('admin') || !$loggedin_user->isDeveloper()){?>
		<select name="resource_id" class="input_text">
			<option value="0">All</option>
			<?= SI_User::getSelectTags($_REQUEST['resource_id']) ?>
		</select>
<?	}else{ ?>
		<input name="resource_id" type="hidden" value="<?= $loggedin_user->id ?>">
		<?= $loggedin_user->first_name.' '.$loggedin_user->last_name ?>
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Start:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="start" id="start" SIZE="10" value="<?= $_REQUEST['start_ts'] > 0  ? date("n/j/Y", $_REQUEST['start_ts']) :  "" ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('start')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">End:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="end" id="end" SIZE="10" value="<?= $_REQUEST['end_ts'] > 0  ? date("n/j/Y", $_REQUEST['end_ts']) :  "" ?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('end')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Billable Only?</td>
	<td class="form_field_cell">
<?	if($loggedin_user->hasRight('admin') || $loggedin_user->isDeveloper()){?>
		<input name="billable" type="radio" value="Y" <?= checked($_REQUEST['billable'], "Y") ?>>Yes&nbsp;
		<input name="billable" type="radio" value="N" <?= checked($_REQUEST['billable'], "N") ?>>No&nbsp;
<?	}else{ ?>
		<input name="billable" type="hidden" value="Y">
		Yes
<?	} ?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Show Graphs?</td>
	<td class="form_field_cell">
<?	if($loggedin_user->isDeveloper()){?>
		<input name="graph_company" type="checkbox" value="Y" <?= checked($_REQUEST['graph_company'], "Y") ?>>Time By Company<br>
<?	} ?>
		<input name="graph_project" type="checkbox" value="Y" <?= checked($_REQUEST['graph_project'], "Y") ?>>Time By Project<br>
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
if(count($activities) > 0){
	$company_totals = array();
?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Time Entries</a><div>
	<div class="gridToolbar">
		<a class="link1" href="report_export.php?<?= $_SERVER['QUERY_STRING'] ?>" style="background-image:url(images/export.png);">Export report</a>
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $url."detail=y" ?>" style="background-image:url(images/plus.png);">Show details</a>
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $url."detail=n" ?>" style="background-image:url(images/minus.png);">Hide details</a>
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 0, 0, false)">Company</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 1, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 2, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 4, 0, false)">Started</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId1', 5, 0, false)">Completed</a></th>
		<th class="dg_header_cell">Options</th>
	</tr>
	<tbody id="bodyId1">
<?	$total_time = 0;
	$i = 0;
	foreach($activities as $act){
		$i++;
		$total_time += ($act->end_ts - $act->start_ts);
		$company_totals[$act->company_name] += ($act->end_ts - $act->start_ts);
		$project_totals[$act->project_name] += ($act->end_ts - $act->start_ts);
	?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $act->company_name ?></td>
		<td class="dg_data_cell_1"><?= $act->project_name ?></td>
		<td class="dg_data_cell_1"><?= $act->task_name ?></td>
		<td class="dg_data_cell_1"><?= $act->start_ts>0 ? date("Y-n-j H:i", $act->start_ts) : "None" ?></td>
		<td class="dg_data_cell_1"><?= $act->end_ts>0 ? date("Y-n-j H:i", $act->end_ts) : "None" ?></td>
		<td class="dg_data_cell_1">&nbsp;
			<a class="link1" href="task_activities.php?task_id=<?= $act->task_id ?>"><img src="images/activity.gif" width="16" height="16" alt="List All Task Time" border="0" /></a>&nbsp;|&nbsp;
			<a class="link1" href="task_activity.php?mode=edit&id=<?= $act->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit Entry" border="0" /></a>&nbsp;
		</td>
	</tr>
<? if($_REQUEST['detail']){?>
	<tr>
		<td colspan="9" class="dg_data_cell_1"><?= nl2br($act->text) ?></td>
	</tr>
<? 	if($i != count($activities)){?>
	<tr>
		<td colspan="9" class="dg_header_cell">&nbsp;</td>
	</tr>     
<? 	} //If not last ?>
<? } //If detail ?>
<?
	}?>
	</tbody>
	<tr>
		<td colspan="4" class="form_header_cell" align="right">Total Time Spent:</td>
		<td class="form_field_cell"><?= formatLengthOfTime($total_time) ?></td>
		<td class="form_header_cell" align="right">&nbsp;</td>
	</tr>
</table>
	</div>
</div>
<? if($_REQUEST['graph_company'] == 'Y'){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company Totals</a><div>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
  <tr>
    <td width="40%"><table border="0" cellspacing="0" cellpadding="0" class="dg_table">
      <tr>
        <th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 0, 1, false)">Name</a></th>
        <th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 1, 0, false)">Hours</a></th>
      </tr>
      <tbody id="bodyId2">
        <? $total_company_time = 0;
			foreach($company_totals as $name => $total){
					$total_company_time += $total;
		?>
        <tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
          <td class="dg_data_cell_1"><?= $name ?></td>
          <td class="dg_data_cell_1"><?= formatLengthOfTime($total) ?></td>
        </tr>
        <? }?>
      </tbody>
      <tr>
        <td class="form_header_cell" align="right">Total Time Spent:</td>
        <td class="form_field_cell" colspan="2"><?= formatLengthOfTime($total_company_time) ?></td>
      </tr>
    </table></td>
    <td><img src="<?= "graph.php?type=company&start_ts=".$_REQUEST['start_ts']."&end_ts=".$_REQUEST['end_ts']."&resource_id=".$_REQUEST['resource_id']."&company_id=".$_REQUEST['company_id']."&unbilled=".$_REQUEST['unbilled'] ?>" border="0" /></td>
  </tr>
</table>
	</div>
</div>
<? }  //if graph_company 
?>
<? if($_REQUEST['graph_project'] == 'Y'){?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Project Totals</a><div>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
	<td width="40%">
		<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
			<tr>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 0, 1, false)">Name</a></th>
				<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 1, 0, false)">Hours</a></th>
			</tr>
			<tbody id="bodyId2">
		<?$total_project_time = 0;
			foreach($project_totals as $name => $total){
					$total_project_time += $total;
		?>
			<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
				<td class="dg_data_cell_1"><?= $name ?></td>
				<td class="dg_data_cell_1"><?= formatLengthOfTime($total) ?></td>
			</tr>
		<? }?>
			</tbody>
			<tr>
				<td class="form_header_cell" align="right">Total Time Spent:</td>
				<td class="form_field_cell" colspan="2"><?= formatLengthOfTime($total_project_time) ?></td>
			</tr>
		</table>
	</td>
	<td>
	<img src="<?= "graph.php?type=project&start_ts=".$_REQUEST['start_ts']."&end_ts=".$_REQUEST['end_ts']."&resource_id=".$_REQUEST['resource_id']."&company_id=".$_REQUEST['company_id']."&unbilled=".$_REQUEST['unbilled'] ?>" BORDER="0">
	</td>
</tr>
</table>
	</div>
</div>
<? }
}elseif($_REQUEST['save']){
?>
<span class="error">No activities Found</span>
<? } ?>
<? require('footer.php'); ?>
