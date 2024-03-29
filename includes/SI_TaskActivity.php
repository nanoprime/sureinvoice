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
require_once('SI_UserTransaction.php');
require_once('SI_CompanyTransaction.php');
require_once('SI_User.php');
require_once('SI_Task.php');

////////////////////////////////////////////////////////////
// Code generated by CodeGen (http://uversaconsulting.net).
// Insert custom code where indicated below.
////////////////////////////////////////////////////////////

// SI_TaskActivity Class Definition
////////////////////////////////////////////////////////////
class SI_TaskActivity{
	var $id;
	
	var $task_id;
	
	var $user_id;
	
	var $text;
	
	var $start_ts;
	
	var $end_ts;
	
	var $hourly_cost;
	
	var $hourly_rate;
	
	var $cost_trans_id;
	
	var $com_trans_id;
	
	var $invoice_id;
	
    var $sales_com_type_id;

	var $item_code_id;
	
	var $completed_items;

	var $_user;

	var $_task;

	var $error;

	function SI_TaskActivity(){
		$this->error = '';
		$this->id = 0;
		$this->task_id = 0;
		$this->user_id = 0;
		$this->text = '';
		$this->start_ts = 0;
		$this->end_ts = 0;
		$this->hourly_cost = 0.00;
		$this->hourly_rate = 0.00;
		$this->cost_trans_id = 0;
		$this->com_trans_id = 0;
		$this->invoice_id = 0;
		$this->sales_com_type_id = 0;

		$this->task = '';
		$this->company = '';
		$this->project = '';

		$this->_user = FALSE;
		$this->_task = FALSE;
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

		if($this->calculate() === FALSE)
			return FALSE;

		if($this->_updateTransactions() === FALSE)
			return FALSE;

		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO task_activities (task_id, user_id, text, start_ts, ".
			"end_ts, hourly_cost, hourly_rate, cost_trans_id, ".
			"com_trans_id, invoice_id, sales_com_type_id, item_code_id)".
			" VALUES(".$this->task_id.", ".$this->user_id.", '".$this->text."', ".$this->start_ts.", ".
			"".$this->end_ts.", ".$this->hourly_cost.", ".$this->hourly_rate.", ".$this->cost_trans_id.", ".
			"".$this->com_trans_id.", ".$this->invoice_id.", ".$this->sales_com_type_id.", ".$this->item_code_id.")");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id($db_conn->_conn);
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_TaskActivity::update() : TaskActivity id not set\n";
			return FALSE;
		}

		$oldTA = new SI_TaskActivity();
		if($oldTA->get($this->id) === FALSE){
			$this->error = "SI_TaskActivity::update(): Error getting current TaskActivity: ".$oldTA->getLastError();
			return FALSE;
		}
		if($this->_updateTransactions($oldTA) === FALSE){
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("UPDATE task_activities SET task_id = ".$this->task_id.", ".
			"user_id = ".$this->user_id.", text = '".$this->text."', ".
			"start_ts = ".$this->start_ts.", end_ts = ".$this->end_ts.", ".
			"hourly_cost = ".$this->hourly_cost.", hourly_rate = ".$this->hourly_rate.", ".
			"cost_trans_id = ".$this->cost_trans_id.", com_trans_id = ".$this->com_trans_id.", ".
			"invoice_id = ".$this->invoice_id.", sales_com_type_id = ".$this->sales_com_type_id.", ".
			"item_code_id = ".$this->item_code_id.
			" WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_TaskActivity::delete() : TaskActivity id not set\n";
			return FALSE;
		}

		$ta = new SI_TaskActivity();
		if($ta->get($id) === FALSE){
			$this->error = "SI_TaskActivity::delete(): Error getting current activity: ".$ta->getLastError();
			return FALSE;
		}

    if($ta->_deleteTransactions() === FALSE){
      $this->error = "SI_TaskActiviy::delete(): Error removing transactions: ".$ta->getLastError();
      return FALSE;
    }

		$result = $db_conn->query("DELETE FROM task_activities WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_TaskActivity::get() : TaskActivity id not set\n";
			return FALSE;
		}

		$TaskActivity = SI_TaskActivity::retrieveSet("WHERE a.id = $id", TRUE);
		if($TaskActivity === FALSE){
			return FALSE;
		}

		if(isset($TaskActivity[0])){
			$this->updateFromAssocArray($TaskActivity[0]);
			if($this->calculate() === FALSE)
				return FALSE;
			$this->stripSlashes();
		}else{
			$this->error = "SI_TaskActivity::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT a.id, a.task_id, a.user_id, a.text, a.start_ts, a.end_ts, ".
			"a.hourly_cost, a.hourly_rate, a.cost_trans_id, a.com_trans_id, a.invoice_id, a.sales_com_type_id, a.item_code_id, ".
			"t.name AS task_name, c.name AS company_name, p.id AS project_id, p.name AS project_name ".
			"FROM task_activities AS a ".
			"LEFT JOIN tasks AS t ON a.task_id = t.id ".
			"LEFT JOIN projects AS p ON t.project_id = p.id ".
			"LEFT JOIN companies AS c ON p.company_id = c.id ".
			"LEFT JOIN user_transactions AS ut_com ON a.com_trans_id = ut_com.id ".
			"LEFT JOIN user_transactions AS ut_cost ON a.cost_trans_id = ut_cost.id ".
			$clause);

		if($result === FALSE){
			$this->error = "SI_TaskActivity::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$TaskActivity[] = $row;
			}else{
				$temp =& new SI_TaskActivity();
				$temp->updateFromAssocArray($row);
				if($temp->calculate() === FALSE){
					$this->error = "SI_TaskActivity::retrieveSet(): Error calculating: ".$temp->getLastError();
					return FALSE;
				}
				$temp->stripSlashes();
				$TaskActivity[] =& $temp;
			}

		}

		return $TaskActivity;
	}

	function getActivitiesForCompany($company_id, $unbilled = TRUE, $invoice = 0, $include_spec = false){
		global $db_conn;

		$invoice = intval($invoice);
		$company_id = intval($company_id);
		if($invoice > 0 && $company_id > 0){
			$clause = "WHERE p.company_id = $company_id AND i.id = $invoice";
		}elseif($invoice > 0){
			$clause = "WHERE i.id = $invoice";
		}elseif($company_id > 0 && $unbilled){
			if($include_spec){
				$clause = "WHERE p.company_id = $company_id AND ill.id IS NULL AND (((p.billable = 'Y' OR p.billable = 'S') AND t.billable = 'D' )OR (t.billable = 'Y' OR t.billable = 'S'))";
			}else{
				$clause = "WHERE p.company_id = $company_id AND ill.id IS NULL AND ((p.billable = 'Y' AND t.billable = 'D' )OR t.billable = 'Y')";
			}
		}elseif($company_id > 0){
			$clause = "WHERE p.company_id = $company_id";
		}else{
			$this->error = "SI_TaskActivity::getActivitiesForCompany(): Invalid parameters";
			return FALSE;
		}

		$sql = "SELECT a.id, a.task_id, a.user_id, a.text, a.start_ts, a.end_ts,
			a.hourly_cost, a.hourly_rate, a.cost_trans_id, a.com_trans_id, il.invoice_id, a.sales_com_type_id, a.item_code_id, 
			t.name AS task_name, c.name AS company_name, p.id AS project_id, p.name AS project_name,
			ROUND(((a.end_ts - a.start_ts) / 60 / 60) * a.hourly_cost, 2) AS cost,
			ROUND(((a.end_ts - a.start_ts) / 60 / 60) * a.hourly_rate, 2) AS price
			FROM task_activities AS a
			LEFT JOIN tasks AS t ON a.task_id = t.id
			LEFT JOIN projects AS p ON t.project_id = p.id
			LEFT JOIN companies AS c ON p.company_id = c.id
			LEFT JOIN invoice_line_links AS ill ON ill.task_activity_id = a.id
			LEFT JOIN invoice_lines AS il ON il.id = ill.invoice_line_id 
			LEFT JOIN invoices AS i ON i.id = il.invoice_id
			$clause AND a.hourly_rate > 0
			";

		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_TaskActivity::getActivitiesForCompany(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$activities = array();
		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_TaskActivity();
			$temp->updateFromAssocArray($row);
			$temp->billed = ($row['invoice_id'] > 0 ? TRUE : FALSE);
			$temp->_calcPrice();
			$temp->_calcCost();
			$activities[] =& $temp;
		}

		return $activities;
	}

	function getActivitiesForInvoice($invoice){
		//TODO: This doesn't work with the new structure
		global $db_conn;

		$activities = SI_TaskActivity::retrieveSet("WHERE a.invoice = $invoice ");
		if($activities === FALSE){
			return FALSE;
		}

		return $activities;
	}

	function getActivitiesForCheck($check){
		//TODO: This doesn't work with the new structure
		global $db_conn;

		$activities = SI_TaskActivity::retrieveSet("WHERE a.check = $check ");
		if($activities === FALSE){
			return FALSE;
		}

		return $activities;
	}

	function getOpenActivities($user_id){
		global $db_conn;

		$user_id = intval($user_id);
		if($user_id <= 0){
				$this->error = "SI_TaskActivity::getOpenActivities() : Invalid parameter!\n";
				return FALSE;
		}

		$activities = SI_TaskActivity::retrieveSet("WHERE a.user_id = $user_id AND (a.start_ts > 0 AND a.end_ts = 0) ");
		if($activities === FALSE){
			return FALSE;
		}

		return $activities;
	}

	function applyInvoice($ids, $invoice){
		//TODO: This doesn't work with the new structure
		global $db_conn;

		$invoice = intval($invoice);
		if(empty($invoice) || count($ids) == 0){
				$this->error = "SI_TaskActivity::applyInvoice() : Invalid parameters!\n";
				return FALSE;
		}

		$result = $db_conn->query("UPDATE task_activities SET invoice = ".$invoice." ".
			"WHERE id IN (".join(",",$ids).")");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::applyInvoice() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function applyCheck($ids, $check){
		//TODO: This doesn't work with the new structure
		global $db_conn;

		$check = intval($check);
		if(empty($check) || count($ids) == 0){
				$this->error = "SI_TaskActivity::applyCheck() : Invalid parameters!\n";
				return FALSE;
		}

		$result = $db_conn->query("UPDATE task_activities SET `check` = ".$check." ".
			"WHERE id IN (".join(",",$ids).")");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::applyCheck() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function find($start_ts, $end_ts, $resource_id = 0, $company_id = 0, $billable_only = 'N'){
		global $db_conn;

		$start_ts = intval($start_ts);
		$end_ts = intval($end_ts);
		$sql = "WHERE (a.start_ts >= $start_ts AND a.end_ts <= $end_ts) ";

		$resource_id = intval($resource_id);
		if($resource_id > 0)
			$sql .= "AND a.user_id = $resource_id ";

		$company_id = intval($company_id);
		if($company_id > 0)
			$sql .= "AND p.company_id = $company_id ";

		if($billable_only == 'Y')
			$sql .= "AND ((p.billable = 'Y' AND t.billable = 'D' )OR t.billable = 'Y')";

		return $this->retrieveSet($sql." ORDER BY a.start_ts");
	}

	function addItem($item_id){
		global $db_conn;

		if(empty($this->id)){
				$this->error = "SI_TaskActivity::addItem($item_id) : Invalid activity id!\n";
				return FALSE;
		}

		$item_id = intval($item_id);
		if(empty($item_id)){
				$this->error = "SI_TaskActivity::addItem($item_id) : Invalid parameter!\n";
				return FALSE;
		}

		$result = $db_conn->query("UPDATE task_items SET task_activity_id = ".$this->id." ".
			"WHERE id = ".$item_id);

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::addItem($item_id) : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function removeItem($item_id){
		global $db_conn;

		$item_id = intval($item_id);
		if(empty($item_id)){
				$this->error = "SI_TaskActivity::removeItem($item_id) : Invalid parameter!\n";
				return FALSE;
		}

		$result = $db_conn->query("UPDATE task_items SET task_activity_id = 0 ".
			"WHERE id = ".$item_id);

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::removeItem($item_id) : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function addItems($item_ids){
		if(!is_array($item_ids)){
			$this->error = "SI_TaskActivity::addItems() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		foreach($item_ids as $item_id){
			if($this->addItem($item_id) === FALSE){
				return FALSE;
			}
		}

		return TRUE;
	}

	function setItems($item_ids){
		global $db_conn;

		if(!is_array($item_ids)){
			$this->error = "SI_TaskActivity::setItems() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		if(empty($this->id)){
				$this->error = "SI_TaskActivity::setItems() : Invalid activity id!\n";
				return FALSE;
		}

		$result = $db_conn->query("UPDATE task_items SET task_activity_id = 0 ".
			"WHERE task_activity_id = ".$this->id);
		if($result === FALSE){
			$this->error = "SI_TaskActivity::setItems() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$item_ids_string = implode(",", $item_ids);
		$result = $db_conn->query("UPDATE task_items SET task_activity_id = ".$this->id." ".
			"WHERE id IN (".$item_ids_string.")");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_TaskActivity::setItems() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function getCompletedItems(){
		if(empty($this->id) || empty($this->task_id)){
			$this->error = "SI_TaskActivity::getCompletedItems(): Invalid Task!\n";
			return FALSE;
		}

		$ti = new SI_TaskItem();
		$items = $ti->getCompletedTaskItems($this->task_id, $this->id);
		if($items === FALSE){
			$this->error = "SI_TaskActivity::getCompletedItems(): Error getting items:\n".$ti->getLastError();
			return FALSE;
		}else{
			$this->completed_items = $items;
		}

		return TRUE;
	}

	function getCompletedItemsHTML(){
		if($this->completed_items	=== NULL){
			if($this->getCompletedItems() === FALSE){
				return FALSE;
			}
		}

		$html = '';
		for($i=0; $i<count($this->completed_items); $i++){
			$completed = $this->completed_items[$i]->task_activity_id == $this->id;
			$html .= ($completed ? '<IMG SRC="images/check-green.gif">' : '<IMG SRC="images/dot-red.gif">')."&nbsp;".$this->completed_items[$i]->item."<BR>\n";
			if($this->completed_items[$i]->hasChildren()){
				foreach($this->completed_items[$i]->children as $child){
					$child_completed = $child->task_activity_id == $this->id;
					$html .= "&nbsp;&nbsp;&nbsp;&nbsp;".($child_completed ? '<IMG SRC="images/check-green.gif">' : '<IMG SRC="images/dot-red.gif">')."&nbsp;".$child->item."<BR>\n";
				}
			}
		}

		return $html;

	}

	function _deleteTransactions(){
		$ct = new SI_CompanyTransaction();
		$ut = new SI_UserTransaction();
		
		if($this->com_trans_id > 0){
			if($ut->delete($this->com_trans_id) === FALSE){
				$this->error = "SI_TaskActivity::_deleteTransactions(): Error deleting commission transaction: ".$ut->getLastError();
				return FALSE;
			}
		}

		if($this->cost_trans_id> 0){
			if($ut->delete($this->cost_trans_id) === FALSE){
				$this->error = "SI_TaskActivity::_deleteTransactions(): Error deleting cost transaction: ".$ut->getLastError();
				return FALSE;
			}
		}

		if($this->price_trans_id> 0){
			if($ct->delete($this->price_trans_id) === FALSE){
				$this->error = "SI_TaskActivity::_deleteTransactions(): Error deleting price transaction: ".$ct->getLastError();
				return FALSE;
			}
		}

		return TRUE;
	}

	function _updateTransactions($oldTA = null){
		$ut = new SI_UserTransaction();
		$ct = new SI_CompanyTransaction();

		if($this->start_ts > 0 && $this->end_ts > 0){
			$task = $this->getTask();
			if($task === FALSE)
				return FALSE;

			$project = $task->getProject();
			if($project === FALSE){
				$this->error = "SI_TaskActivity::_updateTransactions(): Error getting project: ".$task->getLastError();
				return FALSE;
			}

			$company = $project->getCompany();
			if($company === FALSE){
				$this->error = "SI_TaskActivity::_updateTransactions(): Error getting company: ".$project->getLastError();
				return FALSE;
			}

			if(is_null($oldTA) || $oldTA->start_ts != $this->start_ts || $oldTA->end_ts != $this->end_ts){
		
				// Calculate cost
				if($this->_calcCost() === FALSE)
					return FALSE;
	
				// Add cost transaction
				$act_user = $this->getUser();
	//			print("TA cost is ".$this->cost."\n");
				if($this->cost > 0.00 && $act_user->rate_type != 'SALARY'){
					$cost_trans = new SI_UserTransaction();
					$cost_trans->amount = $this->cost;
					// TODO Remove after migration is completed
					if(isset($GLOBALS['phpdt_import']) && $GLOBALS['phpdt_import'] == true){
						$cost_trans->timestamp = $GLOBALS['phpdt_cost_ts'];
					}else{ 
						$cost_trans->timestamp = time();
					}
					$cost_trans->description = $project->name.": ".$task->name;
					$cost_trans->user_id = $this->user_id;
					if($cost_trans->add() === FALSE){
						$this->error = "SI_TaskActivity::_updateTransactions(): Error adding cost transaction: ".$cost_trans->getLastError();
						return FALSE;
					}
		
					// Set cost trans id
					$this->cost_trans_id = $cost_trans->id;
				}else{
					$this->cost_trans_id = 0;	
				}
			}
			
			if($task->isBillable() || $task->isSpec()){
				if($task->hasCommission()){
					// Calculate commission
					if($this->_calcCommission() === FALSE)
						return FALSE;

					// Add commission transaction
					$sct_user = $task->getSalesCommissionUser();
					if($sct_user === FALSE){
						$this->error = "SI_TaskActivity::_updateTransactions(): Error getting sales commission user: ".$task->getLastError();
						return FALSE;
					}

					$com_trans = new SI_UserTransaction();
					$com_trans->amount = $this->com_amount;
					$com_trans->timestamp = time();
					$com_trans->description = "Commission on Task ".$task->name;
					$com_trans->user_id = $sct_user->id;
					if($com_trans->add() === FALSE){
						$this->error = "SI_TaskActivity::_updateTransactions(): Error adding cost transaction: ".$com_trans->getLastError();
						return FALSE;
					}

					// Set commission trans id
					$this->com_trans_id = $com_trans->id;
				}else{
					$this->com_trans_id = 0;
				}
			}

		}else{
			// It's not completed so 0-out all trans ids
			$this->com_trans_id = 0;
			$this->cost_trans_id = 0;
		}

		// Delete any remaining old transactions
		if($oldTA != null){
			if(($oldTA->com_trans_id != 0 && $this->com_trans_id == 0) ||
				 ($oldTA->com_trans_id != $this->com_trans_id)){
				$ut = new SI_UserTransaction();
				if($ut->delete($oldTA->com_trans_id) === FALSE){
					$this->error = "SI_TaskActivity::_updateTransactions(): Error removing commission transaction: ".$ut->getLastError();
					return FALSE;
				}
			}

			if(($oldTA->cost_trans_id != 0 && $this->cost_trans_id == 0) ||
				 ($oldTA->cost_trans_id != $this->cost_trans_id)){
				if($ut->delete($oldTA->cost_trans_id) === FALSE){
					$this->error = "SI_TaskActivity::_updateTransactions(): Error removing cost transaction: ".$ut->getLastError();
					return FALSE;
				}
			}
		}
	}

	function calculate(){
		if($this->_calcPrice() === FALSE){
			return FALSE;
		}

		if($this->_calcCost() === FALSE){
			return FALSE;
		}

		if($this->_calcCommission() === FALSE){
			return FALSE;
		}

		return TRUE;
	}

	function _calcCost(){
		$act_user = $this->getUser();
		if($act_user === FALSE)
			return FALSE;

		$amount = $act_user->calculateCost($this->start_ts, $this->end_ts, $this->hourly_rate, $this->hourly_cost);
		//debug_message("Calculated cost of $amount");
		if($amount === FALSE){
			$this->error = "SI_TaskActivity::_calcCost(): Error calculating cost: ".$act_user->getLastError();
			return FALSE;
		}

		$this->cost = $amount;
		return TRUE;
	}

	function _calcPrice(){
		$amount = 0.00;

		if($this->hourly_rate  > 0){
			$amount = $this->hourly_rate * $this->getQuantity();
		}

		$this->price = $amount;

		return TRUE;
	}

	function _calcCommission(){
		$task = $this->getTask();
		if($task === FALSE)
			return FALSE;

		$amount = 0.00;
		if($this->sales_com_type_id > 0){
			$task = $this->getTask();
			if(!$task->isSpec() || ($task->isSpec() && $task->isCompleted())){
				$sct = new SI_SalesCommissionType();
				if($sct->get($this->sales_com_type_id) === FALSE){
					$this->error = "SI_TaskActivity::_calcCommission(): Error getting sales commission type: ".$sct->getLastError();
					return FALSE;
				}

				//debug_message("Found price of ".$this->price." and cost of ".$this->cost."\n");
				$amount = round($sct->calculateCommission($this->price, $this->cost), 2);
				if($amount === FALSE){
					$this->error = "SI_TaskActivity::_calcCommission(): Error calculating commission: ".$sct->getLastError();
					return FALSE;
				}
			}
		}

		$this->com_amount = $amount;

		return TRUE;
	}


	function getItemCode(){
		if($this->item_code_id <= 0){
			$this->error = 	"SI_TaskActivity::getItemCode(): Item Code id is not set";
			return FALSE;
		}
		
		if($this->_item_code == FALSE){
			$ic = new SI_ItemCode();
			if($ic->get($this->item_code_id) === FALSE){
				$this->error = "SI_TaskActivity::getItemCode(): Error getting item code {$this->item_code_id}: ".$ic->getLastError();
				return FALSE;
			}
			$this->_item_code =& $ic;
		}

		return $this->_item_code;
	}

	function getTask(){
		if($this->task_id <= 0){
			$this->error = 	"SI_TaskActivity::getTask(): Task id is not set";
			return FALSE;
		}
		
		if($this->_task == FALSE){
			$task = new SI_Task();
			if($task->get($this->task_id) === FALSE){
				$this->error = "SI_TaskActivity::getTask(): Error getting task {$this->task_id}: ".$task->getLastError();
				return FALSE;
			}
			$this->_task =& $task;
		}

		return $this->_task;
	}

	function getProject(){
		if($this->getTask() === FALSE)
			return FALSE;
		
		$project =& $this->_task->getProject();
		if($project === FALSE){
			$this->error = "SI_TaskActivity::getTask(): Error getting project from task: ".$this->_task->getLastError();
			return FALSE;
		}
		
		return $project;
	}

	function getUser(){
		if($this->user_id <= 0){
			$this->error = "SI_TaskActivity::getTask(): User id is not set";
			return FALSE;
		}
		
		if($this->_user == FALSE){
			$user = new SI_User();
			if($user->get($this->user_id) === FALSE){
				$this->error = "SI_TaskActivity::getUser(): Error getting user: ".$user->getLastError();
				return FALSE;
			}
			$this->_user =& $user;
		}

		return $this->_user;
	}

	function getUserName(){
		if($this->getUser() === FALSE)
			return FALSE;
			
		return $this->_user->first_name.' '.$this->_user->last_name;	
	}
	
	function getActivities($company_id = 0, $user_id = 0, $check = NULL, $invoice = NULL){
		global $db_conn;

		$clauses = array();
		if($company_id > 0){
			$clauses[] = "c.company_id = ".intval($company_id);
		}

		if($user_id > 0){
			$clauses[] = "a.user_id = ".intval($user_id);
		}

		if($check > 0){
			$clauses[] = "cht.check_id = ".intval($check);
		}elseif($check === 0){
			$clauses[] = "cht.check_id IS NULL";
		}

		if($invoice > 0){
			$clauses[] = "il.invoice_id = ".intval($invoice);
		}elseif($invoice === 0){
			$clauses[] = "il.invoice_id IS NULL";
		}

		if($invoice > 0 && $company_id > 0){
			$clause = "p.company_id = $company_id AND a.invoice = ".intval($invoice);
		}elseif($invoice > 0){
			$clause = "a.invoice = 0".intval($invoice);
		}elseif($company_id > 0 && $unbilled){
			$clause = "p.company_id = $company_id AND a.invoice = 0 AND ((p.billable = 'Y' AND t.billable = 'D' )OR t.billable = 'Y')";
		}else{
			$clause = "p.company_id = $company_id";
		}

		$sql = "SELECT a.id, a.task_id, a.user_id, a.text, a.start_ts, a.end_ts,
			a.hourly_cost, a.hourly_rate, a.cost_trans_id, a.com_trans_id, a.invoice_id, a.sales_com_type_id, a.item_code_id, 
			t.name AS task_name,	c.name AS company_name, p.id AS project_id, p.name AS project_name
			FROM task_activities AS a
			LEFT JOIN tasks AS t ON a.task_id = t.id
			LEFT JOIN projects AS p ON t.project_id = p.id
			LEFT JOIN companies AS c ON p.company_id = c.id
			LEFT JOIN check_transactions AS cht ON a.id = cht.trans_id
			LEFT JOIN invoice_line_links AS ill ON a.id = ill.task_activity_id
			LEFT JOIN invoice_lines AS il ON ill.invoice_line_id = il.id
			WHERE $clause
			";

		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_TaskActivity::getActivities(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$activities = array();
		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_TaskActivity();
			$temp->updateFromAssocArray($row);
			$temp->stripSlashes();
			$activities[] =& $temp;
		}

		return $activities;
	}

	function getByCostTransId($cost_trans_id = NULL){
		global $db_conn;

		if(!isset($cost_trans_id)){
			$cost_trans_id = $this->cost_trans_id;
		}

		if(!isset($cost_trans_id)){
			$this->error = "SI_TaskActivity::getByCostTransId() : TaskActivity price_trans_id not set\n";
			return FALSE;
		}

		$TaskActivity = SI_TaskActivity::retrieveSet("WHERE a.cost_trans_id = $cost_trans_id", TRUE);
		if($TaskActivity === FALSE){
			return FALSE;
		}

		if(isset($TaskActivity[0])){
			$this->updateFromAssocArray($TaskActivity[0]);
			$this->stripSlashes();
		}else{
			$this->error = "SI_TaskActivity::getByCostTransId() : No data retrieved from query\n";
		}
		return TRUE;
	}

	function getByTaskId($task_id){
		global $db_conn;

		$task_id = intval($task_id);
		if($task_id <= 0){
			$this->error = "SI_TaskActivity::getByTaskId() : TaskActivity task_id not set\n";
			return FALSE;
		}

		$TaskActivity = SI_TaskActivity::retrieveSet("WHERE a.task_id = $task_id");
		if($TaskActivity === FALSE){
			return FALSE;
		}

		return $TaskActivity;
	}

	function isPaid(){
		global $db_conn;
		
		if($this->cost_trans_id <= 0)
			return FALSE;
			
		$sql = "SELECT check_id FROM check_transactions WHERE trans_id = ".$this->cost_trans_id;
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_TaskActivity::isPaid(): Error looking up transaction id: ".$db_conn->getLastError();
			return FALSE;
		}
		
		if($row = $result->fetchRow()){
			if($row[0] > 0)
				return TRUE;
		}
		
		return FALSE;	
	}

	function isBilled(){
		global $db_conn;
		
		if($this->id <= 0)
			return FALSE;
			
		$sql = "SELECT invoice_line_id FROM invoice_line_links WHERE  task_activity_id  = ".$this->id;
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_TaskActivity::isBilled(): Error looking up transaction id: ".$db_conn->getLastError();
			return FALSE;
		}
		
		if($row = $result->fetchRow()){
			if($row[0] > 0)
				return TRUE;
		}
		
		return FALSE;	
	}

	function getQuantity(){
		$quantity = 0;
		if($this->start_ts > 0 && $this->end_ts > 0 && $this->end_ts > $this->start_ts){
			$quantity = round((($this->end_ts - $this->start_ts) / 3600), 2);
		}
		
		return $quantity;
	}
	
	function getCalendarActivities($user_id, $start_ts, $end_ts, $interval){
//		print("getCalendarActivities($user_id, $start_ts, $end_ts, $interval)<BR>\n");
		$intervals = array(
			'hour' => 3600,
			'day'  => 86400,
			'week' => 604800);

		$user_id = intval($user_id);
		if($user_id <= 0){
			$this->error = "SI_TaskActivity::getCalendarActivities($user_id, $start_ts, $end_ts, $interval): Invalid user ID specified!\n";
			return FALSE;
		}

		$start_ts = intval($start_ts);
		$end_ts = intval($end_ts);
		if($start_ts <= 0 || $end_ts <= 0){
			$this->error = "SI_TaskActivity::getCalendarActivities($user_id, $start_ts, $end_ts, $interval): Invalid start or end timestamps!\n";
			return FALSE;
		}

		if(!isset($intervals[$interval])){
			$this->error = "SI_TaskActivity::getCalendarActivities($user_id, $start_ts, $end_ts, $interval): Unknown interval specified!\n";
			return FALSE;
		}
		$interval_ts = $intervals[$interval];

		$sql = "WHERE a.user_id = $user_id AND (a.end_ts - a.start_ts) > 0 AND (a.start_ts BETWEEN $start_ts AND $end_ts OR a.end_ts BETWEEN $start_ts AND $end_ts) ORDER BY a.start_ts";
		
		$activities = $this->retrieveSet($sql);
		if($activities === FALSE){
			return FALSE;
		}

		$task_array = array();
		$current_key = $start_ts;
		$interval = $interval_ts;
		if(count($activities) > 0){
			foreach($activities as  $ta){
				if($ta->start_ts == 0) continue;
				if($ta->end_ts == 0) $ta->end_ts == time();				
//				print("Current interval is ".date('Y-m-d H:i', $current_key)." ($current_key) interval end is ".date('Y-m-d H:i', ($current_key + $interval))."(".($current_key + $interval).")<BR>\n");

				// Move to the next interval
				while($ta->start_ts > ($current_key + $interval_ts)){
					$current_key = $current_key+$interval_ts;
					$task_array[$current_key] = array();
//					print("Changing interval to ".date('Y-m-d H:i', $current_key)." ($current_key)<BR>\n");
				}
				
				// Determine total time for interval
//				print("TA ID ".$ta->id." Start ".date('Y-m-d H:i', $ta->start_ts)."(".$ta->start_ts.") end ".date('Y-m-d H:i', $ta->end_ts)."(".$ta->end_ts.")<BR>\n");
				if($interval != 3600){		
					if($ta->start_ts < $current_key || $ta->end_ts > ($current_key + $interval)){
						$end_ts = ($ta->end_ts > ($current_key + $interval) ? ($current_key + $interval) : $ta->end_ts);
						$start_ts = ($ta->start_ts < $current_key ? $current_key : $ta->start_ts);
						$ta->total_interval_time = $end_ts - $start_ts;
//						print("Adjusted total ".$ta->total_interval_time."<BR>\n");		
					}else{
						$ta->total_interval_time = $ta->end_ts - $ta->start_ts;
//						print("Total ".$ta->total_interval_time."<BR>\n");		
					}
				}else{
					$ta->total_interval_time = $ta->end_ts - $ta->start_ts;
//					print("Day mode total ".$ta->total_interval_time."<BR>\n");		
				}
				$task_array[$current_key][] = $ta;
			}
		}

		return $task_array;
	}

	function getActivityDetailHTML($id){
		global $loggedin_user;
		
		$ta = new SI_TaskActivity();
		if($ta->get($id) === false){
			return "Error getting detail for $id: ".$this->getLastError();
		}
		$html = '';
		//$html .= print_r($ta, true);
		$html .= '<div class="ad_div">';
		$html .= '<span class="ad_label">Company:</span>&nbsp;'.$ta->company_name.'<br/>';
		$html .= '<span class="ad_label">User:</span>&nbsp;'.$ta->getUserName().'<br/>';
		if($loggedin_user->hasRight('accounting')){
			$html .= '<span class="ad_label">Hourly Cost:</span>&nbsp;'.$ta->hourly_cost.'<br/>';
			$html .= '<span class="ad_label">Hourly Rate:</span>&nbsp;'.$ta->hourly_rate.'<br/>';
		}
		$html .= '<span class="ad_label">Note:</span><br/>'.nl2br($ta->text).'<br/>';
		$html .= '</div>';
		return $html;
	}
}

