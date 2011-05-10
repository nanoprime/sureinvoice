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
require_once('SI_InvoiceLine.php');
require_once('SI_Account.php');
require_once('SI_Company.php');
require_once('SI_CompanyTransaction.php');
require_once('SI_TaskActivity.php');
require_once('SI_PaymentSchedule.php');
require_once('SI_Expense.php');
require_once('SI_PDFInvoice.php');
require_once('SI_RateStructure.php');

/**
 * Constant for defining no expense aggregation
 */
define('SI_EXPENSE_AGGREGATION_NONE', 1);

/**
 * Constant for defining expense aggregation based 
 * on matching unit_price and description
 */
define('SI_EXPENSE_AGGREGATION_DESC', 2);

/**
 * Constant for defining expense based on matching prices
 */
define('SI_EXPENSE_AGGREGATION_PRICE', 3);


/**
 * Constant for defining no task activity aggregation
 */
define('SI_ACTIVITY_AGGREGATION_NONE', 1);

/**
 * Constant for defining task activity aggregation based 
 * on matching item code and task
 */
define('SI_ACTIVITY_AGGREGATION_TASK', 2);

/**
 * Constant for defining expense based on item code
 */
define('SI_ACTIVITY_AGGREGATION_ITEM_CODE', 3);

/**
 * SI_Invoice class
 * 
 * This class provides methods for dealing with
 * invoices in SureInvoice. You can add, modify or
 * remove invoices plus get any information related
 * to a company.
 * 
 * Many methods in this class rely on a global variable 
 * called $db_conn that is a {@link DBConn} object for 
 * database access.
 * 
 * @package com.uversainc.sureinvoice 
 * @author Cory Powers <cory@uversainc.com>
 * @copyright 2005 Uversa, Inc
 */
class SI_Invoice{
	/**
	 * SureInvoice invoice id, greater than 0 if this
	 * object is populated. This is an auto_increment
	 * value from the datbase;
	 * 
	 * @access public
	 * @var int
	 */
	var $id;
	
	/**
	 * The id of the company this invoice is for
	 * 
	 * @access public
	 * @var int
	 */
	var $company_id;

	/**
	 * The first address line on the invoice
	 * 
	 * @access public
	 * @var string
	 */
	var $address1;

	/**
	 * The second address line on the invoice
	 * 
	 * @access public
	 * @var string
	 */
	var $address2; 

	/**
	 * The city for this invoice
	 * 
	 * @access public
	 * @var string
	 */
	var $city;

	/**
	 * The state for this invoice
	 * 
	 * @access public
	 * @var string
	 */
	var $state;

	/**
	 * The zip code for this invoice
	 * 
	 * @access public
	 * @var string
	 */	
	var $zip;
	
	/**
	 * The unix timestamp of when the invoice was created
	 * 
	 * @access public
	 * @var int
	 */
	var $timestamp;
	
	/**
	 * The terms for the invoice
	 * 
	 * @access public
	 * @var NET15|NET30|NET45
	 */
	var $terms;
	
	/**
	 * The company transaction id related to this invoice
	 * 
	 * @access public
	 * @var int 
	 */
	var $trans_id;

	/**
	 * The unix timestamp of when the invoice was last emailed
	 * 
	 * @access public
	 * @var int
	 */
	var $sent_ts;

	/**
	 * The unix timestamp of when the invoice was last updated
	 * 
	 * @access public
	 * @var int
	 */
	var $updated_ts;

	/**
	 * Holds the most recent error that has occured
	 * 
	 * @var string
	 * @access private
	 */
	var $error;

	/**
	 * An array of {@link SI_InvoiceLine} objects.
	 * 
	 * @var Array
	 * @access private
	 */
	var $_lines;

	/**
	 * SI_Invoice Constructor
	 * 
	 * The constructor sets up the default values for 
	 * an invoice.
	 */
	function SI_Invoice(){
		$this->error = '';
		$this->id = 0;
		$this->company_id = 0;
		$this->address1 = '';
		$this->address2 = '';
		$this->city = '';
		$this->state = '';
		$this->zip = '';
		$this->timestamp = 0;
		$this->terms = '';
		$this->trans_id = 0;
		$this->sent_ts = 0;
		
		$this->_lines = NULL;
	}

	/**
	 * Updates the member values from an associative array
	 * 
	 * This method will set a member variable for each key
	 * in the provided array.
	 * 
	 * @access public
	 * @param Array $array An associative array
	 */
	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['company_id'])) $this->company_id = $array['company_id'];
		if(isset($array['address1'])) $this->address1 = $array['address1'];
		if(isset($array['address2'])) $this->address2 = $array['address2'];
		if(isset($array['city'])) $this->city = $array['city'];
		if(isset($array['state'])) $this->state = $array['state'];
		if(isset($array['zip'])) $this->zip = $array['zip'];
		if(isset($array['timestamp'])) $this->timestamp = $array['timestamp'];
		if(isset($array['terms'])) $this->terms = $array['terms'];
		if(isset($array['trans_id'])) $this->trans_id = $array['trans_id'];
		if(isset($array['sent_ts'])) $this->sent_ts = $array['sent_ts'];
		if(isset($array['updated_ts'])) $this->updated_ts = $array['updated_ts'];
	}

	/**
	 * Escapes all string member variables
	 * 
	 * This method will loop through all the
	 * member variables on this object and if
	 * they are a string as determined by is_string() 
	 * then they are escaped using the escapeString 
	 * method on the {@link DBConn} object
	 * 
	 * @see DBConn::escapeString()
	 * @global DBConn Database access object
	 * @access public
	 */
	function escapeStrings(){
		global $db_conn;
		
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = $db_conn->escapeString($value);
			}
		}
	}

	/**
	 * Removes slashes from member variables
	 * 
	 * This method will loop through all the
	 * member variables on this object and if
	 * they are a string as determined by is_string() 
	 * then the slashes will be stripped by using
	 * the stripcslashes() function
	 * 
	 * @access public
	 */
	function stripSlashes(){
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = stripcslashes($value);
			}
		}
	}

	/**
	 * Gets the last error message
	 * 
	 * This method will return a textual
	 * description of the last error that 
	 * occured. 
	 * 
	 * You should call this function if any
	 * of the methods on this class return 
	 * FALSE. This will provide more information
	 * on the error that occured.
	 * 
	 * @access public
	 */
	function getLastError(){
		return $this->error;
	}

	/**
	 * Method to add a new invoice
	 * 
	 * This method will add a new invoice
	 * to the SureInvoice database. The $id of
	 * this invoice will be updated with the 
	 * id stored in the database.
	 * 
	 * This method will handle escaping of the
	 * strings stored in the member variables
	 * prior to inserting the data into the
	 * database.
	 * 
	 * @global DBConn Database access object
	 * @access public
	 * @see getLastError()
	 * @return bool TRUE on success or FALSE on error
	 */
	function add(){
		global $db_conn;

		$this->timestamp = time();
		$this->updated_ts = time();
    
		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO invoices (company_id, address1, address2, city, ".
		  "state, zip, timestamp, terms, ".
		  "trans_id, sent_ts, updated_ts)".
		  " VALUES(".$this->company_id.", '".$this->address1."', '".$this->address2."', '".$this->city."', ".
		  "'".$this->state."', '".$this->zip."', ".$this->timestamp.", '".$this->terms."', ".
		  "".$this->trans_id.", ".$this->sent_ts.", ".$this->updated_ts.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id($db_conn->_conn);
			return TRUE;
		}else{
			$this->error = "SI_Invoice::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to update an invoice
	 * 
	 * This method will update an existing invoice
	 * in the SureInvoice database. The $id of
	 * this invoice must be greater than 0.
	 * 
	 * This method will handle escaping of the
	 * strings stored in the member variables
	 * prior to inserting the data into the
	 * database.
	 * 
	 * @global DBConn Database access object
	 * @access public
	 * @see getLastError()
	 * @return bool TRUE on success or FALSE on error
	 */
	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_Invoice::update() : Invoice id not set\n";
			return FALSE;
		}

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("UPDATE invoices SET company_id = ".$this->company_id.", ".
		  "address1 = '".$this->address1."', address2 = '".$this->address2."', ".
		  "city = '".$this->city."', state = '".$this->state."', ".
		  "zip = '".$this->zip."', timestamp = ".$this->timestamp.", ".
		  "terms = '".$this->terms."', trans_id = ".$this->trans_id.", ".
		  "sent_ts = ".$this->sent_ts.", updated_ts = ".$this->updated_ts."".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Invoice::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to remove an invoice
	 * 
	 * This method will delete an invoice
	 * from the SureInvoice database. 
	 * 
	 * If the $id param is NULL the $id
	 * of the current object will be used
	 * as the ID to delete.
	 *
	 * @param int|NULL $id ID of the company to delete or NULL to use $this->id
	 * @static 
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return bool TRUE on success or FALSE on error
	 */
	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Invoice::delete() : Invoice id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM invoices WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Invoice::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to retreive an invoice
	 * 
	 * This method will populate this
	 * instance with the data from the 
	 * SureInvoice database for the 
	 * provided $id. 
	 * 
	 * This method will automatically populate
	 * all of the associated line items for this 
	 * invoice.
	 * 
	 * If the $id param is NULL the $id
	 * of the current object will be used
	 * as the ID to retreive.
	 *
	 * @param int|NULL $id ID of the company to retrieve or NULL to use $this->id
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @see _populateLines()
	 * @return bool TRUE on success or FALSE on error
	 */
	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Invoice::get() : Invoice id not set\n";
			return FALSE;
		}

		$Invoice = SI_Invoice::retrieveSet("WHERE id = $id", TRUE);
		if($Invoice === FALSE){
			return FALSE;
		}

		if(isset($Invoice[0])){
			$this->updateFromAssocArray($Invoice[0]);
			if($this->_populateLines() === FALSE)
				return FALSE;
			$this->stripSlashes();
		}else{
			$this->error = "SI_Invoice::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Method to retreive a set of invoices
	 *
	 * You can provide a where clause to this
	 * function to limit the result set. The 
	 * clause could also be just an order by
	 * clause.
	 * 
	 * The function will return an Array of
	 * SI_Invoice objects for each company
	 * found. 
	 *
	 * This method will automatically populate
	 * all of the associated line items for this 
	 * invoice.
	 * 
	 * @param string $clause Optional where or order by clause to limit the results
	 * @param bool $raw If TRUE result will be an indexed array instead of an Array of Objects
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @see _populateLines()
	 * @return mixed Array of SI_Invoice objects or FALSE on error
	 */
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

		$sql = "SELECT  id, company_id, address1, address2, city, state, ".
			"zip, timestamp, terms, trans_id, sent_ts, updated_ts".
			" FROM invoices ".$clause;
		$result = $db_conn->query($sql);

		if(!$result){
			$this->error = "SI_Invoice::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$Invoice[] = $row;
			}else{
				$temp =& new SI_Invoice();
				$temp->updateFromAssocArray($row);
				if($temp->_populateLines() === FALSE)
					return FALSE;
				$temp->stripSlashes();
				$Invoice[] =& $temp;
			}

		}

		return $Invoice;
	}

	/**
	 * Method to populate line items for this invoice
	 *
	 * This method will populate all line items for this
	 * invoice by calling the getInvoiceLines method on the
	 * {@link SI_InvoiceLine} class with this invoice's $id
	 * as the parameter.
	 * 
	 * @access private
	 * @see getLastError()
	 * @see $_lines
	 * @see SI_InvoiceLine::getInvoiceLines()
	 * @return TRUE|FALSE TRUE on success or FALSE on error
	 */
	function _populateLines(){
		$il = new SI_InvoiceLine();
		$result = $il->getInvoiceLines($this->id);
		if($result === FALSE){
			$this->error = "SI_Invoice::_populateLines(): Error getting invoice lines: ".$il->getLastError()."\n";
			return FALSE;
		}

		$this->_lines = $result;
		return TRUE;
	}

	function getTermsSelectTags($selected = NULL){
		$tags = "";

		$options = array( 'NET15' => 'NET15', 'NET30' => 'NET30', 'NET45' => 'NET45', 'NET60' => 'NET60', 'NET90' => 'NET90', 'IMMEDIATE' => 'IMMEDIATE' );

		foreach($options as $value => $name){
			$sel_text = "";
			if(!is_null($selected) && $value == $selected){
				$sel_text = " SELECTED";
			}elseif(is_null($selected) && isset($GLOBALS['CONFIG']['invoice_terms']) && $GLOBALS['CONFIG']['invoice_terms'] == $value){				
				$sel_text = " SELECTED";
			}

			$tags .= "<OPTION VALUE=\"".$value."\"".$sel_text.">".$name." ".$row[2]."</OPTION>\n";
		}
		return $tags;
	}

	function addTaskActivities($ta_ids, $aggregation_type = SI_ACTIVITY_AGGREGATION_TASK){
		if(!is_array($ta_ids) || count($ta_ids) <= 0){
			$this->error = "SI_Invoice::addTaskActivities(): Invalid parameter!";
			return FALSE;
		}

		$clause = " WHERE a.id IN (".implode(',', $ta_ids).")";
		$ta = new SI_TaskActivity();
		$tas = $ta->retrieveSet($clause);
		if($tas === FALSE){
			$this->error = "SI_Invoice::addTaskActivities(): Error getting activities: ".$ta->getLastError();
			return FALSE;
		}

		$tas_count = count($tas);
		$lines = array();
		for($i=0; $i<$tas_count; $i++){

			if($aggregation_type == SI_ACTIVITY_AGGREGATION_TASK){
				$key = $tas[$i]->task_id.$tas[$i]->hourly_rate;
			}elseif($aggregation_type == SI_ACTIVITY_AGGREGATION_ITEM_CODE){
				$key = $tas[$i]->item_code_id.$tas[$i]->hourly_rate;
			}else{
				$key = $tas[$i]->id;
			}
			if(!isset($lines[$key])){
				$lines[$key] =& new SI_InvoiceLine();
				$lines[$key]->invoice_id = $this->id;
				$lines[$key]->item_code_id = $tas[$i]->item_code_id;
			}
			$il =& $lines[$key];

			if($tas[$i]->getTask() === FALSE){
				$this->error = "SI_Invoice::addTaskActivities(): Error getting task for activity: ".$tas[$i]->getLastError();
				return FALSE;
			}

			if($tas[$i]->_task->getProject() === FALSE){
				$this->error = "SI_Invoice::addTaskActivities(): Error getting project for activity: ".$tas[$i]->getLastError();
				return FALSE;
			}

			$il->quantity += round(($tas[$i]->end_ts - $tas[$i]->start_ts) / 60 /60, 2);
			$il->unit_price = $tas[$i]->hourly_rate;
			if($aggregation_type == SI_ACTIVITY_AGGREGATION_ITEM_CODE){
				$ic = $tas[$i]->getItemCode();
				$il->description = $ic->description;
			}else{
				$il->description = $tas[$i]->_task->_project->name.": ".$tas[$i]->_task->name;
			}
			$il->addLink(SI_INVOICE_LINE_LINK_ACTVITY, $tas[$i]->id);
			$il->addTax();
		}

		if(!$this->_lines)
			$this->_lines = array();
			
		foreach($lines as $line){
			if($line->add() === FALSE){
				$this->error = "SI_Invoice::addTaskActivities(): Error adding line item: ".$line->getLastError();
				return FALSE;
			}
			$this->_lines[] = $line;
		}

		return TRUE;
	}

	function addPaymentSchedules($ps_ids){
		if(!is_array($ps_ids) || count($ps_ids) <= 0){
			$this->error = "SI_Invoice::addPaymentSchedules(): Invalid parameter!";
			return FALSE;
		}

		$clause = " WHERE id IN (".implode(',', $ps_ids).")";
		$ps = new SI_PaymentSchedule();
		$ps_items = $ps->retrieveSet($clause);
		if($ps_items === FALSE){
			$this->error = "SI_Invoice::addPaymentSchedules(): Error getting payment schedules: ".$ps->getLastError();
			return FALSE;
		}

		$ps_count = count($ps_items);
		$lines = array();
		for($i=0; $i<$ps_count; $i++){
			if($ps_items[$i]->getProject() === FALSE){
				$this->error = "SI_Invoice::addPaymentSchedules(): Error getting project for payment schedule: ".$tas[$i]->getLastError();
				return FALSE;
			}
			$il = new SI_InvoiceLine();
			$il->invoice_id = $this->id;
			$il->quantity = 1;
			$il->item_code_id = $ps_items[$i]->item_code_id;
			$il->unit_price = $ps_items[$i]->amount;
			$il->description = $ps_items[$i]->_project->name.": ".$ps_items[$i]->description;
			$ill = new SI_InvoiceLineLink();
			$ill->payment_schedule_id = $ps_items[$i]->id;
			$il->_links[] = $ill;
			$il->addTax();
			$lines[] = $il;
		}

		if(!$this->_lines)
			$this->_lines = array();
			
		foreach($lines as $line){
			if($line->add() === FALSE){
				$this->error = "SI_Invoice::addPaymentSchedules(): Error adding line item: ".$line->getLastError();
				return FALSE;
			}
			$this->_lines[] = $line;
		}

		return TRUE;
	}	
	
	function addExpenses($expense_ids, $aggregation_type = SI_EXPENSE_AGGREGATION_DESC){
		if(!is_array($expense_ids) || count($expense_ids) <= 0){
			$this->error = "SI_Invoice::addExpenses(): Invalid parameter!";
			return FALSE;
		}

		$clause = " WHERE e.id IN (".implode(',', $expense_ids).")";
		$exp = new SI_Expense();
		$expenses = $exp->retrieveSet($clause);
		if($expenses === FALSE){
			$this->error = "SI_Invoice::addExpenses(): Error getting expenses: ".$exp->getLastError();
			return FALSE;
		}

		$expense_count = count($expenses);
		$lines = array();
		for($i=0; $i<$expense_count; $i++){
			if($aggregation_type == SI_EXPENSE_AGGREGATION_DESC){
				if(!isset($lines[md5(strtolower($expenses[$i]->description).'-'.$expenses[$i]->price)])){
					$lines[md5(strtolower($expenses[$i]->description).'-'.$expenses[$i]->price)] = new SI_InvoiceLine();
				}
				$il =& $lines[md5(strtolower($expenses[$i]->description).'-'.$expenses[$i]->price)];
			}elseif($aggregation_type == SI_EXPENSE_AGGREGATION_PRICE){
				if(!isset($lines[(string)$expenses[$i]->price])){
					$lines[(string)$expenses[$i]->price] = new SI_InvoiceLine();
				}
				$il =& $lines[(string)$expenses[$i]->price];
			}else{
				$lines[] = new SI_InvoiceLine();
				$il =& $lines[count($lines) - 1];	
			}
			$il->invoice_id = $this->id;
			$il->quantity += 1;
			$il->item_code_id = $expenses[$i]->item_code_id;
			$il->unit_price = $expenses[$i]->price;
			$il->description = $expenses[$i]->description;
			$il->addLink(SI_INVOICE_LINE_LINK_EXPENSE, $expenses[$i]->id);
			$il->addTax();
		}

		if(!$this->_lines)
			$this->_lines = array();
		
		foreach($lines as $line){
			if($line->add() === FALSE){
				$this->error = "SI_Invoice::addExpenses(): Error adding line item: ".$line->getLastError();
				return FALSE;
			}
			$this->_lines[] = $line;
		}

		return TRUE;
	}

	function addCustomLines($custom_lines){
		if(!is_array($custom_lines) || count($custom_lines) <= 0){
			return TRUE;
		}

		$lines = array();
		foreach($custom_lines as $new_line){
			if(!isset($new_line['item_code_id']) || $new_line['item_code_id'] <= 0 || !isset($new_line['quantity']) || !isset($new_line['description']) || !isset($new_line['price']))
				continue;
				
			if($new_line['quantity'] <= 0)
				continue;
			
			$il = new SI_InvoiceLine();
			$il->invoice_id = $this->id;
			$il->item_code_id = $new_line['item_code_id'];
			$il->quantity = $new_line['quantity'];
			$il->unit_price = floatval($new_line['price']);
			$il->description = $new_line['description'];
			$il->addTax();
			$lines[] = $il;
		}

		if(!$this->_lines)
			$this->_lines = array();
			
		foreach($lines as $line){
			if($line->add() === FALSE){
				$this->error = "SI_Invoice::addCustomLines(): Error adding line item: ".$line->getLastError();
				return FALSE;
			}
			$this->_lines[] = $line;
		}

		return TRUE;
	}

	function getTotal(){
		return round($this->getSubTotal() + $this->getTaxAmount(), 2);	
	}
	
	function getSubTotal(){
		$total_price =  0.00;

		for($i = 0; $i < count($this->_lines); $i++){
			$total_price += $this->_lines[$i]->getSubTotal();
		}

		return round($total_price, 2);
	}

	function getTaxAmount(){
		$tax_amount = 0.00;
		
		for($i = 0; $i < count($this->_lines); $i++){
			$tax_amount += $this->_lines[$i]->getTaxAmount();
		}

		return round($tax_amount, 2);
			
	}
	
	function getAmountDue(){
		global $db_conn;

		$result = $db_conn->query("SELECT SUM(pi.amount) as amount_paid
FROM payment_invoices AS pi 
WHERE pi.invoice_id = '{$this->id}'
GROUP BY pi.invoice_id
");

		if(!$result){
			$this->error = "SI_Invoice::getAmountDue(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$amount_paid = 0.00;
		if($row=$result->fetchRow()){
			$amount_paid = $row[0];
		}
		
		return $this->getTotal() - $amount_paid;
	}

	function getAmountPaid(){
		global $db_conn;

		$result = $db_conn->query("SELECT SUM(pi.amount) as amount_paid
FROM payment_invoices AS pi 
WHERE pi.invoice_id = '{$this->id}'
GROUP BY pi.invoice_id
");

		if(!$result){
			$this->error = "SI_Invoice::getAmountDue(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$amount_paid = 0.00;
		if($row=$result->fetchRow()){
			$amount_paid = $row[0];
		}
		
		return $amount_paid;
	}

	function getDueDate(){
		$interval_days = 0;
		if($this->terms == 'NET15'){
			$interval_days = 15;
		}elseif($this->terms == 'NET30'){
			$interval_days = 30;	
		}elseif($this->terms == 'NET45'){
			$interval_days = 45;	
		}elseif($this->terms == 'NET60'){
			$interval_days = 60;	
		}elseif($this->terms == 'NET90'){
			$interval_days = 90;	
		}
		
		return $this->timestamp + ($interval_days * (24 * (60 * 60)));
	}
	
	function isPastDue(){
		return time() > $this->getDueDate();
	}
	
	function getOutstanding($company_id = 0){
		global $db_conn;

		$company_id = intval($company_id);
		if($company_id > 0){
			$c_id_clause = " it.company_id = $company_id ";
		}else{
			$c_id_clause = " 1 = 1 ";
		}
		
$sql1 = "CREATE TEMPORARY TABLE invoice_totals 
SELECT i.id, i.company_id, ROUND(SUM(il.quantity * il.unit_price),2) AS total
FROM invoices AS i
LEFT JOIN invoice_lines AS il ON i.id = il.invoice_id
GROUP BY i.id
HAVING total > 0
ORDER BY i.id
";

$sql2 = "SELECT i.id, i.company_id, i.address1, i.address2, i.city, i.state,
i.zip, i.timestamp, i.terms, i.trans_id, it.total, i.sent_ts,
ROUND(SUM(pi.amount),2) AS amount_paid
FROM invoices AS i
LEFT JOIN payment_invoices AS pi ON pi.invoice_id = i.id
LEFT JOIN invoice_totals AS it ON it.id = i.id
WHERE it.total IS NOT NULL AND $c_id_clause
GROUP BY i.id
HAVING amount_paid IS NULL OR (total - amount_paid) > 0 
ORDER BY i.timestamp;
";

		$result = $db_conn->query($sql1);
		if(!$result){
			$this->error = "SI_Invoice::getOutstanding(): Phase 1 Error: ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$result = $db_conn->query($sql2);
		if(!$result){
			$this->error = "SI_Invoice::getOutstanding(): Phase 2 Error: ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(TRUE)){
			$temp =& new SI_Invoice();
			$temp->updateFromAssocArray($row);
			$temp->amount_paid = $row['amount_paid'];
			$temp->_populateLines();
			$temp->sent_ts = $row['sent_ts'];
			$invoices[] =& $temp;
		}

		return $invoices;
	}

	function getForCompany($company_id){
		global $db_conn;

		$company_id = intval($company_id);
		$c_id_clause = " WHERE i.company_id = $company_id ";
		
$sql = "SELECT i.id, i.company_id, i.address1, i.address2, i.city, i.state,
i.zip, i.timestamp, i.terms, i.trans_id, i.sent_ts,
ROUND(SUM(pi.amount),2) AS amount_paid
FROM invoices AS i
LEFT JOIN payment_invoices AS pi ON pi.invoice_id = i.id
$c_id_clause
GROUP BY i.id
ORDER BY i.timestamp;
";

		$result = $db_conn->query($sql);
		if(!$result){
			$this->error = "SI_Invoice::getOutstanding(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_Invoice();
			$temp->updateFromAssocArray($row);
			$temp->amount_paid = $row['amount_paid'];
			$temp->_populateLines();
			$temp->sent_ts = $row['sent_ts'];
			$invoices[] =& $temp;
		}

		return $invoices;
	}

	function getName(){
    	$company = new SI_Company();
    	if($company->get($this->company_id) === FALSE){
	      $this->error = "SI_Invoice::getName(): Error getting company: ".$company->getLastError();
    	  return "";
    	}

    	return $company->name;
	}

	function getCompany(){
		if(!$this->_company && $this->company_id > 0){
			$this->_company = new SI_Company();
			if($this->_company->get($this->company_id) === FALSE){
				$this->error = "Error retreiving company information: ".$this->_company->getLastError();
				return FALSE;
			}
		}
		
		return $this->_company;	
	}
	  	
	function getInvoiceText(){
		// Invoice Text
		// name of the company logo, only jpeg is supported
		if(!empty($GLOBALS['CONFIG']['invoice_logo']) && is_file($GLOBALS['CONFIG']['invoice_logo'])){
			$itext['logo'] = $GLOBALS['CONFIG']['invoice_logo'];
			if(!isset($GLOBALS['CONFIG']['invoice_logo_width']) || !isset($GLOBALS['CONFIG']['invoice_logo_width'])){
				list($width, $height) = getimagesize($GLOBALS['CONFIG']['invoice_logo']);
				$GLOBALS['CONFIG']['invoice_logo_width'] = $width;
				$GLOBALS['CONFIG']['invoice_logo_height'] = $height;
			}
			$itext['logo_width'] = $GLOBALS['CONFIG']['invoice_logo_width'];
			$itext['logo_height'] = $GLOBALS['CONFIG']['invoice_logo_height'];
		}elseif(empty($GLOBALS['CONFIG']['invoice_logo'])){
			$itext['logo'] = realpath(dirname(__FILE__).'/../')."/images/sureinvoice_logo.jpg";
			$itext['logo_width'] = "369";
			$itext['logo_height'] = "54";			
		}
		
		//Information as it is to appear on invoices
		if(!isset($GLOBALS['CONFIG']['my_company_id']) || $GLOBALS['CONFIG']['my_company_id'] <= 0){
			$itext['name'] = "Uversa, Inc.";
			$itext['address1'] = "2753 E Broadway Rd.";
			$itext['address2'] = "Suite 101";
			$itext['address3'] = "Mesa, AZ 85204";
			$itext['phone1'] = "480-361-1876";
			$itext['phone2'] = "";
			$itext['fax'] = "";
			$itext['email'] = "info@uversainc.com";
			
			$itext['url'] = "http://www.uversainc.com/";
		}else{
			$company = SureInvoice::getMyCompany();
			$itext['name'] = $company->name;
			$itext['address1'] = $company->address1;
			$itext['address2'] = $company->address2;
			$itext['address3'] = $company->city.', '.$company->state.'  '.$company->zip;
			$itext['phone1'] = $company->phone;
			$itext['fax'] = $company->fax;
		}
		
		if(isset($GLOBALS['CONFIG']['invoice_note']) && !empty($GLOBALS['CONFIG']['invoice_note'])){
			$itext['note'] = $GLOBALS['CONFIG']['invoice_note'];
		}else{
			$itext['note'] = "Thank you for choosing " . $itext['name'] . ", we appreciate your business.\n";
			$itext['note'] .= "Please remit payment to the address in the top right corner of this invoice or visit our website where you can pay online at " . $itext['url'] . ".";			
		}
		
		return $itext;
	}
	
  	function getPDF($detail = false){
		if(!is_array($this->_lines) || count($this->_lines) == 0){
			$this->error = "Invoice has no line items.";
			return FALSE;
		}
		
		$company =& $this->getCompany();
		$company_info = $this->getInvoiceText();
		
		$client_info = array();
		$client_info['name'] = $company->name;
		$client_info['address1'] = $company->address1;
		$client_info['address2'] = $company->address2;
		$client_info['address3'] = $company->city.", ".$company->state."  ".$company->zip;
		$client_info['phone1'] = $company->phone;
		$client_info['fax'] = $company->fax;
		
		$line_items = array();
		$line_index = 0;
		foreach($this->_lines as $line){
			$line_items[$index]['item_code'] = $line->getItemCodeCode();
			$line_items[$index]['quantity'] = $line->quantity;
			$line_items[$index]['description'] = $line->description;
			$line_items[$index]['unit_price'] = number_format($line->unit_price, 2);
			$line_items[$index]['tax_amount'] = number_format($line->getTaxAmount(), 2);
			$line_items[$index]['tax_rate'] = $line->getTaxRate();
			$line_items[$index]['subtotal'] = $line->getSubTotal();
			$line_items[$index]['total'] = $line->getTotal();
			$index++;
			if($detail){
				$activity_ids = $line->getTaskActivityIDs();
				$activities = SI_TaskActivity::retrieveSet("a.id IN (".join(', ', $activity_ids).")");
				foreach ($activities as $act){
					$line_items[$index]['item_code'] = '';
					$line_items[$index]['quantity'] = '';
					$line_items[$index]['unit_price'] = '';
					$line_items[$index]['tax_amount'] = '';
					$line_items[$index]['tax_rate'] = '';
					$line_items[$index]['subtotal'] = '';
					$line_items[$index]['total'] = '';
					$line_items[$index]['description'] = $act->getUsername()." ".date('Y-m-d H:i', $act->start_ts)." - ".date('H:i', $act->end_ts);
					$index++;
					
					
					$line_items[$index]['item_code'] = '';
					$line_items[$index]['quantity'] = '';
					$line_items[$index]['unit_price'] = '';
					$line_items[$index]['tax_amount'] = '';
					$line_items[$index]['tax_rate'] = '';
					$line_items[$index]['subtotal'] = '';
					$line_items[$index]['total'] = '';
					$line_items[$index]['description'] = $act->text;
					$index++;
				}
			}
		}
		
		$inv_pdf = new SI_PDFInvoice($this->id, $company_info, $client_info, $line_items, $this->terms, $this->timestamp, $this->getAmountPaid());
		$pdf_file = $inv_pdf->get_invoice_pdf();
		if($pdf_file === FALSE){
			$this->error = "Error creating invoice PDF: ".$inv_pdf->error;
			return FALSE;
		}
  			
		return $pdf_file;
  	}
  	
	function getNotificationParams(){
		global $loggedin_user;

		$params = $this->getInvoiceText();
		$params['company_name'] = $params['name'];
		$params['company_phone'] = $params['phone1'];
		$params['invoice_num'] = $this->id;
		
		$company =& $this->getCompany();
		if($company === FALSE)
			return FALSE;
		
		$params['invoice_emails'] = $company->getInvoiceEmails();
		if($params['invoice_emails'] === FALSE){
			$this->error = "SI_Invoice::getNotificationParams(): Error getting invoice emails: ".$company->getLastError();
			return FALSE;	
		}
		
		return $params;
	}

	function sendEmail($notification = 'InvoiceEmail'){
		$notif = new Notification();
		$params = $this->getNotificationParams();
//		if(!isset($params['invoice_emails']) || empty($params['invoice_emails']))
//			return TRUE;
			
		if($notif->getByName($notification) === FALSE){
			$this->error = "SI_Invoice::sendEmail() : ".$notif->getLastError()."\n";
			return FALSE;
		}
		if($notif->prepare($params) === FALSE){
			$this->error = "SI_Invoice::sendEmail() : ".$notif->getLastError()."\n";
			return FALSE;
		}
		
		// Setup attachment
		$pdf_file = $this->getPDF();
		if($pdf_file === FALSE)
			return FALSE;
			
		$my_company = SureInvoice::getMyCompany();
		$filename = 'invoice_'.$this->id.'.pdf';
		if(!empty($my_company->name)){
			$normalized_name = str_replace(array(',','.',' ',"\t","'",'"'), '_', $my_company->name);
			$filename = $normalized_name.'_'.$this->id.'.pdf';
		}
		$attachments[0]['data'] = $pdf_file;
		$attachments[0]['name'] = $filename;
		$attachments[0]['type'] = 'application/pdf';
		$attachments[0]['encoding'] = 'base64';
		if($notif->send($attachments) === FALSE){
			$this->error = "SI_Invoice::sendEmail() : ".$notif->getLastError()."\n";
			return FALSE;
		}

		// Update sent_ts
		$this->sent_ts = time();
		if($this->update() === FALSE){
			$this->error = "SI_Invoice::sendEmail(): Email sent, error updating sent timestamp: ".$this->getLastError();
			return FALSE;	
		}
		
		return TRUE;
	}
  	
  	function getLine($line_id){
  		if(is_array($this->_lines) && count($this->_lines) > 0){
  			foreach($this->_lines as $line){
  				if($line->id == $line_id){
  					return $line;	
  				}	
  			}	
  		}
  		
  		return FALSE;
  	}

	function exportQB($clause = ''){
		$invoices = $this->retrieveSet($clause);
		
		if($invoices === FALSE){
			return FALSE;
		}
		
		$app_config = SI_Config::getAppConfig();
		$rec_account = new SI_Account();
		$rec_account->get($app_config['account_rec']);
		
		$exporter = new QBExporter();
		foreach($invoices as $invoice){
			$company =& $invoice->getCompany();
			if(count($invoice->_lines) > 0 && $company != FALSE){
				$invoice_data = array();
				$invoice_data['NAME'] = $company->name;
				$invoice_data['TRNSID'] = $invoice->id;
				$invoice_data['DOCNUM'] = $invoice->id;
				$invoice_data['DATE'] = date("n/j/Y", $invoice->timestamp);
				$invoice_data['TRNSTYPE'] = 'INVOICE';
				$invoice_data['TAXABLE'] = ($invoice->getTaxAmount() > 0.00);
				$invoice_data['TAX_CHARGED'] = ($invoice->getTaxAmount() > 0.00);
				$invoice_data['ACCNT'] = $rec_account->name;
				$invoice_data['AMOUNT'] = $invoice->getTotal();
				$invoice_data['LINES'] = array();
				$invoice_data['TIMESTAMP'] = $invoice->updated_ts;
				foreach($invoice->_lines as $line){
					$item_code =& $line->getItemCode();
					$amount = $line->getTotal() * -1;
					$invoice_data['LINES'][] = array(
						'TRNSTYPE' => 'INVOICE',
						'DATE' => $invoice_data['DATE'],
						'QNTY' => (float)'-'.$line->quantity,
						'MEMO' => '"'.$line->description.'"',
						'AMOUNT' => $amount,
						'PRICE' => (float)$line->unit_price,
						'INVITEM' => $item_code->code,
						'ACCNT' => $item_code->getIncomeAccountName()
					);					
				}

				if($exporter->addItem('Invoice', $invoice_data) === FALSE){
					$this->error = "SI_Company::export(): Error adding customer {$customer->name}: ".$exporter->getLastError();
					return FALSE;
				}
			}
		}
		
		return $exporter->get_string();
	}

	function addDiscountLine(){
		if($this->company_id == 0){
			$this->error = "SI_Invoice::addDiscountLine(): Company ID is not set\n";
			return FALSE;
		}
		
		$discount_amount = 0.00;
		$company =& $this->getCompany();
		$rs =& $company->getRateStructure();
		
		//Get all months with time on this invoice
		$time = array();
		foreach($this->_lines as $line){
			if(in_array($line->item_code_id, $rs->item_code_ids) && $line->getType() == 'Task Activity'){
				$links =& $line->getLinks();
				foreach($links as $link){
					$ta = new SI_TaskActivity();
					if($ta->get($link->task_activity_id) === FALSE){
						$this->error = "SI_Invoice::addDiscountLine(): Error getting task activity!\n";
						return FALSE;	
					}
					$time[date('n-Y', $ta->start_ts)]['new_hours'] += ($ta->end_ts - $ta->start_ts) / 60 / 60;
				}
			}
		}
		
		//Get billed hours for the months on this invoice
		foreach($time as $month => $hours){
			$time[$month]['billed_hours'] = $company->getBilledQuantity($rs->item_code_ids, $month) - $time[$month]['new_hours'];
		}
//		var_dump($time);
		
		//Calculate Discount
		$discount = 0.00;
		foreach($time as $month => $hours){
//			debug_message("Looking at month $month with billed hours of ".$hours['billed_hours']." and new hours of ".$hours['new_hours']);
			$ranges = $rs->getLines();
			$current_hours = $hours['billed_hours'];
			$total_hours = $hours['billed_hours'] + $hours['new_hours'];
			foreach($ranges as $range){
//				debug_message("Looking at range {$range->low} - {$range->high} with current hours of $current_hours");
				if($range->high > 0 && $current_hours <= $range->high){
//					debug_message("$current_hours is below range high");
					if($current_hours < $range->low){
//						debug_message("$current_hours is below range low adding ".($range->low - $current_hours)." to current hours");
						$current_hours += $range->low - $current_hours;
					}
					
					if($current_hours < $range->high && $total_hours > $range->high){
//						debug_message("$total_hours is above range high adding ".($range->high - $current_hours)." hours");
						$discount += ($range->high - $current_hours) * $range->discount;
						$current_hours += ($range->high - $current_hours);
					}elseif($current_hours <= $range->high){
//						debug_message("Adjusting discount and current hours for range with ".($total_hours - $current_hours)." hours");
						$discount += ($total_hours - $current_hours) * $range->discount;
						$current_hours +=  ($total_hours - $current_hours);
					}
				}else{
					if($range->high == 0){
//						debug_message("range high is 0 adding ".($total_hours - $current_hours)." hours to discount.");
						$discount += ($total_hours - $current_hours) * $range->discount;
					}
				}
			}
		}
		var_dump($discount);
		
		//TODO Add discount line item
		$new_line = array();
		$new_line[0]['item_code_id'] =  $rs->discount_item_code_id;
		$new_line[0]['quantity'] = 1;
		$new_line[0]['description'] = 'Volume Discount';
		$new_line[0]['price'] = $discount;
		return $this->addCustomLines($new_line);
	}
}

