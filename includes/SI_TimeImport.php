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
ini_set('memory_limit', '32M');

define('SI_IMPORT_COLUMN_START', 1);
define('SI_IMPORT_COLUMN_END', 2);
define('SI_IMPORT_COLUMN_DURATION', 3);
define('SI_IMPORT_COLUMN_COMPANY', 4);
define('SI_IMPORT_COLUMN_PROJECT', 5);
define('SI_IMPORT_COLUMN_TASK', 6);
define('SI_IMPORT_COLUMN_USER', 7);
define('SI_IMPORT_COLUMN_COMMENTS', 8);
define('SI_IMPORT_COLUMN_ITEMCODE', 9);

define('SI_IMPORT_ACTION_SKIP', 0);
define('SI_IMPORT_ACTION_MAP', 1);
define('SI_IMPORT_ACTION_CREATE', 2);

class SI_TimeImport{
	
	var $file;
	var $column_mappings;
	var $parsed = false;
		
	// Storage for various data types
	var $users;
	var $tasks;
	var $item_codes;

	function SI_TimeImport($file){
		
	}
	
	function hasFile(){
		return !empty($this->file);
	}
	
	function hasMappings(){
		return !empty($this->column_mappings);
	}
	
	function normalize($text){
		$text = strtolower($text);
		$text = preg_replace('/[\\\\\s,\.\-\'\]\[\\/"$]/', '', $text);
		return $text;
	}
	
	function parse($force = false){
		if($force) $this->parsed = false;
		
		if($this->parsed) return true;
		
		if(is_readable($this->file)){
			$this->users = array();
			$this->tasks = array();
			$this->item_codes = array();
			$handle = fopen($this->file, "r");
			$first_row = true;
			while(($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
				if($first_row) {
					$first_row = false;
					continue;
				}
				if(isset($data[$this->column_mappings[SI_IMPORT_COLUMN_USER]])){
					$user = $data[$this->column_mappings[SI_IMPORT_COLUMN_USER]];
					$normalized_user = $this->normalize($user);
					if(empty($normalized_user)) $normalized_user = '_blank_';
					if(!isset($this->users[$normalized_user])){
						$this->users[$normalized_user] = array('user' => $user, 'action' => SI_IMPORT_ACTION_SKIP, 'param' => 0);
					}
				}
				if(isset($data[$this->column_mappings[SI_IMPORT_COLUMN_TASK]])){
					$task = $data[$this->column_mappings[SI_IMPORT_COLUMN_TASK]];
					$normalized_task = $this->normalize($task);
					if(empty($normalized_task)) $normalized_task = '_blank_';
					if(!isset($this->tasks[$normalized_task])){
						$this->tasks[$normalized_task] = array('name' => $task, 'action' => SI_IMPORT_ACTION_SKIP, 'param' => 0);
					}
				}
				if(isset($data[$this->column_mappings[SI_IMPORT_COLUMN_ITEMCODE]])){
					$ic = $data[$this->column_mappings[SI_IMPORT_COLUMN_ITEMCODE]];
					$normalized_ic = $this->normalize($ic);
					if(empty($normalized_ic)) $normalized_ic = '_blank_';
					if(!isset($this->item_codes[$normalized_ic])){
						$this->item_codes[$normalized_ic] = array('name' => $ic, 'action' => SI_IMPORT_ACTION_SKIP, 'param' => 0);
					}
				}
			}
			$this->parsed = true;
		}
		

		return true;
	}
	
	function getUsers(){
		if($this->parse()){
			return $this->users;
		}
	}
	
	function getTasks(){
		if($this->parse()){
			return $this->tasks;
		}
	}
	
	function getItemCodes(){
		if($this->parse()){
			return $this->item_codes;
		}
	}

	function addUserMapping($normalizedName, $action, $param = 0){
		if(isset($this->users[$normalizedName])){
			$this->users[$normalizedName]['action'] = $action;
			$this->users[$normalizedName]['param'] = $param;
		}
	}
	
	function addTaskMapping($normalizedName, $action, $param = 0){
		if(isset($this->tasks[$normalizedName])){
			$this->tasks[$normalizedName]['action'] = $action;
			$this->tasks[$normalizedName]['param'] = $param;
		}
	}

	function addItemCodeMapping($normalizedName, $action, $param = 0){
		if(isset($this->item_codese[$normalizedName])){
			$this->item_codes[$normalizedName]['action'] = $action;
			$this->item_codes[$normalizedName]['param'] = $param;
		}
	}

	function clearMappings(){
		$this->column_mappings = array();
	}
	
	function setColumnMapping($index, $column_type){
		if($column_type == 0) return;
		$this->column_mappings[$column_type] = $index;
	}
	
	function getColumnMapping($index){
		foreach ($this->column_mappings as $type => $col_index){
			if($col_index == $index){
				return $type;
			}
		}
		
		return 0;
	}
	
	function processUploadedFile($file_info){
		var_dump($file_info);
		$dest = $GLOBALS['CONFIG']['attachment_dir'].DIRECTORY_SEPARATOR.'time-import.csv';
		if(move_uploaded_file($file_info['tmp_name'], $dest)){
			$this->file = $dest;
		}else{
			fatal_error("Could not move uploaded file {$file_info['tmp_name']} to $dest!");
		}
	}
	
	function getColumnHeaders(){
		if(is_readable($this->file)){
			$handle = fopen($this->file, "r");
			if(($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
				return $data;
			}
		}
	}
	
	function validate(){
		$errors = array();
		if(!isset($this->column_mappings[SI_IMPORT_COLUMN_START])){
			$errors[100] = "You must select a column that contains the start time or date";
		}
		
		if(!isset($this->column_mappings[SI_IMPORT_COLUMN_DURATION]) 
			&& !isset($this->column_mappings[SI_IMPORT_COLUMN_END])){
			$errors[110] = "You must select a column for either the duration or the end time";
		}

		if(!isset($this->column_mappings[SI_IMPORT_COLUMN_TASK])){
			$errors[120] = "You must select a column that contains the task";
		}
		
		if(!isset($this->column_mappings[SI_IMPORT_COLUMN_USER])){
			$errors[130] = "You must select a column that contains the user";
		}

		return $errors;
	}
	
	function guessMappings(){
		$si_user = new SI_User();
		$si_users = $si_user->getAll();
		if($si_users !== false){
			for($i=0; $i<count($si_users); $i++){
				$normalized_name = $this->normalize($si_users[$i]->first_name.' '.$si_users[$i]->last_name);
				if(isset($this->users[$normalized_name])){
					$this->users[$normalized_name]['action'] = SI_IMPORT_ACTION_MAP;
					$this->users[$normalized_name]['param'] = $si_users[$i]->id;
					continue;
				}

				$normalized_name = $this->normalize($si_users[$i]->last_name.' '.$si_users[$i]->first_name);
				if(isset($this->users[$normalized_name])){
					$this->users[$normalized_name]['action'] = SI_IMPORT_ACTION_MAP;
					$this->users[$normalized_name]['param'] = $si_users[$i]->id;
					continue;
				}
			}
		}
		
		$si_task = new SI_Task();
		$si_tasks = $si_task->retrieveSet();
		if($si_tasks !== false){
			for($i=0; $i<count($si_tasks); $i++){
				$normalized_name = $this->normalize($si_tasks[$i]->name);
				if(isset($this->tasks[$normalized_name])){
					$this->tasks[$normalized_name]['action'] = SI_IMPORT_ACTION_MAP;
					$this->tasks[$normalized_name]['param'] = $si_tasks[$i]->id;
					continue;
				}
			}
		}
		
		$si_item_code = new SI_ItemCode();
		$si_item_codes = $si_item_code->retrieveSet();
		if($si_item_codes !== false){
			for($i=0; $i<count($si_item_codes); $i++){
				$normalized_name = $this->normalize($si_item_codes[$i]->code);
				if(isset($this->item_codes[$normalized_name])){
					$this->item_codes[$normalized_name]['action'] = SI_IMPORT_ACTION_MAP;
					$this->item_codes[$normalized_name]['param'] = $si_item_codes[$i]->id;
					continue;
				}

				$normalized_name = $this->normalize($si_item_codes[$i]->description);
				if(isset($this->item_codes[$normalized_name])){
					$this->item_codes[$normalized_name]['action'] = SI_IMPORT_ACTION_MAP;
					$this->item_codes[$normalized_name]['param'] = $si_item_codes[$i]->id;
					continue;
				}

				$normalized_name = $this->normalize($si_item_codes[$i]->code.' - '.$si_item_codes[$i]->description);
				if(isset($this->item_codes[$normalized_name])){
					$this->item_codes[$normalized_name]['action'] = SI_IMPORT_ACTION_MAP;
					$this->item_codes[$normalized_name]['param'] = $si_item_codes[$i]->id;
					continue;
				}
			}
		}
		
	}
	
	function run($preview = true){
		$results = array();
		if(is_readable($this->file)){
			$handle = fopen($this->file, "r");
			$first_row = true;
			while(($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
				if($first_row){
					$first_row = false;
					continue;
				}
				
				// Process a row
				$result = array();
				$result['action'] = 'Import';
				
				$result['start_ts'] = strtotime($data[$this->column_mappings[SI_IMPORT_COLUMN_START]]);
				
				if(isset($this->column_mappings[SI_IMPORT_COLUMN_DURATION])){
					// Duration based import
					$result['end_ts'] = $result['start_ts'] + (floatval($data[$this->column_mappings[SI_IMPORT_COLUMN_DURATION]]) * 60 * 60);
				}else{
					// Start and end time provided
					$result['start_ts'] = strtotime($data[$this->column_mappings[SI_IMPORT_COLUMN_END]]);
				}
				
				if(isset($this->column_mappings[SI_IMPORT_COLUMN_COMMENTS])){
					$result['comments'] = $data[$this->column_mappings[SI_IMPORT_COLUMN_COMMENTS]];
				}else{
					$results['comments'] = '';
				}

				$user = $data[$this->column_mappings[SI_IMPORT_COLUMN_USER]];
				$normalized_user = $this->normalize($user);
				if(empty($normalized_user)) $normalized_user = '_blank_';
				if($this->users[$normalized_user]['action'] == SI_IMPORT_ACTION_SKIP){
					$result['user_id'] = 0;
					$result['message'] = "Skipped because no user map for '$user' was configured";
					$result['action'] = "Skip";
					$results[] = $result;
					continue;
				}else{
					$result['user_id'] = $this->users[$normalized_user]['param'];
				}
				
				$task = $data[$this->column_mappings[SI_IMPORT_COLUMN_TASK]];
				$normalized_task = $this->normalize($task);
				if(empty($normalized_task)) $normalized_task = '_blank_';
				if($this->tasks[$normalized_task]['action'] == SI_IMPORT_ACTION_SKIP){
					$result['task_id'] = 0;
					$result['message'] = "Skipped because no task map for '$task' was configured";
					$result['action'] = "Skip";
					$results[] = $result;
					continue;
				}else{
					$result['task_id'] = $this->tasks[$normalized_task]['param'];
				}
				
				$task = new SI_Task();
				$task->get($result['task_id']);
				
				$ic = $data[$this->column_mappings[SI_IMPORT_COLUMN_ITEMCODE]];
				$normalized_ic = $this->normalize($ic);
				if(empty($normalized_ic)) $normalized_ic = '_blank_';
				if($this->item_codes[$normalized_ic]['action'] == SI_IMPORT_ACTION_SKIP){
					$result['item_code_id'] = $task->getDefaultItemCode();
					if($result['item_code_id'] == 0){
						$result['message'] = "Skipped because no item code map for '$ic' was configured and no default item code exists for project";
						$result['action'] = "Skip";
						$results[] = $result;
						continue;
					}else{
						$result['message'] = "Item Code retreived from project";
					}
				}else{
					$result['item_code_id'] = $this->item_codes[$normalized_ic]['param'];
				}

				if($result['start_ts'] <= 0 || $result['end_ts'] <= 0){
					$result['message'] = "Invalid start or end time";
					$result['action'] = "Skip";
					$results[] = $result;
					continue;
					
				}

				if($result['start_ts'] > $result['end_ts']){
					$result['message'] = "Start Time is before end time";
					$result['action'] = "Skip";
					$results[] = $result;
					continue;
					
				}
				
				if(($result['end_ts'] - $result['start_ts']) > (12 * 60 * 60)){
					$result['message'] = "Length of time is too long, >12 hours";
					$result['action'] = "Skip";
					$results[] = $result;
					continue;
					
				}

				$project = new SI_Project();
				$company = new SI_Company();
				$task = new SI_Task();
				$item_code = new SI_ItemCode();
				$task_activity = new SI_TaskActivity();
				$task_activity->start_ts = $result['start_ts'];
				$task_activity->end_ts = $result['end_ts'];
				$task_activity->task_id = $result['task_id'];
				$task_activity->user_id = $result['user_id'];
				$task_activity->text = $result['comments'];
				$task_activity->item_code_id = $result['item_code_id'];
		
				if(($task_activity->task_id > 0 || $task_activity->start_ts > 0 || $task_activity->end_ts > 0)){
					if(($task_activity->task_id <= 0 || $task_activity->start_ts <= 0 || $task_activity->end_ts <= 0)){
						$result['action'] =  "Skip";
						$result['message'] = "Skipping incomplete entry\n";
						$results[] = $result;
						continue;
					}
				}else{
					$result['action'] =  "Skip";
					$result['message'] = "Skipping incomplete entry\n";
					$results[] = $result;
					continue;
				}
		
				if($task->get($task_activity->task_id) === FALSE){
					$result['action'] =  "Skip";
					$result['message'] = "Could not retreive task:\n".$task->getLastError();
					$results[] = $result;
					continue;
				}
				
				if($project->get($task->project_id) === FALSE){
					$result['action'] =  "Skip";
					$result['message'] = "Could not retreive project:\n".$project->getLastError();
					$results[] = $result;
					continue;
				}

				$user = new SI_User();
				if($user->get($task_activity->user_id) === FALSE){
					$result['action'] =  "Skip";
					$result['message'] = "Could not retreive user:\n".$user->getLastError();
					$results[] = $result;
					continue;					
				}
				
				$task_activity->hourly_cost = $user->hourly_rate;
				$company = $project->getCompany();
				if($company === FALSE){
					$result['action'] =  "Skip";
					$result['message'] = "Could not get company information:\n".$project->getLastError();
					$results[] = $result;
					continue;					
				}

				$task_activity->hourly_rate = $item_code->getCompanyPrice($company->id, $task_activity->item_code_id);
				if($task_activity->hourly_rate === FALSE){
					$result['action'] =  "Skip";
					$result['message'] = "Error getting price for this item code:\n".$item_code->getLastError();
					$results[] = $result;
					continue;					
				}
				$sct = $task->getSalesCommissionType();
				$task_activity->sales_com_type_id = $sct->id;
			
				if(!$preview){
					if(!$task_activity->add()){
						$result['action'] =  "Skip";
						$result['message'] = "Error adding Task Activity:\n".$task_activity->getLastError();
					}
					
				}
				$results[] = $result;
			}
		}
		
		return $results;
	}
}