<?php
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

class SI_CCProcessor {
	var $cc_engine;
	
	var $error;
	
	var $config_options;
	
	function SI_CCProcessor(){
		$this->error = '';
	}
	
	function getInstance(){
		if(empty($GLOBALS['CONFIG']['cc_processor'])){
			return new SI_CCProcessor();
		}
		
		$class_name = 'SI_CCProcessor_'.ucfirst($GLOBALS['CONFIG']['cc_processor']);
		if(!class_exists($class_name)){
			if(!include_once($class_name.'.php')){
				return false;
			}
		}
		
		$engine = new $class_name();
		$engine->loadConfig();
		return $engine;
	}
	
	function getConfigValue($name){
		if(isset($this->config_options[$name])){
			return $this->config_options[$name]['value'];
		}
		
		return null;
	}

	function getConfigOption($name){
		if(isset($this->config_options[$name])){
			return $this->config_options[$name];
		}
		
		return null;
	}
	
	function getConfigOptions(){
		return $this->config_options;
	}
	
	function setConfigValues($values){
		foreach($values as $name => $value){
			$this->setConfigValue($name, $value);
		}
	}
	
	function setConfigValue($name, $value){
		if(isset($this->config_options[$name])){
			switch ($this->config_options[$name]['type']){
				case 'int':
					$value = intval($value);
					break;
				
				case 'bool':
					$value = $value == true ? true : false;
					break;
			}
			$this->config_options[$name]['value'] = $value;
		}		
	}
	
	function addConfigOption($name, $label, $description, $type, $default = null){
		$this->config_options[$name] = array(
			'name' => $name,
			'label' => $label,
			'description' => $description,
			'type' => $type,
			'value' => $default
		);
	}
	
	function saveConfig(){
		foreach ($this->config_options as $name => $settings){
			$si_config = new SI_Config();
			$si_config->name = 'cc_processor-'.$GLOBALS['CONFIG']['cc_processor'].'-'.$name;
			$si_config->value = $settings['value'];
			if($si_config->update() === FALSE){
				$this->error = "Error updating configuration parameter: {$si_config->name}\n";
				return false;
			}
			
		}
	}
	
	function loadConfig(){
		foreach($GLOBALS['CONFIG'] as $name => $value){
			if(substr($name, 0, strlen('cc_processor-'.$GLOBALS['CONFIG']['cc_processor'].'-')) == 'cc_processor-'.$GLOBALS['CONFIG']['cc_processor'].'-'){
				$param_name = substr($name, strlen('cc_processor-'.$GLOBALS['CONFIG']['cc_processor'].'-'));
				$this->setConfigValue($param_name, $value);
			}
		}
	}
	
	function getLastError(){
		return $this->error;	
	}
	
	function getAvailableEngines(){
		return array('AuthorizeNet' => 'Authorize.Net', 'PayPalPro' => 'PayPal Pro');
	}
	
	function process(){
		$this->error = "No processing engine configured!";
		return false;
	}
}

?>