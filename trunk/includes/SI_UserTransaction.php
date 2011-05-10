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
require_once('SI_TaskActivity.php');
require_once('SI_Check.php');

////////////////////////////////////////////////////////////
// Code generated by CodeGen (http://uversaconsulting.net).
// Insert custom code where indicated below.
////////////////////////////////////////////////////////////

// SI_UserTransaction Class Definition
////////////////////////////////////////////////////////////
class SI_UserTransaction{
	var $id, $user_id, $timestamp, $amount,
		$description;

	var $_ta; 
	
	var $_check;
	
	var $error;

	function SI_UserTransaction(){
		$this->error = '';
		$this->id = 0;
		$this->user_id = 0;
		$this->timestamp = 0;
		$this->amount = 0;
		$this->description = '';
		
		$this->_ta = FALSE;
		$this->_check = FALSE;
	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->user_id = $values[1];
			$this->timestamp = $values[2];
			$this->amount = $values[3];
			$this->description = $values[4];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['user_id'])) $this->user_id = $array['user_id'];
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
		$result = $db_conn->query("INSERT INTO user_transactions (user_id, timestamp, amount, description)".
			" VALUES(".$this->user_id.", ".$this->timestamp.", ".$this->amount.", '".$this->description."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id($db_conn->_conn);
			return TRUE;
		}else{
			$this->error = "SI_UserTransaction::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_UserTransaction::update() : UserTransaction id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE user_transactions SET user_id = ".$this->user_id.", ".
			"timestamp = ".$this->timestamp.", amount = ".$this->amount.", ".
			"description = '".$this->description."' ".
			" WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_UserTransaction::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_UserTransaction::delete() : UserTransaction id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM user_transactions WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_UserTransaction::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_UserTransaction::get() : UserTransaction id not set\n";
			return FALSE;
		}

		$UserTransaction = SI_UserTransaction::retrieveSet("WHERE id = $id", TRUE);
		if($UserTransaction === FALSE){
			return FALSE;
		}

		if(isset($UserTransaction[0])){
			$this->_populateData($UserTransaction[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_UserTransaction::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, user_id, timestamp, amount, description".
			" FROM user_transactions ".$clause);

		if(!$result){
			$this->error = "SI_UserTransaction::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
//		var_dump($result->getSql());
		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$UserTransaction[] = $row;
			}else{
				$temp =& new SI_UserTransaction();
				$temp->_populateData($row);
				$UserTransaction[] =& $temp;
			}

		}

		return $UserTransaction;
	}
// BEGIN - Custom SI_UserTransaction methods
////////////////////////////////////////////////////////////
	function getBalance($id){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$result = $db_conn->query("SELECT SUM(amount) FROM user_transactions WHERE user_id = ".intval($id));

		if($result === FALSE){
			$this->error = "SI_UserTransaction::getBalance(): Error looking up balance: ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$amount = 0.00;
		if($row = $result->fetchRow()){
			$amount = $row[0];
		}

		return $amount;
	}

	function getTransactions($id, $limit = 0, $offset = 0){
		if($limit > 0)
			$limit_sql = ' LIMIT '.intval($offset).','.intval($limit);
		else
			$limit_sql = '';

		$result = $this->retrieveSet("WHERE user_id = ".intval($id)." ORDER BY timestamp DESC ".$limit_sql);

		if($result === FALSE){
			return FALSE;
		}

		return $result;
	}

	function getTransactionCount($id){
		global $db_conn;
		
		$result = $db_conn->query("SELECT COUNT(*) FROM user_transactions WHERE user_id = ".intval($id));

		if($result === FALSE){
			$this->error = "SI_UserTransaction::getTransactionCount(): Error getting transactions: ".$db_conn->getLastError();
			return FALSE;
		}

		$row = $result->fetchRow();
		return $row[0];
	}

	function _getLinked(){
		$id = intval($this->id);
		if($id <= 0)
			return TRUE;
			
		if($this->_ta == FALSE){
			$ta = new SI_TaskActivity();
			$tas = $ta->retrieveSet("WHERE a.cost_trans_id = $id OR a.com_trans_id = $id");
			if($tas === FALSE){
				$this->error = "Error getting linked task: ".$ta->getLastError();
				return FALSE;	
			}
			if(count($tas) == 1){
				$this->_ta = &$tas[0];
			}
		}
		
		if($this->_check == FALSE){
			$check = new SI_Check();
			$checks = $check->retrieveSet("WHERE trans_id = $id");
			if($checks === FALSE){
				$this->error = "Error getting linked task: ".$check->getLastError();
				return FALSE;	
			}
			if(count($checks) == 1){
				$this->_check = &$checks[0];
			}
		}

		return TRUE;
	}
	
	function isCostTrans(){
		if($this->_getLinked() === FALSE)
			return FALSE;
			
		if($this->_ta != FALSE){
			if($this->id == $this->_ta->cost_trans_id)
				return TRUE;	
		}
		
		return FALSE;
	}

	function isComTrans(){
		if($this->_getLinked() === FALSE)
			return FALSE;
			
		if($this->_ta != FALSE){
			if($this->id == $this->_ta->com_trans_id)
				return TRUE;	
		}
		
		return FALSE;
	}

	function isCheckTrans(){
		if($this->_getLinked() === FALSE)
			return FALSE;
			
		if($this->_check != FALSE){
			if($this->id == $this->_check->trans_id)
				return TRUE;	
		}
		
		return FALSE;
	}
	
	function getType(){
		if($this->isCostTrans()){
			return 'Cost';
		}elseif($this->isComTrans()){
			return 'Commission';
		}elseif($this->isCheckTrans()){
			return 'Check';
		}
		
		return 'Unknown';
	}
// END - Custom SI_UserTransaction methods
////////////////////////////////////////////////////////////
}

