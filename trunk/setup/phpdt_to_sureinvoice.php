<?php
/*
 * Created on Apr 13, 2005
 *
 * Migration script from phpdt to sureinvoice
 */
$include_path = dirname(__FILE__).'/../includes/';
require_once $include_path.'common.php';
require_once $include_path.'DBConn.php';
require_once $include_path.'SI_Company.php';
require_once $include_path.'SI_TaskActivity.php';
require_once $include_path.'SI_Invoice.php';
require_once $include_path.'SI_Check.php';

// Parameters
$debug = true;
$phpdt_server='localhost';
$phpdt_database='phpdt';
$phpdt_username='root';
$phpdt_password='';
$GLOBALS['phpdt_import'] = true;

function debug($message){
	global $debug;
	
	if($debug)
		print($message."\n");
		
}

function report_error($message){
	die($message."\n");	
}
function assert_die($param, $message, $strict = true){
	if($strict){
		if($param === FALSE){
			report_error($message);
		}
	}else{
		if(!$param){
			report_error($message);
		}
	}
}

function add_activities($offset, $limit){
	global $dt_db, $si_db, $company_rates, $user_rates, $invoices, $checks;
	
	debug("add_activities($limit, offset)");
	$task_activity_sql = "
	SELECT ta.id, ta.task_id, ta.user_id, ta.text, ta.start_ts, ta.end_ts, ta.invoice, ta.check, p.company_id 
	FROM `task_activities` AS ta
	LEFT JOIN tasks AS t ON t.id = ta.task_id
	LEFT JOIN projects AS p ON p.id = t.project_id
	LIMIT $offset, $limit
	";
	
	$task_activity_result = $dt_db->query($task_activity_sql, TRUE);
	assert_die($task_activity_result, "Could not get task activities!\n".$dt_db->getLastError());
	debug('Got '.$task_activity_result->numRows().' task activity rows from phpdt.');
	while($row = $task_activity_result->fetchArray(MYSQL_ASSOC)){
		$ta = new SI_TaskActivity();
		$ta->task_id = $row['task_id'];
		$ta->user_id = $row['user_id'];
		$ta->text = $row['text'];
		$ta->start_ts = $row['start_ts'];
		$ta->end_ts = $row['end_ts'];
		$ta->hourly_rate = $company_rates[$row['company_id']];
		$ta->hourly_cost = $user_rates[$row['user_id']];
		// Special exception for david
		if($row['user_id'] == 10 && $row['end_ts'] < mktime(0,0,0,1,1,2005)){
			$ta->getUser();
			$ta->_user->rate_type = 'HOURLY';	
		}
		$GLOBALS['phpdt_cost_ts'] = $row['end_ts'];
		assert_die($ta->add(), "Error adding task activity!\n".$ta->getLastError());
		$invoices[$row['invoice']]['company_id'] = $row['company_id']; 
		if($ta->end_ts > $invoices[$row['invoice']]['timestamp']) $invoices[$row['invoice']]['timestamp'] = $ta->end_ts; 
		$invoices[$row['invoice']]['ids'][] = $ta->id; 
		$checks[$row['check']]['user_id'] = $row['user_id'];
		$checks[$row['check']]['ids'][] = $ta->cost_trans_id;
		if($ta->end_ts > $checks[$row['check']]['timestamp']) $checks[$row['check']]['timestamp'] = $ta->end_ts;
		$checks[$row['check']]['amount'] += $ta->cost; 
	}
	$task_activity_result->free();
	debug('Added task activities to sureinvoice!');
}

$si_db = new DBConn(DB_SERVER, DB_DATABASE, DB_USER, DB_PASSWORD);
assert_die($si_db->connect(TRUE), "Could not connect to sureinvoice database!\n".$si_db->getLastError());
debug('Connected to sureinvoice database '.DB_DATABASE);

$dt_db = new DBConn($phpdt_server, $phpdt_database, $phpdt_username, $phpdt_password);
assert_die($dt_db->connect(TRUE), "Could not connect to phpdt database!\n".$dt_db->getLastError());
debug('Connected to phpdt database '.$phpdt_database);

// Migrate companies
$companies = array();
$company_rates = array();
$company_sql = "
SELECT `id`, `name`, `address1`, `address2`, `city`, `state`,
`zip`, `phone`, `fax`, `hourly_rate`, `created_ts`, `updated_ts`
FROM `companies`
";
$company_result = $dt_db->query($company_sql, TRUE);
assert_die($company_result, "Could not get company list!\n".$dt_db->getLastError());
debug('Got '.$company_result->numRows().' company rows from phpdt.');
while($row = $company_result->fetchArray(MYSQL_ASSOC)){
	$company_insert_sql = "
INSERT INTO companies (id, name, address1, address2, city,
state, zip, phone, fax, hourly_rate,created_ts, updated_ts)
VALUES(".$row['id'].", '".$si_db->escapeString($row['name'])."', '".$row['address1']."', 
'".$row['address2']."', '".$row['city']."', '".$row['state']."', 
'".$row['zip']."', '".$row['phone']."', '".$row['fax']."', 
".$row['hourly_rate'].", ".$row['created_ts'].", ".$row['updated_ts'].")
	";
	assert_die($si_db->query($company_insert_sql, TRUE), "Error adding company!\n".$si_db->getLastError(), FALSE);
	debug('Added '.$row['name'].' to sureinvoice');
	$company_rates[$row['id']] = $row['hourly_rate'];
	$companies[$row['id']] = $row;
}
$company_result->free();

// Migrate users
$users = array();
$user_sql = "
SELECT `id`, `user_type_id`, `first_name`, `last_name`, `company_id`, 
`address1`, `address2`, `city`, `state`, `zip`, `email`, `password`, 
`hourly_rate`, `rate_type`, `active`, `created_ts`, `updated_ts`, 
`last_login_ts` 
FROM `users`";
$user_result = $dt_db->query($user_sql, TRUE);
assert_die($user_result, "Could not get user list!\n".$dt_db->getLastError());
debug('Got '.$user_result->numRows().' user rows from phpdt.');
while($row = $user_result->fetchArray(MYSQL_ASSOC)){
	$user_insert_sql = "
INSERT INTO users (id, user_type_id, first_name, last_name, company_id, 
address1, address2, city, state, zip, email, password, hourly_rate, 
rate_type, active, created_ts, updated_ts, last_login_ts)
VALUES(".$row['id'].",".$row['user_type_id'].", '".$si_db->escapeString($row['first_name'])."', 
'".$si_db->escapeString($row['last_name'])."', ".$row['company_id'].", 
'".$si_db->escapeString($row['address1'])."', '".$si_db->escapeString($row['address2'])."', 
'".$si_db->escapeString($row['city'])."', '".$si_db->escapeString($row['state'])."', 
'".$si_db->escapeString($row['zip'])."', '".$si_db->escapeString($row['email'])."', 
'".$row['password']."', '".$row['hourly_rate']."', '".$row['rate_type']."', '".$row['active']."', 
".$row['created_ts'].", ".$row['updated_ts'].", ".$row['last_login_ts'].")
	";
	assert_die($si_db->query($user_insert_sql, TRUE), "Error adding user!\n".$si_db->getLastError(), FALSE);
	$user_rates[$row['id']] = $row['hourly_rate'];
	debug('Added user '.$row['first_name'].' '.$row['last_name'].' to sureinvoice');
	$users[$row['id']] = $row;
}
$user_result->free();

// Migrate Projects
$project_sql = "
SELECT `id`, `owner_id`, `company_id`, `project_status_id`, `project_priority_id`, 
`name`, `description`, `due_ts`, `billable`, `issue_tracking`, `created_ts`, `updated_ts`, 
`sales_com`, `sales_com_type_id`, `sales_com_user_id` 
FROM `projects`
";

$project_result = $dt_db->query($project_sql, TRUE);
assert_die($project_result, "Could not get projects!\n".$dt_db->getLastError());
debug('Got '.$project_result->numRows().' project rows from phpdt.');
while($row = $project_result->fetchArray(MYSQL_ASSOC)){
	$project_insert_sql = "
INSERT INTO projects (id, owner_id, company_id, project_status_id, project_priority_id, 
name, description, due_ts, billable, 
issue_tracking, created_ts,  updated_ts, sales_com, 
sales_com_type_id, sales_com_user_id)
VALUES(".$row['id'].", ".$row['owner_id'].", ".$row['company_id'].", ".$row['project_status_id'].",
".$row['project_priority_id'].", '".$si_db->escapeString($row['name'])."', 
'".$si_db->escapeString($row['description'])."', ".$row['due_ts'].", 
'".$row['billable']."', '".$row['issue_tracking']."', ".$row['created_ts'].", 
".$row['updated_ts'].", 'N', 0, 0)	
";
	assert_die($si_db->query($project_insert_sql, TRUE), "Error adding project!\n".$si_db->getLastError(), FALSE);
	debug('Added project '.$row['name'].' to sureinvoice');
}
$project_result->free();

// Migrate Project Rights
$project_rights_sql = "
SELECT `user_id`, `project_id`, `level` FROM `user_project_rights`
";

$project_rights_result = $dt_db->query($project_rights_sql, TRUE);
assert_die($project_rights_result, "Could not get project rights!\n".$dt_db->getLastError());
debug('Got '.$project_rights_result->numRows().' project right rows from phpdt.');
while($row = $project_rights_result->fetchArray(MYSQL_ASSOC)){
	$project_right_insert_sql = "
INSERT INTO `user_project_rights` (`user_id`, `project_id`, `level`) 
VALUES ('".$row['user_id']."', '".$row['project_id']."', '".$row['level']."')
";
	assert_die($si_db->query($project_right_insert_sql, TRUE), "Error adding project right!\n".$si_db->getLastError(), FALSE);
	//debug('Added project right '.$row['name'].' to sureinvoice');
}
debug('Added all project right rows.');
$project_rights_result->free();

// Migrate Project CCs
$project_ccs_sql = "
SELECT `project_id`, `user_id` FROM `project_cc`
";

$project_ccs_result = $dt_db->query($project_ccs_sql, TRUE);
assert_die($project_ccs_result, "Could not get project CCs!\n".$dt_db->getLastError());
debug('Got '.$project_ccs_result->numRows().' project CC rows from phpdt.');
while($row = $project_ccs_result->fetchArray(MYSQL_ASSOC)){
	$project_cc_insert_sql = "
INSERT INTO `project_cc` (`user_id`, `project_id`) 
VALUES ('".$row['user_id']."', '".$row['project_id']."')
";
	assert_die($si_db->query($project_cc_insert_sql, TRUE), "Error adding project CC!\n".$si_db->getLastError(), FALSE);
	//debug('Added project right '.$row['name'].' to sureinvoice');
}
debug('Added all project CC rows.');
$project_ccs_result->free();

// Migrate Tasks
$task_sql = "
SELECT `id`, `project_id`, `name`, `description`, `task_status_id`, 
`task_priority_id`, `due_ts`, `type`, `billable`, `created_ts`, `updated_ts`, 
`sales_com`, `sales_com_type_id`, `sales_com_user_id` FROM `tasks`
";

$task_result = $dt_db->query($task_sql, TRUE);
assert_die($task_result, "Could not get tasks!\n".$dt_db->getLastError());
debug('Got '.$task_result->numRows().' task rows from phpdt.');
while($row = $task_result->fetchArray(MYSQL_ASSOC)){
	$task_insert_sql = "
INSERT INTO tasks (id, project_id, name, description, task_status_id, 
task_priority_id, due_ts, type, billable, 
created_ts, updated_ts, sales_com, sales_com_type_id, 
sales_com_user_id, amount)
VALUES(".$row['id'].", ".$row['project_id'].", '".$si_db->escapeString($row['name'])."',
 '".$si_db->escapeString($row['description'])."', ".$row['task_status_id'].", 
".$row['task_priority_id'].", ".$row['due_ts'].", '".$row['type']."', '".$row['billable']."', 
".$row['created_ts'].", ".$row['updated_ts'].", '".$row['sales_com']."', ".$row['sales_com_type_id'].", 
".$row['sales_com_user_id'].", 0.00)
";
	assert_die($si_db->query($task_insert_sql, TRUE), "Error adding task!\n".$si_db->getLastError(), FALSE);
	debug('Added task '.$row['name'].' to sureinvoice');
}
$task_result->free();

// Migrate task items
$task_item_sql = "
SELECT `id`, `task_id`, `item`, `task_activity_id`, `parent_id`, `order_number` FROM `task_items`
";

$task_item_result = $dt_db->query($task_item_sql, TRUE);
assert_die($task_item_result, "Could not get task items!\n".$dt_db->getLastError());
debug('Got '.$task_item_result->numRows().' task item rows from phpdt.');
while($row = $task_item_result->fetchArray(MYSQL_ASSOC)){
	$task_item_insert_sql = "
INSERT INTO task_items (id, task_id, item, task_activity_id, parent_id, order_number)
VALUES(".$row['id'].", ".$row['task_id'].", '".$si_db->escapeString($row['item'])."', 
".$row['task_activity_id'].", ".$row['parent_id'].", ".$row['order_number'].")
";
	assert_die($si_db->query($task_item_insert_sql, TRUE), "Error adding task item!\n".$si_db->getLastError(), FALSE);
	debug('Added task item '.$row['item'].' to sureinvoice');
}
$task_item_result->free();

// Migrate task activities
$invoices = array();
$checks = array();
$ta_count = 0;
$task_activity_count_sql = "
SELECT count(ta.id) FROM `task_activities` AS ta
";

$task_activity_count_result = $dt_db->query($task_activity_count_sql, TRUE);
assert_die($task_activity_count_result, "Could not get task activities!\n".$dt_db->getLastError());
if($row = $task_activity_count_result->fetchRow()){
	$ta_count = $row[0];
}
$task_activity_count_result->free();

for($i=0; $i<$ta_count; $i += 50){
	add_activities($i, 50);
}
// Create invoices
$invoice = new SI_Invoice();
foreach($invoices as $number => $data){
	if($number == 0)
		continue;
		
//	debug("Going to create invoice $number for company id ".$data['company_id']." with ids ".join(', ', $data['ids']));
	$company =& $companies[$data['company_id']];
	$invoice_insert_sql = "
INSERT INTO invoices (id, company_id, address1, address2, city, 
state, zip, timestamp, terms, trans_id)
VALUES(".$number.", ".$data['company_id'].", '".$company['address1']."', '".$company['address2']."', '".$company['city']."', 
'".$company['state']."', '".$company['zip']."', ".$data['timestamp'].", 'NET15', 0)
	";
	$invoice_result = $si_db->query($invoice_insert_sql, TRUE);
	assert_die($invoice_result, "Could not add invoice!\n".$si_db->getLastError());
	
	// Add activities
	$invoice->get($number);
//	debug("Adding line items to invoice ".$invoice->id);
	assert_die($invoice->addTaskActivities($data['ids']), "Error adding task activities to invoice: ".$invoice->getLastError());
	
	// Add the company transaction
	$ct = new SI_CompanyTransaction();
	$ct->amount = $invoice->getTotal();
	$ct->company_id = $invoice->company_id;
	$ct->description = "Invoice #".$invoice->id;
	$ct->timestamp = $invoice->timestamp;
	assert_die($ct->add(), "Error adding company transaction: " . $ct->getLastError());

}

// Create checks
$check = new SI_Check();
foreach($checks as $number => $data){
	if($number == 0)
		continue;
		
//	debug("Going to create check $number for amount ".$data['amount']." with ids ".join(', ', $data['ids']));
	$check->user_id = $data['user_id'];
	$check->number = $number;
	$check->timestamp = $data['timestamp'];
	$check->amount = $data['amount'];
	$check->type = 'CHECK';
	$check->name = $users[$data['user_id']]['first_name'].' '.$users[$data['user_id']]['last_name'];
	$check->address1 = $users[$data['user_id']]['address1'];
	$check->address2 = $users[$data['user_id']]['address2'];
	$check->city = $users[$data['user_id']]['city'];
	$check->state = $users[$data['user_id']]['state'];
	$check->zip = $users[$data['user_id']]['zip'];
	assert_die($check->add(), "Error adding check: ".$check->getLastError());
	assert_die($check->attachCostTransactions($data['ids']), "Error adding cost transactions to check: ".$check->getLastError());

	// Add user transaction
	$ut = new SI_UserTransaction();
	$ut->amount = -$check->amount;
	$ut->description = "Check #".$check->number;
	$ut->timestamp = $check->timestamp;
	$ut->user_id = $check->user_id;
	assert_die($ut->add(), "Error adding user transaction: ".$ut->getLastError());
	
	// Update check trans id
	$check->trans_id = $ut->id;
	assert_die($check->update(), "Error updating transaction id for check: ".$check->getLastError());
}

?>
