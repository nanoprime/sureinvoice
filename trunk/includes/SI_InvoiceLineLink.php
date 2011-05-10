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
class SI_InvoiceLineLink{
	var $id, $invoice_line_id, $task_activity_id, $expense_id,
		$payment_schedule_id;

	var $error;

	function SI_InvoiceLineLink(){
		$this->error = '';
		$this->id = 0;
		$this->invoice_line_id = 0;
		$this->task_activity_id = 0;
		$this->expense_id = 0;
		$this->payment_schedule_id = 0;

	}

	function _populateData($values){
		if(is_array($values)){
			$this->id = $values[0];
			$this->invoice_line_id = $values[1];
			$this->task_activity_id = $values[2];
			$this->expense_id = $values[3];
			$this->payment_schedule_id = $values[4];
		}
	}

	function updateFromAssocArray($array){
		if(isset($array['id'])) $this->id = $array['id'];
		if(isset($array['invoice_line_id'])) $this->invoice_line_id = $array['invoice_line_id'];
		if(isset($array['task_activity_id'])) $this->task_activity_id = $array['task_activity_id'];
		if(isset($array['expense_id'])) $this->expense_id = $array['expense_id'];
		if(isset($array['payment_schedule_id'])) $this->payment_schedule_id = $array['payment_schedule_id'];
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
		$result = $db_conn->query("INSERT INTO invoice_line_links (invoice_line_id, task_activity_id, expense_id, payment_schedule_id)".
			" VALUES(".$this->invoice_line_id.", ".$this->task_activity_id.", ".$this->expense_id.", ".$this->payment_schedule_id.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_InvoiceLineLink::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_InvoiceLineLink::update() : InvoiceLineLink id not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE invoice_line_links SET invoice_line_id = ".$this->invoice_line_id.", ".
			"task_activity_id = ".$this->task_activity_id.", expense_id = ".$this->expense_id.", ".
			"payment_schedule_id = ".$this->payment_schedule_id."".
			" WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_InvoiceLineLink::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_InvoiceLineLink::delete() : InvoiceLineLink id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM invoice_line_links WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_InvoiceLineLink::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_InvoiceLineLink::get() : InvoiceLineLink id not set\n";
			return FALSE;
		}

		$InvoiceLineLink = SI_InvoiceLineLink::retrieveSet("WHERE id = $id", TRUE);
		if($InvoiceLineLink === FALSE){
			return FALSE;
		}

		if(isset($InvoiceLineLink[0])){
			$this->_populateData($InvoiceLineLink[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_InvoiceLineLink::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, invoice_line_id, task_activity_id, expense_id, payment_schedule_id".
			" FROM invoice_line_links ".$clause);

		if(!$result){
			$this->error = "SI_InvoiceLineLink::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			if($raw == TRUE){
				$InvoiceLineLink[] = $row;
			}else{
				$temp =& new SI_InvoiceLineLink();
				$temp->_populateData($row);
				$InvoiceLineLink[] =& $temp;
			}

		}
		$result->free();
		
		return $InvoiceLineLink;
	}

// BEGIN - Custom SI_InvoiceLineLink methods
////////////////////////////////////////////////////////////
	function clearForInvoiceLine($invoice_line_id){
		global $db_conn;

		$sql = "DELETE FROM invoice_line_links WHERE invoice_line_id = ".intval($invoice_line_id);
		if($db_conn->query($sql) === FALSE){
			$this->error = "SI_InvoiceLineLink::clearForInvoiceLine(): Error removing links: ".$db_conn->getLastError();
			return FALSE;
		}

		return TRUE;
	}

	function getType(){
		if($this->task_activity_id > 0){
			return 'Task Activity';
		}elseif($this->expense_id > 0){
			return 'Expense';
		}elseif($this->payment_schedule_id > 0){
			return 'Scheduled Payment';
		}
		
		return 'Unknown';	
	}
	
	function getDescription(){
		if($this->task_activity_id > 0){
			$ta = new SI_TaskActivity();
			if($ta->get($this->task_activity_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getDescription(): Error getting task activity: '.$ta->getLastError();
				return FALSE;	
			}
			
			$task = $ta->getTask();
			$project = $task->getProject();
			return $project->name.":".$task->name;
		}elseif($this->expense_id > 0){
			$ex = new SI_Expense();
			if($ex->get($this->expense_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getDescription(): Error getting expense: '.$ex->getLastError();
				return FALSE;	
			}
			return $ex->description;
		}elseif($this->payment_schedule_id > 0){
			$ps = new SI_PaymentSchedule();
			if($ps->get($this->payment_schedule_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getDescription(): Error getting scheduled payment: '.$ps->getLastError();
				return FALSE;	
			}
			return $ps->description;
		}
		
		return 'Unknown';
	}

	function getPrice(){
		if($this->task_activity_id > 0){
			$ta = new SI_TaskActivity();
			if($ta->get($this->task_activity_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getPrice(): Error getting task activity: '.$ta->getLastError();
				return FALSE;	
			}
			
			return $ta->price;
		}elseif($this->expense_id > 0){
			$ex = new SI_Expense();
			if($ex->get($this->expense_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getPrice(): Error getting expense: '.$ex->getLastError();
				return FALSE;	
			}
			return $ex->price;
		}elseif($this->payment_schedule_id > 0){
			$ps = new SI_PaymentSchedule();
			if($ps->get($this->payment_schedule_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getPrice(): Error getting scheduled payment: '.$ps->getLastError();
				return FALSE;	
			}
			return $ps->amount;
		}
		
		return 'Unknown';
	}

	function getQuantity(){
		if($this->task_activity_id > 0){
			$ta = new SI_TaskActivity();
			if($ta->get($this->task_activity_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getQuantity(): Error getting task activity: '.$ta->getLastError();
				return FALSE;	
			}
			
			return $ta->getQuantity();
		}elseif($this->expense_id > 0){
			return 1.0;
		}elseif($this->payment_schedule_id > 0){
			return 1.0;
		}
		
		return 'Unknown';
	}

	function getUnitPrice(){
		if($this->task_activity_id > 0){
			$ta = new SI_TaskActivity();
			if($ta->get($this->task_activity_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getUnitPrice(): Error getting task activity: '.$ta->getLastError();
				return FALSE;	
			}
			
			return $ta->hourly_rate;
		}elseif($this->expense_id > 0){
			$ex = new SI_Expense();
			if($ex->get($this->expense_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getPrice(): Error getting expense: '.$ex->getLastError();
				return FALSE;	
			}
			return $ex->price;
		}elseif($this->payment_schedule_id > 0){
			$ps = new SI_PaymentSchedule();
			if($ps->get($this->payment_schedule_id) === FALSE){
				$this->error = 'SI_InvoiceLineLink::getPrice(): Error getting scheduled payment: '.$ps->getLastError();
				return FALSE;	
			}
			return $ps->amount;
		}
		
		return 'Unknown';
	}
// END - Custom SI_InvoiceLineLink methods 
////////////////////////////////////////////////////////////
}

