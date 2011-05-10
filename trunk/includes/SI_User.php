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
require_once("SI_UserRight.php");
require_once("SI_UserTransaction.php");
require_once("SI_TaskActivity.php");
require_once("SI_Timer.php");

class SI_User{
	var $id, $user_type_id, $first_name, $last_name, 
	  $company_id, $address1, $address2, $city, 
	  $state, $zip, $email, $password, $salary, $hourly_rate,
	  $rate_type, $active, $invoiced, $created_ts, $updated_ts,
	  $last_login_ts, $user_type, $company, $start_page, $deleted,
	  $show_menu, $show_timers;

	var $error;
	var $_user_rights;

	function SI_User(){
		$this->error = '';
		$this->_user_rights = NULL;
		
		$this->id = 0;
		$this->user_type_id = 0;
		$this->first_name = '';
		$this->last_name = '';
		$this->company = '';
		$this->address1 = '';
		$this->address2 = '';
		$this->city = '';
		$this->state = '';
		$this->zip = '';
		$this->email = '';
		$this->password = '';
		$this->salary = 0;
		$this->hourly_rate = 0;
		$this->rate_type = 'SALARY';
		$this->active = 'Y';
		$this->invoiced = 'N';
		$this->created_ts = 0;
		$this->updated_ts = 0;
		$this->last_login_ts = 0;
		$this->user_type = '';
		$this->start_page = 'my_projects.php';
		$this->deleted = 'N';
		$this->show_menu = 1;
		$this->show_timers = 1;

	}

	function updateFromAssocArray($array){
		if(is_array($array)){
			foreach($array as $key => $value)
				$this->$key = $value;
		}
	}

	function escapeStrings(){
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = addslashes($value);
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

		if($this->_calcRate() === FALSE)
			return FALSE;
			
		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO users (user_type_id, first_name, last_name, company_id, ".
		  "address1, address2, city, state, ".
			"zip, email, password, salary, hourly_rate, rate_type, ".
		  "active, invoiced, created_ts, updated_ts, last_login_ts, deleted, show_menu, show_timers)".
			" VALUES(".$this->user_type_id.", '".$this->first_name."', '".$this->last_name."', ".$this->company_id.", ".
			"'".$this->address1."', '".$this->address2."', '".$this->city."', '".$this->state."', ".
			"'".$this->zip."', '".$this->email."', '".$this->password."', '".$this->salary."', '".$this->hourly_rate."', '".$this->rate_type."', ".
		  "'".$this->active."', '".$this->invoiced."', UNIX_TIMESTAMP(), ".$this->updated_ts.", ".$this->last_login_ts.", 'N', ".$this->show_menu.", ".$this->show_timers.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_User::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_User::update() : User id not set\n";
			return FALSE;
		}

		if($this->_calcRate() === FALSE)
			return FALSE;
			
		$this->escapeStrings();
		$result = $db_conn->query("UPDATE users SET user_type_id = ".$this->user_type_id.", ".
		  "first_name = '".$this->first_name."', last_name = '".$this->last_name."', ".
		  "company_id = ".$this->company_id.", address1 = '".$this->address1."', ".
		  "address2 = '".$this->address2."', city = '".$this->city."', ".
		  "state = '".$this->state."', zip = '".$this->zip."', ".
		  "email = '".$this->email."', password = '".$this->password."', salary = ".$this->salary.", ".
		  "hourly_rate = ".$this->hourly_rate.", rate_type = '".$this->rate_type."', ".
		  "active = '".$this->active."', invoiced = '".$this->invoiced."',".
		  "updated_ts = UNIX_TIMESTAMP(), deleted = '".$this->deleted."', ".
		  "show_menu = '".$this->show_menu."', show_timers =  '".$this->show_timers."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_User::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_User::delete() : User id not set\n";
			return FALSE;
		}

		$id = intval($id);
		$result = $db_conn->query("DELETE FROM user_rights WHERE user_id = $id");
		if($result === FALSE){
			$this->error = "SI_User::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
			

		$result = $db_conn->query("UPDATE users SET deleted = 'Y' WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_User::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_User::get() : User id not set\n";
			return FALSE;
		}

		$users = SI_User::retrieveSet("WHERE u.id = $id", TRUE);

		if($users === FALSE){
			return FALSE;
		}

		if(isset($users[0])){
			$this->updateFromAssocArray($users[0]);
		}else{
			$this->error = "SI_User::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	function retrieveSet($clause = '', $raw = FALSE){
		global $db_conn;
		$users = array();

		if(!empty($clause)){
			$clause = trim($clause);
			if(strlen($clause) > 5){
				if(strtolower(substr($clause, 0, 5)) != "where" && strtolower(substr($clause, 0, 5)) != "order")
					$clause = "WHERE ".$clause;
			}else{
				$clause = "WHERE ".$clause;
			}
		}

		$sql = "
		SELECT u.id, u.user_type_id, u.first_name, u.last_name, u.company_id, u.address1,
		u.address2, u.city, u.state, u.zip, u.email, u.password, u.salary, u.hourly_rate, u.rate_type, 
		u.active, u.invoiced, u.created_ts, u.updated_ts, u.last_login_ts, u.deleted, u.show_menu, u.show_timers, 
		ut.name AS user_type, c.name AS company, ut.start_page
		FROM users AS u 
		LEFT JOIN user_types AS ut ON u.user_type_id = ut.id 
		LEFT JOIN companies AS c ON u.company_id = c.id $clause
		";
		
		$result = $db_conn->query($sql);

		if(!$result){
			$this->error = "SI_User::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$users[] = $row;
			}else{
				$temp =& new SI_User();
				$temp->updateFromAssocArray($row);
				$users[] =& $temp;
			}
		}
		
		return $users;
	}
	
	function getAll($clause = ''){
		return SI_User::retrieveSet($clause);
	}
	
	function getUserByLogin($email, $password){
		global $db_conn;
		
		if(empty($email) || empty($password)){
			$this->error = "Both Email and Password are required!";
			return FALSE;
		}
		
		$users = SI_User::retrieveSet("email = '".$db_conn->escapeString($email)."' AND password = '".$db_conn->escapeString($password)."' AND u.active = 'Y' AND u.deleted = 'N'");
		if(is_object($users[0]) && is_a($users[0], 'SI_User')){
			$users[0]->last_login_ts = time();
			$users[0]->_updateLastLogin();
			return $users[0];
		}else{
			$this->error = "Username and password do not match any current accounts";
			return FALSE;
		}
	}
	
	function hasRight($right){
		if(empty($right)){
			return TRUE;
		}else{
			if($this->_user_rights == NULL)
				$this->_user_rights = SI_UserRight::getRightsForUser($this->id);
			for($i = 0; $i < count($this->_user_rights); $i++){
				if($this->_user_rights[$i]->user_right == $right)
					return $this->_user_rights[$i]->right_value;
			}
		}
	}
	
	function _updateLastLogin(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_User::_updateLastLogin() : User id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("UPDATE users SET last_login_ts = UNIX_TIMESTAMP() WHERE id = ".$this->id);
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_User::_updateLastLogin() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function getSetting($setting){
		return $this->$setting;
	}
	
	function saveSetting($setting, $value){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_User::_updateLastLogin() : User id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("UPDATE users SET $setting = '$value' WHERE id = ".$this->id);
		if($result){
			$this->$setting = $value;
			return TRUE;
		}else{
			$this->error = "SI_User::_updateLastLogin() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}		
	}
	
	function getSelectTags($selected = NULL, $exclusions = array(), $resources_only = TRUE, $active_only = TRUE){
		global $db_conn;
		$tags = "";

		$clause = '';
		if(is_array($exclusions) && count($exclusions) > 0){
			$clause .= "u.id NOT IN (".join(', ', $exclusions).") AND ";
		}
		if($resources_only == TRUE){
			$clause .= "ut.resource = 'Y' AND ";
		}
		if($active_only == TRUE){
			$clause .= "u.active = 'Y' AND ";
		}
		if(!empty($clause)){
			$clause = substr($clause, 0, strlen($clause) - 4);
		}else{
			$clause = "1 = 1 ";
		}
		
		$sql = "SELECT u.id, u.first_name, u.last_name FROM users AS u LEFT JOIN user_types AS ut ON u.user_type_id = ut.id WHERE deleted = 'N' AND $clause ORDER BY u.first_name";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_User::getSelectTags(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}


		while($row=$result->fetchRow()){
			$sel_text = "";
			if(in_array($row[0], $exclusions))
				continue;

			if($row[0]==$selected)
				$sel_text = " SELECTED";

			$tags .= "<OPTION VALUE=\"".$row[0]."\"".$sel_text.">".$row[1]." ".$row[2]."</OPTION>\n";
		}
		return $tags;
	}


	function getRateTypeSelectTags($selected = NULL){
		$tags = "";

		$rate_types = array( 'SALARY' => 'SALARY', 'HOURLY' => 'HOURLY', 'HALF_CUST_RATE' => 'HALF_CUST_RATE' );


		foreach($rate_types as $value => $name){
			$sel_text = "";
			if($value==$selected)
				$sel_text = " SELECTED";

			$tags .= "<OPTION VALUE=\"".$value."\"".$sel_text.">".$name." ".$row[2]."</OPTION>\n";
		}
		return $tags;
	}


	function getUserName($user_id = NULL){
		global $db_conn;

		if($user_id === NULL){
			$user_id = $this->id;
		}

		if(intval($user_id) <= 0){
			$this->error = "SI_User::getUserName() : Invalid user id!\n";
			return FALSE;
		}

		$result = $db_conn->query("SELECT first_name, last_name FROM users WHERE id = ".intval($user_id));
		if($result === FALSE){
			$this->error = "SI_User::getUserName() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$name = '';
		if($row = $result->fetchArray()){
			$name = $row[0].' '.$row[1];
		}

		return $name;
	}

	function getUnpaidUsers(){
		global $db_conn;

		$result = $db_conn->query("
SELECT u.id, u.user_type_id, u.first_name, u.last_name, u.company_id, u.address1,
u.address2, u.city, u.state, u.zip, u.email, u.password, u.salary, u.hourly_rate, u.rate_type, u.active, u.invoiced, u.created_ts,
u.updated_ts, u.last_login_ts, u.deleted, ut.name, c.name, SUM(uts.amount) AS amount
FROM user_transactions AS uts
LEFT JOIN users AS u ON uts.user_id = u.id
LEFT JOIN user_types AS ut ON u.user_type_id = ut.id
LEFT JOIN companies AS c ON u.company_id = c.id
LEFT JOIN check_transactions AS ct ON u.id = ct.trans_id
GROUP BY uts.user_id HAVING amount > 0
		");

		if($result === FALSE){
			$this->error = "SI_User::getUnpaidUsers(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$User = array();
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($row['amount'] == 0.00)
				continue;

			$temp =& new SI_User();
			$temp->updateFromAssocArray($row);
			$temp->amount = $row['amount'];
			$User[] =& $temp;
		}

		return $User;
	}

	function getActivities($id, $unpaid = TRUE){
		global $db_conn;

		$unpaid_sql = '';
		if($unpaid){
			$unpaid_sql = " AND ct.check_id IS NULL ";
		}
		
		$sql = "
			SELECT  a.id, a.task_id, a.user_id, a.text, a.start_ts, a.end_ts,
			a.hourly_cost, a.hourly_rate, a.cost_trans_id, a.com_trans_id, a.invoice_id, a.sales_com_type_id,
			t.name, c.name, p.name, ct.check_id AS check_id, p.name AS project_name,
			t.name AS task_name, ut.amount AS cost, 
			ROUND((((a.end_ts - a.start_ts) / 60 / 60) * a.hourly_rate), 2) AS price
			FROM task_activities AS a
			LEFT JOIN tasks AS t ON a.task_id = t.id
			LEFT JOIN projects AS p ON t.project_id = p.id
			LEFT JOIN companies AS c ON p.company_id = c.id
			LEFT JOIN user_transactions AS ut ON a.cost_trans_id = ut.id
			LEFT JOIN check_transactions AS ct ON a.cost_trans_id = ct.trans_id
			WHERE ut.user_id = $id $unpaid_sql
		";

		$result = $db_conn->query($sql);

	  	if($result === FALSE){
	  		$this->error = "SI_User::getActivities(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		$activities = array();
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_TaskActivity();
			$temp->updateFromAssocArray($row);
			$temp->cost_paid = (intval($row['cost_check_id']) > 0 ? TRUE : FALSE);
			$activities[] =& $temp;
		}

		return $activities;
	}

	function getUpaidHourCount(){
		$total_secs = 0;
		$activities = $this->getActivities($this->id, TRUE);
		for($i=0; $i<count($activities); $i++){
			if(!$activities[$i]->cost_paid){
				if($activities[$i]->start_ts > 0 && $activities[$i]->end_ts > 0 && $activities[$i]->end_ts > $activities[$i]->start_ts){
					$total_secs += ($activities[$i]->end_ts - $activities[$i]->start_ts);
				}
			}	
		}
		
		return $total_secs;
	}
	
	function getCommissions($id, $unpaid = TRUE){
		global $db_conn;

		$unpaid_sql = '';
		if($unpaid){
			$unpaid_sql = " AND ct.check_id IS NULL ";
		}
		
		$sql = "
			SELECT  a.id, a.task_id, a.user_id, a.text, a.start_ts, a.end_ts,
			a.hourly_cost, a.hourly_rate, a.cost_trans_id, a.com_trans_id, a.invoice_id, a.sales_com_type_id,
			t.name, c.name, p.name, ct.check_id AS check_id, p.name AS project_name,
			t.name AS task_name, ut.amount AS cost, 
			ROUND((((a.end_ts - a.start_ts) / 60 / 60) * a.hourly_rate), 2) AS price
			FROM task_activities AS a
			LEFT JOIN tasks AS t ON a.task_id = t.id
			LEFT JOIN projects AS p ON t.project_id = p.id
			LEFT JOIN companies AS c ON p.company_id = c.id
			LEFT JOIN user_transactions AS ut ON a.com_trans_id = ut.id
			LEFT JOIN check_transactions AS ct ON a.com_trans_id = ct.trans_id
			WHERE ((p.billable = 'Y' AND t.billable = 'D' )OR t.billable = 'Y') AND 
			ut.user_id = $id $unpaid_sql
		";

		$result = $db_conn->query($sql);

	  	if($result === FALSE){
	  		$this->error = "SI_User::getActivities(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		$activities = array();
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_TaskActivity();
			$temp->updateFromAssocArray($row);
			if($temp->calculate() === FALSE){
				$this->error = "SI_User::getCommissions(): Error calculating activity amounts: " . $temp->getLastError();
				return FALSE;	
			}
			$activities[] =& $temp;
		}

		return $activities;
	}

	function isDeveloper(){
		if($this->user_type_id == 1){
			return TRUE;
		}

		return FALSE;
	}

	function calculateCost($start_ts, $end_ts, $customer_rate, $user_rate = NULL){
		$amount = 0.00;

		if($start_ts <= 0 || $end_ts <= 0){
			return $amount;
		}
		
		if($end_ts < $start_ts){
			$this->error = "SI_User::calculateCost(): End time is earlier than start time!";
			return FALSE;
		}

		if(!isset($user_rate))
			$user_rate = $this->hourly_rate;
			
//		print("User's type is {$this->rate_type} and rate is {$this->hourly_rate}\n");
		if($this->rate_type == '')
			return 0.00;
			
		if($this->rate_type == 'HOURLY' || $this->rate_type == 'SALARY'){
			$amount = $user_rate * round((($end_ts - $start_ts) / 3600), 2);
		}elseif($this->rate_type == 'HALF_CUST_RATE'){
			$amount = ($customer_rate / 2) * round((($end_ts - $start_ts) / 3600), 2);
		}else{
			$this->error = "SI_User::caclulateCost(): Unknown rate type: {$this->rate_type}";
			return FALSE;
		}

		return $amount;
	}

	function getBalance($id = NULL){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$ut = new SI_UserTransaction();
		$amount = $ut->getBalance($id);
		if($amount === FALSE){
			$this->error = "SI_User::getBalance(): Error getting balance: ".$ut->getLastError();
			return FALSE;
		}

		return $amount;
	}

	function getTransactions($id = NULL, $limit = 0, $offset = 0){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$ut = new SI_UserTransaction();
		$result = $ut->getTransactions($id, $limit, $offset);
		if($result === FALSE){
			$this->error = "SI_User::getTransactions(): Error getting transactions: ".$ut->getLastError();
			return FALSE;
		}

		return $result;
	}

	function getTransactionCount($id = NULL){
		global $db_conn;

		if($id === NULL){
			$id = $this->id;
		}

		$ut = new SI_UserTransaction();
		$result = $ut->getTransactionCount($id);
		if($result === FALSE){
			$this->error = "SI_User::getTransactions(): Error getting transaction count: ".$ut->getLastError();
			return FALSE;
		}

		return $result;
	}
	
	function getChecks($id = NULL, $limit = 0, $offset = 0){
		global $db_conn;

		if($id === NULL){
			$id = intval($this->id);
		}else{
			$id = intval($id);
		}

		$limit_sql = '';		
		if($limit > 0 || $offset > 0){
			$limit_sql = " LIMIT $offset, $limit ";
		}

		$check = new SI_Check();
		$result = $check->retrieveSet("WHERE user_id = $id $limit_sql");
		if($result === FALSE){
			$this->error = "SI_User::getChecks(): Error getting checks: ".$check->getLastError();
			return FALSE;
		}

		return $result;
	}

	function setRight($right, $value){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_User::setRight() : User id not set\n";
			return FALSE;
		}
		
		$value = intval($value);
		$right = $db_conn->escapeString($right);
		$result = $db_conn->query("REPLACE INTO user_rights SET right_value = $value WHERE user_right = $right AND user_id = ".$this->id);
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_User::setRight() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}
	
	function updateRights($rights){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_User::updateRights() : User id not set\n";
			return FALSE;
		}
		
		if(!is_array($rights) || count($rights) == 0){
			return TRUE;
		}
		
		$this->id = intval($this->id);
		$result = $db_conn->query("DELETE FROM user_rights WHERE user_id = ".$this->id);
		if($result){
			foreach($rights as $right => $value){
				$value = intval($value);
				$right = $db_conn->escapeString($right);
				$result = $db_conn->query("INSERT INTO user_rights SET user_id = ".$this->id.", user_right = '$right', right_value = $value");
				if($result === FALSE){
					$this->error = "SI_User::setRight() : Error adding right " . $db_conn->getLastError() . "\n";
					return FALSE;
				}
			}
		}else{
			$this->error = "SI_User::setRight() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		
		return TRUE;	
	}

	function getHourlyRates(){
		global $db_conn;
		$rates  = array();

		$sql = "SELECT id, hourly_rate FROM users ORDER BY id";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_User::getSelectTags(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}


		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$rates[$row['id']] = $row['hourly_rate'];
		}
		return $rates;
	}
	
	function _calcRate(){
		if($this->rate_type == 'SALARY' && $this->salary > 0){
			$this->hourly_rate = ($this->salary / (40 * 50));  	
		}else{
			$this->salary = 0;
		}
				
		return TRUE;
	}

	function getRecentTime(){
		global $db_conn, $loggedin_user;

		$sql = "
SELECT ROUND(SUM((end_ts - start_ts) / 60 / 60), 2) as hours, 
DATE(FROM_UNIXTIME(start_ts)) as date FROM task_activities 
WHERE DATE(FROM_UNIXTIME(start_ts)) >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND 
DATE(FROM_UNIXTIME(start_ts)) <= CURDATE() AND
user_id = {$this->id}
GROUP BY date ORDER BY date;
		";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_User::getRecentTime(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$time = array();
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$data = array();
			$data['date'] = $row['date'];
			$data['user_id'] = $loggedin_user->id;
			$data['hours'] = $row['hours'];
			$data['start_ts'] = strtotime($row['date']);
			$data['end_ts'] = strtotime(' +1 day', $data['start_ts']);
			$time[$row['date']] = $data;
		}

		// Fill in missing days
		for($i=0; $i<7; $i++){
			$data = array();
			$timestamp = strtotime("$i days ago");
			$date = date('Y-m-d', $timestamp);
			$data['date'] = $date;
			$data['user_id'] = $loggedin_user->id;
			$data['hours'] = 0;
			$data['start_ts'] = strtotime($date);
			$data['end_ts'] = strtotime(' +1 day', $data['start_ts']);
	
			if(!isset($time[$date])) $time[$date] =  $data;
		}
		
		ksort($time);
		return $time;
		
		
	}
	
	function getTimers(){
		$timer = new SI_Timer();
		$timers = $timer->retrieveSet("WHERE user_id = '{$this->id}' ORDER BY id");
		if($timers === FALSE){
			$this->error = "SI_User::getTimers(): ".$timer->getLastError();
			return FALSE;
		}
		
		return $timers;
	}
	
	function startTimer($name){
		$timer = new SI_Timer();
		$timer->name = $name;
		$timer->user_id = $this->id;
		if($timer->add() == FALSE){
			$this->error = 'SI_User::startTimer(): '.$timer->getLastError();
			return FALSE;
		}
		
		if($timer->start() === FALSE){
			$this->error = 'SI_User::startTimer(): '.$timer->getLastError();
			return FALSE;
		}
		
		return $timer;
	}
}

?>
