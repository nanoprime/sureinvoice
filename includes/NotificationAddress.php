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

// NotificationAddress Class Definition
////////////////////////////////////////////////////////////
class NotificationAddress{
	var $id, $notification_id, $address;

	var $error;

	function NotificationAddress(){
		$this->error = '';
		$this->id = 0;
		$this->notification_id = 0;
		$this->address = '';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->notification_id = $values[1];
			$this->address = $values[2];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['notification_id'])) $this->notification_id = $array['notification_id'];
		if(isset($array['address'])) $this->address = $array['address'];
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
		$result = $PHPNOTIFY_DB->query("INSERT INTO ".PHPNOTIFY_TABLE_PREFIX."notification_addresses (notification_id, address)".
			" VALUES(".$this->notification_id.", '".$this->address."')");
		$this->stripSlashes();
		if(DB::isError($result)){
			$this->error = "NotificationAddress::add() : ".$result->getMessage()."\n";
			return FALSE;
		}else{
			$this->id = mysql_insert_id();
			return TRUE;
		}
	}

	function update(){
		global $PHPNOTIFY_DB;

		if(!isset($this->id)){
			$this->error = "NotificationAddress::update() : NotificationAddress id not set\n";
			return FALSE;
		}

		$this->escapeSimples();
		$result = $PHPNOTIFY_DB->query("UPDATE ".PHPNOTIFY_TABLE_PREFIX."notification_addresses SET notification_id = ".$this->notification_id.", ".
			"address = '".$this->address."'".
			" WHERE id = ".$this->id."");
		$this->stripSlashes();
		if(DB::isError($result)){
			$this->error = "NotificationAddress::update() : ".$result->getMessage()."\n";
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
			$this->error = "NotificationAddress::delete() : NotificationAddress id not set\n";
			return FALSE;
		}

		$result = $PHPNOTIFY_DB->query("DELETE FROM ".PHPNOTIFY_TABLE_PREFIX."notification_addresses WHERE id = $id");

		if(DB::isError($result)){
			$this->error = "NotificationAddress::delete() : ".$result->getMessage()."\n";
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
			$this->error = "NotificationAddress::get() : NotificationAddress id not set\n";
			return FALSE;
		}

		$NotificationAddress = NotificationAddress::retrieveSet("WHERE id = $id", TRUE);
		if($NotificationAddress === FALSE){
			return FALSE;
		}

		if(isset($NotificationAddress[0])){
			$this->_populateData($NotificationAddress[0]);
			$this->stripSlashes();
		}else{
			$this->error = "NotificationAddress::get() : No data retrieved from query\n";
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

		$result = $PHPNOTIFY_DB->query("SELECT  id, notification_id, address".
			" FROM ".PHPNOTIFY_TABLE_PREFIX."notification_addresses ".$clause);

		if(DB::isError($result)){
			$this->error = "NotificationAddress::retrieveSet(): ".$result->getMessage()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$NotificationAddress[] = $row;
			}else{
				$temp =& new NotificationAddress();
				$temp->_populateData($row);
				$temp->stripSlashes();
				$NotificationAddress[] =& $temp;
			}

		}

		return $NotificationAddress;
	}
// BEGIN - Custom NotificationAddress methods
////////////////////////////////////////////////////////////

	function getForNotification($notification_id){
		$notification_id = intval($notification_id);
		if($notification_id <= 0){
			$this->error = "NotificationAddress::getForNotification(): Invalid notification id provided";
			return FALSE;
		}

		return $this->retrieveSet("WHERE notification_id = $notification_id");
	}

// END - Custom NotificationAddress methods
////////////////////////////////////////////////////////////
}
