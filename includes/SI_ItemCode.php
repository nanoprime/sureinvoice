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
 * SI_ItemCode class
 * 
 * This class provides methods for dealing with
 * item codes in SureInvoice. You can add, modify or
 * remove item codes plus get any information related
 * to an item code by using the methods in this class.
 * 
 * Many methods in this class rely on a global variable 
 * called $db_conn that is a {@link DBConn} object for 
 * database access.
 * 
 * @package com.uversainc.sureinvoice 
 * @author Cory Powers <cory@uversainc.com>
 * @copyright 2005 Uversa, Inc
 */
class SI_ItemCode{
	/**
	 * SureInvoice item code id, greater than 0 if this
	 * object is populated. This is an auto_increment
	 * value from the datbase;
	 * 
	 * @access public
	 * @var int
	 */
	var $id;
	
	/**
	 * This item codes' actual code, this is limited to
	 * 25 characters in the database
	 * 
	 * @access public
	 * @var string
	 */
	var $code;
	
	/**
	 * A short, 255 char, description of the item code
	 * 
	 * @access public
	 * @var string
	 */
	var $description;
	
	/**
	 * The cost for this item code, this cost does not
	 * apply for item codes that are used for time entries
	 * 
	 * @access public
	 * @var float
	 */
	var $cost; 
	 
	/**
	 * The default price for this item code, this price
	 * can be overriden by using creating a {@link SI_CompanyPrice}
	 * item for this code that will apply to a particular
	 * company
	 * 
	 * @access public
	 * @var float
	 */
	var $price;

	/**
	 * Indicates whether this item code is taxable or not
	 * 
	 * Valid values are Y or N
	 * 
	 * @access public
	 * @var string
	 */
	var $taxable;

	/**
	 * The income account id for this item code
	 * 
	 * @access public
	 * @var int
	 */
	var $income_account_id;

	/**
	 * The expense account id for this item code
	 * 
	 * @access public
	 * @var int
	 */
	var $expense_account_id;

	/**
	 * The unix timestamp of the last update to this item code
	 * to an invoice for exporting.
	 * 
	 * @access public
	 * @var int
	 */
	var $updated_ts;

	/**
	 * Holds the most recent error that has occured call the
	 * getLastError() method to get the error message
	 * 
	 * @var string
	 * @access private
	 */
	var $error;

	var $_account;

	/**
	 * SI_ItemCode Constructor
	 * 
	 * The constructor sets up the default values for 
	 * an item code.
	 */
	function SI_ItemCode(){
		$this->error = '';
		$this->id = 0;
		$this->code = '';
		$this->description = '';
		$this->cost = 0.00;
		$this->price = 0.00;
		$this->taxable = 'N';
		$this->income_account_id = 0;
		$this->expense_account_id = 0;
		$this->updated_ts = 0;
		
		$this->_income_account = FALSE;
		$this->_expense_account = FALSE;
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
	 * Method to add a new item code
	 * 
	 * This method will add a new item code
	 * to the SureInvoice database. The $id of
	 * this item code will be updated with the 
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

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO item_codes (code, description, cost, price, taxable, income_account_id, expense_account_id, updated_ts)".
		  " VALUES('".$this->code."', '".$this->description."', ".$this->cost.", ".$this->price.", '".$this->taxable."', '".$this->income_account_id."', '".$this->expense_account_id."', '".$this->updated_ts."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_ItemCode::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to update an item code
	 * 
	 * This method will update an existing item code
	 * in the SureInvoice database. The $id of
	 * this item code must be greater than 0.
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
			$this->error = "SI_ItemCode::update() : SI_ItemCode id not set\n";
			return FALSE;
		}

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("UPDATE item_codes SET code = '".$this->code."', ".
		  "description = '".$this->description."', cost = ".$this->cost.", ".
		  "price = ".$this->price.", taxable = '".$this->taxable."', income_account_id = '".$this->income_account_id."', expense_account_id = '".$this->expense_account_id."', updated_ts = '".$this->updated_ts."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_ItemCode::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to remove an item code
	 * 
	 * This method will delete an item code
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
			$this->error = "SI_ItemCode::delete() : SI_ItemCode id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM company_prices WHERE item_code_id = $id");
		if($result === FALSE){
			$this->error = "SI_ItemCode::delete(): Error deleting custom pricing for this item: ".$db_conn->getLastError()."\n";
			return FALSE;			
		}
		
		$result = $db_conn->query("DELETE FROM item_codes WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_ItemCode::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to retreive a item code
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
			$this->error = "SI_ItemCode::get() : SI_ItemCode id not set\n";
			return FALSE;
		}

		$SI_ItemCode = SI_ItemCode::retrieveSet("WHERE id = $id", TRUE);
		if($SI_ItemCode === FALSE){
			return FALSE;
		}

		if(isset($SI_ItemCode[0])){
			$this->updateFromAssocArray($SI_ItemCode[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_ItemCode::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Method to retreive a set of item codes
	 *
	 * You can provide a where clause to this
	 * function to limit the result set. The 
	 * clause could also be just an order by
	 * clause.
	 * 
	 * The function will return an Array of
	 * SI_ItemCode objects for each item code
	 * found. 
	 *
	 * @param string $clause Optional where or order by clause to limit the results
	 * @param bool $raw If TRUE result will be an indexed array instead of an Array of Objects
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return mixed Array of SI_ItemCode objects or FALSE on error
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

		$result = $db_conn->query("SELECT  id, code, description, cost, price, taxable, income_account_id, expense_account_id, updated_ts".
		  " FROM item_codes ".$clause);

		if($result === FALSE){
			$this->error = "SI_ItemCode::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$SI_ItemCode[] = $row;
			}else{
				$temp =& new SI_ItemCode();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$SI_ItemCode[] =& $temp;
			}

		}

		return $SI_ItemCode;
	}

	/**
	 * Method to retreive option tags for all item codes
	 *
	 * This method will provide a string that contains
	 * the HTML option tags for all item codes in the 
	 * database sorted by item code.
	 * 
	 * If an item code id is provided in the $selected
	 * argument, then that option tag will be marked
	 * as selected.
	 *
	 * The exculded_ids argument should be an array of
	 * the any item code ids that should not be included
	 * in the output. 
	 *
	 * @global DBConn Database access object
	 * @access public
	 * @static
	 * @see getLastError()
	 * @return string|FALSE HTML option tags or FALSE on error
	 */
	function getSelectTags($selected = NULL, $excluded_ids = NULL){
		global $db_conn;

		$result = $db_conn->query("SELECT id, code, description FROM item_codes ORDER BY code");

		if($result === FALSE){
			$this->error = "SI_Company::getSelectTags(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}


		while($row=$result->fetchRow()){
			$sel_text = "";
			if($row[0]==$selected)
				$sel_text = " SELECTED";
			if(!in_array($row[0], $excluded_ids)){
				$tags .= "<OPTION VALUE=\"".$row[0]."\"".$sel_text.">".$row[1].' - '.$row[2]."</OPTION>\n";
			}
		}
		return $tags;
	}

	/**
	 * Get list of item codes with company pricing overrides
	 * 
	 * This method will return an array of all item codes
	 * like the retrieveSet funtion except it will include
	 * any company pricing overrides for a company. 
	 * 
	 * This set of data should only be used as read only.
	 * 
	 * @param string $company_id ID of the company
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return mixed Array of SI_ItemCode objects or FALSE on error
	 */
	function getCompanyPricedCodes($company_id){
		global $db_conn;
		
		$clause = "AND cp.company_id = $company_id";
		
		$sql = "
			SELECT id, code, description, cost, IFNULL(cp.price, ic.price) AS price, taxable, income_account_id, expense_account_id,  
			IFNULL(cp.tax_rate, '".$GLOBALS['CONFIG']['tax_rate']."') AS tax_rate
			FROM item_codes AS ic LEFT JOIN company_prices AS cp ON cp.item_code_id = ic.id AND cp.company_id = $company_id";
		
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_ItemCode::getCompanyPricedCodes(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$SI_ItemCode = array();
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_ItemCode();
			$temp->updateFromAssocArray($row);
			$temp->stripSlashes();
			$SI_ItemCode[] =& $temp;
		}

		return $SI_ItemCode;		
	}
	
	/**
	 * Get price for a given item code and company
	 * 
	 * This method will return a price for the provided
	 * item code and company. This method will take into 
	 * account any pricing overrides. 
	 * 
	 * @param string $company_id ID of the company
	 * @param string $item_code_id ID of the item code
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return mixed string of price or FALSE on error
	 */
	function getCompanyPrice($company_id, $item_code_id){
		global $db_conn;
		
		$clause = "AND cp.company_id = $company_id";
		
		$sql = "
			SELECT IFNULL(cp.price, ic.price) AS price
			FROM item_codes AS ic LEFT JOIN company_prices AS cp ON cp.item_code_id = ic.id AND cp.company_id = $company_id
			WHERE ic.id = $item_code_id ";

		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_ItemCode::getCompanyPrice(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		if($row = $result->fetchArray(MYSQL_ASSOC)){
			return $row['price'];
		}
		
		$this->error = 	"SI_ItemCode::getCompanyPrice(): Item code not found\n";
		return FALSE;
	}


	/**
	 * Get the tax rate for this item code
	 * 
	 * This method will return tax rate of this item code, 
	 * it will include any company pricing tax overrides for a company. 
	 * 
	 * @param string $company_id ID of the company
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return float|FALSE tax rate or FALSE on error
	 */
	function getTaxRate($company_id){
		global $db_conn;
		
		$item_code_id = $this->id;
				
		$sql = "
			SELECT IFNULL(cp.tax_rate, '".$GLOBALS['CONFIG']['tax_rate']."') AS tax_rate
			FROM item_codes AS ic LEFT JOIN company_prices AS cp ON cp.item_code_id = ic.id AND cp.company_id = $company_id WHERE ic.id = $item_code_id";

		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_ItemCode::getTaxRate(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$tax_rate = 0.00;
		if($row=$result->fetchArray(MYSQL_ASSOC)){
			$tax_rate = $row['tax_rate'];
		}

		return $tax_rate;		
	}

	function exportQB($clause = ''){
		$codes = $this->retrieveSet($clause);
		
		if($codes === FALSE){
			return FALSE;
		}
		
		$exporter = new QBExporter();
		foreach($codes as $code){
			if(!empty($code->code)){
				$out_code = array();
				$out_code['NAME'] = $code->code;
				$out_code['DESC'] = $code->description;
				$out_code['COST'] = $code->cost;
				$out_code['PRICE'] = $code->price;
				$out_code['TAXABLE'] = $code->taxable;
				$out_code['ACCNT'] = $code->getIncomeAccountName();
				$out_code['COGSACCNT'] = $code->getExpenseAccountName();
				if($exporter->addItem('Item', $out_code) === FALSE){
					$this->error = "SI_ItemCode::export(): Error adding code {$code->name}: ".$exporter->getLastError();
					return FALSE;
				}
			}
		}
		
		return $exporter->get_string();
	}

	function importQB($data){
		if(!isset($data['Item']) || count($data['Item']) == 0){
			return TRUE;
		}
		
		foreach($data['Item'] as $qb_code){
			$cur_codes = $this->retrieveSet("WHERE code = '".mysql_real_escape_string($qb_code['NAME'])."'");
			if($cur_codes === FALSE){
				$this->error = "SI_ItemCode::import(): Error looking for item code with code of {$qb_code['NAME']}";
				return FALSE;
			}
			$code = NULL;
			if(count($cur_codes) != 1){
				// Not found or more than one found so just add a new one
				$code = new SI_ItemCode();
			}else{
				$code =& $cur_codes[0];	
			}

			$code->code = $qb_code['NAME'];
			$code->description = $qb_code['DESC'];
			if(!empty($qb_code['COST'])) $code->cost = preg_replace('/["$,]/', '', $qb_code['COST']);
			if(!empty($qb_code['PRICE'])) $code->price = preg_replace('/["$,]/', '', $qb_code['PRICE']);
			$code->taxable = $qb_code['TAXABLE'];
			$code->income_account_id = SI_Account::getIDForName($qb_code['ACCNT']);
			$code->expense_account_id = SI_Account::getIDForName($qb_code['COGSACCNT']);
						
			$result = FALSE;
			if($code->id > 0){
				$result = $code->update();
			}else{
				$result = $code->add();
			}
			if($result === FALSE){
				$this->error = "SI_ItemCode::importQB(): Error adding code: ".$code->getLastError();
				return FALSE;
			}
		}
		
		return TRUE;
	}

	function getIncomeAccount(){
		if($this->_income_account === FALSE){
			$this->_income_account = new SI_Account();
			if($this->income_account_id > 0){
				if($this->_income_account->get($this->income_account_id) === FALSE){
					$this->error = "SI_InvoiceLine::getItemCode(): Error getting item code: ".$this->_income_account->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_income_account;
	}
	
	function getIncomeAccountName(){
		if($this->getIncomeAccount() === FALSE)
			return '';
			
		return $this->_income_account->name;	
	}

	function getExpenseAccount(){
		if($this->_expense_account === FALSE){
			$this->_expense_account = new SI_Account();
			if($this->expense_account_id > 0){
				if($this->_expense_account->get($this->expense_account_id) === FALSE){
					$this->error = "SI_InvoiceLine::getItemCode(): Error getting item code: ".$this->_expense_account->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_expense_account;
	}
	
	function getExpenseAccountName(){
		if($this->getExpenseAccount() === FALSE)
			return '';
			
		return $this->_expense_account->name;	
	}
	
	function getCodeName($ic_id = null, $long = false){
		if(is_null($ic_id)){
			$ic_id = $this->id;
		}
		
		$ic = new SI_ItemCode();
		$results = $ic->retrieveSet('WHERE id = '.$ic_id, true);

		if($results == false){
			$this->error = "Error looking up item code: ".$ic->getLastError();
			return FALSE;
		}
		
		$name = '';
		foreach($results as $row){
			$name = $row['code'];
			if($long){
				$name .= ' - '.$row['description'];
			}
		}
		
		return $name;
	}
	
}

