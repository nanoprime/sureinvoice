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
class SI_Timer{
	var $id, $user_id, $name, $status, $last_start_ts, $previous_total;

	var $error;

	function SI_Timer(){
		$this->error = '';
		$this->id = 0;
		$this->user_id = 0;
		$this->name = '';
		$this->status = 'PAUSED';
		$this->last_start_ts = 0;
		$this->previous_total = 0;
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

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("
			INSERT INTO timers SET 
			user_id = '".$this->user_id."', 
			name = '".$this->name."', 
			status = '".$this->status."', 
			last_start_ts = '".$this->last_start_ts."', 
			previous_total = '".$this->previous_total."'
		");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_Timer::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_Timer::update() : SI_Timer id not set\n";
			return FALSE;
		}

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("
			UPDATE timers SET 
			user_id = '".$this->user_id."', 
			name = '".$this->name."', 
			status = '".$this->status."', 
			last_start_ts = '".$this->last_start_ts."', 
			previous_total = '".$this->previous_total."'
			WHERE id = '".$this->id."'
		");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Timer::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Timer::delete() : SI_Timer id not set\n";
			return FALSE;
		}
		$result = $db_conn->query("DELETE FROM timer_events WHERE timer_id = $id");

		$result = $db_conn->query("DELETE FROM timers WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Timer::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Timer::get() : SI_Timer id not set\n";
			return FALSE;
		}

		$SI_Timer = SI_Timer::retrieveSet("WHERE id = $id", TRUE);
		if($SI_Timer === FALSE){
			return FALSE;
		}

		if(isset($SI_Timer[0])){
			$this->updateFromAssocArray($SI_Timer[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_Timer::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("
			SELECT id, user_id, name, status, last_start_ts, previous_total FROM timers ".
			$clause
		);

		if($result === FALSE){
			$this->error = "SI_Timer::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$SI_Timer = null;
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$SI_Timer[] = $row;
			}else{
				$temp =& new SI_Timer();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$SI_Timer[] =& $temp;
			}

		}

		return $SI_Timer;
	}

	function pause(){
		global $db_conn;
		
		if($this->id == 0){
			$this->error = "SI_Timer::pause(): ID is not set!";
			return false;
		}
		
		if($this->status != 'RUNNING'){
			$this->error = "SI_Timer::pause(): Timer is not running";
			return false;
		}

		$sql = "
			INSERT INTO timer_events SET 
			timer_id = '{$this->id}',
			start_ts = '{$this->last_start_ts}',
			end_ts = UNIX_TIMESTAMP()
		";
		
		if($db_conn->query($sql) === false){
			$this->error = "SI_Timer::pause(): $sql\nDB Error:\n".$db_conn->getLastError();
			return false;
		}
		
		$sql = "
			SELECT SUM(end_ts - start_ts) as total FROM timer_events WHERE timer_id = '{$this->id}' GROUP BY timer_id
		";
		
		$result = $db_conn->query($sql);
		$new_total = 0;
		if($result === FALSE){
			$this->error = "SI_Timer::pause(): $sql\nDB Error:\n".$db_conn->getLastError();
			return false;			
		}else{
			$row = $result->fetchArray(MYSQL_ASSOC);
			$new_total = $row['total'];
		}
		
		$this->last_start_ts = 0;
		$this->status = 'PAUSED';
		$this->previous_total = $new_total;
		$sql = "
			UPDATE timers SET status = 'PAUSED', last_start_ts = 0, previous_total = '$new_total' WHERE id = '{$this->id}'
		";
		if($db_conn->query($sql) === false){
			$this->error = "SI_Timer::pause(): $sql\nDB Error:\n".$db_conn->getLastError();
			return false;
		}

		return true;
	}
	
	function start(){
		global $db_conn;
		
		if($this->id == 0){
			$this->error = "SI_Timer::start(): ID is not set!";
			return false;
		}

		if($this->status != 'PAUSED'){
			$this->error = "SI_Timer::start(): Timer isn't running!";
			return false;
		}

		$this->status = 'RUNNING';
		$this->last_start_ts = time();
		$sql = "
			UPDATE timers SET status = '{$this->status}', last_start_ts = '{$this->last_start_ts}' WHERE id = '{$this->id}'
		";
		if($db_conn->query($sql) === false){
			$this->error = "SI_Timer::pause(): $sql\nDB Error:\n".$db_conn->getLastError();
			return false;
		}

		return true;	
	}
	
	function stop(){
		$this->pause();
	}
	
	function getTotal(){
		global $db_conn;
		
		if($this->id == 0){
			$this->error = "SI_Timer::pause(): ID is not set!";
			return false;
		}

		if($this->last_start_ts > 0){
			return (time() - $this->last_start_ts) + $this->previous_total;
		}else{
			return $this->previous_total;
		}
	}
}

