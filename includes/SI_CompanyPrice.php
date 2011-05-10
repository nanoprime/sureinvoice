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
 * SI_CompanyPrice class
 * 
 * A SI_CompanyPrice object will override the default price
 * for a particular item code and company. SureInvoice will
 * use the price setup in the SI_CompanyPrice instead of the
 * default price for the item code wherever applicable
 * 
 * This class provides methods for dealing with
 * company prices in SureInvoice. You can add, modify or
 * remove company pricing plus get any information related
 * to a company price.
 * 
 * Many methods in this class rely on a global variable 
 * called $db_conn that is a {@link DBConn} object for 
 * database access.
 * 
 * @package com.uversainc.sureinvoice 
 * @author Cory Powers <cory@uversainc.com>
 * @copyright 2005 Uversa, Inc
 */

class SI_CompanyPrice{
	/**
	 * SureInvoice company id, greater than 0 if this
	 * object is populated.
	 * 
	 * @access public
	 * @var int
	 */
	var $company_id;
	
	/**
	 * SureInvoice item code id, greater than 0 if this
	 * object is populated. 
	 * 
	 * @access public
	 * @var int
	 */
	var $item_code_id;
	
	/**
	 * The price for this item code for the associated
	 * company.
	 * 
	 * @access public
	 * @var float
	 */
	var $price;

	/**
	 * The tax rate for this item code for the associated
	 * company.
	 * 
	 * @access public
	 * @var float
	 */
	var $tax_rate;

	/**
	 * This is a read only variable of the item code
	 * associated with this SI_CompanyPrice object.
	 * This value is populated from the database by
	 * the retrieve set method.
	 * 
	 * @access public
	 * @var string
	 */
	var $code;

	/**
	 * This is a read only variable of the item code description
	 * associated with this SI_CompanyPrice object.
	 * This value is populated from the database by
	 * the retrieve set method.
	 * 
	 * @access public
	 * @var string
	 */
	var $description;

	/**
	 * This is a read only variable of the item code cost
	 * associated with this SI_CompanyPrice object.
	 * This value is populated from the database by
	 * the retrieve set method.
	 * 
	 * @access public
	 * @var float
	 */
	var $cost;

	/**
	 * This is a read only variable of the taxable setting for
	 * the Item Code associated with this SI_CompanyPrice object.
	 * This value is populated from the database by
	 * the retrieve set method.
	 * 
	 * @access public
	 * @var string
	 */
	var $taxable;

	/**
	 * This is a read only variable of the company name
	 * associated with this SI_CompanyPrice object.
	 * This value is populated from the database by
	 * the retrieve set method.
	 * 
	 * @access public
	 * @var string
	 */
	var $name;

	/**
	 * Holds the most recent error that has occured call the
	 * getLastError() method to get the error message
	 * 
	 * @var string
	 * @access private
	 */
	var $error;

	/**
	 * SI_CompanyPrice Constructor
	 * 
	 * The constructor sets up the default values for 
	 * a company price object.
	 */
	function SI_CompanyPrice(){
		$this->error = '';
		$this->company_id = 0;
		$this->item_code_id = 0;
		$this->price = 0.00;
		$this->tax_rate = 0.00;
		$this->taxable = 'N';
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
	 * Method to add a new company price
	 * 
	 * This method will add a new company price
	 * to the SureInvoice database. The $id of
	 * this company price will be updated with the 
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
		$result = $db_conn->query("INSERT INTO company_prices (company_id, item_code_id, price, tax_rate)".
		  " VALUES(".$this->company_id.",".$this->item_code_id.", ".$this->price.", ".$this->tax_rate.")");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_CompanyPrice::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to update a company price
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

		if(!isset($this->company_id)){
			$this->error = "SI_CompanyPrice::update() : SI_CompanyPrice company_id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("REPLACE INTO company_prices SET company_id = ".$this->company_id.
			", item_code_id = ".$this->item_code_id.", price = ".$this->price.", tax_rate = ".$this->tax_rate);
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_CompanyPrice::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to remove a company
	 * 
	 * This method will delete a company price based on 
	 * the provided item_code_id and company_id
	 * from the SureInvoice database. 
	 * 
	 * If the $company_id param is NULL then $company_id
	 * of the current object will be used
	 * as the ID to delete.
	 * 
	 * If the $item_code_id param is NULL then $item_code_id
	 * of the current object will be used
	 * as the ID to delete.
	 *
	 * @param int|NULL $company_id ID of the company id or NULL to use $this->company_id
	 * @param int|NULL $item_code_id ID of the item code or NULL to use $this->item_code_id
	 * @static 
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return bool TRUE on success or FALSE on error
	 */
	function delete($company_id = NULL, $item_code_id = NULL){
		global $db_conn;

		if(!isset($company_id)){
			$company_id = $this->company_id;
		}

		if(!isset($item_code_id)){
			$item_code_id = $this->item_code_id;
		}

		if(!isset($company_id) || !isset($item_code_id)){
			$this->error = "SI_CompanyPrice::delete() : SI_CompanyPrice company_id or item_code_id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM company_prices WHERE company_id = $company_id AND item_code_id = $item_code_id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_CompanyPrice::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	/**
	 * Method to retreive a company price
	 * 
	 * This method will populate this
	 * instance with the data from the 
	 * SureInvoice database for the 
	 * provided $company_id and $item_code_id. 
	 * 
	 * If the $company_id param is NULL then $company_id
	 * of the current object will be used
	 * as the ID to delete.
	 * 
	 * If the $item_code_id param is NULL then $item_code_id
	 * of the current object will be used
	 * as the ID to delete.
	 *
	 * @param int|NULL $company_id ID of the company id or NULL to use $this->company_id
	 * @param int|NULL $item_code_id ID of the item code or NULL to use $this->item_code_id
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return bool TRUE on success or FALSE on error
	 */
	function get($company_id = NULL, $item_code_id = NULL){
		global $db_conn;

		if(!isset($company_id)){
			$company_id = $this->company_id;
		}

		if(!isset($item_code_id)){
			$item_code_id = $this->item_code_id;
		}

		if(!isset($company_id) || !isset($item_code_id)){
			$this->error = "SI_CompanyPrice::get() : SI_CompanyPrice company_id or item_code_id not set\n";
			return FALSE;
		}

		$SI_CompanyPrice = SI_CompanyPrice::retrieveSet("WHERE cp.company_id = $company_id AND cp.item_code_id = $item_code_id", TRUE);
		if($SI_CompanyPrice === FALSE){
			return FALSE;
		}

		if(isset($SI_CompanyPrice[0])){
			$this->updateFromAssocArray($SI_CompanyPrice[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_CompanyPrice::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Method to retreive a set of company prices
	 *
	 * You can provide a where clause to this
	 * function to limit the result set. The 
	 * clause could also be just an order by
	 * clause.
	 * 
	 * The function will return an Array of
	 * SI_CompanyPrice objects for each company
	 * found. 
	 *
	 * @param string $clause Optional where or order by clause to limit the results
	 * @param bool $raw If TRUE result will be a 2D array instead of an Array of Objects
	 * @access public
	 * @global DBConn Database access object
	 * @see getLastError()
	 * @return mixed Array of SI_CompanyPrice objects or FALSE on error
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

		$result = $db_conn->query("
		SELECT cp.company_id, cp.item_code_id, cp.price, cp.tax_rate, ic.cost, ic.code, ic.description, ic.taxable, c.name
		FROM company_prices AS cp
		LEFT JOIN item_codes AS ic ON ic.id = cp.item_code_id
		LEFT JOIN companies AS c ON c.id = cp.company_id "
		.$clause);

		if($result === FALSE){
			$this->error = "SI_CompanyPrice::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$SI_CompanyPrice[] = $row;
			}else{
				$temp =& new SI_CompanyPrice();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$SI_CompanyPrice[] =& $temp;
			}

		}

		return $SI_CompanyPrice;
	}
}

