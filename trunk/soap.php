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

$base_path = realpath(dirname(__FILE__));
require_once($base_path.'/includes/common.php');
require_once($base_path.'/includes/SI_Company.php');
require_once($base_path.'/includes/SI_User.php');
require_once($base_path.'/includes/SI_Project.php');
require_once($base_path.'/includes/SI_Task.php');
require_once($base_path.'/includes/nusoap/nusoap.php');

# create server
$debug = false;
$l_oServer = new soap_server();

# namespace
$t_namespace = 'http://uversainc.com/sureinvoice/';

# wsdl generation
$l_oServer->debug_flag = false;
$l_oServer->configureWSDL( 'SureInvoice', $t_namespace );
$l_oServer->wsdl->schemaTargetNamespace = $t_namespace;


# Setup types

### TaskActivity
$l_oServer->wsdl->addComplexType(
	'TaskActivity',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id'				=>	array( 'name' => 'id',				'type' => 'xsd:integer'),
		'task_id'			=>	array( 'name' => 'task_id',			'type' => 'xsd:integer'),
		'start_ts'			=>	array( 'name' => 'start_ts',		'type' => 'xsd:integer'),
		'end_ts'			=>	array( 'name' => 'end_ts',			'type' => 'xsd:integer'),
		'user_id'			=>	array( 'name' => 'user_id',			'type' => 'xsd:integer'),
		'item_code_id'		=>	array( 'name' => 'item_code_id',	'type' => 'xsd:integer'),
		'text'				=>	array( 'name' => 'text',			'type' => 'xsd:string')
	)
);

### Company
$l_oServer->wsdl->addComplexType(
	'Company',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id'				=>	array( 'name' => 'id',				'type' => 'xsd:integer'),
		'name'				=>	array( 'name' => 'name',			'type' => 'xsd:string')
	)
);

### CompanyArray
$l_oServer->wsdl->addComplexType(
	'CompanyArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array(
		'ref'				=> 'SOAP-ENC:arrayType',
		'wsdl:arrayType'	=> 'tns:Company[]'
	)),
	'tns:Company'
);


### Project
$l_oServer->wsdl->addComplexType(
	'Project',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id'				=>	array( 'name' => 'id',				'type' => 'xsd:integer'),
		'company_id'		=>	array( 'name' => 'company_id',		'type' => 'xsd:integer'),
		'default_item_code_id'	=>	array( 'name' => 'default_item_code_id',		'type' => 'xsd:integer'),
		'name'				=>	array( 'name' => 'name',			'type' => 'xsd:string')
	)
);

### ProjectArray
$l_oServer->wsdl->addComplexType(
	'ProjectArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array(
		'ref'				=> 'SOAP-ENC:arrayType',
		'wsdl:arrayType'	=> 'tns:Project[]'
	)),
	'tns:Project'
);

### Task
$l_oServer->wsdl->addComplexType(
	'Task',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id'				=>	array( 'name' => 'id',				'type' => 'xsd:integer'),
		'project_id'		=>	array( 'name' => 'project_id',		'type' => 'xsd:integer'),
		'name'				=>	array( 'name' => 'name',			'type' => 'xsd:string')
	)
);

### TaskArray
$l_oServer->wsdl->addComplexType(
	'TaskArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array(
		'ref'				=> 'SOAP-ENC:arrayType',
		'wsdl:arrayType'	=> 'tns:Task[]'
	)),
	'tns:Task'
);

### ItemCode
$l_oServer->wsdl->addComplexType(
	'ItemCode',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id'				=>	array( 'name' => 'id',				'type' => 'xsd:integer'),
		'code'		        =>	array( 'name' => 'code',		    'type' => 'xsd:string'),
		'description'		=>	array( 'name' => 'description',	    'type' => 'xsd:string')
	)
);

### ItemCodeArray
$l_oServer->wsdl->addComplexType(
	'ItemCodeArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array(
		'ref'				=> 'SOAP-ENC:arrayType',
		'wsdl:arrayType'	=> 'tns:ItemCode[]'
	)),
	'tns:ItemCode'
);

function auth_user($username, $password){
	return SI_User::getUserByLogin($username, md5($password));
}

function object_to_data($objects, $fields){
	$return = FALSE;

	if(is_array($objects)){
		$return = array();
		$counter = 0;
		foreach($objects as $object){
			foreach($fields as $field){
				$return[$counter][$field] = $object->$field;
			}
			$counter++;			
		}
	}else{
		$return = array();
		foreach($fields as $field){
			$return[$field] = $object->$field;
		}			
	}
	
	return $return;
}

function soap_add_task_activity($username, $password, $task_activity){
	global $loggedin_user;
	
	$user = auth_user($username, $password);
	if($user === FALSE){
		return new soap_fault( 'Client', '', 'Access Denied' );
	}
	$loggedin_user = $user;

	$task = new SI_Task();
	$project = new SI_Project();
	$item_code = new SI_ItemCode();

	$ta = new SI_TaskActivity();
	$ta->start_ts = $task_activity['start_ts'];
	$ta->end_ts = $task_activity['end_ts'];
	$ta->task_id = $task_activity['task_id'];
	$ta->user_id = $loggedin_user->id;
	$ta->text = $task_activity['text'];
	$ta->item_code_id = $task_activity['item_code_id'];

	if(($ta->task_id > 0 || $ta->start_ts > 0 || $ta->end_ts > 0)){
		if(($ta->task_id <= 0 || $ta->start_ts <= 0 || $ta->end_ts <= 0)){
			return new soap_fault('Client', '', 'Invalid data fields in task_activity');
		}
	}

	if($task->get($ta->task_id) === FALSE){
		return new soap_fault('Client', '', 'Could not retreive task ID '.$ta->task_id, $task->getLastError());
	}
	if($project->get($task->project_id) === FALSE){
		return new soap_fault('Client', '', 'Could not retreive project!', $project->getLastError());
	}
	if(!$project->hasRights(PROJECT_RIGHT_EDIT)){
		return new soap_fault('Client', '', 'Insufficent access rights for this project!');
	}

	$ta->hourly_cost = $loggedin_user->hourly_rate;
	$company = $project->getCompany();
	if($company === FALSE){
		return new soap_fault('Client', '', 'Could not get company information!', $project->getLastError());
	}
	$ta->hourly_rate = $item_code->getCompanyPrice($company->id, $ta->item_code_id);
	if($ta->hourly_rate === FALSE){
		return new soap_fault('Client', '', 'Error getting price for this item code!', $item_code->getLastError());
	}
	$sct = $task->getSalesCommissionType();
	$ta->sales_com_type_id = $sct->id;

	if($ta->add()){
		$project->sendUpdateNotification(array("Added new task activity ".$GLOBALS['CONFIG']['url'].'/task_activity.php?mode=edit&id='.$task_activity->id));
	}else{
		return new soap_fault('Client', '', 'Error adding Task Activity!', $ta->getLastError());
	}
	
	return $ta->id;	
}

function soap_get_companies($username, $password){

	$user = auth_user($username, $password);
	if($user === FALSE){
		return new soap_fault( 'Client', '', 'Access Denied' );
	}	
	
	$company = new SI_Company();
	$companies = $company->retrieveSet("WHERE deleted = 'N' ORDER BY name");
	
	return object_to_data($companies, array('id', 'name'));
}

function soap_get_projects($username, $password){

	$user = auth_user($username, $password);
	if($user === FALSE){
		return new soap_fault( 'Client', '', 'Access Denied' );
	}	
	
	$project = new SI_Project();
	$projects = $project->getMyProjects($user->id);
	
	return object_to_data($projects, array('id', 'company_id', 'name', 'default_item_code_id'));
}

function soap_get_tasks($username, $password){

	$user = auth_user($username, $password);
	if($user === FALSE){
		return new soap_fault( 'Client', '', 'Access Denied' );
	}	
	
	$task = new SI_Task();
	$tasks = $task->getTasks($user);
	if($tasks == FALSE){
		return new soap_fault( 'Server', '', $task->getLastError());
	}
	
	return object_to_data($tasks, array('id', 'project_id', 'name'));
}

function soap_get_item_codes($username, $password){

	$user = auth_user($username, $password);
	if($user === FALSE){
		return new soap_fault( 'Client', '', 'Access Denied' );
	}	
	
	$ic = new SI_ItemCode();
	$ics = $ic->retrieveSet(" ORDER BY code ");
	
	return object_to_data($ics, array('id', 'code', 'description'));
}

### soap_get_companies
$l_oServer->register( 'soap_get_companies',
	array(
		'username'		=>	'xsd:string',
		'password'		=>	'xsd:string'
	),
	array(
		'return'	=>	'tns:CompanyArray'
	),
	$t_namespace,
	false, false, false,
	'Get a list of companies.'
);

### soap_get_projects
$l_oServer->register( 'soap_get_projects',
	array(
		'username'		=>	'xsd:string',
		'password'		=>	'xsd:string'
	),
	array(
		'return'	=>	'tns:ProjectArray'
	),
	$t_namespace,
	false, false, false,
	'Get a list of projects.'
);

### soap_get_tasks
$l_oServer->register( 'soap_get_tasks',
	array(
		'username'		=>	'xsd:string',
		'password'		=>	'xsd:string'
	),
	array(
		'return'	=>	'tns:TaskArray'
	),
	$t_namespace,
	false, false, false,
	'Get a list of tasks.'
);

### soap_get_tasks
$l_oServer->register( 'soap_get_item_codes',
	array(
		'username'		=>	'xsd:string',
		'password'		=>	'xsd:string'
	),
	array(
		'return'	=>	'tns:ItemCodeArray'
	),
	$t_namespace,
	false, false, false,
	'Get a list of item codes.'
);

### soap_get_tasks
$l_oServer->register( 'soap_add_task_activity',
	array(
		'username'		=>	'xsd:string',
		'password'		=>	'xsd:string',
		'task_activity'	=>	'tns:TaskActivity',
	),
	array(
		'return'	=>	'xsd:integer'
	),
	$t_namespace,
	false, false, false,
	'Add a task activity.'
);

# pass incoming (posted) data
if ( isset( $HTTP_RAW_POST_DATA ) ) {
	$t_input = $HTTP_RAW_POST_DATA;
}elseif(isset($_SERVER['QUERY_STRING'])){
	$t_input = $_SERVER['QUERY_STRING'];
} else {
	$t_input = implode( "\r\n", file( 'php://input' ) );
}

# Execute whatever is requested from the webservice.
$l_oServer->service( $t_input );

exit;

?>
