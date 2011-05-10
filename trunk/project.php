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
require_once('includes/SI_ProjectStatus.php');
require_once('includes/SI_ProjectPriority.php');
require_once('includes/SI_SalesCommissionType.php');

$title = '';
$project = new SI_Project();

if($_REQUEST['mode'] == 'add'){
	$title = "Add Project";
	$project->owner_id = $loggedin_user->id;
	$project->due_ts = '';
	if(isset($_REQUEST['company_id'])) $project->company_id = $_REQUEST['company_id'];

	if($_POST['save']){
		$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
		$project->updateFromAssocArray($_POST);
		if($project->add() !== false){
			goBack();
		}else{
			$error_msg .= "Error adding Project!\n";
			debug_message($project->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'edit'){
	$title = "Edit Project";
	if(empty($_REQUEST['id'])){
		$error_msg .= "Error: No ID specified!\n";
	}else{
		if(!$project->get($_REQUEST['id'])){
			$error_msg .= "Could not retreive project information!\n";
			debug_message($project->getLastError());
		}
		if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
			fatal_error('Insufficent access rights for this project!');
		}
	}

	if($_POST['save']){
		$_POST['due_ts'] = getTSFromInput($_POST['due_ts']);
		$project->updateFromAssocArray($_POST);
		if($project->update()){
			$activities = array("Project details modified");
			if(!empty($_FILES['attachment_file']['tmp_name'])){
				if($project->addAttachment($_FILES['attachment_file']['tmp_name'], $_FILES['attachment_file']['name'], $_POST['attachment_description']) === FALSE){
					$error_msg .= "Error adding attachment!\n";
					debug_message($project->getLastError());
				}else{
					$activities[] = "Added attachment {$_POST['attachment_description']}";
				}
			}
			if(isset($_POST['right_user_id']) && $_POST['right_user_id'] > 0){
				if(!$project->hasRights(PROJECT_RIGHT_FULL)){
					fatal_error('Insufficent access rights to add users!');
				}
				if($project->addUserRight($_POST['right_user_id'], $_POST['right_level']) === FALSE){
					$error_msg .= "Error adding user access to project!\n";
					debug_message($project->getLastError());
				}else{
					$activities[] = "Added user right level {$_POST['right_level']} to ".SI_User::getUserName($_POST['right_user_id']);
				}
			}
			if(isset($_POST['new_cc_id']) && $_POST['new_cc_id'] > 0){
				if($project->addCC($_POST['new_cc_id']) === FALSE){
					$error_msg .= "Error adding new CC to project!\n";
					debug_message($project->getLastError());
				}else{
					$activities[] = "Added ".SI_User::getUserName($_POST['new_cc_id'])." to project CC list";
				}
			}

			if($project->sendUpdateNotification($activities) === FALSE){
				$error_msg .= "Error sending update notification!\n";
				debug_message($project->getLastError());
			}

			if(empty($error_msg) && $_POST['save'] == "Save"){
				goBack();
			}
		}else{
			$error_msg .= "Error updating Project!\n";
			debug_message($project->getLastError());
		}
	}
}else if($_REQUEST['mode'] == 'delete'){
	$title = "Delete Project";
	$project->id = $_REQUEST['id'];

	//Make sure the user is allowed to delete this
	if(!$project->hasRights(PROJECT_RIGHT_FULL)){
		fatal_error('Insufficent access rights for this project!');
	}else{
		if($project->delete($_REQUEST['id'])){
			goBack();
		}else{
			$error_msg .= "Error deleting Project!\n";
			debug_message($project->getLastError());
		}
	}

}else if($_REQUEST['mode'] == 'delete_right'){
	$title = "Delete User Right";
	$project->get($_REQUEST['id']);

	//Make sure the user is allowed to delete
	if(!$project->hasRights(PROJECT_RIGHT_FULL)){
		fatal_error('Insufficent access rights for this project!');
	}else{
		if($project->deleteUserRight($_REQUEST['user_id']) === FALSE){
			$error_msg .= "Error deleting user access from project!\n";
			debug_message($project->getLastError());
		}
		if($project->sendUpdateNotification(array("Removed rights for user_id ".$_REQUEST['user_id'])) === FALSE){
			$error_msg .= "Error sending update notification!\n";
			debug_message($project->getLastError());
		}
		$_REQUEST['mode'] = 'edit';
	}

}else if($_REQUEST['mode'] == 'delete_attachment'){
	$title = "Delete Attachment";
	$project->get($_REQUEST['id']);

	//Make sure the user is allowed to delete
	if(!$project->hasRights(PROJECT_RIGHT_FULL)){
		fatal_error('Insufficent access rights for this project!');
	}else{
		if($project->deleteAttachment($_REQUEST['attachment_id']) === FALSE){
			$error_msg .= "Error deleting attachment from project!\n";
			debug_message($project->getLastError());
		}
		if($project->sendUpdateNotification(array("Removed attachment ID ".$_REQUEST['attachment_id'])) === FALSE){
			$error_msg .= "Error sending update notification!\n";
			debug_message($project->getLastError());
		}
		$_REQUEST['mode'] = 'edit';
	}

}else if($_REQUEST['mode'] == 'delete_cc'){
	$title = "Delete CC";
	$project->get($_REQUEST['id']);

	//Make sure the user is allowed to delete
	if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
		fatal_error('Insufficent access rights for this project!');
	}else{
		if($project->deleteCC($_REQUEST['cc_id']) === FALSE){
			$error_msg .= "Error deleting cc from project!\n";
			debug_message($project->getLastError());
		}
		if($project->sendUpdateNotification(array("Removed cc ID ".$_REQUEST['attachment_id'])) === FALSE){
			$error_msg .= "Error sending update notification!\n";
			debug_message($project->getLastError());
		}
		$_REQUEST['mode'] = 'edit';
	}

}else{
	$title = "Invalid Mode";
	$error_msg .= "Error: Invalid mode!\n";
}

?>
<? require('header.php'); ?>
<script>
function disableCom(disableIt){
	document.project.sales_com_user_id.disabled = disableIt;
	document.project.sales_com_type_id.disabled = disableIt;
}
</script>
<form name="project" action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" ENCTYPE="multipart/form-data">
<input name="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
<input name="mode" type="hidden" value="<?= $_REQUEST['mode'] ?>">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" /><?= $title ?></a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Name:</td>
	<td class="form_field_cell"><input name="name" class="input_text" size="45" type="text" value="<?= $project->name ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Status:</td>
	<td class="form_field_cell">
		<select name="project_status_id" class="input_text">
			<?= SI_ProjectStatus::getSelectTags($project->project_status_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Priority:</td>
	<td class="form_field_cell">
		<select name="project_priority_id" class="input_text">
			<?= SI_ProjectPriority::getSelectTags($project->project_priority_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Owner:</td>
	<td class="form_field_cell">
		<select name="owner_id" class="input_text">
			<?= SI_User::getSelectTags($project->owner_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell">
		<select name="company_id" class="input_text">
			<option value="0">None</option>
			<?= SI_Company::getSelectTags($project->company_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Default Item Code:</td>
	<td class="form_field_cell">
		<select name="default_item_code_id" class="input_text">
			<option value="0">None</option>
			<?= SI_ItemCode::getSelectTags($project->default_item_code_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Due:</td>
	<td class="form_field_cell">
		<input type="text" class="input_text" name="due_ts" id="due_ts" SIZE="10" value="<?= $project->due_ts > 0 ? date("n/j/Y", $project->due_ts) : ''?>">&nbsp;
		<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('due_ts')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;	
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Sales Commission?</td>
	<td class="form_field_cell">
		<input name="sales_com" type="radio" value="Y" <?= checked($project->sales_com, "Y") ?> onClick="disableCom(false)">Yes&nbsp;
		<input name="sales_com" type="radio" value="N" <?= checked($project->sales_com, "N") ?> onClick="disableCom(true)">No&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Commission User:</td>
	<td class="form_field_cell">
		<select name="sales_com_user_id" class="input_text" disabled>
			<option value="0">None</option>
			<?= SI_User::getSelectTags($project->sales_com_user_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Commission Type:</td>
	<td class="form_field_cell">
		<select name="sales_com_type_id" class="input_text" disabled>
			<option value="0">None</option>
			<?= SI_SalesCommissionType::getSelectTags($project->sales_com_type_id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Billable Type</td>
	<td class="form_field_cell">
		<input name="billable" type="radio" value="Y" <?= checked($project->billable, "Y") ?>>Hourly&nbsp;
		<input name="billable" type="radio" value="N" <?= checked($project->billable, "N") ?>>Non-Billable&nbsp;
		<input name="billable" type="radio" value="S" <?= checked($project->billable, "S") ?>>Flat Rate&nbsp;
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Description:</td>
	<td class="form_field_cell"><textarea name="description" class="input_text" cols="70" rows="15"><?= $project->description ?></textarea></td>
</tr>
<tr>
	<td class="form_field_header_cell">Attachments:</td>
	<td class="form_field_cell">
<?  $attachments = $project->attachments;
		$attachment_count = count($attachments);
		if($attachment_count > 0){
			for($i=0; $i<$attachment_count; $i++){ ?>
		<a href="attachment.php?id=<?= $attachments[$i]->id ?>"><?= $attachments[$i]->path ?></a>&nbsp;-&nbsp;<?= $attachments[$i]->description ?>&nbsp;&nbsp;
		<a href="<?= $_SERVER['PHP_SELF']."?id=".$project->id."&mode=delete_attachment&attachment_id=".$attachments[$i]->id ?>">
		<img src="images/delete_small.gif" border="0" width="13" height="13" align="middle"/></a>&nbsp;<br>
<?  	}
		}else{?>
		<b>No Attachments</b>
<?	} ?>
	</td>
</tr>
<?if($_REQUEST['mode'] == 'edit' && $project->hasRights(PROJECT_RIGHT_EDIT)){?>
<tr>
	<td class="form_field_header_cell">Add Attachment:</td>
	<td class="form_field_cell">
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td><b>File:</b></td>
			<td><input type="file" class="input_text" size="30" name="attachment_file" value="<?= $_REQUEST['attachment_file'] ?>"></td>
		</tr>
		<tr>
			<td><b>Description:</b></td>
			<td><input type="TEXT" class="input_text" size="50" name="attachment_description" value="<?= $_REQUEST['attachment_description'] ?>" maxlength="255"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" class="button" name="save" value="Add Attachment"></td>
		</tr>
		</table>
	</td>
</tr>
<?}?>
<?if($_REQUEST['mode'] == 'edit' && $project->hasRights(PROJECT_RIGHT_FULL)){?>
<tr>
	<td class="form_field_header_cell">Current User Access:</td>
	<td class="form_field_cell">
<?  $project_rights = $project->getUserRights();
		for($i=0; $i<count($project_rights); $i++){
			$cur_user_ids[] = $project_rights[$i]['user_id'];?>
		<?= $project_rights[$i]['user_name'].'&nbsp;-&nbsp;'.$project_rights[$i]['level_name'] ?>&nbsp;&nbsp;
		<a href="<?= $_SERVER['PHP_SELF']."?id=".$project->id."&mode=delete_right&user_id=".$project_rights[$i]['user_id'] ?>">
		<img src="images/delete_small.gif" border="0" width="13" height="13" align="middle"/></a>&nbsp;<br>
<?	}?>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">New User Access:</td>
	<td class="form_field_cell">
		<select name="right_user_id" class="input_text">
			<option value="0">Select User...</option>
			<?= SI_User::getSelectTags($_POST['right_user_id'], $cur_user_ids, FALSE) ?>
		</select>&nbsp;&nbsp;
		<select name="right_level" class="input_text">
			<option value="0">Select Access Level...</option>
<?		for($i=0; $i<count($project->right_level_names); $i++){?>
			<option value="<?= $i ?>"><?= $project->right_level_names[$i] ?></option>
<?		}?>
		</select>
		<input type="submit" class="button" name="save" value="Add User Access">
	</td>
</tr>
<?}?>
<tr>
	<td class="form_field_header_cell">Current CC:</td>
	<td class="form_field_cell">
<?  $ccs = $project->ccs;
		$cc_count = count($ccs);
		$cur_cc_ids = array();
		if($cc_count > 0){
			for($i=0; $i<$cc_count; $i++){
				$cur_cc_ids[] = $ccs[$i]->id;?>
			<?= $ccs[$i]->first_name.' '.$ccs[$i]->last_name ?>&nbsp;&nbsp;
		<a href="<?= $_SERVER['PHP_SELF']."?id=".$project->id."&mode=delete_cc&cc_id=".$ccs[$i]->id ?>">
		<img src="images/delete_small.gif" border="0" width="13" height="13" align="middle"/></a>&nbsp;<br>
<?  	}
		}else{?>
		<b>No CCs Setup</b>
<?	} ?>
	</td>
</tr>
<?if($_REQUEST['mode'] == 'edit' && $project->hasRights(PROJECT_RIGHT_EDIT)){?>
<tr>
	<td class="form_field_header_cell">Add CC:</td>
	<td class="form_field_cell">
		<select name="new_cc_id" class="input_text">
			<option value="0">Select User...</option>
			<?= SI_User::getSelectTags($_POST['new_cc_id'], $cur_cc_ids, FALSE) ?>
		</select>&nbsp;&nbsp;
		<input type="submit" class="button" name="save" value="Add CC">
	</td>
</tr>
<?}?>
<tr>
	<td class="form_field_header_cell">Created On:</td>
	<td class="form_field_cell"><?= $project->created_ts ? date("D M jS, Y \a\\t h:i:s A", $project->created_ts) : "" ?></td>
</tr>
<tr>
	<td class="form_field_header_cell">Last Updated:</td>
	<td class="form_field_cell"><?= $project->updated_ts ? date("D M jS, Y \a\\t h:i:s A", $project->updated_ts) : "" ?></td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right"><input type="submit" class="button" name="save" value="Save"></div>
	</td>
</tr>
</table>
	</div>
</div>
</form>
<?
if($project->sales_com == 'Y')
	print('<script>disableCom(false)</script>');

require('footer.php'); ?>