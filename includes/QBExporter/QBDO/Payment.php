<?php
/*
 * Created on Sep 6, 2005 by cpowers
 *
 */
require_once realpath(dirname(__FILE__)).'/QBBaseDO.php';

class Payment extends QBBaseDO{
	var $header = false;
	
	function Payment($exporter){
		parent::QBBaseDO($exporter);
		
		$this->fields_payment = array(
			'TRNSID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'AMOUNT',
			'DOCNUM'
		);

		$this->fields_line = array(
			'SPLID',
			'TRNSTYPE',
			'DATE',
			'ACCNT',
			'NAME',
			'AMOUNT',
			'DOCNUM'
		);
	}
	
	function export($data){
		if($this->validate($data) === FALSE)
			return FALSE;
		
		$output = '';
		if(!$this->header){
			$output .= "!TRNS\t".$this->getHeaderRow($this->fields_payment)."\n";
			$output .= "!SPL\t".$this->getHeaderRow($this->fields_line)."\n";
			$this->header = true;
		}

		$output .= "TRNS\t";
		foreach($this->fields_payment as $field_name){
			$output .= $this->getDataField($data, $field_name)."\t";
		}
		$output .= "\n";

		foreach($data['LINES'] as $line){
			$output .= "SPL\t";
			foreach($this->fields_line as $field_name){
				$output .= $this->getDataField($line, $field_name)."\t";
			}
			$output .= "\n";
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
			$this->error_msg = "Payment::validate(): Data must be an array!";
			return FALSE;	
		}
		
		$payment =& $data;
		$payment_required_fields = array('NAME', 'ACCNT', 'DATE', 'AMOUNT', 'LINES');
		$line_required_fields = array('NAME', 'ACCNT', 'DATE', 'AMOUNT');
		foreach($payment_required_fields as $field_name){
			if(!isset($payment[$field_name])){
				$this->error_msg = "Payment::validate(): $field_name is a required field on payment!";
				return FALSE;
			}	
		}
		
		if(!is_array($payment['LINES'])){
			$this->error_msg = "Payment::validate(): No lines found, LINES must be an array, for payment!";
			return FALSE;				
		}
		
		foreach($payment['LINES'] as $line){
			foreach($line_required_fields as $field_name){
				if(!isset($line[$field_name])){
					$this->error_msg = "Payment::validate(): $field_name is a required field for line on payment!";
					return FALSE;
				}	
			}
		}
		
		return TRUE;
	}
}
?>
