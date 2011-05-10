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
require_once('includes/SI_CCProcessor.php');

if(isset($_REQUEST['cc_processor'])){
	$GLOBALS['CONFIG']['cc_processor'] = $_REQUEST['cc_processor'];
}

$config = new SI_Config();
$cc_processor = SI_CCProcessor::getInstance();
$options = $cc_processor->getConfigOptions();

if($_POST['save']){
	if(is_array($_POST['params'])){
		$si_config = new SI_Config();
		$si_config->name = 'cc_processor';
		$si_config->value = $_POST['params']['cc_processor'];
		if($si_config->update() === false){
			$error_msg .= "Error saving Credit Card Processor";
			debug_message($si_config->getLastError());
		}
		
		$si_config = new SI_Config();
		$si_config->name = 'cc_types';
		$si_config->value = $_POST['params']['cc_types'];
		if($si_config->update() === false){
			$error_msg .= "Error saving Credit Card Types";
			debug_message($si_config->getLastError());
		}

		unset($_POST['params']['cc_types']);
		unset($_POST['params']['cc_processor']);
		
		$cc_processor->setConfigValues($_POST['params']);
		if($cc_processor->saveConfig() === false){
			$error_msg .= "Error saving CC Processor Settings";
			debug_message($cc_processor->getLastError());
		}
	}
	if(empty($error_msg)){
		header("Location: ".getCurrentURL()."\r\n");
		exit();
	}
}

$title = "Credit Card Processor Configuration";
require('header.php') ?>
<script type="text/javascript">
function reloadPage(){
	var oCCProcessorId = document.getElementById('cc_processor');
	var sCCProcessor = oCCProcessorId.options[oCCProcessorId.selectedIndex].value;
	window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?cc_processor='+sCCProcessor;
}

</script>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)">
<img src="images/arrow_down.jpg" alt="Hide table" />Credit Card Processor Configuration</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th colspan="3">General</th>
	</tr>
	<tr>
		<td class="dg_data_cell_1">Processing Engine</td>
		<td class="dg_data_cell_1">Select the credit card processing engine you would like to use.</td>
		<td class="dg_data_cell_1">
		<select id="cc_processor" name="params[cc_processor]" <?= !SI_Config::canEdit('cc_processor') ? 'DISABLED' : '' ?> onchange="reloadPage()">
			<option value="">No Credit Card Processing</option>	
<?		foreach ($cc_processor->getAvailableEngines() as $name => $label){?>
			<option value="<?= $name ?>" <?= selected($name, $GLOBALS['CONFIG']['cc_processor']) ?>><?= $label ?></option>
<?		}?>
		</select>
		</td>
	</tr>
<? if($GLOBALS['CONFIG']['cc_processor'] != ''){ ?>
	<tr>
		<td class="dg_data_cell_1">Credit Card Types</td>
		<td class="dg_data_cell_1">A comma seperated list of the credit card types you will accept.</td>
		<td class="dg_data_cell_1">
			<input type="text" name="params[cc_types]" class="input_text" size="25" value="<?= $GLOBALS['CONFIG']['cc_types'] ?>" <?= !SI_Config::canEdit('cc_types') ? 'READONLY' : '' ?>>
		</td>
	</tr>
	<tr>
		<th colspan="3"><?= $GLOBALS['CONFIG']['cc_processor'] ?> Setup</th>
	</tr>
<?	foreach ($options as $name => $settings){ ?>
	<tr>
		<td class="dg_data_cell_1"><?= $settings['label'] ?></td>
		<td class="dg_data_cell_1"><?= $settings['description'] ?></td>
		<td class="dg_data_cell_1">
<?		switch ($settings['type']){ 
			case 'bool': ?>
		<input type="radio" name="params[<?= $name ?>]" class="input_text" value="1" <?= checked($cc_processor->getConfigValue($name), true) ?>> Yes &nbsp;
		<input type="radio" name="params[<?= $name ?>]" class="input_text" value="0" <?= checked($cc_processor->getConfigValue($name), false) ?>> No
<?				break;
			
			default: ?>
		<input type="text" name="params[<?= $name ?>]" class="input_text" size="20" value="<?= $cc_processor->getConfigValue($name) ?>">
<?		}?>
		</td>
	</tr>
<?	}
}?>

	<tr>
		<td colspan="3"><div align="right"><input type="submit" class="button" name="save" value="Save"></div></td>
	</tr>
</table>
	</div>
</div>

<? require('footer.php') ?>