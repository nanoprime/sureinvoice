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
class SI_RateStructureLine{
	var $id, $rate_structure_id, $low, $high, 
	  $discount;

	var $error;

	function SI_RateStructureLine(){
		$this->error = '';
		$this->id = 0;
		$this->rate_structure_id = 0;
		$this->low = 0;
		$this->high = 0;
		$this->discount = 0;

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

		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO rate_structure_lines (rate_structure_id, low, high, discount)".
		  " VALUES(".$this->rate_structure_id.", ".$this->low.", ".$this->high.", ".$this->discount.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_RateStructureLine::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_RateStructureLine::update() : SI_RateStructureLine id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE rate_structure_lines SET rate_structure_id = ".$this->rate_structure_id.", ".
		  "low = ".$this->low.", high = ".$this->high.", ".
		  "discount = ".$this->discount."".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_RateStructureLine::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_RateStructureLine::delete() : SI_RateStructureLine id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM rate_structure_lines WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_RateStructureLine::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_RateStructureLine::get() : SI_RateStructureLine id not set\n";
			return FALSE;
		}

		$SI_RateStructureLine = SI_RateStructureLine::retrieveSet("WHERE id = $id", TRUE);
		if($SI_RateStructureLine === FALSE){
			return FALSE;
		}

		if(isset($SI_RateStructureLine[0])){
			$this->updateFromAssocArray($SI_RateStructureLine[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_RateStructureLine::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, rate_structure_id, low, high, discount".
		  " FROM rate_structure_lines ".$clause);

		if($result === FALSE){
			$this->error = "SI_RateStructureLine::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$SI_RateStructureLine[] = $row;
			}else{
				$temp =& new SI_RateStructureLine();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$SI_RateStructureLine[] =& $temp;
			}

		}

		return $SI_RateStructureLine;
	}
}

