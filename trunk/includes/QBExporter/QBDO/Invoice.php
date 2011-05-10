<?php
/*
 * Created on Sep 6, 2005 by cpowers
 *
 */
require_once realpath(dirname(__FILE__)).'/QBBaseDO.php';

class Invoice extends QBBaseDO{
	var $header = false;
	
	function Invoice($exporter){
		parent::QBBaseDO($exporter);
		
		$this->fields_invoice_taxable_notax = array(
			'TRNSID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'CLASS',
			'AMOUNT',
			'DOCNUM',
			'MEMO',
			'CLEAR',
			'TOPRINT',
			'ADDR1',
			'ADDR2',
			'ADDR3',
			'ADDR4',
			'ADDR5',
			'DUEDATE',
			'TERMS',
			'PAID',
			'SHIPDATE'
		);

		$this->fields_line_taxable_notax = array(
			'SPLID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'CLASS',
			'AMOUNT',
			'DOCNUM',
			'MEMO',
			'CLEAR',
			'QNTY',
			'PRICE',
			'INVITEM',
			'PAYMETH',
			'TAXABLE',
			'REIMBEXP',
			'EXTRA'
		);

		$this->fields_invoice_taxable_tax = array(
			'TRNSID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'CLASS',
			'AMOUNT',
			'DOCNUM',
			'MEMO',
			'CLEAR',
			'TOPRINT',
			'NAMEISTAXABLE',
			'ADDR1',
			'ADDR2',
			'ADDR3',
			'ADDR4',
			'DUEDATE',
			'TERMS',
			'OTHER1'
		);

		$this->fields_line_taxable_tax = array(
			'SPLID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'CLASS',
			'AMOUNT',
			'DOCNUM',
			'MEMO',
			'CLEAR',
			'QNTY',
			'PRICE',
			'INVITEM',
			'PAYMETH',
			'TAXABLE',
			'SERVICEDATE',
			'OTHER2',
			'EXTRA'
		);

		$this->fields_invoice_nontaxable = array(
			'TRNSID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'CLASS',
			'AMOUNT',
			'DOCNUM',
			'MEMO',
			'CLEAR',
			'TOPRINT',
			'NAMEISTAXABLE',
			'ADDR1',
			'ADDR3',
			'TERMS',
			'SHIPVIA',
			'SHIPDATE'
		);

		$this->fields_line_nontaxable = array(
			'SPLID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'CLASS',
			'AMOUNT',
			'DOCNUM',
			'MEMO',
			'CLEAR',
			'QNTY',
			'PRICE',
			'INVITEM',
			'TAXABLE',
			'OTHER2',
			'YEARTODATE',
			'WAGEBASE'
		);
	}
	
	function export($data){
		if($this->validate($data) === FALSE)
			return FALSE;
		
		$output = '';
		var_dump($this->exporter);
		if($this->exporter->isSalesTaxEnabled()){
			if($data['TAX_CHARGED']){
				if(!$this->header){
					$output .= "!TRNS\t".$this->getHeaderRow($this->fields_invoice_taxable_tax)."\n";
					$output .= "!SPL\t".$this->getHeaderRow($this->fields_line_taxable_tax)."\n";
					$this->header = true;
				}
				
				$output .= "TRNS\t";
				foreach($this->fields_invoice_taxable_tax as $field_name){
					$output .= $this->getDataField($data, $field_name)."\t";
				}
				$output .= "\n";

				foreach($data['LINES'] as $line){
					$output .= "SPL\t";
					foreach($this->fields_line_taxable_tax as $field_name){
						$output .= $this->getDataField($line, $field_name)."\t";
					}
					$output .= "\n";
				}
			}else{
				if(!$this->header){
					$output .= "!TRNS\t".$this->getHeaderRow($this->fields_invoice_taxable_notax)."\n";
					$output .= "!SPL\t".$this->getHeaderRow($this->fields_line_taxable_notax)."\n";
					$this->header = true;
				}

				$output .= "TRNS\t";
				foreach($this->fields_invoice_taxable_notax as $field_name){
					$output .= $this->getDataField($data, $field_name)."\t";
				}
				$output .= "\n";

				foreach($data['LINES'] as $line){
					$output .= "SPL\t";
					foreach($this->fields_line_taxable_notax as $field_name){
						$output .= $this->getDataField($line, $field_name)."\t";
					}	
					$output .= "\n";
				}
			}
		}else{
			if(!$this->header){
				$output .= "!TRNS\t".$this->getHeaderRow($this->fields_invoice_nontaxable)."\n";
				$output .= "!SPL\t".$this->getHeaderRow($this->fields_line_nontaxable)."\n";
				$this->header = true;
			}
	
			$output .= "TRNS\t";
			foreach($this->fields_invoice_nontaxable as $field_name){
				$output .= $this->getDataField($data, $field_name)."\t";
			}
			$output .= "\n";

			foreach($data['LINES'] as $line){
				$output .= "SPL\t";
				foreach($this->fields_line_nontaxable as $field_name){
					$output .= $this->getDataField($line, $field_name)."\t";
				}
				$output .= "\n";
			}
			
		}
		$output .= "ENDTRNS\n";

		return $output;
	}
	
	function import($fields){
		//TODO Not implemented
		return '';
		
	}
	
	function validate($data){
		if(!is_array($data)){
			$this->error_msg = "Invoice::validate(): Data must be an array!";
			return FALSE;	
		}
		
		$invoice =& $data;
		$invoice_required_fields = array('TAXABLE', 'NAME', 'ACCNT', 'AMOUNT', 'TAX_CHARGED', 'LINES');
		$line_required_fields = array('QNTY', 'ACCNT', 'PRICE');
		foreach($invoice_required_fields as $field_name){
			if(!isset($invoice[$field_name])){
				$this->error_msg = "Invoice::validate(): $field_name is a required field on invoice!";
				return FALSE;
			}	
		}
		
		if(!is_array($invoice['LINES'])){
			$this->error_msg = "Invoice::validate(): No lines found, LINES must be an array, for invoice!";
			return FALSE;				
		}
		
		foreach($invoice['LINES'] as $line){
			foreach($line_required_fields as $field_name){
				if(!isset($line[$field_name])){
					$this->error_msg = "Invoice::validate(): $field_name is a required field for line on invoice!";
					return FALSE;
				}	
			}
		}
		
		return TRUE;
	}
}
?>
