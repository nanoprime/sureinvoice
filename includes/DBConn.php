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
class DBConn{

	var $_server, $_db, $_username, $_password;

	var $_conn = NULL;
	var $_connected = FALSE;
	var $_error = "";
	
	/**
	 * DBConn::DBConn()
	 * 
	 * DBConn constructor 
	 * 
	 * @param $dbServer
	 * @param $dbName
	 * @param $dbUsername
	 * @param $dbPassword
	 * @return 
	 **/
	function DBConn($dbServer, $dbName, $dbUsername, $dbPassword){
		$this->_server = $dbServer;
		$this->_db = $dbName;
		$this->_username = $dbUsername;
		$this->_password = $dbPassword;
		if(!$this->connect()){
			$this->_error = 'Could not connect to database';
		}
	}

	function connect($force_new = false){
		$this->_conn = @mysql_connect($this->_server, $this->_username, $this->_password, $force_new);
		if($this->_conn == FALSE){
			$this->_error = "Could not connect to Server({$this->_server}): ".$this->getMySQLError();
			return FALSE;
		}
		
		if(@mysql_select_db($this->_db, $this->_conn)==FALSE){
			$this->_error = "Could not select database({$this->_server}): ".$this->getMySQLError();
			return FALSE;
		}
		$this->_connected = TRUE;
		$this->_error = "";
		return TRUE;
	}

	function query($sql, $select_db = FALSE){
		if(!$this->_connected){
			$this->connect();
		}
		
		if($select_db){
			if(@mysql_select_db($this->_db, $this->_conn)==FALSE){
				$this->_error = "Could not select database({$this->_server}): ".$this->getMySQLError();
				return FALSE;
			}
		}

		$result = @mysql_query($sql, $this->_conn);
		if($result === FALSE){
			$this->_error = "SQL: ".$sql."\nError: ".$this->getMySQLError();
			//trigger_error($this->_error, E_USER_ERROR);
			return FALSE;
		}else if($result === TRUE){
			$this->_error = "";
			return TRUE;
		}else{
			$this->_error = "";
			return new DBResult($result, $sql);
		}
	}

	function getMySQLError(){
		return "[" . @mysql_errno($this->_conn) . "] " . @mysql_error($this->_conn);
	}

	function getLastError(){
		return $this->_error;
	}

	function reportError(){
		$exitMessage = "There has been a database error: " . $this->getError();
		$this->close();
		exit($exitMessage);
		return;
	}
	
	function close(){
		if($_connected){
			return @mysql_close($this->_conn);
		}
		return TRUE;
	}
	
	function escapeString($string){
		return @mysql_escape_string($string);
	}
}

class DBResult{
	var $_sql, $_result;
	
	function DBResult($result, $sql = ""){
		$this->_result = $result;
		$this->_sql = $sql;
	}
	
	function numRows(){
		return @mysql_num_rows($this->_result);
	}
	
	function fetchRow(){
		return @mysql_fetch_row($this->_result);
	}

	function fetchArray($type = MYSQL_BOTH){
		return @mysql_fetch_array($this->_result, $type);
	}

	function fetchField($offset=0){
		return @mysql_fetch_field($this->_result, $offset);
	}

	function free(){
		return @mysql_free_result($this->_result);
	}
	
	function getSql(){
		return $this->_sql;
	}
}
?>