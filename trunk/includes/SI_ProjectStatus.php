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
class SI_ProjectStatus{
	var $id, $name, $completed;

	var $error;

	function SI_ProjectStatus(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->completed = '';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->name = $values[1];
			$this->completed = $values[2];
		}
	}

	function updateFromAssocArray($array){
		$this->id = $array['id'];
		$this->name = $array['name'];
		$this->completed = $array['completed'];
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
		$result = $db_conn->query("INSERT INTO project_statuses (name, completed)".
		  " VALUES('".$this->name."', '".$this->completed."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_ProjectStatus::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_ProjectStatus::update() : ProjectStatus id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE project_statuses SET name = '".$this->name."', ".
		  "completed = '".$this->completed."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_ProjectStatus::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_ProjectStatus::delete() : ProjectStatus id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM project_statuses WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_ProjectStatus::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_ProjectStatus::get() : ProjectStatus id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("SELECT  id, name, completed".
	  " FROM project_statuses WHERE id = $id");
		if(!$result){
			$this->error = "SI_ProjectStatus::get() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		if($row=$result->fetchRow()){
			$this->_populateData($row);
		}else{
			$this->error = "SI_ProjectStatus::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}
	function retrieveSet($clause = ''){
		global $db_conn;
		$result = $db_conn->query("SELECT  id, name, completed".
			" FROM project_statuses ".$clause);

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
			$this->error = "SI_ProjectStatus::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			$temp =& new SI_ProjectStatus();
			$temp->_populateData($row);
			$ProjectStatus[] =& $temp;

		}
		return $ProjectStatus;
	}
// BEGIN - Custom SI_ProjectStatus methods 
////////////////////////////////////////////////////////////
	function getSelectTags($selected = 0){
		global $db_conn;
		$tags = "";
		
		$result = $db_conn->query("SELECT id, name FROM project_statuses ORDER BY name");
		
		if($result === FALSE){
			$this->error = "SI_ProjectStatus::getSelectTags(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		if($selected == 0){
			$selected = $GLOBALS['CONFIG']['default_project_status_id'];
		}

		while($row=$result->fetchRow()){
			$sel_text = "";
			if($row[0]==$selected)
				$sel_text = " SELECTED";
			$tags .= "<OPTION VALUE=\"".$row[0]."\"".$sel_text.">".$row[1]."</OPTION>\n";
		}
		return $tags;
	}


// END - Custom SI_ProjectStatus methods 
////////////////////////////////////////////////////////////
}