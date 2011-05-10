<?php
/*
 * Created on Sep 6, 2005 by cpowers
 *
 */
require_once realpath(dirname(__FILE__)).'/QBBaseDO.php';

class Item extends QBBaseDO{
	var $header = false;
	
	function Item($exporter){
		parent::QBBaseDO($exporter);
		
		$this->fields = array(
			'NAME',
			'REFNUM',
			'TIMESTAMP',
			'INVITEMTYPE',
			'DESC',
			'PURCHASEDESC',
			'ACCNT',
			'ASSETACCNT',
			'COGSACCNT',
			'QNTY',
			'QNTY',
			'PRICE',
			'COST',
			'TAXABLE',
			'SALESTAXCODE',
			'PAYMETH',
			'TAXVEND',
			'TAXDIST',
			'PREFVEND',
			'REORDERPOINT',
			'EXTRA',
			'CUSTFLD1',
			'CUSTFLD2',
			'CUSTFLD3',
			'CUSTFLD4',
			'CUSTFLD5',
			'DEP_TYPE',
			'ISPASSEDTHRU',
			'HIDDEN',
			'DELCOUNT',
			'USEID',
			'ISNEW',
			'PO_NUM',
			'SERIALNUM',
			'WARRANTY',
			'LOCATION',
			'VENDOR',
			'ASSETDESC',
			'SALEDATE',
			'SALEEXPENSE',
			'NOTES',
			'ASSETNUM',
			'COSTBASIS',
			'ACCUMDEPR',
			'UNRECBASIS',
			'PURCHASEDATE'
		);
	}
	
	function export($data){
		if($this->validate($data) === FALSE)
			return FALSE;
		
		$output = '';
		if(!$this->header){
			$output .= "!INVITEM\t".$this->getHeaderRow()."\n";
			$this->header = true;
		}

		$output .= "INVITEM\t".$this->getExportRow($data)."\n";
		
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
