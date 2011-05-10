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
require_once('SI_User.php');

class SI_Reports{

	var $error;

	function SI_Reports(){
		$this->error = '';
	}

	function getLastError(){
		return $this->error;
	}

	function individualGross($start_ts, $end_ts, $use_salaries = TRUE){
		global $db_conn;

		$start_ts = intval($start_ts);
		$end_ts = intval($end_ts);
		$sql = "
SELECT u.id, u.first_name, u.last_name, 
sum(ta.hourly_cost * ((ta.end_ts - ta.start_ts) / 60 / 60)) as cost, 
sum(ta.end_ts - ta.start_ts) as hours_worked, 
sum(IF((t.billable = 'Y' OR (t.billable = 'D' AND p.billable = 'Y')), 
ta.end_ts - ta.start_ts,
0.00)) as hours_billed,
sum(IF((t.billable = 'Y' OR (t.billable = 'D' AND p.billable = 'Y')), 
ta.hourly_rate * ((ta.end_ts - ta.start_ts) / 60 / 60),
0.00)) as price
FROM task_activities AS ta 
LEFT JOIN users as u on u.id = ta.user_id 
LEFT JOIN tasks as t on t.id = ta.task_id 
LEFT JOIN projects as p on p.id = t.project_id 
WHERE start_ts between $start_ts AND $end_ts
GROUP BY ta.user_id
		";
		
		$result = $db_conn->query($sql);
		if($result === false){
			$this->error = "SI_Reports::individualGross() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$rows = array();
		while($row =  $result->fetchArray(MYSQL_ASSOC)){
			$rows[$row['id']] = $row;
		}
		$result->free();
		
		// Calculate  salart costs
		if($use_salaries){
			$months = round((($end_ts - $start_ts) / 60 / 60 / 24) / 30, 1);
			$user = new SI_User();
			$users = $user->retrieveSet("WHERE u.rate_type = 'SALARY'");
			if($users === false){
				$this->error = "Error getting users: ".$user->getLastError();
				return false;
			}
			foreach($users as $user){
				if($user->salary == 0) continue;
				if(isset($rows[$user->id])){
					$rows[$user->id]['cost'] = ($user->salary / 12) * $months;
				}elseif($user->active == 'Y' && $user->deleted == 'N'){
					$rows[$user->id] = array();
					$rows[$user->id]['first_name'] = $user->first_name;
					$rows[$user->id]['last_name'] = $user->last_name;
					$rows[$user->id]['cost'] = ($user->salary / 12) * $months;
					$rows[$user->id]['price'] = 0.00;
				}
			}
		}
		
		return $rows;
	}

	function salesByItemCode($start_ts, $end_ts){
		global $db_conn;

		$start_ts = intval($start_ts);
		$end_ts = intval($end_ts);
		$sql = "
SELECT il.item_code_id, ic.code, ic.description, SUM(il.quantity * il.unit_price) as sales
FROM invoice_lines AS il
LEFT JOIN item_codes as ic on ic.id = il.item_code_id
LEFT JOIN invoices AS i ON i.id = il.invoice_id
WHERE i.timestamp between $start_ts AND $end_ts
GROUP BY il.item_code_id
ORDER BY ic.code
		";
		
		$result = $db_conn->query($sql);
		if($result === false){
			$this->error = "SI_Reports::salesByItemCode() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$rows = array();
		while($row =  $result->fetchArray(MYSQL_ASSOC)){
			$rows[$row['item_code_id']] = $row;
		}
		$result->free();
		
		return $rows;
	}

	function salesByItemCodeDetail($item_code_id, $start_ts, $end_ts){
		global $db_conn;

		$item_code_id = intval($item_code_id);
		$start_ts = intval($start_ts);
		$end_ts = intval($end_ts);
		$sql = "
SELECT c.id, ic.code, ic.description, c.name, SUM(il.quantity * il.unit_price) as sales
FROM invoice_lines AS il
LEFT JOIN item_codes as ic on ic.id = il.item_code_id
LEFT JOIN invoices AS i ON i.id = il.invoice_id
LEFT JOIN companies AS c ON c.id = i.company_id
WHERE i.timestamp between $start_ts AND $end_ts AND il.item_code_id = $item_code_id
GROUP BY c.id
ORDER BY c.name
		";
		
		$result = $db_conn->query($sql);
		if($result === false){
			$this->error = "SI_Reports::salesByItemCodeDetail() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		$rows = array();
		while($row =  $result->fetchArray(MYSQL_ASSOC)){
			$rows[$row['id']] = $row;
		}
		$result->free();
		
		return $rows;
	}
}