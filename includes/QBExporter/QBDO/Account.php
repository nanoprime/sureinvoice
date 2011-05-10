<?php
/*
 * Created on Sep 6, 2005 by cpowers
 *
 */
require_once realpath(dirname(__FILE__)).'/QBBaseDO.php';

class Account extends QBBaseDO{
	var $header = false;
	
	function Account($exporter){
		parent::QBBaseDO($exporter);
		$this->fields = array(
			'NAME',
			'REFNUM',
			'TIMESTAMP',
			'ACCNTTYPE',
			'OBAMOUNT',
			'DESC',
			'ACCNUM',
			'SCD',
			'BANKNUM',
			'EXTRA',
			'HIDDEN',
			'DELCOUNT',
			'USEID'
		);
	}
	
	function export($data){
		if($this->validate($data) === FALSE)
			return FALSE;
		
		$output = '';
		if(!$this->header){
			$output .= "!ACCNT\t".$this->getHeaderRow()."\n";
			$this->header = true;
		}

		$output .= "ACCNT\t".$this->getExportRow($data)."\n";
		
		return $output;
	}
	
	function import($fields){
		
		return $this->getImportData($fields);
		
	}
	
	function validate($data){
		if(!is_array($data)){
			$this->error_msg = "Item::validate(): Data must be an array!";
			return FALSE;	
		}
		
		if(!isset($data['NAME']) || empty($data['NAME'])){
			$this->error_msg = "Item::validate(): NAME is a required field for a customer!";
			return FALSE;	
		}
		
		return TRUE;
	}
}
?>
