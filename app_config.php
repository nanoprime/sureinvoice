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
require_once('includes/SI_User.php');

checkLogin("admin");

require_once('includes/SI_Config.php');
require_once('includes/SI_Account.php');

$config = new SI_Config();

if($_REQUEST['mode'] == 'delete' && !empty($_REQUEST['name'])){
	if($config->delete($_REQUEST['name']) === FALSE){
		$error_msg = "Error removing configuration parameter\n!";
		debug_message($config->getLastError());
	}
}

if($_POST['save']){
	if(!empty($_POST['new_name'])){
		$new_config = new SI_Config();
		$new_config->name = $_POST['new_name'];
		$new_config->value = $_POST['new_value'];
		if($new_config->add() === FALSE){
			$error_msg .= "Error adding new configuration paramenter.\n";
			debug_message($new_config->getLastError());
		}
	}

	if(is_array($_POST['params'])){
		foreach($_POST['params'] as $param_name => $param_value){
			if(!empty($param_name)){
				$modified_config = new SI_Config();
				$modified_config->name = $param_name;
				$modified_config->value = $param_value;
				if($modified_config->update() === FALSE){
					$error_msg .= "Error updating configuration paramenter: $param_name\n";
					debug_message($modified_config->getLastError());
					break;
				}
			}
		}
	}
	if(empty($error_msg)){
		header("Location: ".getCurrentURL()."\r\n");
		exit();
	}
}

$config_items = $config->retrieveSet("ORDER BY name");
if($config_items === FALSE){
	$error_msg .= "Error getting configuration items!\n";
	debug_message($config->getLastError());
}

$title = "Global Configuration";

require('header.php') ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Configuration</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th>Paramenter</th>
		<th>Value</th>
		<th>Delete</th>
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($config_items); $i++){ ?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $config_items[$i]->name ?></td>
		<td class="dg_data_cell_1">
<?		if($config_items[$i]->name == 'account_rec' || $config_items[$i]->name == 'account_payment'){ ?>
		<select name="params[<?= $config_items[$i]->name ?>]">
			<option value="0">Select account...</option>
			<?= SI_Account::getSelectTags($config_items[$i]->value) ?>
		</select>	
<?		}else{ ?>
		<input type="text" class="input_text" size="45" name="params[<?= $config_items[$i]->name ?>]" value="<?= $config_items[$i]->value ?>" />	
<?		} ?>		</td>
		<td class="dg_data_cell_1" align="center">&nbsp;
			<a class="link1" href="app_config.php?mode=delete&amp;name=<?= $config_items[$i]->name ?>"><img src="images/delete.png" width="16" height="16" alt="Delete" title="Delete" border="0" /></a>&nbsp;		</td>
	</tr>
<? }?>
</tbody>
<tr>
	<td colspan="3" class="form_field_cell">
		<div align="right">
	      <input type="submit" class="button" name="save" value="Save" /></div>
	</td>
</tr>
</table>
	</div>
</div>

<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Add New Parameter</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<th>Name:</th>
	<td><input name="new_name" class="input_text" size="20" type="text" /></td>
</tr>
<tr>
	<th>Value:</th>
	<td><input name="new_value" class="input_text" size="45" type="text" /></td>
</tr>
<tr>
	<td colspan="2" class="form_field_cell">
		<div align="right">
	      <input type="submit" class="button" name="save" value="Save" /></div>
	</td>
</tr>
</table>
	</div>
</div>
</form>
<? require('footer.php') ?>