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
require_once('SI_ItemCode.php');
require_once('SI_Project.php');
require_once('SI_Company.php');
require_once('SI_Task.php');

class SI_PaymentSchedule{
	var $id;
	
	var $project_id;
	
	var $task_id;
	
	var $amount;

	var $due_ts;
	
	var $description;

	var $item_code_id;
	
	var $error;

	var $_task;
	
	var $_project;
	
	var $_company;

	var $_item_code;

	function SI_PaymentSchedule(){
		$this->error = '';
		$this->id = 0;
		$this->project_id = 0;
		$this->task_id = 0;
		$this->amount = 0;
		$this->due_ts = 0;
		$this->description = '';
		$this->item_code_id = 0;
		
		$this->_project = FALSE;
		$this->_task = FALSE;
		$this->_company = FALSE;
		$this->_item_code = FALSE;
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
		$result = $db_conn->query("INSERT INTO payment_schedule (project_id, task_id, amount, due_ts, ".
		  "description, item_code_id)".
			" VALUES(".$this->project_id.", ".$this->task_id.", ".$this->amount.", ".$this->due_ts.", ".
		  "'".$this->description."', ".$this->item_code_id.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_PaymentSchedule::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_PaymentSchedule::update() : PaymentSchedule id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE payment_schedule SET project_id = ".$this->project_id.", ".
			"task_id = ".$this->task_id.", amount = ".$this->amount.", ".
		  "due_ts = ".$this->due_ts.", description = '".$this->description."', item_code_id = ".$this->item_code_id."".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_PaymentSchedule::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_PaymentSchedule::delete() : PaymentSchedule id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM payment_schedule WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_PaymentSchedule::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_PaymentSchedule::get() : PaymentSchedule id not set\n";
			return FALSE;
		}

		$PaymentSchedule = SI_PaymentSchedule::retrieveSet("WHERE id = $id", TRUE);
		if($PaymentSchedule === FALSE){
			return FALSE;
		}

		if(isset($PaymentSchedule[0])){
			$this->updateFromAssocArray($PaymentSchedule[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_PaymentSchedule::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, project_id, task_id, amount, due_ts, description, item_code_id".
			" FROM payment_schedule ".$clause);

		if($result === FALSE){
			$this->error = "SI_PaymentSchedule::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$PaymentSchedule[] = $row;
			}else{
				$temp =& new SI_PaymentSchedule();
				$temp->updateFromAssocArray($row);
				$PaymentSchedule[] =& $temp;
			}

		}

		return $PaymentSchedule;
	}

	function getForProject($project_id, $unbilled = TRUE){
		global $db_conn;

		$project_id = intval($project_id);
		if($unbilled){
			$billed_clause = " AND ill.id IS NULL ";
		}

		$sql = "
SELECT  ps.id, ps.project_id, ps.task_id, ps.amount, ps.due_ts, ps.description, ps.item_code_id
FROM payment_schedule AS ps
LEFT JOIN invoice_line_links AS ill ON ps.id = ill.payment_schedule_id
WHERE ps.project_id = $project_id $billed_clause
ORDER BY ps.due_ts
		";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_PaymentSchedule::getForProject(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$PaymentSchedule[] = $row;
			}else{
				$temp =& new SI_PaymentSchedule();
				$temp->updateFromAssocArray($row);
				$PaymentSchedule[] =& $temp;
			}

		}

		return $PaymentSchedule;
	}

	function getForTask($task_id, $unbilled = TRUE){
		global $db_conn;

		$task_id = intval($task_id);
		if($unbilled){
			$billed_clause = " AND ill.id IS NULL ";
		}

		$sql = "
SELECT  ps.id, ps.project_id, ps.task_id, ps.amount, ps.due_ts, ps.description, ps.item_code_id
FROM payment_schedule AS ps
LEFT JOIN invoice_line_links AS ill ON ps.id = ill.payment_schedule_id
WHERE ps.task_id = $task_id $billed_clause
ORDER BY ps.due_ts
		";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_PaymentSchedule::getForTask(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$PaymentSchedule[] = $row;
			}else{
				$temp =& new SI_PaymentSchedule();
				$temp->updateFromAssocArray($row);
				$PaymentSchedule[] =& $temp;
			}

		}

		return $PaymentSchedule;
	}

	function getForCompany($company_id, $unbilled = TRUE){
		global $db_conn;

		$company_id = intval($company_id);
		if($unbilled){
			$billed_clause = " AND ill.id IS NULL ";
		}

		$sql = "
SELECT  ps.id, ps.project_id, ps.task_id, ps.amount, ps.due_ts, ps.description, ps.item_code_id
FROM payment_schedule AS ps
LEFT JOIN invoice_line_links AS ill ON ps.id = ill.payment_schedule_id
LEFT JOIN projects AS p ON ps.project_id = p.id
LEFT JOIN tasks AS t ON ps.task_id = t.id
LEFT JOIN projects AS tp ON t.project_id = tp.id
WHERE (tp.company_id = $company_id OR p.company_id = $company_id)
$billed_clause
ORDER BY ps.due_ts
		";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_PaymentSchedule::getForCompany(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_PaymentSchedule();
			$temp->updateFromAssocArray($row);
			$PaymentSchedule[] =& $temp;
		}

		return $PaymentSchedule;
	}

	function getTaskId(){
		if($this->getTask() === FALSE)
			return FALSE;

		return $this->_task->id;
	}

	function getTaskName(){
		if($this->getTask() === FALSE)
			return FALSE;

		return $this->_task->name;
	}

	function getProjectId(){
		if($this->getProject() === FALSE)
			return FALSE;

		return $this->_project->id;
	}


	function getProjectName(){
		if($this->getProject() === FALSE)
			return FALSE;

		return $this->_project->name;
	}

	function getCompanyName(){
		if($this->getCompany() === FALSE)
			return FALSE;

		return $this->_company->name;
	}

	function getProject(){
		if($this->_project === FALSE){
			$this->_project = new SI_Project();
			if($this->task_id > 0){
				if($this->getTask() === FALSE)
					return FALSE;

				if($this->_project->get($this->_task->project_id) === FALSE){
					$this->error = "SI_PaymentSchedule::getTask(): Error getting project: ".$this->_project->getLastError();
					return FALSE;
				}
			}elseif($this->project_id > 0){
				if($this->_project->get($this->project_id) === FALSE){
					$this->error = "SI_PaymentSchedule::getTask(): Error getting project: ".$this->_project->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_project;
	}

	function getTask(){
		if($this->_task === FALSE){
			$this->_task = new SI_Task();
			if($this->task_id > 0){
				if($this->_task->get($this->task_id) === FALSE){
					$this->error = "SI_PaymentSchedule::getTask(): Error getting task: ".$this->_task->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_task;
	}

	function getItemCode(){
		if($this->_item_code === FALSE){
			$this->_item_code = new SI_ItemCode();
			if($this->item_code_id > 0){
				if($this->_item_code->get($this->item_code_id) === FALSE){
					$this->error = "SI_PaymentSchedule::getItemCode(): Error getting item code: ".$this->_item_code->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_item_code;
	}

	function getItemCodeCode(){
		if($this->getItemCode() === FALSE)
			return FALSE;

		return $this->_item_code->code;
	}


	function getCompany(){
		if($this->_company === FALSE){
			$this->_company = new SI_Company();
			if($this->getProject() === FALSE)
				return FALSE;
			if($this->_project->company_id > 0){
				if($this->_company->get($this->_project->company_id) === FALSE){
					$this->error = "SI_PaymentSchedule::getCompany(): Error getting company: ".$this->_company->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_company;
	}

	function getUpcoming($time = FALSE){
		global $db_conn;

		$time_clause = '';
		if($time){
			$time_clause = 	"AND ps.due_ts <= $time";
		}
		
		$sql = "
SELECT  ps.id, ps.project_id, ps.task_id, ps.amount, ps.due_ts, ps.description, ps.item_code_id
FROM payment_schedule AS ps
LEFT JOIN invoice_line_links AS ill ON ps.id = ill.payment_schedule_id
WHERE ill.id IS NULL $time_clause
ORDER BY ps.due_ts 
		";
		$result = $db_conn->query($sql);

		if($result === FALSE){
			$this->error = "SI_PaymentSchedule::getForTask(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$PaymentSchedule[] = $row;
			}else{
				$temp =& new SI_PaymentSchedule();
				$temp->updateFromAssocArray($row);
				$PaymentSchedule[] =& $temp;
			}

		}

		return $PaymentSchedule;
	}
	
	function generateScheduledPayments($project_id, $task_id, $frequency, $start_ts, $end_ts, $item_code, $description_format, $amount){
		$months = array( 1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');
		$scheduled_payments = array();
		if($frequency == 'Monthly'){
			$dom = (int)date('d', $start_ts);
			$start_month = (int)date('m', $start_ts);
			$start_year = (int)date('Y', $start_ts);
			$end_month = (int)date('m', $end_ts);
			$end_year = (int)date('Y', $end_ts);
			$current_month = $start_month;
			$current_year = $start_year;
			//print("From $start_month/$start_year to $end_month/$end_year on the $dom\n");
			while($current_year < $end_year || 
			$current_month <= $end_month && $current_year == $end_year){
				//print("Creating payment for $current_month/$dom/$current_year\n");
				$ps = new SI_PaymentSchedule();
				if($task_id > 0){
					$ps->task_id = $task_id;
					$task = $ps->getTask();
					$ps->project_id = $task->project_id;
				}else{
					$ps->project_id = $project_id;
				}
				$ps->amount = $amount;
				$ps->due_ts = strtotime($current_year.'-'.$current_month.'-'.$dom);
				if(stristr($description_format, '|MONTH|')){
					$ps->description = str_replace('|MONTH|', $months[(int)$current_month], $description_format);
				}else{
					$ps->description = $description_format;
				}
				$ps->item_code_id = $item_code;
				if($ps->add()){
					$scheduled_payments[] = $ps;
				}else{
					return FALSE;
				}
				if($current_month == 12){
					$current_month = 1;
					$current_year++;
				}else{
					$current_month++;
				}
				//print("Next date $current_month/$dom/$current_year\n");
			}
		}
		
		return $scheduled_payments;
	}
}
