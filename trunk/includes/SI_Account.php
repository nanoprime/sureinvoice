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
class SI_Account{
	var $id, $name, $description, $type, $updated_ts;

	var $error;

	function SI_Account(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->description = '';
		$this->type = '';
		$this->updated_ts = 0;
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

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO accounts (name, description, type, updated_ts)".
		  " VALUES('".$this->name."', '".$this->description."', '".$this->type."', '".$this->updated_ts."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_Account::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_Account::update() : SI_Account id not set\n";
			return FALSE;
		}

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("UPDATE accounts SET name = '".$this->name."', ".
		  "description = '".$this->description."', type = '".$this->type."', updated_ts = '".$this->updated_ts."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Account::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Account::delete() : SI_Account id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM accounts WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Account::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Account::get() : SI_Account id not set\n";
			return FALSE;
		}

		$SI_Account = SI_Account::retrieveSet("WHERE id = $id", TRUE);
		if($SI_Account === FALSE){
			return FALSE;
		}

		if(isset($SI_Account[0])){
			$this->updateFromAssocArray($SI_Account[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_Account::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Method to retreive option tags for all companies
	 *
	 * This method will provide a string that contains
	 * the HTML option tags for all accounts in the 
	 * database sorted by Account Name.
	 * 
	 * If a account id is provided in the $selected
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

		$result = $db_conn->query("SELECT id, name FROM accounts ORDER BY name");

		if($result === FALSE){
			$this->error = "SI_Account::getSelectTags(): ".$db_conn->getLastError()."\n";
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

		$result = $db_conn->query("SELECT  id, name, description, type, updated_ts".
		  " FROM accounts ".$clause);

		if($result === FALSE){
			$this->error = "SI_Account::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$SI_Account[] = $row;
			}else{
				$temp =& new SI_Account();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$SI_Account[] =& $temp;
			}

		}

		return $SI_Account;
	}

	function exportQB($clause = ''){
		$accounts = $this->retrieveSet($clause);
		
		if($accounts === FALSE){
			return FALSE;
		}
		
		$exporter = new QBExporter();
		foreach($accounts as $account){
			if(!empty($account->name)){
				$out_account = array();
				$out_account['NAME'] = $account->name;
				$out_account['DESC'] = $account->description;
				$out_account['ACCNTTYPE'] = $account->type;
				if($exporter->addItem('Account', $out_account) === FALSE){
					$this->error = "SI_ItemCode::export(): Error adding code {$code->name}: ".$exporter->getLastError();
					return FALSE;
				}
			}
		}
		
		return $exporter->get_string();
	}

	function importQB($data){
		global $db_conn;
		
		if(!isset($data['Account']) || count($data['Account']) == 0){
			return TRUE;
		}
		
		foreach($data['Account'] as $qb_account){
			$cur_accounts = $this->retrieveSet("WHERE name = '".$db_conn->escapeString($qb_account['NAME'])."'");
			if($cur_accounts === FALSE){
				$this->error = "SI_Account::import(): Error looking for account with name of {$qb_account['NAME']}";
				return FALSE;
			}
			$account = NULL;
			if(count($cur_accounts) != 1){
				// Not found or more than one found so just add a new one
				$account = new SI_Account();
			}else{
				$account =& $cur_accounts[0];	
			}

			$account->name = $qb_account['NAME'];
			$account->description = $qb_account['DESC'];
			$account->type = $qb_account['ACCNTTYPE'];
						
			$result = FALSE;
			if($account->id > 0){
				$result = $account->update();
			}else{
				$result = $account->add();
			}
			if($result === FALSE){
				$this->error = "SI_Account::importQB(): Error adding account: ".$account->getLastError();
				return FALSE;
			}
		}
		
		return TRUE;
	}

	function getIDForName($name){
		global $db_conn;
		
		$accounts = SI_Account::retrieveSet("WHERE name LIKE '".$db_conn->escapeString($name)."'");
		if(is_array($accounts) && count($accounts) > 0){
			return $accounts[0]->id;
		}
		
		return 0;
	}
}

