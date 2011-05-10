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

/**
 * Pull in the SI_Invoice class
 */
require_once("SI_Invoice.php");
/**
 * Pull in the SI_Expense class
 */
require_once('SI_Expense.php');
/**
 * Pull in the SI_CompanyTransaction class
 */
require_once("SI_CompanyTransaction.php");

/**
 * Pull in the QBExporter Class
 */
require_once(realpath(dirname(__FILE__))."/QBExporter/QBExporter.php");

/**
 * SI_Company class
 * 
 * This class provides methods for dealing with
 * companies in SureInvoice. You can add, modify or
 * remove companies plus get any information related
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
class SI_Company{
	/**
	 * SureInvoice company id, greater than 0 if this
	 * object is populated. This is an auto_increment
	 * value from the datbase;
	 * 
	 * @var int
	 */
	var $id;
	
	/**
	 * The name of the company
	 * 
	 * @access public
	 * @var string
	 */
	var $name;
	
	/**
	 * The first address line for the company
	 * 
	 * @access public
	 * @var string
	 */
	var $address1;
	
	/**
	 * The second address line for the company
	 * 
	 * @access public
	 * @var string
	 */
	var $address2; 
	
	/**
	 * The city for the company
	 * 
	 * @access public
	 * @var string
	 */
	var $city;
	
	/**
	 * The state for the company
	 * 
	 * @access public
	 * @var string
	 */
	var $state;
	
	/**
	 * The zip code for the company
	 * 
	 * @access public
	 * @var string
	 */
	var $zip;
	
	/**
	 * The company's phone number
	 * 
	 * @access public
	 * @var string
	 */
	var $phone; 
	
	/**
	 * The company's fax number
	 * 
	 * @access public
	 * @var string
	 */
	var $fax;
	
	/**
	 * The id of the associated rate structure
	 * 
	 * @access public
	 * @var string
	 */
	var $rate_structure_id;
	
	/**
	 * The unix timestamp of when the company was created
	 * 
	 * @access public
	 * @var string
	 */
	var $created_ts;
	
	/**
	 * The unix timestamp of the last update to the company
	 * 
	 * @access public
	 * @var string
	 */
	var $updated_ts;

	/**
	 * A flag to indicate when a company is 'deleted'
	 * 
	 * @access public
	 * @var string
	 */
	var $deleted;

	/**
	 * Holds the most recent error that has occured
	 * 
	 * @var string
	 * @access private
	 */
	var $error;

	/**
	 * SI_Company Constructor
	 * 
	 * The constructor sets up the default values for 
	 * a company.
	 */
	function SI_Company(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->address1 = '';
		$this->address2 = '';
		$this->city = '';
		$this->state = '';
		$this->zip = '';
		$this->phone = '';
		$this->fax = '';
		$this->rate_structure_id = 0;
		$this->created_ts = 0;
		$this->updated_ts = 0;
		$this->deleted = 'N';

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
		if(is_array($array)){
			foreach($array as $key => $value)
				$this->$key = $value;
		}
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
	 * Method to add a new company
	 * 
	 * This method will add a new company
	 * to the SureInvoice database. The $id of
	 * this company will be updated with the 
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

		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO companies (name, address1, address2, city, ".
			"state, zip, phone, fax, rate_structure_id, ".
		  "created_ts, updated_ts, deleted)".
			" VALUES('".$this->name."', '".$this->address1."', '".$this->address2."', '".$this->city."', ".
			"'".$this->state."', '".$this->zip."', '".$this->phone."', '".$this->fax."',  ".$this->rate_structure_id.", ".
		  "UNIX_TIMESTAMP(), ".$this->updated_ts.", 'N')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_Company::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to update a company
	 * 
	 * This method will update an existing company
	 * in the SureInvoice database. The $id of
	 * this company must be greater than 0.
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
			$this->error = "SI_Company::update() : Company id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE companies SET name = '".$this->name."', ".
		  "address1 = '".$this->address1."', address2 = '".$this->address2."', ".
		  "city = '".$this->city."', state = '".$this->state."', ".
		  "zip = '".$this->zip."', phone = '".$this->phone."', ".
			"fax = '".$this->fax."', rate_structure_id = '".$this->rate_structure_id."', created_ts = ".$this->created_ts.", ".
		  "updated_ts = UNIX_TIMESTAMP(), deleted = '".$this->deleted."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Company::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}
	
	/**
	 * Method to remove a company
	 * 
	 * This method will delete a company
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
			$this->error = "SI_Company::delete() : Company id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("UPDATE companies SET deleted = 'Y' WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Company::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to retreive a company
	 * 
	 * This method will populate this
	 * instance with the data from the 
	 * SureInvoice database for the 
	 * provided $id. 
	 * 
	 * If the $id param is NULL the $id
	 * of the current object will be used
	 * as the ID to retreive.
	 *
	 * @param int|NULL $id ID of the company to retrieve or NULL to use $this->id
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return bool TRUE on success or FALSE on error
	 */
	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Company::get() : Company id not set\n";
			return FALSE;
		}

		$Company = SI_Company::retrieveSet("WHERE id = $id", TRUE);
		if($Company === FALSE){
			return FALSE;
		}

		if(isset($Company[0])){
			$this->updateFromAssocArray($Company[0]);
		}else{
			$this->error = "SI_Company::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Method to retreive a set of companies
	 *
	 * You can provide a where clause to this
	 * function to limit the result set. The 
	 * clause could also be just an order by
	 * clause.
	 * 
	 * The function will return an Array of
	 * SI_Company objects for each company
	 * found. 
	 *
	 * @param string $clause Optional where or order by clause to limit the results
	 * @param bool $raw If TRUE result will be an indexed array instead of an Array of Objects
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return mixed Array of SI_Company objects or FALSE on error
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

		$result = $db_conn->query("SELECT  id, name, address1, address2, city, state, ".
			"zip, phone, fax, rate_structure_id, created_ts, updated_ts, deleted".
		  " FROM companies ".$clause);

		if(!$result){
			$this->error = "SI_Company::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$Company[] = $row;
			}else{
				$temp =& new SI_Company();
				$temp->updateFromAssocArray($row);
				$Company[] =& $temp;
			}
		}
		$result->free();

		return $Company;
	}

	/**
	 * Method to retreive all companies with a balance
	 *
	 * This method will provide an Array of SI_Company 
	 * objects for each company that has a balance greater
	 * than zero.
	 *
	 * @global DBConn Database access object
	 * @access public
	 * @static
	 * @see getLastError()
	 * @return Array|FALSE Array of SI_Company objects or FALSE on error
	 */
	function getCompanysWithBalance(){
		global $db_conn;

		$result = $db_conn->query("
SELECT c.id, c.name, c.address1, c.address2, c.city, c.state,
c.zip, c.phone, c.fax, c.rate_structure_id, c.created_ts, c.updated_ts, c.deleted, SUM(ct.amount) as price
FROM company_transactions AS ct
LEFT JOIN companies AS c ON ct.company_id = c.id
WHERE c.deleted = 'N'
GROUP BY c.id 
HAVING price > 0
		");

		if(!$result){
			$this->error = "SI_Company::getCompanysWithBalance(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_Company();
			$temp->updateFromAssocArray($row);
			$temp->price = $row['price'];
			$Company[] =& $temp;
		}

		return $Company;
	}

	/**
	 * Method to retreive all companies with unbilled hours
	 *
	 * This method will provide an Array of SI_Company 
	 * objects for each company that has time that has
	 * not been applied to any Invoice
	 *
	 * @global DBConn Database access object
	 * @access public
	 * @static
	 * @see getLastError()
	 * @return Array|FALSE Array of SI_Company objects or FALSE on error
	 */
	function getCompanysWithUnbilledAmount(){
		global $db_conn;

		$result = $db_conn->query("
SELECT c.id, c.name, c.address1, c.address2, c.city, c.state,
c.zip, c.phone, c.fax, c.rate_structure_id, c.created_ts, c.updated_ts, 
c.deleted, SUM(end_ts - start_ts) as time_spent, 
SUM(ROUND((((ta.end_ts - ta.start_ts) / 60 / 60)),2) * ta.hourly_rate) AS amount
FROM task_activities AS ta
LEFT JOIN tasks AS t ON ta.task_id = t.id
LEFT JOIN projects AS p ON t.project_id = p.id
LEFT JOIN companies AS c ON p.company_id = c.id
LEFT JOIN invoice_line_links AS ill ON ta.id = ill.task_activity_id
WHERE c.deleted = 'N' AND ill.invoice_line_id IS NULL AND 
((p.billable = 'Y' AND t.billable = 'D' )OR t.billable = 'Y') AND ta.hourly_rate > 0
GROUP BY c.id
HAVING amount > 0
		");

		if(!$result){
			$this->error = "SI_Company::getCompanysWithBalance(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_Company();
			$temp->updateFromAssocArray($row);
			$Company[] =& $temp;
		}

		return $Company;
	}

	/**
	 * Method to retreive the Invoices for this company
	 *
	 * This method will provide an Array of {@link SI_Invoice} 
	 * objects for this company. The array will be sorted
	 * in descending order by Invoice timestamp.
	 *
	 * @global DBConn Database access object
	 * @access public
	 * @see SI_Invoice
	 * @see getLastError()
	 * @return Array|FALSE Array of {@link SI_Invoice} objects or FALSE on error
	 */
	function getInvoices(){
		global $db_conn;

		$invoice = new SI_Invoice();
		$invoices = $invoice->retrieveSet("WHERE company_id = {$this->id} ORDER BY timestamp DESC");
		if($invoices === FALSE){
			$this->error = "SI_Company::getInvoices(): Error getting invoices: ".$invoice->getLastError();
    		return FALSE;
    	}
	
	    return $invoices;
	}

	/**
	 * Method to retreive option tags for all companies
	 *
	 * This method will provide a string that contains
	 * the HTML option tags for all companies in the 
	 * database sorted by Company Name.
	 * 
	 * If a company id is provided in the $selected
	 * argument, then that option tag will be marked
	 * as selected.
	 *
	 * @global DBConn Database access object
	 * @access public
	 * @static
	 * @see getLastError()
	 * @return string|FALSE HTML option tags or FALSE on error
	 */
	function getSelectTags($selected = NULL){
		global $db_conn;

		$result = $db_conn->query("SELECT id, name FROM companies WHERE deleted = 'N' ORDER BY name");

		if($result === FALSE){
			$this->error = "SI_Company::getSelectTags(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}


		while($row=$result->fetchRow()){
			$sel_text = "";
			if($row[0]==$selected)
				$sel_text = " SELECTED";
			$tags .= "<OPTION VALUE=\"".$row[0]."\"".$sel_text.">".$row[1]."</OPTION>\n";
		}
		return $tags;
	}

	/**
	 * Method to get this company's balance
	 *
	 * This method will provide the outstanding
	 * balance for this company.
	 *
	 * @param int $id ID of the company to lookup transactions for or NULL to use $this->id
	 * @global DBConn Database access object
	 * @access public
	 * @see getLastError()
	 * @return float|FALSE Current balance or FALSE on error
	 */
	function getBalance($id = NULL){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$ct = new SI_CompanyTransaction();
		$amount = $ct->getBalance($id);
		if($amount === FALSE){
			$this->error = "SI_Company::getBalance(): Error getting balance: ".$ct->getLastError();
			return FALSE;
		}

		return $amount;
	}

	/**
	 * Method to get this company's transactions
	 *
	 * This method will an Array of {@link SI_CompanyTransaction} 
	 * objects for this company
	 *
	 * @global DBConn Database access object
	 * @param int $id ID of the company to lookup transactions for or NULL to use $this->id
	 * @param int $limit Number of transactions to limit the results to or 0 for no limit
	 * @access public
	 * @see SI_CompanyTransaction
	 * @see getLastError()
	 * @return Array|FALSE Array of {@link SI_CompanyTransaction} objects or FALSE on error
	 */
	function getTransactions($id = NULL, $limit = 0){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$ct = new SI_CompanyTransaction();
		$result = $ct->getTransactions($id, $limit);
		if($result === FALSE){
			$this->error = "SI_Company::getTransactions(): Error getting transactions: ".$ct->getLastError();
			return FALSE;
		}

		return $result;
	}

	/**
	 * Method to get this company's name
	 *
	 * This method will get the Company's name or FALSE 
	 * on error. This method can be called staticlly 
	 * when a $company_id is provided.
	 *
	 * @global DBConn Database access object
	 * @param int $company_id ID of the company to lookup transactions for or NULL to use $this->id
	 * @static
	 * @access public
	 * @see getLastError()
	 * @return string|FALSE The company's name or FALSE on error
	 */
	function getName($company_id = NULL){
		global $db_conn;

		if($company_id === NULL){
			$company_id = $this->id;
		}

		$company_id = intval($company_id);
		if($company_id <= 0){
			$this->error = "SI_Company::getName() : Invalid company id!\n";
			return FALSE;
		}

		$result = $db_conn->query("SELECT name FROM companies WHERE id = $company_id");
		if($result === FALSE){
			$this->error = "SI_Company::getName() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$name = '';
		if($row = $result->fetchArray(MYSQL_ASSOC)){
			$name = $row['name'];
		}

		return $name;
	}

	/**
	 * Method to add an Expense to this company
	 *
	 * This method will add a new expense to this
	 * company. 
	 *
	 * @param string $description The expense's description
	 * @param float $cost The expense's cost
	 * @param float $price The expense's price
	 * @access public
	 * @see getLastError()
	 * @return TRUE|FALSE TRUE on success or FALSE on error
	 */
	function addExpense($item_code_id, $description, $cost, $price){
		$expense = new SI_Expense();
		$expense->item_code_id = $item_code_id;
		$expense->company_id = $this->id;
		$expense->description = $description;
		$expense->cost = $cost;
		$expense->price = $price;
		if($expense->add() === FALSE){
			$this->error = "SI_Company::addExpense(): Error adding expense: ".$expense->getLastError();
			return FALSE;	
		}
		
		return TRUE;
	}
	
	/**
	 * Method to get this company's expenses
	 *
	 * This method will an Array of {@link SI_Expense} 
	 * objects for this company
	 * 
	 * If the $unbilled param is TRUE then only expenses
	 * that have not yet been applied to an invoice 
	 * will be returned. Otherwise all expenses for this
	 * company will be returned.
	 *
	 * @global DBConn Database access object
	 * @param bool $unbilled TRUE to return only expenses not applied to an invoice
	 * @access public
	 * @see SI_Expense
	 * @see getLastError()
	 * @return Array|FALSE Array of {@link SI_Expense} objects or FALSE on error
	 */
	function getExpenses($unbilled = FALSE){
		$exp = new SI_Expense();
		$expenses = $exp->getForCompany($this->id, $unbilled);
		if($expenses === FALSE){
			$this->error = "SI_Company::getExpenses(): " . $exp->getLastError();
			return FALSE;
		}
		
		return $expenses;
	}

	/**
	 * Method to get email addreses for invoices
	 *
	 * This method will an Array of the email addresses
	 * for each user in SureInvoice that is attached to this
	 * company and is setup to receive invoices.
	 * 
	 * @global DBConn Database access object
	 * @access public
	 * @see SI_User
	 * @see getLastError()
	 * @return Array|FALSE Array of formatted email addresses or FALSE on error
	 */
	function getInvoiceEmails(){
		global $db_conn;
		
		if($this->id <= 0)
			return array();
			
		$sql = "SELECT first_name, last_name, email FROM users WHERE invoiced = 'Y' AND company_id = ".$this->id;
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Company::getInvoiceEmails(): Error getting emails: ".$db_conn->getLastError();
			return FALSE;
		}
		
		$emails = array();
		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$emails[] = $row['email'];
		}
		
		return $emails;
	}
	
	/**
	 * Method the unix timestamp of the last payment made by this company
	 *
	 * This method provide the unix timestamp of
	 * the last payment made by this company. If the 
	 * company has not made any payments then this
	 * method will return 0.
	 * 
	 * @global DBConn Database access object
	 * @access public
	 * @see getLastError()
	 * @return int|FALSE Unix timestamp of the last payment made by this company or FALSE on error
	 */
	function getLastPaymentTS(){
		global $db_conn;
		
		$sql = "SELECT timestamp FROM payments WHERE company_id = {$this->id} ORDER BY timestamp DESC LIMIT 1";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Company::getLastPaymentTS(): Error looking up last payment timestamp: ".$db_conn->getLastError();
			return FALSE;	
		}
		
		$timestamp = 0;
		if($row = $result->fetchArray(MYSQL_ASSOC)){
			$timestamp = $row['timestamp'];
		}
		
		return $timestamp;
	}

	function exportQB($clause = ''){
		$companies = $this->retrieveSet($clause);
		
		if($companies === FALSE){
			return FALSE;
		}
		
		$exporter = new QBExporter();
		foreach($companies as $company){
			if(!empty($company->name)){
				$customer = array();
				$customer['NAME'] = $company->name;
				$customer['BADDR1'] = $company->address1;
				$customer['BADDR2'] = $company->address2;
				$customer['BADDR3'] = $company->city.', '.$company->state.'  '.$company->zip;
				$customer['PHONE1'] = $company->phone;
				$customer['FAXNUM'] = $company->fax;
				$customer['TIMESTAMP'] = $company->updated_ts;
				if($exporter->addItem('Customer', $customer) === FALSE){
					$this->error = "SI_Company::export(): Error adding customer {$customer->name}: ".$exporter->getLastError();
					return FALSE;
				}
			}
		}
		
		return $exporter->get_string();
	}

	function importQB($data){
		if(!isset($data['Customer']) || count($data['Customer']) == 0){
			return TRUE;
		}
		
		foreach($data['Customer'] as $qb_company){
			$qb_company['NAME'] = str_replace('"', '', $qb_company['NAME']);
			$cur_companies = $this->retrieveSet("WHERE name = '".mysql_real_escape_string($qb_company['NAME'])."' AND deleted = 'N'");
			if($cur_companies === FALSE){
				$this->error = "SI_Company::import(): Error looking for company with name {$qb_company['NAME']}";
				return FALSE;
			}
			$company = NULL;
			if(count($cur_companies) != 1){
				// Not found or more than one found so just add a new one
				$company = new SI_Company();
			}else{
				$company =& $cur_companies[0];	
			}

			$company->name = $qb_company['NAME'];
			$company->address1 = $qb_company['BADDR1'];
			$company->address2 = $qb_company['BADDR2'];
			$matches = array();
			if(preg_match_all("\s*(.*)\s*,\s*([^\s])\s*([0-9\-])", $qb_company['BADDR3'], $matches)){
				$company->city = $matches[1];
				$company->state = $matches[2];
				$company->zip = $matches[3];
			}
			if(preg_match_all("\s*(.*)\s*,\s*([^\s])\s*([0-9\-])", $qb_company['BADDR4'], $matches)){
				$company->city = $matches[1];
				$company->state = $matches[2];
				$company->zip = $matches[3];
			}
			$company->phone = $qb_company['PHONE1'];
			$company->fax = $qb_company['FAXNUM'];
			$company->updated_ts = time();
			
			$result = FALSE;
			if($company->id > 0){
				$result = $company->update();
			}else{
				$result = $company->add();
			}
			if($result === FALSE){
				$this->error = "SI_Company::importQB(): Error adding company: ".$company->getLastError();
				return FALSE;
			}
		}
		
		return TRUE;
	}

	function getRateStructure(){
		$rs = new SI_RateStructure();
		if($this->rate_structure_id > 0){
			if($rs->get($this->rate_structure_id) === FALSE){
				$this->error = "SI_Company::getRateStructure(): Error getting rate structure: ".$rs->getLastError()."\n";
				return FALSE;
			}
		}
		
		return $rs;		
	}
	
	function getBilledQuantity($item_code_ids, $date){
		global $db_conn;
		
		list($month, $year) = split('-', $date);
		$month_start_ts = strtotime(date("$year-$month-01 00:00:00"));
		$month_end_ts = strtotime(date("$year-$month-01 00:00:00")." +1 month") - 1 ;
		
//		var_dump($month_start_ts, date('n-d-Y', $month_start_ts), $month_end_ts, date('n-d-Y', $month_end_ts));
		
		$sql = "
SELECT il.id, il.description, SUM((ta.end_ts - ta.start_ts) / 60 /60) as quantity FROM invoice_lines AS il
LEFT JOIN invoice_line_links AS ill ON ill.invoice_line_id = il.id 
LEFT JOIN task_activities AS ta ON ill.task_activity_id = ta.id
LEFT JOIN invoices AS i ON i.id = il.invoice_id
WHERE ta.start_ts >= $month_start_ts AND ta.end_ts <=  $month_end_ts 
AND i.company_id = {$this->id} AND il.item_code_id IN (".join(',', $item_code_ids).")
GROUP BY il.id
		";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Company::getBilledQuantity(): Error getting hours for $date";
			return FALSE;	
		}
		
		$quantity = 0.00;
		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$quantity += $row['quantity'];
		}
		
		return $quantity;
	}

	function getActiveCompanies(){
		$company = new SI_Company();
		return $company->retrieveSet("WHERE deleted = 'N' ORDER BY name");
	}
}

