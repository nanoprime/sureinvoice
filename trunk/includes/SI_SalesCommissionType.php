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

class SI_SalesCommissionType{
	var $id, $name, $type, $rate;

	var $error;

	function SI_SalesCommissionType(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->type = '';
		$this->rate = 0.00;

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->name = $values[1];
			$this->type = $values[2];
			$this->rate = $values[3];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['name'])) $this->name = $array['name'];
		if(isset($array['type'])) $this->type = $array['type'];
		if(isset($array['rate'])) $this->rate = $array['rate'];
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
		$result = $db_conn->query("INSERT INTO sales_com_types (name, type, rate)".
		  " VALUES('".$this->name."', '".$this->type."', ".$this->rate.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_SalesCommissionType::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_SalesCommissionType::update() : SalesCommissionType id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE sales_com_types SET name = '".$this->name."', ".
		  "type = '".$this->type."', rate = ".$this->rate."".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_SalesCommissionType::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_SalesCommissionType::delete() : SalesCommissionType id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM sales_com_types WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_SalesCommissionType::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_SalesCommissionType::get() : SalesCommissionType id not set\n";
			return FALSE;
		}

		$SalesCommissionType = SI_SalesCommissionType::retrieveSet("WHERE id = $id", TRUE);
		if($SalesCommissionType === FALSE){
			return FALSE;
		}

		if(isset($SalesCommissionType[0])){
			$this->_populateData($SalesCommissionType[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_SalesCommissionType::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, name, type, rate".
		  " FROM sales_com_types ".$clause);

		if(!$result){
			$this->error = "SI_SalesCommissionType::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$SalesCommissionType[] = $row;
			}else{
				$temp =& new SI_SalesCommissionType();
				$temp->_populateData($row);
				$SalesCommissionType[] =& $temp;
			}

		}

		return $SalesCommissionType;
	}
// BEGIN - Custom SI_SalesCommissionType methods 
////////////////////////////////////////////////////////////
	function getSelectTags($selected = NULL){
		global $db_conn;

		$result = $db_conn->query("SELECT id, name FROM sales_com_types ORDER BY name");

		if($result === FALSE){
			$this->error = "SI_SalesCommissionType::getTypeSelectTags(): ".$db_conn->getLastError()."\n";
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

	function getTypeSelectTags($selected = NULL){
		$types = array('AMOUNT', 'PERCENT_NET', 'PERCENT_GROSS');

		foreach($types as $type){
			$sel_text = "";
			if($type == $selected)
				$sel_text = " SELECTED";
			$tags .= "<OPTION VALUE=\"".$type."\"".$sel_text.">".$type."</OPTION>\n";
		}
		return $tags;
	}

	function calculateCommission($price, $cost, $type = '', $rate = ''){
		$amount = 0.00;

		if($type == '')
			$type = $this->type;

		if($rate == '')
			$rate = $this->rate;

		if($type == 'PERCENT_NET'){
			$amount = ($price - $cost) * $rate;
		}elseif($type == 'PERCENT_GROSS'){
			$amount = $price * $rate;
		}elseif($type == 'AMOUNT'){
			$amount = $rate;
		}else{
			$this->error = "SaleCommissionType::calculateCommission(): Invalid commission type: $type";
			return FALSE;
		}

		if($amount < 0)
			$amount = 0.00;
			
		return $amount;
	}
// END - Custom SI_SalesCommissionType methods
////////////////////////////////////////////////////////////
}

