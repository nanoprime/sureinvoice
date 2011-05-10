<?php
/*
 * This set of files is designed to allow an application
 * to easily export data into quickbooks. It has been designed
 * to be usable in any application and requires little knowledge
 * of the export file format (which is good for you cause its ugly).
 * 
 * Your application should track what is exported to qb and only
 * export something once otherwise you could end up with duplicate
 * transactions.
 * 
 * Created on Sep 6, 2005 by Cory Powers <cpowers@uversainc.com>
 *
 */

class QBExporter{
	
	var $file_type;
	
	var $error_msg;
	
	var $export_data;
	
	var $exporter_objects;
	
	var $base_path;
	
	var $sales_tax_enabled;
	
	function QBExporter($file_type = 'iif'){
		$this->file_type = $file_type;
		$this->error_msg = '';
		$this->exporter_objects = array();
		$this->export_data = '';
		$this->base_path = realpath(dirname(__FILE__));
		$this->sales_tax_enabled = FALSE;		
	}

	function addItem($type, $data){
		if(!isset($this->exporter_objects[$type])){
			if(is_readable($this->base_path.'/QBDO/'.$type.'.php')){
				require_once $this->base_path.'/QBDO/'.$type.'.php';
				$this->exporter_objects[$type] =& new $type($this);
			}else{
				$this->error_msg = 'Can not find exporter for type '.$type;
				return FALSE;
			}
		}
		
		$exporter =& $this->exporter_objects[$type];
		$output = $exporter->export($data);
		if($output === FALSE){
			$this->error_msg = "Error adding $type item: ".$exporter->getLastError();
			return FALSE;
		}
		
		$this->export_data .= $output;
		
		return TRUE;
	}
	
	function getLastError(){
		return $this->error_msg;	
	}
	
	function export($file_name){
		if(is_writable($file_name)){
			$handle = fopen($file_name, "w+");
			fwrite($handle, $this->export_data);
			fclose($handle);
		}
	}
	
	function get_string(){
		return $this->export_data;
	}

	function isSalesTaxEnabled(){
		return $this->sales_tax_enabled	== TRUE;
	}
	
	function setSalesTaxSetting($setting){
		if($setting == TRUE){
			$this->sales_tax_enabled = TRUE;
		}else{
			$this->sales_tax_enabled = FALSE;
		}		
	}
}
?>
