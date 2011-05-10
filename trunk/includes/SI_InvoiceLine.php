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
require_once('SI_ItemCode.php');
require_once('SI_InvoiceLineLink.php');

define('SI_INVOICE_LINE_LINK_ACTVITY', 1);
define('SI_INVOICE_LINE_LINK_EXPENSE', 2);
define('SI_INVOICE_LINE_LINK_PAYMENT', 3);

class SI_InvoiceLine{
	var $id;
	
	var $invoice_id;
	
	var $quantity;
	
	var $description;
	 
	var $unit_price;
	
	var $item_code_id;
	
	var $tax_amount;

	var $error;

	var $_links;

	var $_item_code;
	
	var $_invoice;
	
	function SI_InvoiceLine(){
		$this->error = '';
		$this->id = 0;
		$this->invoice_id = 0;
		$this->quantity = 0;
		$this->description = '';
		$this->unit_price = 0;
		$this->item_code_id = 0;
		$this->tax_amount = 0.00;

		$this->_links = array();
		$this->_item_code = FALSE;
		$this->_invoice = FALSE;
	}

	function updateFromAssocArray($array){
		if(is_array($array)){
			foreach($array as $key => $value)
				$this->$key = $value;
		}
	}

	function escapeStrings(){
		global $db_conn;
		
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = $db_conn->escapeString($value);
			}
		}
	}

	function stripSlashes(){
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = stripcslashes($value);
			}
		}
	}

	function getLastError(){
		return $this->error;
	}

	function add(){
		global $db_conn;

		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO invoice_lines (invoice_id, quantity, description, unit_price, item_code_id, tax_amount)".
		  " VALUES(".$this->invoice_id.", ".$this->quantity.", '".$this->description."', ".$this->unit_price.", ".$this->item_code_id.", ".$this->tax_amount.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id($db_conn->_conn);
			if($this->attachLinks($this->_transaction_ids) === FALSE)
        return FALSE;

			return TRUE;
		}else{
			$this->error = "SI_InvoiceLine::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_InvoiceLine::update() : InvoiceLine id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE invoice_lines SET invoice_id = ".$this->invoice_id.", ".
		  "quantity = ".$this->quantity.", description = '".$this->description."', ".
		  "unit_price = ".$this->unit_price.", item_code_id = ".$this->item_code_id.", ".
		  "tax_amount = ".$this->tax_amount.
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_InvoiceLine::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_InvoiceLine::delete() : InvoiceLine id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM invoice_line_links WHERE invoice_line_id = $id");
		if($result === FALSE){
			$this->error = "SI_InvoiceLine::delete(): Error removing line links: ".$db_conn->getLastError()."\n";
			return FALSE;	
		}
		
		$result = $db_conn->query("DELETE FROM invoice_lines WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_InvoiceLine::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_InvoiceLine::get() : InvoiceLine id not set\n";
			return FALSE;
		}

		$InvoiceLine = SI_InvoiceLine::retrieveSet("WHERE id = $id", TRUE);
		if($InvoiceLine === FALSE){
			return FALSE;
		}

		if(isset($InvoiceLine[0])){
			$this->updateFromAssocArray($InvoiceLine[0]);
			if($this->_populateLinks() === FALSE)
				return FALSE;
			$this->stripSlashes();
		}else{
			$this->error = "SI_InvoiceLine::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	function retrieveSet($clause = '', $raw = FALSE){
		global $db_conn;

		if(!empty($clause)){
			$clause = trim($clause);
			if(strlen($clause) > 5){
				if(strtolower(substr($clause, 0, 5)) != "where" && strtolower(substr($clause, 0, 5)) != "order")
					$clause = "WHERE ".$clause;
			}else{
				$clause = "WHERE ".$clause;
			}
		}

		$result = $db_conn->query("SELECT  id, invoice_id, quantity, description, unit_price, item_code_id, tax_amount ".
		  " FROM invoice_lines ".$clause);

		if(!$result){
			$this->error = "SI_InvoiceLine::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$InvoiceLine[] = $row;
			}else{
				$temp =& new SI_InvoiceLine();
				$temp->updateFromAssocArray($row);
				if($temp->_populateLinks() === FALSE)
					return FALSE;
				$InvoiceLine[] =& $temp;
			}

		}

		$result->free();
		
		return $InvoiceLine;
	}

	function addLink($link_type, $id){
		if($link_type == SI_INVOICE_LINE_LINK_ACTVITY){
			$field = 'task_activity_id';
		}elseif($link_type == SI_INVOICE_LINE_LINK_EXPENSE){
			$field = 'expense_id';
		}elseif($link_type == SI_INVOICE_LINE_LINK_PAYMENT){
			$field = 'payment_schedule_id';
		}else{
			$this->error = 'SI_InvoiceLine::addLink(): Unknown link type: '.$link_type;
			return FALSE;
		}
		
		$ill = new SI_InvoiceLineLink();
		$ill->$field = $id;
		$this->_links[] = $ill;
		return TRUE;
	}
	
	function getInvoiceLines($invoice_id){
		$clause = "WHERE invoice_id = ".intval($invoice_id);

		$lines = $this->retrieveSet($clause);
		if($lines === FALSE){
			return FALSE;
		}

		return $lines;
	}

	function getSubTotal(){
		return round($this->quantity * $this->unit_price, 2);
	}

	function getTotal(){
		return round($this->getSubTotal() + $this->getTaxAmount(), 2);
	}

	function attachLinks($append = FALSE){
		global $db_conn;

		if(!$append)
			if($this->clearLinks() === FALSE)
				return FALSE;

		if(count($this->_links) == 0)
			return TRUE;

		foreach($this->_links as $link){
			$link->invoice_line_id = $this->id;
			if($link->add() === FALSE){
				$this->error = "SI_InvoiceLine::attachLinks(): Error adding link: ".$link->getLastError();
				return FALSE;
			}
		}

		return TRUE;
	}

	function clearLinks(){
		global $db_conn;

		$link = new SI_InvoiceLineLink();
		if($link->clearForInvoiceLine($this->id)=== FALSE){
			$this->error = "SI_InvoiceLine::clearLinks(): Error removing links: ".$link->getLastError();
			return FALSE;
		}

		return TRUE;
	}

	function _populateLinks(){
		$ill = new SI_InvoiceLineLink();
		$links = $ill->retrieveSet("WHERE invoice_line_id = ".$this->id); 
		if($links === FALSE){
			$this->error = "SI_InvoiceLine::_populateLinks(): Error getting links: ".$ill->getLastError();
			return FALSE;			
		}
		
		$this->_links =& $links;
		
		return TRUE; 
	}

	function getInvoice(){
		if($this->_invoice === FALSE){
			$this->_invoice = new SI_Invoice();
			if($this->invoice_id > 0){
				if($this->_invoice->get($this->invoice_id) === FALSE){
					$this->error = "SI_InvoiceLine::getInvoice(): Error getting invoice: ".$invoice->getLastError();
					return FALSE;	
				}
			}
		}
				
		return $this->_invoice;
	}
	
	function getLinks(){
		if($this->_links === FALSE){
			$this->_populateLinks();
		}
		
		return $this->_links;	
	}
	
	function getTaskActivityIDs(){
		if($this->_links === FALSE){
			$this->_populateLinks();
		}
		
		$ids = array();
		foreach($this->_links as $link){
			if($link->getType() == 'Task Activity'){
				$ids[] = $link->task_activity_id;
			}
		}
		
		return $ids;
	}
	
	function getItemCode(){
		if($this->_item_code === FALSE){
			$this->_item_code = new SI_ItemCode();
			if($this->item_code_id > 0){
				if($this->_item_code->get($this->item_code_id) === FALSE){
					$this->error = "SI_InvoiceLine::getItemCode(): Error getting item code: ".$this->_item_code->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_item_code;
	}

	function getItemCodeCode(){
		if($this->getItemCode() === FALSE)
			return FALSE;

		return $this->_item_code->code;
	}

	function isTaxable(){
		if($this->getItemCode() === FALSE)
			return FALSE;
		
		return $this->_item_code->taxable == 'Y';
	}
	
	function getTaxRate(){
		if($this->getItemCode() === FALSE)
			return FALSE;
		
		if($this->getInvoice() === FALSE)
			return FALSE;
		
		$company = $this->_invoice->getCompany();
		return $this->_item_code->getTaxRate($company->id) / 100;		
	}

	function addTax(){
		if($this->isTaxable() && $this->getSubTotal() > 0){
			$this->tax_amount = $this->getSubTotal() * $this->getTaxRate();
		}else{
			$this->tax_amount = 0.00;
		}
	}
	
	function getTaxAmount(){
		return $this->tax_amount;
	}

	function getType(){
		$links =& $this->getLinks();
		$type = '';
		foreach($links as $link){
			$link_type = $link->getType();
			if(empty($type)){
				$type = $link_type;
			}else{
				if($type != $link_type){
					return 'Mixed';
				}	
			}
		}
		
		return $type;
	}
}

