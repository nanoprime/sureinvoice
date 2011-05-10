	</div>
</div>
	<a href="javascript:;" id="sidebarShow" onclick="Uversa.SureInvoice.SideBar.toggle(true, true)" title="Show menu" style="display:none;"></a>
	<div id="sidebar" style="display:none;">
		<a id="sidebarHider" href="javascript:;" onclick="Uversa.SureInvoice.SideBar.toggle(false, true)"></a>
		<div id="sidebarContent">
<? 	if($loggedin_user->hasRight("accounting")){?>			
<ul style="margin-bottom:10px;">
		<li><a href="accounting.php"><strong><img src="images/sidebar_accounting.png" alt="Accounting" width="16" height="16" style="float:left;margin-right:3px;" />Accounting</strong></a></li>
		<li><a href="invoice.php"><strong><img src="images/new_invoice.png" alt="Accounting" width="16" height="16" style="float:left;margin-right:3px;" />New invoice</strong></a></li>																														
</ul>
<? } ?>
			<div id="userMenu">
				<h3>User Menu</h3>
						<ul>
<? 	if($loggedin_user->isDeveloper()){?>			
							<li><a href="project_add.php">Create Project</a></li>
<? } ?>
							<li><a href="activities_log.php">Time Log</a></li>
							<li><a href="logout.php">Logout</a></li>
						</ul>
			</div>						
			<div id="recentTime">
				<!-- <a href="javascript:;" onclick="Uversa.SureInvoice.RecentTime.update()">Update</a> -->
				<h3>Recent time</h3>
					<ul id="recentTimeList">
<?				foreach($loggedin_user->getRecentTime() as $date => $data){ ?>
					<li><strong><?= $date ?></strong> - <a href="reports.php?resource_id=<?= $data['user_id'] ?>&start_ts=<?= $data['start_ts'] ?>&end_ts=<?= $data['end_ts'] ?>&save=Report"><?= $data['hours'] ?></a></li>
<? } ?>
					</ul>
			</div>
<? 	if($loggedin_user->hasRight("accounting")){?>			
			<div id="adminMenu">
				<h3>Admin menu</h3>
						<ul>
							<li><a href="setup.php">Configuration</a></li>
							<li><a href="accounts.php">Accounts</a></li>
							<li><a href="companies.php">Companies</a></li>
							<li><a href="time_import_1.php">Import Time</a></li>
							<li><a href="item_codes.php">Item Codes</a></li>
							<li><a href="notifications.php">Notifications</a></li>
							<li><a href="qb_export_all.php">Quickbooks Export</a></li>
							<li><a href="qb_import.php">Quickbooks Import</a></li>
							<li><a href="rate_structures.php">Rate Structures</a></li>
							<li><a href="sales_commission_types.php">Sales Commission Types</a></li>
							<li><a href="setup_statuses.php">Status & Priorities</a></li>
							<li><a href="users.php">Users</a></li>
							<li><a href="user_types.php">User Types</a></li>
						</ul>
			</div>						
<? } ?>
		</div>	
		<div id="sidebarBottom">&nbsp;</div>
	</div>
	<div id="footer">Logged in as <?= $loggedin_user->email ?> to SureInvoice - &copy; <?=date('Y');?> Uversa Inc.</div>
<div id="SICalContainer" style="display: hidden; position: absolute; z-index: 100;"></div>	
<div id="SIAddTAPopup" style="visibility: hidden;">
<div class="box">
	<div class="boxTitle">
		<h3>Add Time</h3><span class="boxTitleRight">&nbsp;</span><span class="boxTitleCorner">&nbsp;</span>
	</div>
	<div class="boxContent">
		<form action="json.php/addTaskActivity" method="POST">
		<table border="0" cellspacing="10" cellpadding="0" class="form_table">
		<tr>
			<td>
				<input type="hidden" name="ta_popup_task_id"  id="ta_popup_task_id" value="">
				<input type="text" class="siACInput" name="ta_popup_task_name" id="ta_popup_task_name" SIZE="50"  value=""><br />
				<div id="ta_popup_ac_container" class="siACContainer"></div>
				<select name="ta_popup_item_code_id" id="ta_popup_item_code_id" CLASS="input_text">
					<?= SI_ItemCode::getSelectTags() ?>
				</select><br>
				<input type="text" class="input_text" name="ta_popup_start_ts[date]" id="ta_popup_start_ts_date" SIZE="10" autocomplete="off">&nbsp;
				<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('ta_popup_start_ts_date', 'SIAddTAPopup', 'ta_popup_end_ts_date')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
				<input type="text" class="input_text" name="ta_popup_start_ts[time]" id="ta_popup_start_ts_time" SIZE="7" autocomplete="off">&nbsp;
				<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.currentTime('ta_popup_start_ts_date', 'ta_popup_start_ts_time', 'ta_popup_end_ts_date', 'ta_popup_end_ts_time')"><img width="16" height="16" border="0" src="images/set_time.gif"/></a>&nbsp;<br/>
				<input type="text" class="input_text" name="ta_popup_end_ts[date]" id="ta_popup_end_ts_date" SIZE="10" autocomplete="off">&nbsp;
				<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.show('ta_popup_end_ts_date', 'SIAddTAPopup')"><img width="16" height="16" border="0" src="images/dynCalendar.gif"/></a>&nbsp;
				<input type="text" class="input_text" name="ta_popup_end_ts[time]" id="ta_popup_end_ts_time" SIZE="7" autocomplete="off">&nbsp;
				<a href="javascript:;" onclick="Uversa.SureInvoice.Calendar.currentTime('ta_popup_end_ts_date', 'ta_popup_end_ts_time')"><img width="16" height="16" border="0" src="images/set_time.gif"/></a>&nbsp;<br/>
			</td>
			<td valign="top" class="form_field_cell">
				<textarea name="ta_popup_text" id="ta_popup_text" CLASS="input_text" COLS="45" ROWS="5"></textarea>
			</td>
		</tr>
		</table>
		</form>
	</div>
	<div class="boxBottom">
		<span class="boxCornerL">&nbsp;</span><span class="boxCornerR"></span>
	</div>
</div>
</div>
<div id="timers">
<div id="timers_hidden" style="display: none"> 
<a href="javascript:;" onclick="Uversa.SureInvoice.Timers.toggle(true, true)"><img src="templates/blueish/timer_bg_show.png"></a>
</div>
<div id="timers_shown" style="display: none; overflow: hidden">
<div id="running_timers">
</div>
<div class="timer" id="timer_new">
<input id="timer_new_name" name="timer_new_name" type="text" class="input" size="15" value="Enter timer name...">
<a href="javascript:;" onclick="Uversa.SureInvoice.Timers.addTimer()"><img src="templates/blueish/timer_play.png" align="top"></a>
</div>
<a href="javascript:;" onclick="Uversa.SureInvoice.Timers.toggle(false, true)"><img src="templates/blueish/timer_bg.png"></a>
</div>
</div>
</body>
</html>
