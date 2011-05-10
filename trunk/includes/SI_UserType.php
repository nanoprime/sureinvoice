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


// SI_UserType Class Definition 
////////////////////////////////////////////////////////////
class SI_UserType{
	var $id, $name, $resource, $start_page;

	var $error;

	function SI_UserType(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->resource = 'N';
		$this->start_page = 'my_projects.php';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->name = $values[1];
			$this->resource = $values[2];
			$this->start_page = $values[3];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['name'])) $this->name = $array['name'];
		if(isset($array['resource'])) $this->resource = $array['resource'];
		if(isset($array['start_page'])) $this->start_page = $array['start_page'];
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
		$result = $db_conn->query("INSERT INTO user_types (name, resource, start_page)".
		  " VALUES('".$this->name."', '".$this->resource."', '".$this->start_page."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_UserType::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_UserType::update() : UserType id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE user_types SET name = '".$this->name."', ".
		  "resource = '".$this->resource."', start_page = '".$this->start_page."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_UserType::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_UserType::delete() : UserType id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM user_types WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_UserType::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_UserType::get() : UserType id not set\n";
			return FALSE;
		}

		$UserType = SI_UserType::retrieveSet("WHERE id = $id", TRUE);
		if($UserType === FALSE){
			return FALSE;
		}

		if(isset($UserType[0])){
			$this->_populateData($UserType[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_UserType::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, name, resource, start_page".
		  " FROM user_types ".$clause);

		if(!$result){
			$this->error = "SI_UserType::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$UserType[] = $row;
			}else{
				$temp =& new SI_UserType();
				$temp->_populateData($row);
				$UserType[] =& $temp;
			}

		}

		return $UserType;
	}
// BEGIN - Custom SI_UserType methods 
////////////////////////////////////////////////////////////
	function getSelectTags($selected = NULL){
		global $db_conn;
		$tags = "";

		$result = $db_conn->query("SELECT id, name FROM user_types ORDER BY name");

		if($result === FALSE){
			$this->error = "SI_UserType::getSelectTags(): ".$db_conn->getLastError()."\n";
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


// END - Custom SI_UserType methods 
////////////////////////////////////////////////////////////
}

