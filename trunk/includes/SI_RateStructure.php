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
 * Pull in the SI_ItemCode class
 */
require_once("SI_ItemCode.php");

/**
 * Pull in the SI_RateStructureLine class
 */
require_once("SI_RateStructureLine.php");

class SI_RateStructure{
	var $id, $name, $type, $discount_item_code_id;

	var $item_code_ids;
	
	var $lines;
	
	var $error;

	function SI_RateStructure(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->type = '';
		$this->discount_item_code_id = 0;
		$this->item_code_ids = FALSE;
		$this->lines = FALSE;
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
		$result = $db_conn->query("INSERT INTO rate_structures (name, type, discount_item_code_id)".
		  " VALUES('".$this->name."', '".$this->type."', '".$this->discount_item_code_id."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_RateStructure::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_RateStructure::update() : SI_RateStructure id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE rate_structures SET name = '".$this->name."', ".
		  "type = '".$this->type."', discount_item_code_id = '".$this->discount_item_code_id."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_RateStructure::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_RateStructure::delete() : SI_RateStructure id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM rate_structures WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_RateStructure::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_RateStructure::get() : SI_RateStructure id not set\n";
			return FALSE;
		}

		$SI_RateStructure = SI_RateStructure::retrieveSet("WHERE id = $id", TRUE);
		if($SI_RateStructure === FALSE){
			return FALSE;
		}

		if(isset($SI_RateStructure[0])){
			$this->updateFromAssocArray($SI_RateStructure[0]);
			if($this->_populateItemCodes() === FALSE) return FALSE;
			$this->stripSlashes();
		}else{
			$this->error = "SI_RateStructure::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, name, type, discount_item_code_id".
		  " FROM rate_structures ".$clause);

		if($result === FALSE){
			$this->error = "SI_RateStructure::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$SI_RateStructure[] = $row;
			}else{
				$temp =& new SI_RateStructure();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$SI_RateStructure[] =& $temp;
			}

		}

		return $SI_RateStructure;
	}

	function _populateItemCodes(){
		global $db_conn;
		
		$sql = "SELECT item_code_id FROM rate_structure_item_codes WHERE rate_structure_id = '".$db_conn->escapeString($this->id)."'";
		$result = $db_conn->query($sql);
		
		if($result === FALSE){
			$this->error = "SI_RateStructure::_populateItemCodes(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		$this->item_code_ids = array();
		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$this->item_code_ids[] = $row['item_code_id'];
		}

		return TRUE;
	}
	
	function addItemCode($item_code_id){
		global $db_conn;
		
		$sql = "REPLACE INTO rate_structure_item_codes SET rate_structure_id = '".$db_conn->escapeString($this->id)."', item_code_id = '".$db_conn->escapeString($item_code_id)."'";
		$result = $db_conn->query($sql);
		
		if($result === FALSE){
			$this->error = "SI_RateStructure::addItemCode(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		return TRUE;
	}
	
	function deleteItemCode($item_code_id){
		global $db_conn;
		
		$sql = "DELETE FROM rate_structure_item_codes WHERE rate_structure_id = '".$db_conn->escapeString($this->id)."' AND item_code_id = '".$db_conn->escapeString($item_code_id)."'";
		$result = $db_conn->query($sql);
		
		if($result === FALSE){
			$this->error = "SI_RateStructure::deleteItemCodes(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		return TRUE;
	}

	function getItemCodes(){
		if($this->_populateItemCodes() === FALSE) return FALSE;
		
		if(count($this->item_code_ids) == 0) return array();
		
		$ic = new SI_ItemCode();
		$item_codes = $ic->retrieveSet('WHERE id IN ('.join(',', $this->item_code_ids).')'); 
		if($item_codes === FALSE){
			$this->error = "SI_RateStructure::getItemCodes(): ".$ic->getLastError()."\n";
			return FALSE;	
		}
		
		return $item_codes;
	}

	function _populateLines(){
		
		$line = new SI_RateStructureLine();
		$lines = $line->retrieveSet("WHERE rate_structure_id = '{$this->id}' ORDER by low, high"); 
		if($lines === FALSE){
			$this->error = "SI_RateStructure::_populateLines(): ".$line->getLastError()."\n";
			return FALSE;	
		}
		
		$this->lines =& $lines;
		return TRUE;
	}

	function getLines(){
		if($this->_populateLines() === FALSE) return FALSE;
		
		return $this->lines;
	}

	function getTypeSelectTags($selected){
	
		$options = array(
			'MONTHLY' => 'Monthly discount, discount is applied on the first invoice of the following month',
			'INVOICE' => 'Per invoice discount, discount is applied to each invoice'
		);
		
		$html = '';
		foreach($options as $value => $label){
			if($value == $selected){
				$selected_html = ' SELECTED ';
			}else{
				$selected_html = '';
			}
			$html .= "<OPTION VALUE=\"$value\"$selected_html>$label</OPTION>\n";
		}
		
		return $html;
	}
	
	function validateNewLine($line){
		if($this->_populateLines() === FALSE) return FALSE;
		
		foreach($this->lines as $current_line){
			$current_line_range = $current_line->high == 0 ? "{$current_line->low}+" : "{$current_line->low} - {$current_line->high}";
			
			if($line->high <= $current_line->high && $line->high > $current_line->low){
				$this->error = "SI_RateStructure::validateNewLine(): New line is in range of current line $current_line_range\n";
				return FALSE;	
			}

			if($line->low < $current_line->high && $line->low >= $current_line->low){
				$this->error = "SI_RateStructure::validateNewLine(): New line is in range of current line $current_line_range\n";
				return FALSE;	
			}

			if($line->low <= $current_line->low && $line->high >= $current_line->high){
				$this->error = "SI_RateStructure::validateNewLine(): New line range conflicts with current line $current_line_range\n";
				return FALSE;	
			}

			if($current_line->high == 0 && $line->low >= $current_line->low){
				$this->error = "SI_RateStructure::validateNewLine(): New line range conflicts with current line $current_line_range\n";
				return FALSE;	
			}
		}
		
		return TRUE;
	}

	/**
	 * Method to retreive option tags for all rate structures
	 *
	 * This method will provide a string that contains
	 * the HTML option tags for all rate structures in the 
	 * database sorted by Name.
	 * 
	 * If a rate structure id is provided in the $selected
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

		$result = $db_conn->query("SELECT id, name FROM rate_structures ORDER BY name");

		if($result === FALSE){
			$this->error = "SI_Company::getSelectTags(): ".$db_conn->getLastError()."\n";
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
}

