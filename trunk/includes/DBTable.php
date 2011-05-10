<?php
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

require_once(dirname(__FILE__) . "/config.php");

/**
 * class DBTable
 *
 */

class DBTable {
	var $_prefix;
	var $_table;
	var $_db;
	var $_sequence_name;
  var $_use_sequences;
	var $_populated = false;

	function DBTable($db = NULL, $use_sequences = FALSE, $prefix = NULL) {

    // Shold we use global sequences or rely on an AUTO_INCREMENT field
    $this->_use_sequences = $use_sequences;

    if ($db != NULL) {
      $this->_db = $db;
    }else{
      $db = $GLOBALS['frame']['adodb']['db'];
      if (is_object($db) && is_a($db,"adoconnection")) {
        $this->_db = $db;
      }
    }

    if ($prefix != NULL) {
      $this->_prefix = $prefix;
    }else{
      $pre = $GLOBALS['frame']['config']['db_prefix'];
      if (!empty($pre)) {
        $this->_prefix = $GLOBALS['frame']['config']['db_prefix'];
      }
    }

    $this->_sequence_name = $this->_prefix . $this->_sequence_name;
	}
	
	function persist() {
		$sql = "REPLACE INTO " . $this->_prefix . $this->_table . " SET ";
		$fields = $this->_list_fields();
		$db = $this->_db;
		$pkeys = $db->MetaPrimaryKeys($this->_table);

		foreach ($fields as $field) {
			$func = "get_" . $field;
			//echo "f: $field m: $func status: " .  (is_callable(array($this,$func))? "yes" : "no") . "<br>";
      if(isset($this->$field)){
				$val = $this->$field;

        if (in_array($field,$pkeys)  && empty($val)) {
          if($this->_use_sequences){
            $last_id = $db->GenID("sequences");
            $this->$field = $last_id; // Store the sequence away
					  $val = $last_id;
          }
				}

				if (isset($val)) {
					//echo "s: $field to: $val <br>";
					$sql .= " `" . $field . "` = '" . mysql_real_escape_string(strval($val)) ."',";
				}
			}
		}

		if (strrpos($sql,",") == (strlen($sql) -1)) {
				$sql = substr($sql,0,(strlen($sql) -1));
		}

		//echo "<br>sql is: " . $sql . "<br /><br>";
		//var_dump($sql);
		$db->execute($sql);
		return true;
	}

	function populate() {
		$sql = "SELECT * from " . $this->_prefix  . $this->_table . " WHERE id = '" . mysql_real_escape_string(strval($this->id))  . "'";
		$db = $this->_db;
		$results = $db->Execute($sql);
		if ($results && !$results->EOF) {
			$this->_populated = true;
			foreach ($results->fields as $field_name => $field) {
        $this->$field_name = $field;
			}
		}
	}

	function populate_array($results) {
		  if (is_array($results)) {
			foreach ($results as $field_name => $field) {
        $this->$field_name = $field;
			}
		}
	}

	function _list_fields() {
		$sql = "SHOW COLUMNS FROM ". mysql_real_escape_string($this->_table);
        $res = $this->_db->Execute($sql);
        //or die("DB Error: " . $this->_db->ErrorMsg())
        $field_list = array();
        while(!$res->EOF) {
            $field_list[] = $res->fields['Field'];
        	$res->MoveNext();
        }
		return $field_list;
	}

	function _execute($sql) {
      if (!empty($sql)) {
        if ($this->_db !=NULL) {
          $this->_db->SetFetchMode(ADODB_FETCH_ASSOC);
          $res = $this->_db->Execute($sql) or die("Error in query: $query. " . $this->_db->ErrorMsg());
          //$this->_db->SetFetchMode(ADODB_FETCH_NUM);
          return $res;
        }
        else {
          //log failed db error
          return false;
        }
      }
    }

} // end of DBTable
?>
