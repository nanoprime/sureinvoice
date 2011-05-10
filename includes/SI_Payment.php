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
class SI_Payment{
	var $id, $company_id, $timestamp, $amount, 
	  $type, $check_no, $auth_code, $trans_id, $updated_ts;

	var $error;

	function SI_Payment(){
		$this->error = '';
		$this->id = 0;
		$this->company_id = 0;
		$this->timestamp = time();
		$this->amount = 0;
		$this->type = '';
		$this->check_no = 0;
		$this->auth_code = '';
		$this->trans_id = 0;
		$this->updated_ts = 0;

		$this->_company = FALSE;
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
		$result = $db_conn->query("INSERT INTO payments (company_id, timestamp, amount, type, ".
		  "check_no, auth_code, trans_id, updated_ts)".
		  " VALUES(".$this->company_id.", ".$this->timestamp.", ".$this->amount.", '".$this->type."', ".
		  "".$this->check_no.", '".$this->auth_code."', ".$this->trans_id.", '".$this->updated_ts."')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_Payment::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_Payment::update() : Payment id not set\n";
			return FALSE;
		}

		$this->updated_ts = time();
		$this->escapeStrings();
		$result = $db_conn->query("UPDATE payments SET company_id = ".$this->company_id.", ".
		  "timestamp = ".$this->timestamp.", amount = ".$this->amount.", ".
		  "type = '".$this->type."', check_no = ".$this->check_no.", ".
		  "auth_code = '".$this->auth_code."', trans_id = ".$this->trans_id.", ".
		  "updated_ts = '".$this->updated_ts."'".
		  " WHERE id = ".$this->id."");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Payment::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Payment::delete() : Payment id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM payments WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Payment::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Payment::get() : Payment id not set\n";
			return FALSE;
		}

		$Payment = SI_Payment::retrieveSet("WHERE id = $id", TRUE);
		if($Payment === FALSE){
			return FALSE;
		}

		if(isset($Payment[0])){
			$this->updateFromAssocArray($Payment[0]);
			$this->stripSlashes();
			if($this->_populateInvoices() === FALSE)
				return FALSE;
		}else{
			$this->error = "SI_Payment::get() : No data retrieved from query\n";
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

		$result = $db_conn->query("SELECT  id, company_id, timestamp, amount, type, check_no, ".
		  "auth_code, trans_id, updated_ts".
		  " FROM payments ".$clause);

		if($result === FALSE){
			$this->error = "SI_Payment::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$Payment[] = $row;
			}else{
				$temp =& new SI_Payment();
				$temp->updateFromAssocArray($row);
				$temp->stripSlashes();
				if($temp->_populateInvoices() === FALSE)
					return FALSE;
				$Payment[] =& $temp;
			}

		}

		return $Payment;
	}
// BEGIN - Custom SI_Payment methods 
////////////////////////////////////////////////////////////
	var $_invoice_ids;

	function _populateInvoices(){
		global $db_conn;

		$this->_invoice_ids = array();
		if($this->id <= 0)
			return TRUE;

		$sql = "SELECT invoice_id,amount FROM payment_invoices WHERE payment_id = {$this->id}";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Payment::_populateInvoices(): Error getting invoice IDs: ".$db_conn->getLastError();
			return FALSE;
		}

		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$this->_invoice_ids[$row['invoice_id']] = $row['amount'];
		}

		return TRUE;
	}

	function onInvoice($invoice_id){
		if(isset($this->_invoice_ids[$invoice_id]))
			return TRUE;

		return FALSE;
	}

	function getAmountForInvoice($invoice_id){
		if(isset($this->_invoice_ids[$invoice_id]))
			return $this->_invoice_ids[$invoice_id];

		return 0.00;
	}

	function getTypeSelectTags($selected = NULL){
		$tags = "";

		$options = array( 'CHECK' => 'CHECK', 'CC' => 'CC', 'CASH' => 'CASH' );

		foreach($options as $value => $name){
			$sel_text = "";
			if($value==$selected)
				$sel_text = " SELECTED";

			$tags .= "<OPTION VALUE=\"".$value."\"".$sel_text.">".$name." ".$row[2]."</OPTION>\n";
		}
		return $tags;
	}

	function attachInvoices($invoices, $append = FALSE){
		global $db_conn;

		if(!$append)
			if($this->clearInvoices() === FALSE)
				return FALSE;

		if(count($invoices) == 0)
			return TRUE;

		$this->id = intval($this->id);
		foreach($invoices as $id => $amount){
			$id = intval($id);
			if($db_conn->query("INSERT INTO payment_invoices SET invoice_id = $id, payment_id = {$this->id}, amount = '$amount'") === FALSE){
				$this->error = "SI_Payment::attachInvoices(): Error adding invoice: ".$db_conn->getLastError();
				return FALSE;
			}
		}

		return TRUE;
	}

	function clearInvoices(){
		global $db_conn;

		$this->id = intval($this->id);
		if($db_conn->query("DELETE FROM payment_invoices WHERE payment_id = {$this->id}") === FALSE){
			$this->error = "SI_Payment::clearInvoices(): Error removing invoices: ".$db_conn->getLastError();
			return FALSE;
		}

		return TRUE;
	}

	function getForInvoice($invoice_id){
		global $db_conn;

		$result = $db_conn->query("SELECT p.id, p.company_id, p.timestamp, p.amount, p.type, p.check_no, ".
		  "p.auth_code, p.trans_id".
		  " FROM payments AS p
			LEFT JOIN payment_invoices AS pi ON pi.payment_id = p.id
			WHERE pi.invoice_id = '$invoice_id'");

		if($result === FALSE){
			$this->error = "SI_Payment::getForInvoice(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_Payment();
			$temp->updateFromAssocArray($row);
			$temp->stripSlashes();
			if($temp->_populateInvoices() === FALSE)
				return FALSE;
			$Payment[] =& $temp;
		}

		return $Payment;
	}

	function getCompany(){
		if(!$this->_company && $this->company_id > 0){
			$this->_company = new SI_Company();
			if($this->_company->get($this->company_id) === FALSE){
				$this->error = "Error retreiving company information: ".$this->_company->getLastError();
				return FALSE;
			}
		}
		
		return $this->_company;	
	}
	  	
	function exportQB($clause = ''){
		$payments = $this->retrieveSet($clause);
		
		if($payments === FALSE){
			return FALSE;
		}
		
		$app_config = SI_Config::getAppConfig();
		$rec_account = new SI_Account();
		$rec_account->get($app_config['account_rec']);
		$payment_account = new SI_Account();
		$payment_account->get($app_config['account_payment']);
		
		$exporter = new QBExporter();
		foreach($payments as $payment){
			$company =& $payment->getCompany();
			if($company != FALSE){
				$payment_data = array();
				$payment_data['NAME'] = $company->name;
				$payment_data['TRNSID'] = $payment->id;
				$payment_data['ACCNT'] = $payment_account->name;
				$payment_data['DOCNUM'] = $payment->id;
				$payment_data['DATE'] = date("n/j/Y", $payment->timestamp);
				$payment_data['TRNSTYPE'] = 'PAYMENT';
				$payment_data['AMOUNT'] = $payment->amount;
				$payment_data['LINES'] = array();
				$payment_data['LINES'][] = array(
					'TRNSTYPE' => 'PAYMENT',
					'DATE' => $payment_data['DATE'],
					'DOCNUM' => $payment_data['DOCNUM'],
					'NAME' => $payment_data['NAME'],
					'AMOUNT' => ($payment->amount * -1),
					'ACCNT' => $rec_account->name
				);					

				if($exporter->addItem('Payment', $payment_data) === FALSE){
					$this->error = "SI_Payment::export(): Error adding payment {$payment->id}: ".$exporter->getLastError();
					return FALSE;
				}
			}
		}
		
		return $exporter->get_string();
	}

}
