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
require_once('SI_Timer.php');
require_once('SI_Company.php');

/**
 * SureInvoice class
 * 
 * Global class that provides methods that are 
 * needed in many areas of the application.
 * 
 * @package SureInvoice
 *
 */
class SureInvoice {
	var $error = '';
	
	/**
	 * constructor
	 *
	 * @return SureInvoice
	 */
	function SureInvoice(){
		
	}
	
	/**
	 * Returns the last error that occurred
	 *
	 * @return string
	 */
	function getLastError(){
		return $this->error;
	}

	/**
	 * This is a noop function that is called via AJAX
	 * to keep the user's session alive
	 *
	 * @return bool
	 */
	function stayAlive(){
		global $db_conn, $loggedin_user;
		
		// Don't really need to do anything cause we just want the session to be accessed
		
		return true;		
	}
	
	/**
	 * Saves a user setting to the database
	 *
	 * @param sring $setting The name of the setting
	 * @param string $value The value
	 * @return bool
	 */
	function saveUserSetting($setting, $value){
		global $loggedin_user;

		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		return $loggedin_user->saveSetting($setting, $value);
	}

	/**
	 * Retreive a user setting
	 *
	 * @param string $setting The name of the setting to retrieve
	 * @return string
	 */
	function getUserSetting($setting){
		global $loggedin_user;

		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		return $loggedin_user->getSetting($setting);
	}

	/**
	 * Retreive an array of the users last 7 days of 
	 * time
	 *
	 * @return array
	 */
	function getRecentTime(){
		global $loggedin_user;
		
		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		$time = $loggedin_user->getRecentTime();
		if($time === FALSE){
			$this->error = "Error getting recent time: ".$loggedin_user->getLastError();			
			return false;
		}
		
		if(is_null($time) || count($time) == 0){
			return array();
		}		
		
		return array_values($time);
	}
	
	/**
	 * Method to return the timer data about the currently
	 * logged in user.
	 *
	 * @return array
	 */
	function getTimerData(){
		global $loggedin_user;
		
		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		$timers = $loggedin_user->getTimers();
		if($timers === FALSE){
			$this->error = "Error getting timers: ".$loggedin_user->getLastError();			
			return false;
		}
		if(is_null($timers) || count($timers) == 0){
			return array();
		}
		
		$timer_data = array();
		foreach ($timers as $timer){
			$timer_data[] = array(
				'id' => $timer->id,
				'name' => $timer->name,
				'last_start_ts' => $timer->last_start_ts,
				'total' => formatLengthOfTime($timer->getTotal()),
				'status' => $timer->status
			);
		}
		
		return $timer_data;
	}
	
	function addTimer($name){
		global $loggedin_user;

		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		$timer = $loggedin_user->startTimer($name);
		$timer_data = array();
		if($timer !== FALSE){
			$timer_data['id'] = $timer->id;
			$timer_data['name'] = $timer->name;
			$timer_data['status'] = $timer->status;
			$timer_data['total'] = formatLengthOfTime($timer->getTotal());
		}else{
			$this->error = "Error adding timer: ".$loggedin_user->getLastError();
			return false;
		}
		
		return $timer_data;
	}
	
	function pauseTimer($id){
		global $loggedin_user;
		
		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}

		$timer = new SI_Timer();
		$timer_data = array();
		if($timer->get($id) !== FALSE){
			$timer->pause();
			$timer_data['id'] = $timer->id;
			$timer_data['name'] = $timer->name;
			$timer_data['status'] = $timer->status;
			$timer_data['total'] = formatLengthOfTime($timer->getTotal());			
		}else{
			$this->error = "Error pausing timer: ".$timer->getLastError();
			return false;			
		}
		
		return $timer_data;
	}

	function startTimer($id){
		global $loggedin_user;
		
		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}

		$timer = new SI_Timer();
		$timer_data = array();
		if($timer->get($id) !== FALSE){
			$timer->start();
			$timer_data['id'] = $timer->id;
			$timer_data['name'] = $timer->name;
			$timer_data['status'] = $timer->status;
			$timer_data['total'] = formatLengthOfTime($timer->getTotal());			
		}else{
			$this->error = "Error starting timer: ".$timer->getLastError();
			return false;			
		}
		
		return $timer_data;
	}

	function deleteTimer($id){
		global $loggedin_user;
		
		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}

		$timer = new SI_Timer();
		if($timer->get($id) !== FALSE){
			$timer->delete();
		}else{
			$this->error = "Error deleting timer: ".$timer->getLastError();
			return false;			
		}
		
		return TRUE;
	}
	
	function importGetUsers(){
		global $loggedin_user;

		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		if(!isset($_SESSION['SureInvoice']['TimeImport'])){
			$this->error = "No import is currently in progress!";
			return false;
		}
		
		$importer = $_SESSION['SureInvoice']['TimeImport'];
		
		$users = $importer->getUsers();
		$html = "";
		foreach ($users as $normalized_name => $data){
			$action_options = "
	<option value=".SI_IMPORT_ACTION_SKIP." ".selected(SI_IMPORT_ACTION_SKIP, intval($data['action'])).">Skip</option>
	<option value=".SI_IMPORT_ACTION_MAP." ".selected(SI_IMPORT_ACTION_MAP, intval($data['action'])).">Map</option>
			";
			$map_options = "<option value='0'>Unknown</option>".SI_User::getSelectTags($data['param']);
			$html .=  <<<EOF
<tr>
	<td>{$data['user']}</td>
	<td><select name="actions[$normalized_name]">
		{$action_options}
	</select>
	</td>
	<td><select name="params[$normalized_name]">
		{$map_options}
		</select>
	</td>
</tr>

EOF;
		}
		
		return $html;
	}
	
	function importGetTasks(){
		global $loggedin_user;

		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		if(!isset($_SESSION['SureInvoice']['TimeImport'])){
			$this->error = "No import is currently in progress!";
			return false;
		}
		
		$importer = $_SESSION['SureInvoice']['TimeImport'];
		
		$tasks = $importer->getTasks();
		$html = "";
		foreach ($tasks as $normalized_name => $data){
			$action_options = "
	<option value=".SI_IMPORT_ACTION_SKIP." ".selected(SI_IMPORT_ACTION_SKIP, intval($data['action'])).">Skip</option>
	<option value=".SI_IMPORT_ACTION_MAP." ".selected(SI_IMPORT_ACTION_MAP, intval($data['action'])).">Map</option>
			";
			$map_options = "<option value='0'>Unknown</option>".SI_Task::getSelectTags($data['param']);
			$html .=  <<<EOF
<tr>
	<td>{$data['name']}</td>
	<td><select name="actions[$normalized_name]">
		{$action_options}
	</select>
	</td>
	<td><select name="params[$normalized_name]">
		{$map_options}
		</select>
	</td>
</tr>

EOF;
		}
		
		return $html;
	}

	function importGetItemCodes(){
		global $loggedin_user;

		if(!isLoggedIn()){
			$this->error = "User not logged in.";
			return false;
		}
		
		if(!isset($_SESSION['SureInvoice']['TimeImport'])){
			$this->error = "No import is currently in progress!";
			return false;
		}
		
		$importer = $_SESSION['SureInvoice']['TimeImport'];
		
		$item_codes = $importer->getItemCodes();
		$html = "";
		foreach ($item_codes as $normalized_name => $data){
			$action_options = "
	<option value=".SI_IMPORT_ACTION_SKIP." ".selected(SI_IMPORT_ACTION_SKIP, intval($data['action'])).">Skip</option>
	<option value=".SI_IMPORT_ACTION_MAP." ".selected(SI_IMPORT_ACTION_MAP, intval($data['action'])).">Map</option>
			";
			$map_options = "<option value='0'>Unknown</option>".SI_ItemCode::getSelectTags($data['param']);
			$html .=  <<<EOF
<tr>
	<td>{$data['name']}</td>
	<td><select name="actions[$normalized_name]">
		{$action_options}
	</select>
	</td>
	<td><select name="params[$normalized_name]">
		{$map_options}
		</select>
	</td>
</tr>

EOF;
		}
		
		return $html;
	}
	
	function getCurrencySymbol() {
		if(isset($GLOBALS['CONFIG']['currency_symbol']) && !empty($GLOBALS['CONFIG']['currency_symbol'])){
			if($GLOBALS['CONFIG']['currency_symbol'] != '$'){
				return '&#'.$GLOBALS['CONFIG']['currency_symbol'].';';
			}else{
				return '$';
			}
		}
		
		return '$';
	}
	
	function getCurrencyCode() {
		if(isset($GLOBALS['CONFIG']['currency_code']) && !empty($GLOBALS['CONFIG']['currency_code'])){
			return $GLOBALS['CONFIG']['currency_code'];
		}
		
		return 'USD';
	}

	function getCurrencySymbolPDF(){
		if(isset($GLOBALS['CONFIG']['currency_symbol'])){
			if($GLOBALS['CONFIG']['currency_symbol'] == '$'){
				return '$';
			}elseif($GLOBALS['CONFIG']['currency_symbol'] == '8364'){
				return '~';
			}else{
				return SureInvoice::getCurrencyCode();
			}
		}else{
			return '$';
		}
	}

	function authenticateUser($email, $password){
		session_regenerate_id();
		$user = new SI_User();
		$login_user = $user->getUserByLogin($email, md5($password));
		if($login_user === FALSE || is_null($login_user)){
			$this->error = $user->getLastError();
			unset($_SESSION['userObj']);
			return FALSE;
		}else{
			$user->hasRight("admin");
			$_SESSION['userObj'] = $login_user;
			return $login_user;
		}
	}

	function getMyCompany(){
		$company = new SI_Company();
		if(isset($GLOBALS['CONFIG']['my_company_id']) && $GLOBALS['CONFIG']['my_company_id'] > 0){
			$company->get($GLOBALS['CONFIG']['my_company_id']);
		}

		return $company;
	}
}
?>
