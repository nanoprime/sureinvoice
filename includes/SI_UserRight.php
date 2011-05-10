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

class SI_UserRight{
	var $id, $user_id, $user_right, $right_value;

	var $error;

	function SI_UserRight(){
		$this->error = '';
		$this->id = 0;
		$this->user_id = 0;
		$this->user_right = '';
		$this->right_value = 0;

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->user_id = $values[1];
			$this->user_right = $values[2];
			$this->right_value = $values[3];
		}
	}

	function escapeStrings(){
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = addslashes($value);
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
		$result = $db_conn->query("INSERT INTO user_rights (user_id, user_right, right_value)".
		  " VALUES(".$this->user_id.", '".$this->user_right."', ".$this->right_value.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_UserRight::add() : ".$db_conn->getError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_UserRight::update() : UserRight id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE user_rights SET user_id = ".$this->user_id.", ".
		  "user_right = '".$this->user_right."', right_value = ".$this->right_value."".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_UserRight::update() : ".$db_conn->getError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_UserRight::delete() : UserRight id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM user_rights WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_UserRight::delete() : ".$db_conn->getError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_UserRight::get() : UserRight id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("SELECT  id, user_id, user_right, right_value".
		" FROM user_rights WHERE id = $id");
		if(!$result){
			$this->error = "SI_UserRight::get() : ".$db_conn->getError()."\n";
			return FALSE;
		}
		if($row=$result->fetchRow()){
			$this->_populateData($row);
		}else{
			$this->error = "Event::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	function retrieveSet($clause = ''){
		global $db_conn;
		$UserRight = array();

		$result = $db_conn->query("SELECT  id, user_id, user_right, right_value".
			" FROM user_rights ".$clause);

		if(!empty($clause)){
			$clause = trim($clause);
			if(strlen($clause) > 5){
				if(strtolower(substr($clause, 0, 5)) != "where" && strtolower(substr($clause, 0, 5)) != "order")
					$clause = "WHERE ".$clause;
			}else{
				$clause = "WHERE ".$clause;
			}
		}

		if(!$result){
			$this->error = "SI_UserRight::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			$temp =& new SI_UserRight($db_conn);
			$temp->_populateData($row);
			$UserRight[] =& $temp;

		}
		return $UserRight;
	}
	
	function getRightsForUser($id){
		global $db_conn;
		
		$id = intval($id);
		if($id)
			return SI_UserRight::retrieveSet("WHERE user_id = $id");
	}
}

?>