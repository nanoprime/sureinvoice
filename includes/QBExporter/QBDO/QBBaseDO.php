<?php
/*
 * Created on Sep 6, 2005 by Cory Powers <cpowers@uversainc.com>
 *
 */
 
class QBBaseDO{
	
	var $error_msg;
	
	var $exporter;
	
	function QBBaseDO($exporter){
		$this->error_msg = '';
		$this->exporter = $exporter;	
	}
	
	function getLastError(){
		return $this->error_msg;
	}
	
	function export($data){
		$this->error_msg = "Invalid Exporter Implementation, export method must be overriden";
		return FALSE;	
	}
	
	/**
	 * getDataField()
	 * 
	 * Helper method that checks to make sure the provided field
	 * name is in the array, if it is it returns the value with any
	 * tabs replaced by a space. If it is not then an empty string 
	 * is returned.
	 * 
	 */
	function getDataField($array, $field){
		if(isset($array[$field])){
			return str_replace("\t", ' ', $array[$field]);
		}
		
		return '';	
	}

	function getHeaderRow($fields = FALSE){
		if(!$fields){
			$fields = $this->fields;
		}
		
		if($this->_validateFields($fields) === FALSE) return FALSE;
		
		$output = '';
		foreach($fields as $field_name){
			$output .= "$field_name\t";
		}
		
		return $output;
	}
	
	function getExportRow($data, $fields = FALSE){
		if(!$fields){
			$fields = $this->fields;
		}

		if($this->_validateFields($fields) === FALSE) return FALSE;

		$output = '';
		foreach($fields as $field_name){
			$output .= $this->getDataField($data, $field_name)."\t";
		}
		
		return $output;		
	}
	
	function getImportData($data_fields, $fields = FALSE){
		if(!$fields){
			$fields = $this->fields;
		}

		if($this->_validateFields($fields) === FALSE) return FALSE;

		$data = array();
		foreach($fields as $index => $field_name){
			if(isset($data_fields[$index])){
				$data[$field_name] = $data_fields[$index];
			}
		}
		
		return $data;		
	}

	function _validateFields($fields){
		if(!is_array($fields) || count($fields) == 0){
			$this->error_msg = "fields are not setup correctly!";
			return FALSE;	
		}
	
		return TRUE;	
	}
}
?>
