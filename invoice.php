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
require_once('includes/common.php');
require_once('includes/SI_Invoice.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_TaskActivity.php');
require_once('includes/SI_PaymentSchedule.php');
require_once('includes/SI_ItemCode.php');

checkLogin('accounting');

$num_custom_lines = 5;
$activity = new SI_TaskActivity();
$ps = new SI_PaymentSchedule();
$invoice = new SI_Invoice();
$item_code = new SI_ItemCode();

if ($_POST['save']){
	$ta_ids = $_POST['task_activity_ids'];
	$ps_ids = $_POST['payment_schedule_ids'];
	$ex_ids = $_POST['expense_ids'];
	
	$at_least_one = false;
	if(count($ta_ids) == 0 && count($ps_ids) == 0 && count($ex_ids) == 0){
		if(isset($_REQUEST['custom']) && is_array($_REQUEST['custom'])){
			foreach($_REQUEST['custom'] as $custom_line){
				if($custom_line['quantity'] > 0 || $custom_line['description'] != ''){
					$at_least_one = true;
				}
			}
		}
	}else{
		$at_least_one = true;
	}
	
	if(!$at_least_one){
		$error_msg .= "You must select at least one item for the invoice!\n";
	}

	if(empty($error_msg)){
		$invoice->updateFromAssocArray($_POST);
		if($invoice->add() !== FALSE){
			if(count($ta_ids) > 0){
				if($invoice->addTaskActivities($ta_ids, $_POST['activity_aggregation_type']) === FALSE){
					$error_msg .= "Error adding activities to invoice!\n";
					debug_message($invoice->getLastError());
				}
			}

			if(count($ps_ids) > 0){
				if($invoice->addPaymentSchedules($ps_ids) === FALSE){
					$error_msg .= "Error adding payment schedules to invoice!\n";
					debug_message($invoice->getLastError());
				}
			}

			if(count($ex_ids) > 0){
				if($invoice->addExpenses($ex_ids, $_POST['expense_aggregation_type']) === FALSE){
					$error_msg .= "Error adding expenses to invoice!\n";
					debug_message($invoice->getLastError());
				}
			}

			// Add custom line items
			if($invoice->addCustomLines($_REQUEST['custom']) === FALSE){
				$error_msg .= "Error adding custom line items to invoice!\n";
				debug_message($invoice->getLastError());
			}
			
			//Add discount line
			if($_POST['discount_line'] == 'Y'){
				if($invoice->addDiscountLine() === FALSE){
					$error_msg = "Error calculating discount line\n";
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
			
			if(empty($error_msg)){
				goBack();
			}
		}else{
			$error_msg .= "Error adding Invoice!\n";
			debug_message($invoice->getLastError());
		}
	}
}

$_REQUEST['detail'] = strtolower(substr($_REQUEST['detail'],0,1)) == "y" ? TRUE : FALSE;

$company = new SI_Company();
$title = "Create Invoice";
$total_time = 0;
if(isset($_REQUEST['company_id']) && $_REQUEST['company_id'] > 0){
	if($company->get($_REQUEST['company_id']) === FALSE){
		$error_msg .= "Could not retrieve information for Company ID ".$_REQUEST['company_id']."!\n";
		debug_message($company->getLastError());
	}
	
	$activities = $activity->getActivitiesForCompany($_REQUEST['company_id']);
	if($activities === FALSE){
		$error_msg .= "Could not retrieve Activity List for Company ID ".$_REQUEST['company_id']."!\n";
		debug_message($activity->getLastError());
	}
	
	$items = $ps->getForCompany($_REQUEST['company_id']);
	if($items === FALSE){
		$error_msg .= "Could not retrieve scheduled payments for Company ID ".$_REQUEST['company_id']."!\n";
		debug_message($ps->getLastError());
	}
	
	$expenses = $company->getExpenses(TRUE);
	if($expenses === FALSE){
		$error_msg .= "Could not retrieve expenses for Company ID ".$_REQUEST['company_id']."!\n";
		debug_message($company->getLastError());
	}

	$item_codes = $item_code->getCompanyPricedCodes($company->id);
	if($item_codes === FALSE){
		$error_msg .= "Could not get item codes for company!\n";
		debug_message($item_code->getLastError());	
	}
}

require('header.php') ?>
<script>
	function updatePrice(lineId){
		var oQuantity = document.getElementById('quantity_'+lineId);	
		var oPrice = document.getElementById('price_'+lineId);	
		var oLineTotal = document.getElementById('line_total_'+lineId);	
		var oTotal = document.getElementById('custom_line_total');
		
		var fQuantity = parseFloat(oQuantity.value);
		var fPrice = parseFloat(oPrice.value);
		if(!isNaN(fQuantity) && fQuantity > 0 && !isNaN(fPrice)){
			oLineTotal.innerHTML = Math.round((fQuantity * fPrice)*100)/100;
		}else{
			oLineTotal.innerHTML = '0.00';	
		}
		
		var newTotal = 0.00
		for(i=0; i < <?= $num_custom_lines ?>; i++){
			var oLineTotal = document.getElementById('line_total_'+i);
			var fLineTotal = parseFloat(oLineTotal.innerHTML);
			if(!isNaN(fLineTotal)){
				newTotal = newTotal + fLineTotal;
			}
		}
		oTotal.innerHTML = Math.round(newTotal*100)/100;
	}

	function reloadPage(){
		var oCompanyId = document.getElementById('company_id');
		var oResource = document.getElementById('user_id');
		var iCompanyId = oCompanyId.options[oCompanyId.selectedIndex].value;
		window.location = '<?= $_SERVER['PHP_SELF'] ?>?company_id='+iCompanyId;
	}

	function ItemCode(id, description, cost, price){
		this.id = id;
		this.description = description;
		this.cost = cost;
		this.price = price;	
	}
	
	var item_prices = new Array();
<?	for($i = 0; $i <= count($item_codes); $i++){
		if(!empty($item_codes[$i]->id)){
			print("item_prices[".$item_codes[$i]->id."] = new ItemCode(\"".$item_codes[$i]->id."\",\"".$item_codes[$i]->description."\", \"".$item_codes[$i]->cost."\", \"".$item_codes[$i]->price."\");\n");
		} 
	} ?>

	function updateCustomLine(line_index){
		var oPrice = document.getElementById('price_'+line_index);
		var oDescription = document.getElementById('description_'+line_index);
		var oCode = document.getElementById('item_code_id_'+line_index);
		if(item_prices[oCode.options[oCode.selectedIndex].value]){
			oPrice.value = item_prices[oCode.options[oCode.selectedIndex].value].price;	
			oDescription.value = item_prices[oCode.options[oCode.selectedIndex].value].description;
		}else{
			oPrice.value = '';	
			oDescription.value = '';			
		}	
		updatePrice(line_index);
	}


</script>
<form action="<?= $_SERVER['PHP_SELF'] ?>" METHOD="POST" NAME="chk">
<input type="hidden" name="detail" value="<?= $_REQUEST['detail'] ?>">
<div class="tableContainer" style="clear:both;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Company information</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="form_table">
<tr>
	<td class="form_field_header_cell">Company:</td>
	<td class="form_field_cell">
		<select name="company_id" id="company_id" class="input_text" onchange="reloadPage()" >
			<option value="0">Select company...</option>
			<?= SI_Company::getSelectTags($company->id) ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 1:</td>
	<td class="form_field_cell"><input name="address1" class="input_text" size="35" type="text" value="<?= $company->address1 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Address Line 2:</td>
	<td class="form_field_cell"><input name="address2" class="input_text" size="35" type="text" value="<?= $company->address2 ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">City:</td>
	<td class="form_field_cell"><input name="city" class="input_text" size="35" type="text" value="<?= $company->city ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">State:</td>
	<td class="form_field_cell"><input name="state" class="input_text" size="5" type="text" value="<?= $company->state ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Zip:</td>
	<td class="form_field_cell"><input name="zip" class="input_text" size="10" type="text" value="<?= $company->zip ?>"></td>
</tr>
<tr>
	<td class="form_field_header_cell">Terms:</td>
	<td class="form_field_cell">
		<select name="terms" class="input_text">
			<?= SI_Invoice::getTermsSelectTags() ?>
		</select>
	</td>
</tr>
<tr>
	<td class="form_field_header_cell">Rate Structure:</td>
	<td class="form_field_cell">
		<select name="discount_line" class="input_text">
			<option value="Y">Calculate Discount, if setup for company</option>
			<option value="N">Do Not Calculate Discount</option>
		</select>
	</td>
</tr>
</table>
	</div>
</div>
<? if(count($activities)>0){ ?>
<div class="tableContainer" style="display:block;">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Select Activities</a><div>
	<div class="gridToolbar">
		Show details 
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link3" : "link1" ?>" HREF="<?= $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']."&detail=y" ?>" style="background-image:url(images/plus.png);">Yes</a>
		<a class="<?= $_REQUEST['detail'] == TRUE ? "link1" : "link3" ?>" HREF="<?= $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']."&detail=n" ?>" style="background-image:url(images/minus.png);">No</a>		  
	</div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 1, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 2, 0, false)">Start</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 3, 0, false)">End</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 4, 0, false)">Time Spent</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 5, 0, false)">Hourly Rate</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId', 6, 0, false)">Price</a></th>
		<th class="dg_header_cell">Include?</th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId">
<? for($i = 0; $i < count($activities); $i++){
		if($activities[$i]->hourly_rate <= 0)
			continue;

		$activities[$i]->_calcPrice();
		$act_time = ($activities[$i]->end_ts>0 &&  $activities[$i]->start_ts>0 ? $activities[$i]->end_ts - $activities[$i]->start_ts : 0);
		$total_time += $act_time;
		$total_cost += $activities[$i]->cost;
		$total_com += $activities[$i]->com_amount;
		$total_price += $activities[$i]->price;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $activities[$i]->project_name ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->task_name ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->start_ts>0 ? date("n/j/y H:i", $activities[$i]->start_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $activities[$i]->end_ts>0 ? date("n/j/y H:i", $activities[$i]->end_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $act_time>0 ? formatLengthOfTime($act_time) :  "" ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->hourly_rate, 2) ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($activities[$i]->price, 2) ?></td>
		<td class="gridActions"><input type="checkbox" name="task_activity_ids[]" value="<?= $activities[$i]->id ?>"></td>
		<td class="gridActions">
			<a class="link1" href="task_activity.php?mode=edit&id=<?= $activities[$i]->id ?>"><img src="images/edit.png" width="16" height="16" alt="Edit" border="0" /></a>
		</td>
		<td class="gridActions">
			<a class="link1" href="task_activity.php?mode=delete&id=<?= $activities[$i]->id ?>"><img src="images/delete.png" width="16" height="16" alt="" border="0" /></a>
		</td>		
	</tr>
<? if($_REQUEST['detail']){?>
	<tr>
		<td colspan="11" class="dg_data_cell_1"><?= nl2br($activities[$i]->text) ?></td>
	</tr>
<? 		if($i != count($activities)-1){?>
	<tr>
		<td colspan="11" class="dg_header_cell">&nbsp;</td>
	</tr>
<? 		} //If not last
	} //If detail 
}?>
	</tbody>
	<tr>
		<td colspan="4" class="form_header_cell" align="right">Totals:</td>
		<td class="form_field_cell"><?= formatLengthOfTime($total_time) ?></td>
		<td class="form_header_cell"></td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price, 2) ?></td>
		<td class="gridActions"><input type="checkbox" name="select_all" onclick='SelectAll("task_activity_ids[]")'></td>
		<td class="form_header_cell" align="right"></td>
	</tr>
	<tr>
		<td colspan="8" class="form_header_cell" align="right">
			<b>Aggregation Type:&nbsp;</b>
			<select name="activity_aggregation_type">
				<option value="<?= SI_ACTIVITY_AGGREGATION_TASK ?>">Task, Item Code &amp; Price</option>
				<option value="<?= SI_ACTIVITY_AGGREGATION_ITEM_CODE ?>">Item Code and Price</option>
				<option value="<?= SI_ACTIVITY_AGGREGATION_NONE ?>">No Aggregation</option>
			</select>
		</td>
		<td colspan="2" class="form_header_cell" align="right">
			<input type="submit" name="save" value="Create Invoice"/>
		</td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<? if(count($items)>0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Select Scheduled Payments</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 0, 1, false)">Item Code</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 1, 0, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 2, 0, false)">Task</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 3, 0, false)">Due Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 4, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId2', 5, 0, false)">Amount</a></th>
		<th class="dg_header_cell">Include?</th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId2">
<? for($i = 0; $i < count($items); $i++){
		$total_price2 += $items[$i]->amount;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $items[$i]->getItemCodeCode() == '' ? '<b>N/A</b>' : $items[$i]->getItemCodeCode() ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->getProjectName() == '' ? '<b>N/A</b>' : $items[$i]->getProjectName() ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->getTaskName() == '' ? '<b>N/A</b>' : $items[$i]->getTaskName() ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->due_ts>0 ? date("n/j/y", $items[$i]->due_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $items[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($items[$i]->amount, 2) ?></td>
		<td class="gridActions"><input type="checkbox" name="payment_schedule_ids[]" value="<?= $items[$i]->id ?>"></td>
		<td class="gridActions">
			<a class="link1" href="payment_schedule.php?mode=edit&id=<?= $items[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit" border="0" /></a>		</td>
		<td class="gridActions">
			<a class="link1" href="payment_schedule.php?mode=delete&id=<?= $items[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete" border="0" /></a>		</td>		
	</tr>
<? }?>
	</tbody>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price2, 2) ?></td>
		<td class="gridActions"><input type="checkbox" name="select_all" onclick='SelectAll("payment_schedule_ids[]")'></td>
	    <td class="gridActions">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="8" class="form_header_cell" align="right">
			<input type="submit" name="save" value="Create Invoice"/>		</td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<? if(count($expenses)>0){ ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Select Expenses</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 0, 1, false)">Project</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 1, 0, false)">Date</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 3, 0, false)">Description</a></th>
		<th class="dg_header_cell"><a class="link1" href="" onclick="return sortTable('bodyId3', 4, 0, false)">Amount</a></th>
		<th class="dg_header_cell">Include?</th>
		<th class="dg_header_cell">Edit</th>
		<th class="dg_header_cell">Delete</th>		
	</tr>
	<tbody id="bodyId3">
<? for($i = 0; $i < count($expenses); $i++){
		$total_price3 += $expenses[$i]->price;
?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1"><?= $expenses[$i]->getProjectName() == "" ? '<b>N/A</b>' : $expenses[$i]->getProjectName() ?></td>
		<td class="dg_data_cell_1"><?= $expenses[$i]->created_ts>0 ? date("n/j/y", $expenses[$i]->created_ts) :  "" ?></td>
		<td class="dg_data_cell_1"><?= $expenses[$i]->description ?></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol().number_format($expenses[$i]->price, 2) ?></td>
		<td class="dg_data_cell_1"><input type="checkbox" name="expense_ids[]" value="<?= $expenses[$i]->id ?>"></td>
		<td class="dg_data_cell_1">
			<a class="link1" href="expense.php?mode=edit&id=<?= $expenses[$i]->id ?>"><img src="images/edit.png" width="16" height="16" title="Edit" border="0" /></a>
		</td>
		<td class="dg_data_cell_1">
			<a class="link1" href="expense.php?mode=delete&id=<?= $expenses[$i]->id ?>"><img src="images/delete.png" width="16" height="16" title="Delete" border="0" /></a>
		</td>		
	</tr>
<? }?>
	</tbody>
	<tr>
		<td colspan="3" class="form_header_cell" align="right">Total:</td>
		<td class="form_field_cell"><?= SureInvoice::getCurrencySymbol().number_format($total_price3, 2) ?></td>
		<td class="form_header_cell"><input type="checkbox" name="select_all" onclick='SelectAll("expense_ids[]")'></td>
		<td class="form_header_cell" align="right"></td>
	</tr>
	<tr>
		<td colspan="4" class="form_header_cell">
			<b>Aggregation Type:&nbsp;</b>
			<select name="expense_aggregation_type">
				<option value="<?= SI_EXPENSE_AGGREGATION_DESC ?>">Description and Price</option>
				<option value="<?= SI_EXPENSE_AGGREGATION_PRICE ?>">Price Only</option>
				<option value="<?= SI_EXPENSE_AGGREGATION_NONE ?>">No Aggregation</option>
			</select>
		</td>
		<td colspan="2" class="form_header_cell" align="right">
			<input type="submit" name="save" value="Create Invoice"/>
		</td>
	</tr>
</table>
	</div>
</div>
<? } ?>
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Add Custom Line Items</a><div>
<table border="0" cellspacing="0" cellpadding="0" class="dg_table">
	<tr>
		<th class="dg_header_cell">Item Code</th>
		<th class="dg_header_cell">Quantity</th>
		<th class="dg_header_cell">Description</th>
		<th class="dg_header_cell">Unit Price</th>
		<th class="dg_header_cell">Extended Price</th>
	</tr>
<? for($i = 0; $i < $num_custom_lines; $i++){?>
	<tr onmouseover="this.style.backgroundColor ='#CCCCCC'" onmouseout="this.style.backgroundColor ='#FFFFFF'">
		<td class="dg_data_cell_1">
			<select name="custom[<?= $i ?>][item_code_id]" ID="item_code_id_<?= $i ?>" CLASS="input_text" onChange="updateCustomLine('<?= $i ?>')">
				<option value="0">No expense</option>
				<?= SI_ItemCode::getSelectTags() ?>
			</select>
		</td>
		<td class="dg_data_cell_1"><input type="text" class="input_text" name="custom[<?= $i ?>][quantity]" id="quantity_<?= $i ?>" size="10" maxlength="20" onchange="updatePrice(<?= $i ?>)"/></td>
		<td class="dg_data_cell_1"><input type="text" class="input_text" name="custom[<?= $i ?>][description]" id="description_<?= $i ?>" size="40" maxlength="255"/></td>
		<td class="dg_data_cell_1"><input type="text" class="input_text" name="custom[<?= $i ?>][price]" id="price_<?= $i ?>" size="10" maxlength="20" onchange="updatePrice(<?= $i ?>)"/></td>
		<td class="dg_data_cell_1"><?= SureInvoice::getCurrencySymbol() ?><span id="line_total_<?= $i ?>">0.00</span></td>
	</tr>
<? }?>
	<tr>
		<td colspan="4" class="form_header_cell" align="right">Custom Line Item Total:</td>
		<td class="form_field_cell"><strong><?= SureInvoice::getCurrencySymbol() ?><span id="custom_line_total">0.00</span></strong></td>
	</tr>
	<tr>
		<td colspan="5" class="form_header_cell" align="right">
			<input type="submit" name="save" value="Create Invoice"/>
		</td>
	</tr>
</table>
	</div>
</div>

</form>
<? require('footer.php') ?>
