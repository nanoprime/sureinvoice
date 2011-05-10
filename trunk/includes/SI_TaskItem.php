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

define("TI_ORDER_UP", 1);
define("TI_ORDER_DOWN", 2);

// SI_TaskItem Class Definition 
////////////////////////////////////////////////////////////
class SI_TaskItem{
	var $id, $task_id, $item, $task_activity_id, 
	  $parent_id, $order_number;

	var $error;

	var $children;
	
	function SI_TaskItem(){
		$this->error = '';
		$this->id = 0;
		$this->task_id = 0;
		$this->item = '';
		$this->task_activity_id = 0;
		$this->parent_id = 0;
		$this->order_number = 0;
		$this->children = array();
	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->task_id = $values[1];
			$this->item = $values[2];
			$this->task_activity_id = $values[3];
			$this->parent_id = $values[4];
			$this->order_number = $values[5];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['task_id'])) $this->task_id = $array['task_id'];
		if(isset($array['item'])) $this->item = $array['item'];
		if(isset($array['task_activity_id'])) $this->task_activity_id = $array['task_activity_id'];
		if(isset($array['parent_id'])) $this->parent_id = $array['parent_id'];
		if(isset($array['order_number'])) $this->order_number = $array['order_number'];
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

		$this->order_number = $this->getNextOrderNumber();
		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO task_items (task_id, item, task_activity_id, parent_id, order_number)".
		  " VALUES(".$this->task_id.", '".$this->item."', ".$this->task_activity_id.", ".$this->parent_id.", ".$this->order_number.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_TaskItem::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_TaskItem::update() : TaskItem id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE task_items SET task_id = ".$this->task_id.", ".
		  "item = '".$this->item."', task_activity_id = ".$this->task_activity_id.", ".
		  "parent_id = ".$this->parent_id.", order_number = ".$this->order_number."".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskItem::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_TaskItem::delete() : TaskItem id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM task_items WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskItem::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_TaskItem::get() : TaskItem id not set\n";
			return FALSE;
		}

		$TaskItem = SI_TaskItem::retrieveSet("WHERE ti.id = $id", TRUE);
		if($TaskItem === FALSE){
			return FALSE;
		}

		if(isset($TaskItem[0])){
			$this->_populateData($TaskItem[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_TaskItem::get() : No data retrieved from query\n";
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

		$result = $db_conn->query(
			"SELECT ti.id, ti.task_id, ti.item, ti.task_activity_id, ti.parent_id, ti.order_number
		  FROM task_items AS ti
			LEFT JOIN task_items AS pti ON ti.parent_id = pti.id
			LEFT JOIN tasks AS t ON ti.task_id = t.id
			".$clause);

		if(!$result){
			$this->error = "SI_TaskItem::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$TaskItem[] = $row;
			}else{
				$temp =& new SI_TaskItem();
				$temp->_populateData($row);
				$TaskItem[] =& $temp;
			}

		}

		$result->free();
		return $TaskItem;
	}
// BEGIN - Custom SI_TaskItem methods 
////////////////////////////////////////////////////////////
	function getParentSelectTags($task_id, $selected = NULL){
		global $db_conn;
		$tags = "";
		
		$task_id = intval($task_id);
		if($task_id <= 0){
			$this->error = "SI_TaskItem::getParentSelectTags(): Invalid parameters!\n";
			return FALSE;
		}
		
		
		$result = $db_conn->query("SELECT id, item FROM task_items WHERE task_id = $task_id AND parent_id = 0 ORDER BY order_number, item");
		
		if($result === FALSE){
			$this->error = "SI_TaskItem::getParentSelectTags(): ".$db_conn->getLastError()."\n";
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

	function getTaskItems($id){
		global $db_conn;

		$id = intval($id);
		if($id <= 0){
			$this->error = "SI_TaskItem::getItemsForTask(): Invalid parameter\n";
			return FALSE;
		}

		$clause = "ti.task_id = $id ORDER BY pti.item, ti.order_number, ti.item";
		$raw_items = &SI_TaskItem::retrieveSet($clause);
		if($raw_items === FALSE){
			return FALSE;
		}

		$parents = array(); // Array of parents indexed by task_items.id
		foreach($raw_items as $ti){
			if($ti->parent_id == 0){
				$parents[$ti->id] = $ti;
			}else{
				$parents[$ti->parent_id]->children[] = $ti;
			}
		}

		//Clean up indexes
		$items = array();
		foreach($parents as $ti){
			$items[] = $ti;
		}

		return $items;
	}

	function getCompletedTaskItems($id, $activity_id){

		$items = $this->getTaskItems($id);
		if($items === FALSE)
			return FALSE;

		$new_items = array();
		$index = 0;
		foreach($items as $ti){
			if($ti->hasChildren()){
				$new_items[$index] = $ti;
				$new_items[$index]->children = array();
				foreach($ti->children as $child){
					if($child->task_activity_id == $activity_id)
						$new_items[$index]->children[] = $child;
				}
			}elseif($ti->task_activity_id == $activity_id){
				$new_items[$index] = $ti;
				$new_items[$index]->children = array();
			}
			$index++;
		}

		return $new_items;
	}

	function hasChildren(){
		if(is_array($this->children) && count($this->children)>0){
			return TRUE;
		}

		return FALSE;
	}

	function getNextOrderNumber(){
		global $db_conn;

		$task_id = intval($task_id);
		if(intval($this->task_id) <= 0){
			$this->error = "SI_TaskItem::getNextOrderNumber(): Invalid Task ID!\n";
			return FALSE;
		}
		
		
		$result = $db_conn->query("SELECT order_number FROM task_items 
			WHERE task_id = {$this->task_id} AND parent_id = {$this->parent_id}
			ORDER BY order_number DESC LIMIT 1");
		
		if($result === FALSE){
			$this->error = "SI_TaskItem::getNextOrderNumber(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		$row = $result->fetchRow();
		if($row === FALSE){
			$next_num = 1;
		}else{
			$next_num = $row[0] + 1;
		}
		
		return $next_num;
	}

	function switchOrderNumber($direction){
		global $db_conn;
		
		if($direction != TI_ORDER_DOWN && $direction != TI_ORDER_UP){
			$this->error = "SI_TaskItem::switchOrderNumber($direction): Invalid direction!\n";
			return FALSE;
		}
		
		if(!isset($this->id)){
			$this->error = "SI_TaskItem::switchOrderNumber($direction) : TaskItem id not set\n";
			return FALSE;
		}

		$source_on = $this->order_number;
		$temp_on = $this->getNextOrderNumber();
		if($direction == TI_ORDER_DOWN){
			$dest_on = $this->order_number + 1;
		}else{
			$dest_on = $this->order_number - 1;
		}	
		
		if($direction == TI_ORDER_UP && $this->order_number <= 1){
			return TRUE;
		}

		if($direction == TI_ORDER_DOWN && $this->order_number >= $temp_on){
			return TRUE;
		}

		$result = $db_conn->query("UPDATE task_items SET order_number = $temp_on
			WHERE task_id = {$this->task_id} AND parent_id = {$this->parent_id} AND order_number = $dest_on");
		if($result === FALSE){
			$this->error = "SI_TaskItem::switchOrderNumber($direction): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$result = $db_conn->query("UPDATE task_items SET order_number = $dest_on WHERE id = {$this->id}");
		if($result === FALSE){
			$this->error = "SI_TaskItem::switchOrderNumber($direction): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$result = $db_conn->query("UPDATE task_items SET order_number = $source_on
			WHERE task_id = {$this->task_id} AND parent_id = {$this->parent_id} AND order_number = $temp_on");
		if($result === FALSE){
			$this->error = "SI_TaskItem::switchOrderNumber($direction): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$this->order_number = $dest_on;		
		return TRUE;
	}
// END - Custom SI_TaskItem methods 
////////////////////////////////////////////////////////////
}

