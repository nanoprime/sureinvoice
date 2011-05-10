<?php
/*
 * Created on Sep 18, 2007
 *
 * Author: Robert Gimbel <rgimbel at cybarworks.com>
 * 
 *   AutoInvoice.php is a script run from cron to automatically create invoices out of
 *         unbilled time, scheduled payments, and expenses that are stored in the 
 *         SureInvoice application.
 * 
 *   Copyright (C) 2007  Robert G Gimbel
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 */
$incpath = realpath(dirname(__FILE__).'/../includes');
require_once($incpath . '/common.php');
require_once($incpath . '/SI_Invoice.php');
require_once($incpath . '/SI_Company.php');
require_once($incpath . '/SI_TaskActivity.php');
require_once($incpath . '/SI_PaymentSchedule.php');
require_once($incpath . '/SI_ItemCode.php');

//Get list of
echo("Starting of AutoInvoice.\n\n");
//$comp_ids = '';
echo (" -Getting Companies with unbilled time.\n");
$company = new SI_Company();
$companies_time = $company->getCompanysWithUnbilledAmount();
if($companies === FALSE){
	echo ("****ERR: Could not retrieve Outstanding Hours list! ****\n");
	debug_message($company->getLastError());
} elseif (count($companies_time) > 0) {
	foreach($companies_time as $comp_time){
		$comp_ids[] = $comp_time->id;
		echo ("  * $comp_time->name - ". formatLengthOfTime($comp_time->time_spent)." ".SureInvoice::getCurrencySymbol().number_format($comp_time->amount, 2)."\n");
	}
}

echo (" -Getting Upcoming Scheduled Payment.\n");
$ps = new SI_PaymentSchedule();
$time = time() + 16 * (24 * (60 * 60));
$ps_items = $ps->getUpcoming($time);
if($ps_items === FALSE){
	echo("****ERR: Could not retreive upcoming scheduled billings! ****\n");
	debug_message($ps->getLastError());
} elseif (count($ps_items) > 0) {
	foreach($ps_items as $scheduled_payment){
		$ps_comp = $scheduled_payment->getCompany();
		$comp_ids[] = $ps_comp->id;
		echo ("  * $ps_comp->id \n");
	}
}

echo (" -Getting unbilled expenses.\n");
$expense = new SI_Expense();
$expenses = $expense->getUnbilled();
if($expenses === FALSE){
	echo("****ERR: Could not retreive unbilled expenses! ****\n");
	debug_message($expense->getLastError());
} elseif (count($expenses) > 0) {
	foreach($expenses as $exp){
		$comp_ids[] = $exp->getCompany()->id;
		echo ("  * ". $exp->getCompany()->id." \n");
	}
}
echo("\n\n$$$$ START MAKIN MONEY $$$$\n\n");
$comp_ids = array_unique($comp_ids);
if (count($comp_ids) > 0) {
	foreach($comp_ids as $compid){
		echo("$$$ Generating Invoice for Company_id - $compid\n");
		$invoice = new SI_Invoice();
		$company = new SI_Company();
		if ($company->get($compid)){
			$company_array = get_object_vars($company);
			$company_array['company_id'] = $company->id;
			unset($company_array['id']);
		}
		$invoice->updateFromAssocArray($company_array);
		if($invoice->add() !== FALSE){
			$activity = new SI_TaskActivity();
			$temp_array = $activity->getActivitiesForCompany($compid);
			if($temp_array != NULL){
				$ta_ids ='';
				foreach($temp_array as $temptask){
					$ta_ids[] = $temptask->id;
				}
				$ta_ids = array_unique($ta_ids);
			}
			if(count($ta_ids) > 0){
				if($invoice->addTaskActivities($ta_ids, SI_ACTIVITY_AGGREGATION_TASK) === FALSE){
					$error_msg .= "Error adding activities to invoice!\n";
					debug_message($invoice->getLastError());
				}
			}

			$ps = new SI_PaymentSchedule();
			$ps_array = $ps->getForCompany($compid);
			if($ps_array != NULL){
				$ps_ids = '';
				foreach($ps_array as $tempps){
					$ps_ids[] = $tempps->id;
				}
				$ps_ids = array_unique($ps_ids);
			}
			if(count($ps_ids) > 0){
				if($invoice->addPaymentSchedules($ps_ids) === FALSE){
					$error_msg .= "Error adding payment schedules to invoice!\n";
					debug_message($invoice->getLastError());
				}
			}

			$expense_array = $company->getExpenses(TRUE);
			if( $expense_array != NULL){
				$ex_ids ='';
				foreach($expense_array as $tempexp){
					$ex_ids[] = $tempexp->id;
				}
				$ex_ids = array_unique($ex_ids);
			}
			if(count($ex_ids) > 0){
				if($invoice->addExpenses($ex_ids, SI_EXPENSE_AGGREGATION_DESC) === FALSE){
					$error_msg .= "Error adding expenses to invoice!\n";
					debug_message($invoice->getLastError());
				}
			}
			
			// Add the company transaction
			if(empty($error_msg)){
				$ct = new SI_CompanyTransaction();
				$ct->amount = $invoice->getTotal();
				$ct->company_id = $invoice->company_id;
				$ct->description = "Invoice #".$invoice->id;
				$ct->timestamp = $invoice->timestamp;
				if($ct->add() === FALSE){
					$error_msg .= "Error adding company transaction!\n";
					debug_message($ct->getLastError());
				}
	
				$invoice->trans_id = $ct->id;
				if($invoice->update() === FALSE){
					$error_msg .= "Error updating invoice with company transaction id!\n";
					debug_message($invoice->getLastError());
				}
			}
	
		}else{
			$echo( "Error adding Invoice!\n");
			debug_message($invoice->getLastError());
		}
	}
}


echo("\n\nEnd of AutoInvoice.\n");
?>
