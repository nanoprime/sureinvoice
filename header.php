<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>SureInvoice - By UVERSA</title>
<link rel="icon" href="favicon.ico" type="image/x-icon" /> 
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" /> 
<script language="javascript" type="text/javascript" src="javascript.php?libs=yui.dom,yui.event,yui.ext.DomHelper,yui.container,yui.ext.EventManager,yui.autocomplete,yui.ext.JSON,yui.calendar"></script>
<script language="javascript" type="text/javascript" src="includes/javascript/sureinvoice.js"></script>
<script language="javascript" type="text/javascript" src="includes/javascript/tablesort.js"></script>
<script language="javascript" type="text/javascript">
var gSideBarOpen = <?= $loggedin_user->show_menu == 1 ? 'true' : 'false' ?>;
var gTimersOpen = <?= $loggedin_user->show_timers == 1 ? 'true' : 'false' ?>;
</script>
<link href="templates/blueish/styles.css" rel="stylesheet" type="text/css" media="screen"/>
<link href="templates/blueish/print.css" rel="stylesheet" type="text/css" media="print"/>
<!--[if IE]>
<style type="text/css">
	* { behavior: url(attributes.htc); }
	.gridToolbar{
		display: inline;
	}
</style>
<![endif]-->
</head>
<body>
<div id="header"><img src="images/si_logo.jpg" alt="SureInvoice" name="logo" width="250" height="83" id="logo"/>
<?
$html_tab_selected = basename($_SERVER['SCRIPT_NAME']);
?>
	<ul id="nav">
		<li><a href="<?= $loggedin_user->start_page ?>" <? if($html_tab_selected == $loggedin_user->start_page){echo 'class="navSelected"';}; ?>><span>Home</span></a></li>	
<? 	if($loggedin_user->isDeveloper()){?>		
		<li><a href="time_entry.php" <? if($html_tab_selected == "time_entry.php"){echo 'class="navSelected"';}; ?>><span>Enter Time</span></a></li>
		<li><a href="javascript:;" id="SIOpenTADialog"><span>Quick Add</span></a></li>
		<li><a href="my_projects.php" <? if($html_tab_selected == "my_projects.php"){echo 'class="navSelected"';}; ?>><span>My Projects</span></a></li>
		<li><a href="cal_month.php" <? if($html_tab_selected == "cal_month.php"){echo 'class="navSelected"';}; ?>><span>Calendar</span></a></li>		
<?	} ?>
		<li><a href="reports.php" <? if($html_tab_selected == "reports.php"){echo 'class="navSelected"';}; ?>><span>Reports</span></a></li>		
		<li><a href="logout.php"><span>Logout</span></a></li>		
	</ul>
</div>
<div id="wrapper">
	<div id="main">
	<?= displayError($error_msg, 'error'); ?>
