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
/*
 * SureInvoiceDetection
 *
 * Class for determining the currently 
 * installed version of SureInvoice
 *
 */
 
class SureInvoiceDetection {
	
	var $field_values;
	
	function SureInvoiceDetection(){
		$this->field_values = array('db_server' => '', 'db_user' => '', 'db_password' => '', 'db_database' => '');
	}
	
	function getCurrentVersion($fields){
		$base_si_path = realpath(dirname(__FILE__).'/../');
		require_once($base_si_path.'/includes/DBConn.php');
		
		// Check for version using db settings provided by user
		$DB_SERVER = '';
		$DB_DATABASE = '';
		$DB_USER = '';
		$DB_PASSWORD = '';
		if(file_exists($base_si_path.'/includes/global_config.php')){
			if(include($base_si_path.'/includes/global_config.php')){
				if(defined('DB_SERVER') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_DATABASE')){

					$this->field_values['db_server'] = DB_SERVER;
					$this->field_values['db_user'] = DB_USER;
					$this->field_values['db_password'] = DB_PASSWORD;
					$this->field_values['db_database'] = DB_DATABASE;
					
					
					$DB_SERVER = DB_SERVER;
					$DB_DATABASE = DB_DATABASE;
					$DB_USER = DB_USER;
					$DB_PASSWORD = DB_PASSWORD;
				}
			}
		}

		if(empty($DB_SERVER) || empty($DB_DATABASE) || empty($DB_USER)){
			for($i=0; $i<count($fields); $i++){
				if($fields[$i]->name == 'db_server') $DB_SERVER = $fields[$i]->value;
				if($fields[$i]->name == 'db_database') $DB_DATABASE = $fields[$i]->value;
				if($fields[$i]->name == 'db_user') $DB_USER = $fields[$i]->value;
				if($fields[$i]->name == 'db_password') $DB_PASSWORD = $fields[$i]->value;
			}
		}
		
		$db_conn = new DBConn($DB_SERVER, $DB_DATABASE, $DB_USER, $DB_PASSWORD, TRUE);
		if($db_conn->connect() == FALSE){
			return FALSE;
		}
		
		return $this->_getVersionNumberFromDB($db_conn);
	}

	function _getVersionNumberFromDB($db_conn){
		// Get all the tables in the db
		$si_tables = $db_conn->query('SHOW TABLES;');
		if($si_tables !== false){
			while($table = $si_tables->fetchArray()){
				// item_codes table was added in 1.0
				if($table[0] == 'item_codes'){
					return '1.0';
				}
			}
		}else{
			return false;
		}
		
		$pi_fields = $db_conn->query('SHOW COLUMNS FROM payment_invoices;');
		if($pi_fields !== false){
			while($row = $pi_fields->fetchArray(MYSQL_ASSOC)){
				if($row['Field'] == 'amount'){
					return '0.4';
				}
			}
		}

		$expense_fields = $db_conn->query('SHOW COLUMNS FROM expenses;');
		if($expense_fields !== false){
			while($row = $expense_fields->fetchArray(MYSQL_ASSOC)){
				if($row['Field'] == 'company_id'){
					return '0.3';
				}
			}
		}
		
		return '0.2';		
	}	
	
	function getSpecialActions($installed_version){
		return FALSE;
	}
	
	function updateFields(&$fields){
		for($i=0; $i<count($fields); $i++){
			if(isset($this->field_values[$fields->name])){
				$fields[$i]->default_value = $this->field_values[$fields->name];
				$fields[$i]->value = $this->field_values[$fields->name]; 
			}
		}
	}
}
?>
