<?php
/*
 * Created on Sep 6, 2005 by cpowers
 *
 */
require_once realpath(dirname(__FILE__)).'/QBBaseDO.php';

class Customer extends QBBaseDO{
	var $header = false;
	
	function Customer($exporter){
		parent::QBBaseDO($exporter);
		
		$this->fields = array(
			'NAME',
			'REFNUM',
			'TIMESTAMP',
			'BADDR1',
			'BADDR2',
			'BADDR3',
			'BADDR4',
			'BADDR5',
			'SADDR1',
			'SADDR2',
			'SADDR3',
			'SADDR4',
			'SADDR5',
			'PHONE1',
			'PHONE2',
			'FAXNUM',
			'EMAIL',
			'NOTE',
			'CONT1',
			'CONT2',
			'CTYPE',
			'TERMS',
			'TAXABLE',
			'SALESTAXCODE',
			'LIMIT',
			'RESALENUM',
			'REP',
			'TAXITEM',
			'NOTEPAD',
			'SALUTATION',
			'COMPANYNAME',
			'FIRSTNAME',
			'MIDINIT',
			'LASTNAME',
			'CUSTFLD1',
			'CUSTFLD2',
			'CUSTFLD3',
			'CUSTFLD4',
			'CUSTFLD5',
			'CUSTFLD6',
			'CUSTFLD7',
			'CUSTFLD8',
			'CUSTFLD9',
			'CUSTFLD10',
			'CUSTFLD11',
			'CUSTFLD12',
			'CUSTFLD13',
			'CUSTFLD14',
			'CUSTFLD15',
			'JOBDESC',
			'JOBTYPE',
			'JOBSTATUS',
			'JOBSTART',
			'JOBPROJEND',
			'JOBEND',
			'HIDDEN',
			'DELCOUNT',
			'PRICELEVEL'
		);
	}
	
	function export($data){
		if($this->validate($data) === FALSE)
			return FALSE;
		
		$output = '';
		if(!$this->header){
			$output .= "!CUST\t".$this->getHeaderRow()."\n";
			$this->header = true;
		}

		$output .= "CUST\t".$this->getExportRow($data)."\n";
		
		return $output;
	}
	
	function import($fields){
		
		return $this->getImportData($fields);
		
	}
	
	function validate($data){
		if(!is_array($data)){
			$this->error_msg = "Customer::validate(): Data must be an array!";
			return FALSE;	
		}
		
		if(!isset($data['NAME']) || empty($data['NAME'])){
			$this->error_msg = "Customer::validate(): NAME is a required field for a customer!";
			return FALSE;	
		}
		
		return TRUE;
	}
}
?>
