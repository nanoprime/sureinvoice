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
require_once('Notify.php');

////////////////////////////////////////////////////////////
// Code generated by CodeGen (http://uversaconsulting.net).
// Insert custom code where indicated below.
////////////////////////////////////////////////////////////

// NotificationMacro Class Definition 
////////////////////////////////////////////////////////////
class NotificationMacro{
	var $id, $notification_id, $name, $description;

	var $error;

	function NotificationMacro(){
		$this->error = '';
		$this->id = 0;
		$this->notification_id = 0;
		$this->name = '';
		$this->description = '';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->notification_id = $values[1];
			$this->name = $values[2];
			$this->description = $values[3];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['notification_id'])) $this->notification_id = $array['notification_id'];
		if(isset($array['name'])) $this->name = $array['name'];
		if(isset($array['description'])) $this->description = $array['description'];
	}

	function escapeSimples(){
		global $PHPNOTIFY_DB;
		
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = $PHPNOTIFY_DB->escapeSimple($value);
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
		global $PHPNOTIFY_DB;

		$this->escapeSimples();
		$result = $PHPNOTIFY_DB->query("INSERT INTO ".PHPNOTIFY_TABLE_PREFIX."notification_macros (notification_id, name, description)".
			" VALUES(".$this->notification_id.", '".$this->name."', '".$this->description."')");
		$this->stripSlashes();
		if(DB::isError($result)){
			$this->error = "NotificationMacro::add() : ".$result->getMessage()."\n";
			return FALSE;
		}else{
			$this->id = mysql_insert_id();
			return TRUE;
		}
	}

	function update(){
		global $PHPNOTIFY_DB;

		if(!isset($this->id)){
			$this->error = "NotificationMacro::update() : NotificationMacro id not set\n";
			return FALSE;
		}

		$this->escapeSimples();
		$result = $PHPNOTIFY_DB->query("UPDATE ".PHPNOTIFY_TABLE_PREFIX."notification_macros SET notification_id = ".$this->notification_id.", ".
			"name = '".$this->name."', description = '".$this->description."'".
			" WHERE id = ".$this->id."");
		$this->stripSlashes();
		if(DB::isError($result)){
			$this->error = "NotificationMacro::update() : ".$result->getMessage()."\n";
			return FALSE;
		}else{
			return TRUE;
		}
	}

	function delete($id = NULL){
		global $PHPNOTIFY_DB;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "NotificationMacro::delete() : NotificationMacro id not set\n";
			return FALSE;
		}

		$result = $PHPNOTIFY_DB->query("DELETE FROM ".PHPNOTIFY_TABLE_PREFIX."notification_macros WHERE id = $id");

		if(DB::isError($result)){
			$this->error = "NotificationMacro::delete() : ".$result->getMessage()."\n";
			return FALSE;
		}else{
			return TRUE;
		}
	}

	function get($id = NULL){
		global $PHPNOTIFY_DB;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "NotificationMacro::get() : NotificationMacro id not set\n";
			return FALSE;
		}

		$NotificationMacro = NotificationMacro::retrieveSet("WHERE id = $id", TRUE);
		if($NotificationMacro === FALSE){
			return FALSE;
		}

		if(isset($NotificationMacro[0])){
			$this->_populateData($NotificationMacro[0]);
			$this->stripSlashes();
		}else{
			$this->error = "NotificationMacro::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	function retrieveSet($clause = '', $raw = FALSE){
		global $PHPNOTIFY_DB;

		if(!empty($clause)){
			$clause = trim($clause);
			if(strlen($clause) > 5){
				if(strtolower(substr($clause, 0, 5)) != "where" && strtolower(substr($clause, 0, 5)) != "order")
					$clause = "WHERE ".$clause;
			}else{
				$clause = "WHERE ".$clause;
			}
		}

		$result = $PHPNOTIFY_DB->query("SELECT  id, notification_id, name, description".
			" FROM ".PHPNOTIFY_TABLE_PREFIX."notification_macros ".$clause);

		if(DB::isError($result)){
			$this->error = "NotificationMacro::retrieveSet(): ".$result->getMessage()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$NotificationMacro[] = $row;
			}else{
				$temp =& new NotificationMacro();
				$temp->_populateData($row);
				$temp->stripSlashes();
				$NotificationMacro[] =& $temp;
			}

		}

		return $NotificationMacro;
	}
// BEGIN - Custom NotificationMacro methods
////////////////////////////////////////////////////////////
	function getForNotification($notification_id){
		global $PHPNOTIFY_DB;

		return $this->retrieveSet("WHERE notification_id = ".$PHPNOTIFY_DB->quoteSmart($notification_id));
	}

// END - Custom NotificationMacro methods
////////////////////////////////////////////////////////////
}

