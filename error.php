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
//require_once("includes/common.php");

if(isset($_SESSION['last_error_message'])){
	$error_msg = $_SESSION['last_error_message'];
	unset($_SESSION['last_error_message']);
}else if(isset($_REQUEST['error'])){
	$error_msg = $_REQUEST['error'];
}else{
	$error_msg = "We are sorry, an error has occured while processing your request, please try again later.\n\n".
			"The system administrator has been notified of the problem.\n";
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>SureInvoice - By UVERSA</title>
<link rel="icon" href="favicon.ico" type="image/x-icon" /> 
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" /> 
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
</div>
<div id="wrapper">
	<div id="main">
<div class="tableContainer">
<a href="javascript:;" class="tCollapse" onclick="toggleGrid(this)"><img src="images/arrow_down.jpg" alt="Hide table" />Fatal Error</a><div>
	
		<DIV ALIGN="CENTER" class="error">
		<?= stripslashes($error_msg) ?>
		</DIV>
</div></div>
	</div>
</div>
<div id="footer">SureInvoice - &copy; <?=date('Y');?> Uversa Inc.</div>
</BODY>
</HTML>
