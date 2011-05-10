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
class SI_CompanyTransaction{
	var $id, $company_id, $timestamp, $amount, 
	  $description;

	var $error;

	function SI_CompanyTransaction(){
		$this->error = '';
		$this->id = 0;
		$this->company_id = 0;
		$this->timestamp = 0;
		$this->amount = 0;
		$this->description = '';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->company_id = $values[1];
			$this->timestamp = $values[2];
			$this->amount = $values[3];
			$this->description = $values[4];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['company_id'])) $this->company_id = $array['company_id'];
		if(isset($array['timestamp'])) $this->timestamp = $array['timestamp'];
		if(isset($array['amount'])) $this->amount = $array['amount'];
		if(isset($array['description'])) $this->description = $array['description'];
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
		$result = $db_conn->query("INSERT INTO company_transactions (company_id, timestamp, amount, description)".
		  " VALUES(".$this->company_id.", ".$this->timestamp.", ".$this->amount.", '".$this->description."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_CompanyTransaction::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_CompanyTransaction::update() : CompanyTransaction id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE company_transactions SET company_id = ".$this->company_id.", ".
		  "timestamp = ".$this->timestamp.", amount = ".$this->amount.", ".
		  "description = '".$this->description."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_CompanyTransaction::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_CompanyTransaction::delete() : CompanyTransaction id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM company_transactions WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_CompanyTransaction::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_CompanyTransaction::get() : CompanyTransaction id not set\n";
			return FALSE;
		}

		$CompanyTransaction = SI_CompanyTransaction::retrieveSet("WHERE id = $id", TRUE);
		if($CompanyTransaction === FALSE){
			return FALSE;
		}

		if(isset($CompanyTransaction[0])){
			$this->_populateData($CompanyTransaction[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_CompanyTransaction::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, company_id, timestamp, amount, description".
		  " FROM company_transactions ".$clause);

		if(!$result){
			$this->error = "SI_CompanyTransaction::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$CompanyTransaction[] = $row;
			}else{
				$temp =& new SI_CompanyTransaction();
				$temp->_populateData($row);
				$CompanyTransaction[] =& $temp;
			}

		}

		return $CompanyTransaction;
	}
// BEGIN - Custom SI_CompanyTransaction methods 
////////////////////////////////////////////////////////////
	function getBalance($id){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$result = $db_conn->query("SELECT SUM(amount) FROM company_transactions WHERE company_id = ".intval($id));

		if($result === FALSE){
			$this->error = "SI_CompanyTransaction::getBalance(): Error looking up balance: ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$amount = 0.00;
		if($row = $result->fetchRow()){
			$amount = $row[0];
		}

		return $amount;
	}

	function getTransactions($id, $limit = 0){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		if($limit > 0)
			$limit_sql = ' LIMIT '.intval($limit);
		else
			$limit_sql = '';

		$result = $this->retrieveSet("WHERE company_id = ".intval($id)." ORDER BY timestamp DESC ".$limit_sql);

		if($result === FALSE){
			return FALSE;
		}

		return $result;
	}


// END - Custom SI_CompanyTransaction methods 
////////////////////////////////////////////////////////////
}

