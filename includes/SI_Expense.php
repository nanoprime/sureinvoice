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
require_once('common.php');

class SI_Expense{
	var $id;
	
	var $company_id;
	
	var $task_id;
	
	var $project_id;
	
	var $description;
	
	var $cost;
	
	var $price;
	
	var $created_ts;
	
	var $item_code_id;

	var $error;

	var $_company;
	
	var $_project;

	var $_task;

	function SI_Expense(){
		$this->error = '';
		$this->id = 0;
		$this->company_id = 0;
		$this->task_id = 0;
		$this->project_id = 0;
		$this->description = '';
		$this->cost = 0.00;
		$this->price = 0.00;
		$this->created_ts = time();
		$this->item_code_id = 0;
		
		$this->_company = FALSE;
		$this->_project = FALSE;
		$this->_task = FALSE;
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
		$result = $db_conn->query("INSERT INTO expenses (company_id, task_id, project_id, description, cost, ".
		  "price, created_ts, item_code_id)".
		  " VALUES(".$this->company_id.", ".$this->task_id.", ".$this->project_id.", '".$this->description."', ".$this->cost.", ".
		  "".$this->price.", ".$this->created_ts.", ".$this->item_code_id.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_Expense::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_Expense::update() : Expense id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE expenses SET company_id = ".$this->company_id.", ".
		  "task_id = ".$this->task_id.", project_id = ".$this->project_id.", ".
		  "description = '".$this->description."', ".
		  "cost = ".$this->cost.", price = ".$this->price.", created_ts = ".$this->created_ts.", ".
		  "item_code_id = ".$this->item_code_id.
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Expense::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Expense::delete() : Expense id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM expenses WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Expense::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Expense::get() : Expense id not set\n";
			return FALSE;
		}

		$Expense = SI_Expense::retrieveSet("WHERE e.id = $id", TRUE);
		if($Expense === FALSE){
			return FALSE;
		}

		if(isset($Expense[0])){
			$this->updateFromAssocArray($Expense[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_Expense::get() : No data retrieved from query\n";
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
SELECT e.id, e.company_id, e.task_id, e.description, e.project_id, e.cost, e.price, e.created_ts, e.item_code_id, ill.id AS ill_id
FROM expenses AS e
LEFT JOIN companies AS c ON c.id = e.company_id
LEFT JOIN projects AS p ON p.id = e.project_id
LEFT JOIN tasks AS t ON t.id = e.task_id 
LEFT JOIN projects AS tp ON tp.id = t.project_id
LEFT JOIN invoice_line_links AS ill ON ill.expense_id = e.id
$clause");

		if($result === FALSE){
			$this->error = "SI_Expense::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$Expense[] = $row;
			}else{
				$temp =& new SI_Expense();
				if(empty($row['ill_id']))
					$row['billed'] = TRUE;
				else
					$row['billed'] = FALSE;
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				$Expense[] =& $temp;
			}

		}

		return $Expense;
	}

	function getForTask($task_id, $unbilled = FALSE){
		global $db_conn;

		$task_id = intval($task_id);
		if($task_id <= 0){
			$this->error = "SI_Expense::getForTask() : Invalid task id!\n";
			return FALSE;
		}
		
		$unbilled_sql = '';
		if($unbilled){
			$unbilled_sql = " AND ill.id IS NULL";
		}

		return SI_Expense::retrieveSet("WHERE e.task_id = $task_id $unbilled_sql");
	}

	function getForProject($project_id, $unbilled = FALSE){
		global $db_conn;

		$project_id = intval($project_id);
		if($project_id <= 0){
			$this->error = "SI_Expense::getForProject() : Invalid project id!\n";
			return FALSE;
		}

		$unbilled_sql = '';
		if($unbilled){
			$unbilled_sql = " AND ill.id IS NULL";
		}

		return SI_Expense::retrieveSet("WHERE e.project_id = $project_id OR t.project_id = $project_id $unbilled_sql");
	}

	function getForCompany($company_id, $unbilled = FALSE){
		global $db_conn;

		$company_id = intval($company_id);
		if($company_id <= 0){
			$this->error = "SI_Expense::getForCompany() : Invalid company id!\n";
			return FALSE;
		}

		$unbilled_sql = '';
		if($unbilled){
			$unbilled_sql = " AND ill.id IS NULL";
		}

		return SI_Expense::retrieveSet("WHERE (e.company_id = $company_id OR p.company_id = $company_id OR tp.company_id = $company_id) $unbilled_sql");
	}

	function getProjectName(){
		if($this->getProject() === FALSE)
			return FALSE;

		return $this->_project->name;
	}

	function getProject(){
		if($this->_project === FALSE){
			$this->_project = new SI_Project();
			if($this->task_id > 0){
				if($this->getTask() === FALSE)
					return FALSE;

				if($this->_project->get($this->_task->project_id) === FALSE){
					$this->error = "SI_Expense::getTask(): Error getting project: ".$this->_project->getLastError();
					return FALSE;
				}
			}elseif($this->project_id > 0){
				if($this->_project->get($this->project_id) === FALSE){
					$this->error = "SI_Expense::getTask(): Error getting project: ".$this->_project->getLastError();
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
					$this->error = "SI_Expense::getTask(): Error getting task: ".$this->_task->getLastError();
					return FALSE;
				}
			}
		}

		return $this->_task;
	}

	function getCompany(){
		if($this->_company === FALSE){
			$this->_company = new SI_Company();
			if($this->company_id > 0){
				if($this->_company->get($this->company_id) === FALSE){
					$this->error = "SI_Expense::getCompany(): Error getting company: ".$this->_company->getLastError();
					return FALSE;
				}
			}else{
				if($this->getProject() === FALSE)
					return FALSE;
				if($this->_project->company_id > 0){
					if($this->_company->get($this->_project->company_id) === FALSE){
						$this->error = "SI_Expense::getCompany(): Error getting company: ".$this->_company->getLastError();
						return FALSE;
					}
				}
			}

		}

		return $this->_company;
	}

	function getUnbilled(){
		return SI_Expense::retrieveSet("WHERE ill.id IS NULL");			
	}
}

