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

class Notification{
	var $id, $name, $description, $from, $subject,
		$email, $active;

	var $error;

	var $prepared_body;

	var $prepared_subject;

	var $prepared_addresses;

	var $prepared_from;

	var $parameters;

	var $macros;

	var $addresses;

	function Notification(){
		$this->error = '';
		$this->id = 0;
		$this->name = '';
		$this->description = '';
		$this->from_address;
		$this->subject = '';
		$this->email = '';
		$this->active = 'N';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->name = $values[1];
			$this->description = $values[2];
			$this->from_address = $values[3];
			$this->subject = $values[4];
			$this->email = $values[5];
			$this->active = $values[6];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['name'])) $this->name = $array['name'];
		if(isset($array['description'])) $this->description = $array['description'];
		if(isset($array['from_address'])) $this->from_address = $array['from_address'];
		if(isset($array['subject'])) $this->subject = $array['subject'];
		if(isset($array['email'])) $this->email = $array['email'];
		if(isset($array['active'])) $this->active = $array['active'];
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
		$result = $PHPNOTIFY_DB->query("INSERT INTO ".PHPNOTIFY_TABLE_PREFIX."notifications (name, description, from_address, subject, email, ".
			"active)".
			" VALUES('".$this->name."', '".$this->description."', '".$this->from_address."', '".$this->subject."', '".$this->email."', ".
			"'".$this->active."')");
		$this->stripSlashes();

		if(DB::isError($result)){
			$this->error = "Notification::add() : ".$result->getMessage()."\n";
			return FALSE;
		}else{
			$this->id = mysql_insert_id();
			return TRUE;
		}
	}

	function update(){
		global $PHPNOTIFY_DB;

		if(!isset($this->id)){
			$this->error = "Notification::update() : Notification id not set\n";
			return FALSE;
		}

		$this->escapeSimples();
		$result = $PHPNOTIFY_DB->query("UPDATE ".PHPNOTIFY_TABLE_PREFIX."notifications SET name = '".$this->name."', ".
			"description = '".$this->description."', from_address = '".$this->from_address."', ".
			"subject = '".$this->subject."', ".
			"email = '".$this->email."', active = '".$this->active."'".
			" WHERE id = ".$this->id."");
		$this->stripSlashes();
		if(DB::isError($result)){
			$this->error = "Notification::update() : ".$result->getMessage()."\n";
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
			$this->error = "Notification::delete() : Notification id not set\n";
			return FALSE;
		}

		$result = $PHPNOTIFY_DB->query("DELETE FROM ".PHPNOTIFY_TABLE_PREFIX."notifications WHERE id = $id");

		if(DB::isError($result)){
			$this->error = "Notification::delete() : ".$result->getMessage()."\n";
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
			$this->error = "Notification::get() : Notification id not set\n";
			return FALSE;
		}

		$Notification = Notification::retrieveSet("WHERE id = $id", TRUE);
		if($Notification === FALSE){
			return FALSE;
		}

		if(isset($Notification[0])){
			$this->_populateData($Notification[0]);
			$this->stripSlashes();
			if($this->_populate() === FALSE)
				return FALSE;
		}else{
			$this->error = "Notification::get() : No data retrieved from query\n";
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

		$result = $PHPNOTIFY_DB->query("SELECT  id, name, description, from_address, subject, email, active".
			" FROM ".PHPNOTIFY_TABLE_PREFIX."notifications ".$clause);

		if(DB::isError($result)){
			$this->error = "Notification::retrieveSet(): ".$result->getMessage()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$Notification[] = $row;
			}else{
				$temp =& new Notification();
				$temp->_populateData($row);
				$temp->stripSlashes();
				$Notification[] =& $temp;
			}

		}

		return $Notification;
	}

	function _populate(){
		$this->id = intval($this->id);
		if($this->id <= 0){
			$this->error = "Notification::_populate(): Invalid notification id!";
			return FALSE;
		}

		$nm = new NotificationMacro();
		$result = $nm->getForNotification($this->id);
		if($result === FALSE){
			$this->error = "Notification::_populate(): Error getting macros: ".$nm->getLastError();
			return FALSE;
		}

		$this->macros = $result;

		$na = new NotificationAddress();
		$result = $na->getForNotification($this->id);
		if($result === FALSE){
			$this->error = "Notification::_populate(): Error getting addresses: ".$na->getLastError();
			return FALSE;
		}

		$this->addresses = $result;
		return TRUE;
	}

	function prepare($params){
		if(!is_array($params)){
			$this->error = "Notification::prepare(): Invalid parameters!";
			return FALSE;
		}

		$this->prepared_body = '';
		$this->prepared_email = '';
		$this->prepared_subject = '';
		$this->prepared_from = '';

		$this->parameters = $params;

		if($this->prepareBody() === FALSE)
			return FALSE;

		if($this->prepareSubject() === FALSE)
			return FALSE;

		if($this->prepareAddresses() === FALSE)
			return FALSE;

		if($this->prepareFrom() === FALSE)
			return FALSE;

		return TRUE;
	}

	function prepareBody(){
		$text = $this->email;
		foreach($this->parameters as $key => $value){
			if(!is_array($value))
				$text = preg_replace("/\|".$key."\|/", $value, $text);
		}
		$this->prepared_body = $text;

		return TRUE;
	}

	function prepareSubject(){
		$text = $this->subject;
		foreach($this->parameters as $key => $value){
			if(!is_array($value))
				$text = preg_replace("/\|".$key."\|/", $value, $text);
		}
		$this->prepared_subject = $text;

		return TRUE;
	}

	function prepareFrom(){
		$text = $this->from_address;
		foreach($this->parameters as $key => $value){
			if(!is_array($value))
				$text = preg_replace("/\|".$key."\|/", $value, $text);
		}
		$this->prepared_from = $text;

		return TRUE;
	}

	function prepareAddresses(){

		if(intval($this->id) <= 0){
			$this->error .= "Notification::prepareAddresses(): Invalid notification id setup";
			return FALSE;
		}

		$na = new NotificationAddress();
		$addresses = $na->getForNotification($this->id);
		if($addresses === FALSE){
			$this->error = "Error getting notification addresses: ".$na->getLastError();
			return FALSE;
		}

		$address_string = "";
		foreach($addresses as $address){
			$text = $address->address;
			if(strstr($text, "|") !== FALSE){
				foreach($this->parameters as $key => $value){
					if(is_array($value) && strstr($text,"|$key|")){
						$text = implode(',', $value);
					}else{
						$text = preg_replace("/\|".$key."\|/", $value, $text);
					}
				}
			}
			$address_string .= $text.", ";
		}
		$address_string = substr($address_string, 0, strlen($address_string)-2);
		$this->prepared_addresses = $address_string;

		return TRUE;
	}

	function getByName($name){
		global $PHPNOTIFY_DB;

		$Notification = $this->retrieveSet("WHERE name = ".$PHPNOTIFY_DB->quoteSmart($name), TRUE);
		if($Notification === FALSE){
			return FALSE;
		}

		if(isset($Notification[0])){
			$this->_populateData($Notification[0]);
			$this->stripSlashes();
			if($this->getMacros() === FALSE)
				return FALSE;
		}else{
			$this->error = "Notification::getByName($name) : No data retrieved from query\n";
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * send()
	 *
	 * Method to send the prepared email, the prepare method
	 * must be called prior to calling this function.
	 *
	 * %attachments must be an associative array with the
	 * following stucture.
	 * $attachments['data'] = The actual attachment data
	 * $attachments['name'] = The filename of the attachment
	 * $attachments['type'] = The mime content type, defaults to application/octet-stream
	 * $attachments['encoding'] = The type of encoding to be used, defaults to base64
	 *
	 * $headers must be an associative array with the
	 * key being the header name like so.
	 * $header['Reply-To'] = "someone@somewhere.com"
	 *
	 * If you include values for Subject or From in the $headers
	 * array they will be used instead of the respective values
	 * from the prepared notification
	 *
	 * @param array $attachments Array of items to attach
	 * @param array $headers Array of additonal headers to be included
	 * @return boolean TRUE on success, FALSE on error. See getLastError() for the reason
	 */
	function send($attachments = NULL, $headers = NULL){

		if($this->active == 'N'){
			$this->error = "Notification::send(): Skipping inactive message!";
			return TRUE;
		}

		if(empty($this->prepared_body) || empty($this->prepared_addresses)){
			$this->error = "Notification::send(): Skipping message with empty body or addresses!";
			var_dump($this);
			var_dump($this->parameters);
			return FALSE;
		}

		if($headers != NULL && !is_array($headers)){
			$this->error = "Notification::send(): Invalid headers provided!";
			return FALSE;
		}else{
			$found_from = FALSE;
			$found_subject = FALSE;
			foreach($headers as $header => $value){
				if(strtolower($header) == "from")
					$found_from = TRUE;
				elseif(strtolower($header) == "subject")
					$found_subject = TRUE;
			}
			if(!$found_from)
				$headers['From'] = $this->prepared_from;
			if(!$found_subject)
				$headers['Subject'] = $this->prepared_subject;
		}

		$mime = new Mail_mime();
		$mime->setTXTBody($this->prepared_body);

		if(is_array($attachments) && count($attachments) > 0){
			foreach($attachments as $attach){
				if(empty($attach['data']) || empty($attach['name'])){
					$this->error = "Notification::send(): Invalid attachment!";
					return FALSE;
				}
				if(!isset($attach['type'])) $attach['type'] = 'application/octet-stream';
				if(!isset($attach['encoding'])) $attach['encoding'] = 'base64';
				$result = $mime->addAttachment($attach['data'], $attach['type'], $attach['name'], FALSE, $attach['encoding']);
				if(PEAR::IsError($result)){
					$this->error = "Notification::send(): Error adding attachment: ".$result->getMessage();
					return FALSE;
				}
			}
		}

		$message = $mime->get();
		$headers = $mime->headers($headers);
		$mailer = Mail::factory('mail');
		$result = $mailer->send($this->prepared_addresses, $headers, $message);
		if(PEAR::IsError($result)){
			$this->error = "Notification::send(): Error sending message: ".$result->getMessage();
			return FALSE;
		}

		return TRUE;
	}

	function getMacros(){
		$this->id = intval($this->id);
		if($this->id <= 0){
			$this->error = "Notification::getMacros(): Invalid notification id!";
			return FALSE;
		}

		$nm = new NotificationMacro();
		$result = $nm->getForNotification($this->id);
		if($result === FALSE){
			$this->error = "Notification::getMacros(): Error getting macros: ".$nm->getLastError();
			return FALSE;
		}

		$this->macros = $result;
		return TRUE;
	}

	function addAddress($email){
		$this->id = intval($this->id);
		if($this->id <= 0){
			$this->error = "Notification::addAddress(): Invalid notification id!";
			return FALSE;
		}

		$na = new NotificationAddress();
		$na->notification_id = $this->id;
		$na->address = $email;
		$result = $na->add();
		if($result === FALSE){
			$this->error = "Notification::addAddress(): Error adding address: ".$na->getLastError();
			return FALSE;
		}

		return TRUE;
	}

	function addMacro($name, $description){
		$this->id = intval($this->id);
		if($this->id <= 0){
			$this->error = "Notification::addMacro(): Invalid notification id!";
			return FALSE;
		}

		$nm = new NotificationMacro();
		$nm->notification_id = $this->id;
		$nm->name = $name;
		$nm->description = $description;
		$result = $nm->add();
		if($result === FALSE){
			$this->error = "Notification::addMacro(): Error adding address: ".$nm->getLastError();
			return FALSE;
		}

		return TRUE;
	}
}

