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

require_once('SI_Project.php');
require_once('SI_TaskItem.php');
require_once('SI_TaskStatus.php');
require_once('SI_TaskPriority.php');
require_once('SI_Attachment.php');
require_once('SI_SalesCommissionType.php');

// SI_Task Class Definition
////////////////////////////////////////////////////////////
class SI_Task{
	var $id, $project_id, $name, $description, 
		$task_status_id, $task_priority_id, $due_ts, $type,
		$billable, $created_ts, $updated_ts, $sales_com, 
		$sales_com_type_id, $sales_com_user_id, $amount,
		$status, $priority, $project, $deleted;

	var $error;

	var $_project;

	var $_sct;

	var $_sct_user;

	var $items;

	var $_total_cost;

	function SI_Task(){
		$this->error = '';
		$this->id = 0;
		$this->project_id = 0;
		$this->name = '';
		$this->description = '';
		$this->task_status_id = 0;
		$this->task_priority_id = 0;
		$this->due_ts = 0;
		$this->type = 'FREEFORM';
		$this->billable = 'D';
		$this->created_ts = 0;
		$this->updated_ts = 0;
		$this->sales_com = 'D';
		$this->sales_com_type_id = 0;
		$this->sales_com_user_id = 0;
		$this->amount = 0.00;
		$this->deleted = 'N';

		$this->status = '';
		$this->priority = '';
		$this->project = '';

		$this->items = NULL;
		$this->_project = FALSE;
		$this->_sct_user = FALSE;
		$this->_sct = FALSE;
		$this->_total_cost = FALSE;

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

		if($this->_updateCommissions() === FALSE)
			return FALSE;

		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO tasks (project_id, name, description, task_status_id, ".
		  "task_priority_id, due_ts, type, billable, ".
		  "created_ts, updated_ts, sales_com, sales_com_type_id, ".
		  "sales_com_user_id, amount, deleted)".
		  " VALUES(".$this->project_id.", '".$this->name."', '".$this->description."', ".$this->task_status_id.", ".
		  "".$this->task_priority_id.", ".$this->due_ts.", '".$this->type."', '".$this->billable."', ".
			"UNIX_TIMESTAMP(), ".$this->updated_ts.", '".$this->sales_com."', ".$this->sales_com_type_id.", ".
		  "".$this->sales_com_user_id.", ".$this->amount.", 'N')");
		$this->stripSlashes();
		if($result){
			$this->id = mysql_insert_id();
			return TRUE;
		}else{
			$this->error = "SI_Task::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->id)){
			$this->error = "SI_Task::update() : Task id not set\n";
			return FALSE;
		}

		if($this->_updateCommissions() === FALSE)
			return FALSE;

		$this->escapeStrings();
		$result = $db_conn->query("
UPDATE tasks SET project_id = ".$this->project_id.", 
name = '".$this->name."', description = '".$this->description."',
task_status_id = ".$this->task_status_id.", task_priority_id = ".$this->task_priority_id.",
due_ts = ".$this->due_ts.", type = '".$this->type."', 
billable = '".$this->billable."', created_ts = ".$this->created_ts.",
updated_ts = UNIX_TIMESTAMP(), sales_com = '".$this->sales_com."',
sales_com_type_id = ".$this->sales_com_type_id.", sales_com_user_id = ".$this->sales_com_user_id.", 
amount = ".$this->amount.", deleted = '".$this->deleted."'
WHERE id = ".$this->id
		);
		
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Task::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Task::delete() : Task id not set\n";
			return FALSE;
		}

		$result = $db_conn->query("UPDATE tasks SET deleted = 'Y' WHERE id = $id");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Task::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($id = NULL){
		global $db_conn;

		if(!isset($id)){
			$id = $this->id;
		}

		if(!isset($id)){
			$this->error = "SI_Task::get() : Task id not set\n";
			return FALSE;
		}

		$Task = SI_Task::retrieveSet("WHERE t.id = $id", TRUE);
		if($Task === FALSE){
			return FALSE;
		}

		if(isset($Task[0])){
			$this->updateFromAssocArray($Task[0]);
			if($this->_populateAttachments() === FALSE)
				return FALSE;
			//TODO Need to fix the seg faults in this method
//			if($this->type == 'ITEMIZED')
//				if($this->getTaskItems() === FALSE)
//					return FALSE;
			$this->stripSlashes();
		}else{
			$this->error = "SI_Task::get() : No data retrieved from query\n";
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

		$sql = "
SELECT t.id, t.project_id, t.name, t.description, t.task_status_id, t.task_priority_id,
t.due_ts, t.type, t.billable, t.created_ts, t.updated_ts, t.sales_com,
t.sales_com_type_id, t.sales_com_user_id, t.amount, t.deleted,
s.name AS status, p.name AS priority, proj.name AS project
FROM tasks AS t
LEFT JOIN task_statuses AS s ON t.task_status_id = s.id
LEFT JOIN task_priorities AS p ON t.task_priority_id = p.id
LEFT JOIN projects AS proj ON t.project_id = proj.id 
".$clause;

		$result = $db_conn->query($sql);
		if(!$result){
			$this->error = "SI_Task::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchArray(MYSQL_ASSOC)){
			if($raw == TRUE){
				$Task[] = $row;
			}else{
				$temp =& new SI_Task();
				$temp->updateFromAssocArray($row);
				if($temp->_populateAttachments() === FALSE){
					$this->error = $temp->getLastError();
					return FALSE;
				}
				if($temp->type == 'ITEMIZED'){
					if($temp->getTaskItems() === FALSE){
						$this->error = $temp->getLastError();
						return FALSE;
					}
				}
				$temp->stripSlashes();
				$Task[] =& $temp;
			}
		}
		$result->free();

		return $Task;
	}

	function getTypeTags($selected){
		$values = array(
			'FREEFORM' => 'FREEFORM',
			'ITEMIZED' => 'ITEMIZED' );

		$tags = '';
		foreach($values as $value => $name){
			if($value == $selected){
				$sel_text = ' SELECTED';
			}else{
				$sel_text = '';
			}
			$tags .= "<OPTION VALUE='$value'$sel_text>$name</OPTION>\n";
		}

		return $tags;
	}

	function addTaskItem($item, $parent_id = 0){
		if(empty($this->id) || $this->type != 'ITEMIZED'){
			$this->error = "SI_Task::addTaskItem(): Invalid Task!\n";
			return FALSE;
		}

		$ti = new SI_TaskItem();
		$ti->item = $item;
		$ti->parent_id = $parent_id;
		$ti->task_id = $this->id;
		if($ti->add() === FALSE){
			$this->error = "SI_Task::addTaskItem(): Error adding task\n".$ti->getLastError();
			return FALSE;
		}

		$this->getTaskItems();
		return $ti;
	}

	function deleteTaskItem($item_id){
		if(empty($this->id) || $this->type != 'ITEMIZED'){
			$this->error = "SI_Task::deleteTaskItem(): Invalid Task!\n";
			return FALSE;
		}

		$ti = new SI_TaskItem();
		if($ti->delete($item_id) === FALSE){
			$this->error = "SI_Task::deleteTaskItem(): Error adding task\n".$ti->getLastError();
			return FALSE;
		}

		$this->getTaskItems();
		return $ti;
	}

	function getTaskItems(){
		if(empty($this->id) || $this->type != 'ITEMIZED'){
			$this->error = "SI_Task::getTaskItems(): Invalid Task!\n";
			return FALSE;
		}

		$ti = new SI_TaskItem();
		$items = $ti->getTaskItems($this->id);
		if($items === FALSE){
			$this->error = "SI_Task::getTaskItems(): Error getting items:\n".$ti->getLastError();
			return FALSE;
		}else{
			$this->items = $items;
		}

		return TRUE;
	}

	function getTaskItemsHTML($activity_id = NULL, $mode = 'EDIT'){
		static $js_wrote = FALSE;

		if($this->items	=== NULL){
			if($this->getTaskItems() === FALSE){
				return FALSE;
			}
		}

		if($mode != 'EDIT' && $mode != 'VIEW'){
			$this->error = "SI_Task::getTaskItemsHTML(): Invalid mode $mode specified!";
			return FALSE;
		}

		$html = '';
		if($js_wrote == FALSE){
			// Need to add js to check box when label is clicked
			$html .= '';
			$js_wrote = TRUE;
		}

		for($i=0; $i<count($this->items); $i++){
			$completed = !($this->items[$i]->task_activity_id == 0 || $this->items[$i]->task_activity_id == $activity_id);
			if($mode == 'EDIT'){
				$html .= "<INPUT TYPE='checkbox' NAME='item_ids[]' VALUE='".$this->items[$i]->id."'".($completed ? ' CHECKED DISABLED' : checked($this->items[$i]->task_activity_id, $activity_id)).">&nbsp;".$this->items[$i]->item."<BR>\n";
			}else{
				$html .= ($completed ? '<IMG SRC="images/check-green.gif">' : '<IMG SRC="images/dot-red.gif">')."&nbsp;".$this->items[$i]->item."<BR>\n";
			}
			if($this->items[$i]->hasChildren()){
				foreach($this->items[$i]->children as $child){
					$child_completed = !($child->task_activity_id == 0 || $child->task_activity_id == $activity_id);
					if($mode == 'EDIT'){
						$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE='checkbox' NAME='item_ids[]' VALUE='".$child->id."'".($child_completed ? 'CHECKED DISABLED' : checked($child->task_activity_id, $activity_id)).">&nbsp;".$child->item."<BR>\n";
					}else{
						$html .= "&nbsp;&nbsp;&nbsp;&nbsp;".($child_completed ? '<IMG SRC="images/check-green.gif">' : '<IMG SRC="images/dot-red.gif">')."&nbsp;".$child->item."<BR>\n";
					}
				}
			}
		}

		return $html;

	}

	function getCalendarTasks($user_id, $start_ts, $end_ts, $interval){
		$intervals = array(
			'hour' => 3600,
			'day'  => 86400,
			'week' => 604800);

		$user_id = intval($user_id);
		if($user_id <= 0){
			$this->error = "SI_Task::getCalendarTasks($user_id, $start_ts, $end_ts, $interval): Invalid user ID specified!\n";
			return FALSE;
		}

		$start_ts = intval($start_ts);
		$end_ts = intval($end_ts);
		if($start_ts <= 0 || $end_ts <= 0){
			$this->error = "SI_Task::getCalendarTasks($user_id, $start_ts, $end_ts, $interval): Invalid start or end timestamps!\n";
			return FALSE;
		}

		if(!isset($intervals[$interval])){
			$this->error = "SI_Task::getCalendarTasks($user_id, $start_ts, $end_ts, $interval): Unknown interval specified!\n";
			return FALSE;
		}
		$interval_ts = $intervals[$interval];


		$tasks = $this->getUpcoming($user_id, $start_ts, $end_ts);
		if($tasks === FALSE){
			return FALSE;
		}

		$task_array = array();
		$current_key = $start_ts;
		if(count($tasks) > 0){
			foreach($tasks as  $task){
				while(!($task->due_ts >= $current_key && $task->due_ts < $current_key + $interval_ts)){
					$current_key = $current_key+$interval_ts;
					$task_array[$current_key] = array();
				}
				$task_array[$current_key][] = $task;
			}
		}

		return $task_array;
	}

	function _populateAttachments(){
		$attachment = new SI_Attachment();
		$attachments = $attachment->getAttachmentsForTask($this->id);
		if($attachments === FALSE){
			$this->error = "SI_Task::_populateAttachments(): Error getting attachments:\n".$attachment->getLastError();
			return FALSE;
		}else{
			$this->attachments = $attachments;
		}

		return TRUE;
	}

	function deleteAttachment($attachment_id){
		$attachment = new SI_Attachment();
		if($attachment->delete($attachment_id) === FALSE){
			$this->error = "SI_Task::deleteAttachment($attachment_id): ".$attachment->getLastError()."\n";
			return FALSE;
		}

		if($this->_populateAttachments() === FALSE)
			return FALSE;

		return TRUE;
	}

	function addAttachment($local_file, $filename, $description){
		$attachment = new SI_Attachment();
		$filename = $attachment->save($local_file, $filename);
		if($filename === FALSE){
			$this->error = "SI_Task::addAttachment(): ".$attachment->getLastError()."\n";
			return FALSE;
		}

		$attachment->task_id = $this->id;
		$attachment->description = $description;
		$attachment->path = $filename;
		if($attachment->add() === FALSE){
			$this->error = "SI_Task::addAttachment(): ".$attachment->getLastError()."\n";
			return FALSE;
		}

		if($this->_populateAttachments() === FALSE)
			return FALSE;

		return TRUE;
	}

	function getUpcoming($user_id, $start_ts = 0, $end_ts = 0, $limit = 0){
		global $db_conn;

		if(intval($user_id) <= 0){
			$this->error =  "SI_Task::getUpcoming(): Invalid user id!\n";
			return FALSE;
		}

		if($start_ts > 0 && $end_ts > 0){
			$time_sql = " t.due_ts BETWEEN $start_ts AND $end_ts ";
		}else{
			$time_sql = ' t.due_ts > 0 ';
		}

		if($limit > 0)
			$limit_sql = " LIMIT ".intval($limit).' ';

		$sql = 'SELECT project_id FROM user_project_rights
		WHERE user_id = '.$user_id.' AND level > '.PROJECT_RIGHT_NONE;

		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Task::getUpcoming(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$ids = array();
		while($row = $result->fetchArray()){
			$ids[] = $row[0];
		}

		if(count($ids) > 0){
			return $this->retrieveSet("WHERE t.deleted = 'N' AND proj.id IN (".implode(',', $ids).") AND $time_sql AND s.completed = 'N' ORDER BY t.due_ts, p.priority_level $limit_sql");
		}else{
			return array();
		}
	}

	function _updateCommissions(){
		if($this->sales_com != 'Y'){
			$this->sales_com_type_id = 0;
			$this->sales_com_user_id = 0;
		}

		// Calculate commissions on spec tasks
		if($this->hasCommission() && ($this->isSpec() && $this->isCompleted())){
			$ta = new SI_TaskActivity();
			$tas = $ta->getByTaskId($this->id);
			if($tas === FALSE){
				$this->error = "SI_Task::_updateCommissions(): Error getting task activities: ".$ta->getLastError();
				return FALSE;
			}

			for($i=0; $i<count($tas); $i++){
				if($tas[$i]->update() === FALSE){
					$this->error = "SI_Task::_updateCommissions(): Error updating task activitiy: ".$tas[$i]->getLastError();
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	function getProject(){
		if($this->_project == FALSE && $this->project_id > 0){
			$project = new SI_Project();
			if($project->get($this->project_id) === FALSE){
				$this->error = "SI_Task::getProject(): Error getting project: ".$project->getLastError();
				return FALSE;
			}
			$this->_project =& $project;
		}

		return $this->_project;
	}

	function getSalesCommissionType(){
		if($this->_sct == FALSE){
			$sct = new SI_SalesCommissionType();
			if($this->sales_com == 'Y' && $this->sales_com_type_id > 0){
				if($sct->get($this->sales_com_type_id) === FALSE){
					$this->error = "SI_Task::getSalesCommissionType(): Error getting sales commission: ".$sct->getLastError();
					return FALSE;
				}
				$this->_sct = $sct;
			}elseif($this->sales_com == 'N'){
				$this->_sct = $sct;
			}elseif($this->sales_com == 'D'){
				$project = $this->getProject();
				if($project === FALSE)
					return FALSE;

				$sct = $project->getSalesCommissionType();
				if($sct === FALSE){
					$this->error = "SI_Task::getSalesCommissionType(): Error getting sales commission: ".$project->getLastError();
					return FALSE;
				}

				$this->_sct = $sct;
			}else{
				$this->error = "SI_Task::getSalesCommissionType(): Invalid sales commission setting: {$this->sales_com}";
				return FALSE;
			}
		}

		return $this->_sct;
	}

	function getSalesCommissionUser(){
		if($this->_sct_user == FALSE){
			$sct_user = new SI_User();
			if($this->sales_com == 'Y' && $this->sales_com_user_id > 0){
				if($sct_user->get($this->sales_com_user_id) === FALSE){
					$this->error = "SI_Task::getSalesCommissionUser(): Error getting sales commission user: ".$sct_user->getLastError();
					return FALSE;
				}
				$this->_sct_user = $sct_user;
			}elseif($this->sales_com == 'N'){
				$this->_sct_user = $sct_user;
			}elseif($this->sales_com == 'D'){
				$project = $this->getProject();
				if($project === FALSE)
					return FALSE;

				$sct_user = $project->getSalesCommissionUser();
				if($sct_user === FALSE){
					$this->error = "SI_Task::getSalesCommissionUser(): Error getting sales commission user: ".$project->getLastError();
					return FALSE;
				}

				$this->_sct_user = $sct_user;
			}else{
				$this->error = "SI_Task::getSalesCommissionUser(): Invalid sales commission setting: {$this->sales_com}";
				return FALSE;
			}
		}

		return $this->_sct_user;
	}

	function isBillable(){
		$retval = FALSE;
		if($this->billable == 'Y'){
			$retval = TRUE;
		}elseif($this->billable == 'N'){
			$retval = FALSE;
		}elseif($this->billable == 'S'){
			$retval = FALSE;
		}elseif($this->billable == 'D'){
			$project = $this->getProject();
			if($project !== FALSE){
				$retval = $project->isBillable();
			}
		}

		return $retval;

	}

	function isSpec(){
		$retval = FALSE;
		if($this->billable == 'S'){
			$retval = TRUE;
		}elseif($this->billable == 'D'){
			$project = $this->getProject();
			if($project !== FALSE){
				$retval = $project->isSpec();
			}
		}

		return $retval;

	}

	function isCompleted(){
		$ts = new SI_TaskStatus();
		if($ts->get($this->task_status_id) === FALSE){
			$this->error = "SI_Task::isCompleted(): Error getting task status: ".$ts->getLastError();
			return FALSE;
		}

		if($ts->completed = 'Y')
			return TRUE;

		return FALSE;
	}

	function hasCommission(){
		$retval = FALSE;
		if($this->sales_com == 'Y'){
			$retval = TRUE;
		}elseif($this->sales_com == 'N'){
			$retval = FALSE;
		}elseif($this->sales_com == 'D'){
			$project = $this->getProject();
			if($project !== FALSE){
				$retval = $project->hasCommission();
			}
		}

		return $retval;

	}

	function getTotalCost(){
		global $db_conn;

		$this->id = intval($this->id);
		if($this->_total_cost === FALSE && $this->id > 0){
			$sql = "
SELECT SUM(ROUND(((a.end_ts - a.start_ts) / 60 / 60) * a.hourly_rate, 2)) AS amount
FROM task_activities AS a
LEFT JOIN tasks AS t ON a.task_id = t.id
LEFT JOIN projects AS p ON t.project_id = p.id
WHERE t.id = {$this->id}
			";
			$result = $db_conn->query($sql);
			if($result === FALSE){
				$this->error = "SI_Task::getTotalCost(): Error getting total cost: ".$db_conn->getLastError();
				return FALSE;
			}

			while($row = $result->fetchArray(MYSQL_ASSOC)){
				$this->_total_cost = $row['amount'];
			}
		}

		return $this->_total_cost;
	}

	function findTasks($search_string){
		global $db_conn, $loggedin_user;

		$rights_sql = '';
		if(!$loggedin_user->hasRight('admin')){
			$rights_sql = 'AND pr.level IS NOT NULL AND pr.level > 1';
		}
		
		$sql = "
SELECT t.id, t.name AS task_name, p.name AS project_name, c.name AS company_name
FROM tasks AS t
LEFT JOIN projects AS p ON t.project_id = p.id
LEFT JOIN user_project_rights AS pr ON pr.project_id = p.id AND pr.user_id = {$loggedin_user->id} 
LEFT JOIN companies AS c ON p.company_id = c.id
LEFT JOIN project_statuses AS ps ON p.project_status_id = ps.id
LEFT JOIN task_statuses AS ts ON t.task_status_id = ts.id
WHERE (t.name LIKE '%".mysql_escape_string($search_string)."%' OR
p.name LIKE '%".mysql_escape_string($search_string)."%' OR
c.name LIKE '%".mysql_escape_string($search_string)."%')
AND (ts.completed = 'N' AND ps.completed = 'N') AND t.deleted = 'N'
$rights_sql
ORDER BY c.name, p.name, t.name
			";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Task::findTasks(): Error getting searching for tasks: ".$db_conn->getLastError();
			return $this->error;
		}

		$tasks = array();
		$index = 0;
		while($row = $result->fetchArray(MYSQL_ASSOC)){
			$tasks[$index]['id'] = $row['id'];
			$tasks[$index]['string'] = $row['company_name'].': '.$row['project_name'].': '.$row['task_name'];
			$tasks[$index]['company_name'] = $row['company_name'];			
			$tasks[$index]['project_name'] = $row['project_name'];			
			$tasks[$index]['task_name'] = $row['task_name'];			
			$index++;
		}

		return $tasks;
	}

	function getTasks($user){
		global $db_conn;

		$rights_sql = '';
		if(!$user->hasRight('admin')){
			$rights_sql = 'AND pr.level IS NOT NULL AND pr.level > 1';
		}
		
		$sql = "
SELECT t.id, t.name AS name, t.project_id, p.name AS project, c.name AS company
FROM tasks AS t
LEFT JOIN projects AS p ON t.project_id = p.id
LEFT JOIN user_project_rights AS pr ON pr.project_id = p.id AND pr.user_id = {$user->id} 
LEFT JOIN companies AS c ON p.company_id = c.id
LEFT JOIN project_statuses AS ps ON p.project_status_id = ps.id
LEFT JOIN task_statuses AS ts ON t.task_status_id = ts.id
WHERE (ts.completed = 'N' AND ps.completed = 'N') AND t.deleted = 'N'
$rights_sql
			";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Task::getTasks(): Error getting searching for tasks: ".$db_conn->getLastError();
			return $this->error;
		}

		$tasks = array();
		while($row=$result->fetchArray(MYSQL_ASSOC)){
			$temp =& new SI_Task();
			$temp->updateFromAssocArray($row);
			if($temp->_populateAttachments() === FALSE){
				$this->error = $temp->getLastError();
				return FALSE;
			}
			if($temp->type == 'ITEMIZED')
				if($temp->getTaskItems() === FALSE){
					$this->error = $temp->getLastError();
					return FALSE;
				}
			$tasks[] =& $temp;
		}
		$result->free();
        
		return $tasks;
	}

	function getDefaultItemCode($task_id){
		global $db_conn;

		$sql = "
SELECT p.default_item_code_id
FROM tasks AS t
LEFT JOIN projects AS p ON t.project_id = p.id
WHERE t.id = $task_id
			";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Task::getDefaultItemCode(): Error getting default item code for task id: ".$db_conn->getLastError();
			return $this->error;
		}

		$default_item_code_id = 0;
		if($row = $result->fetchArray(MYSQL_ASSOC)){
			$default_item_code_id = $row['default_item_code_id'];
		}

		return $default_item_code_id;			
	}

	function hasBilledActivities(){
		global $db_conn;

		$sql = "
SELECT ta.id
FROM task_activities AS ta
LEFT JOIN invoice_line_links AS ill ON ill.task_activity_id = ta.id
WHERE ta.task_id = '{$this->id}' AND ill.id IS NOT NULL
			";
		$result = $db_conn->query($sql);
		if($result === FALSE){
			$this->error = "SI_Task::hasBilledActivities(): Error looking for billed activities: ".$db_conn->getLastError();
			return $this->error;
		}

		return ($result->numRows() > 0);
	}

	/**
	 * Method to retreive option tags for all tasks
	 * 
	 * If a company_id is provided this will on provide a
	 * list of tasks for the provided company_id
	 *
	 * This method will provide a string that contains
	 * the HTML option tags for all tasks in the 
	 * database sorted by Project Name, Task Name.
	 * 
	 * If a task id is provided in the $selected
	 * argument, then that option tag will be marked
	 * as selected.
	 *
	 * @global DBConn Database access object
	 * @access public
	 * @static
	 * @see getLastError()
	 * @return string|FALSE HTML option tags or FALSE on error
	 */
	function getSelectTags($selected = NULL, $company_id = 0){
		global $db_conn;
		
		$company_id = intval($company_id);
		$company_sql = '';
		if($company_id > 0){
			$company_sql = "AND p.company_id = $company_id";	
		}

		$result = $db_conn->query("SELECT t.id, CONCAT(p.name, ':', t.name) FROM tasks AS t LEFT JOIN task_statuses AS ts ON t.task_status_id = ts.id LEFT JOIN projects AS p ON p.id = t.project_id LEFT JOIN project_statuses AS ps ON p.project_status_id = ps.id WHERE ts.completed = 'N' AND ps.completed = 'N' $company_sql ORDER BY p.name, t.name");

		if($result === FALSE){
			$this->error = "SI_Task::getSelectTags(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}


		while($row=$result->fetchRow()){
			$sel_text = "";
			if($row[0]==$selected)
				$sel_text = " SELECTED";
			$tags .= "<OPTION VALUE=\"".$row[0]."\"".$sel_text.">".$row[1]."</OPTION>\n";
		}
		return $tags;
	}

	function getLongName($task_id = null){
		if(is_null($task_id)){
			$task_id = $this->id;
		}
		
		$task = new SI_Task();
		$results = $task->retrieveSet('WHERE t.id = '.$task_id, true);

		if($results == false){
			$this->error = "Error looking up task: ".$task->getLastError();
			return FALSE;
		}
		
		$name = '';
		foreach($results as $row){
			$name = $row['project']."<br>\n".$row['name'];
		}
		
		return $name;
	}
}

