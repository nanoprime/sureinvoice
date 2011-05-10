<?php
/*
 * 
 * 
 * Created on Sep 6, 2005 by Cory Powers <cpowers@uversainc.com>
 *
 */

class QBImporter{
	
	var $file_type;
	
	var $error_msg;
	
	var $export_data;
	
	var $exporter_objects;
	
	var $base_path;
	
	var $type_mapping;
	
	var $debug;
	
	function QBImporter($file_type = 'iif', $debug = false){
		$this->file_type = $file_type;
		$this->error_msg = '';
		$this->importer_objects = array();
		$this->export_data = '';
		$this->base_path = realpath(dirname(__FILE__));
		$this->debug = $debug;
		$this->type_mapping = array(
			'CUST' => 'Customer',
			'INVITEM' =>  'Item',
			'ACCNT' => 'Account'
		);
	}
	
	function getLastError(){
		return $this->error_msg;	
	}
	
	function debugMessage($message){
		if($this->debug) print("$message<BR>\n");
	}
	
	function import($file_name){
		$data = array();
		
		$this->debugMessage("Loading file $file_name.");
		if(is_readable($file_name)){
			$handle = fopen($file_name, "r");
			while(!feof($handle)){
				$line = fgets($handle);
				$this->debugMessage("Line: $line");
				if(strpos($line, '!') === 0){
					$this->debugMessage("Skipping comment line.");
					continue;
				}
				
				$fields = split("\t", $line);
				$row_type = array_shift($fields);
				$this->debugMessage("Found ".count($fields)." fields on line of type $row_type.");
				if(isset($this->type_mapping[$row_type])){
					$importer_type = $this->type_mapping[$row_type]; 
					if(!isset($this->importer_objects[$importer_type])){
						if(is_readable($this->base_path.'/QBDO/'.$importer_type.'.php')){
							require_once $this->base_path.'/QBDO/'.$importer_type.'.php';
							$this->importer_objects[$importer_type] =& new $importer_type();
							$this->debugMessage("Configured importer of $importer_type type for line of type $row_type");
						}else{
							$this->error_msg = 'Can not find importer for type '.$importer_type;
							return FALSE;
						}
					}
					
					$importer =& $this->importer_objects[$importer_type];
					$importer_data = $importer->import($fields);
					if($importer_data === FALSE){
						$this->error_msg = "Error importing data: ".$importer->getLastError();
						return FALSE;	
					}
					$data[$importer_type][] = $importer_data;
				}else{
					$this->debugMessage("Skipping unknown line type $row_type.");
				}					
			}
			fclose($handle);
		}else{
			$this->error_msg = "$file_name is not readable";
			return FALSE;	
		}
		
		return $data;
	}
}
?>
