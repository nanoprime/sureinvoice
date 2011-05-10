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
require_once("includes/common.php");

$_SESSION['userObj'] = '';

if($_POST['login_email'] && $_POST['login_password']){
	$email = $_POST['login_email'];
	$password = md5($_POST['login_password']);
}else if($_COOKIE['sure_invoice_id_cookie'] && $_COOKIE['sure_invoice_password_cookie']){
	$email = $_COOKIE['sure_invoice_id_cookie'];
	$password = $_COOKIE['sure_invoice_password_cookie'];
}

if(!empty($email) && !empty($password)){
	if(loginUser($email, $password)){
		if(isset($_POST['set_cookie'])){
			# Install cookie that expires in one year
			$expires = time()+31104000;
			setcookie("sure_invoice_id_cookie", $email, $expires);
			setcookie("sure_invoice_password_cookie", $password, $expires);
		}
		if(isset($_REQUEST['referrer']) && !empty($_REQUEST['referrer'])){
			header("Location: ".$_REQUEST['referrer']."\r\n");
			exit;
		}else{
			header("Location: ".getCurrentURL($loggedin_user->start_page)."\r\n");
			exit;
		}
	}else{
		$error_msg .= "Login failure! Please try again";
	}
}else{
	if($_POST['login_submit'])
		$error_msg .= "You must provide an email address and password to login!";
}

?>
<!--
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=iso-8859-2">
	<TITLE>SureInvoice - Login</TITLE>
	<LINK HREF="templates/default/default.css" REL="stylesheet" TYPE="text/css">
</HEAD>
<BODY BGCOLOR="#FFFFFF" TEXT="#000000">

<TABLE WIDTH="100%" BORDER="0" CELLPADDING="0" CELLSPACING="0">
<TR>
	<TD>
		<IMG SRC="templates/default/sureinvoice_logo.png"  BORDER="0" ALT="LOGO">
	</TD>
</TR>
</TABLE>
<?= displayError($error_msg, 'error'); ?>
<FORM ACTION="<?= $_SERVER['PHP_SELF'] ?>" METHOD="post">
<input type="hidden" name="referrer" value="<?= $_REQUEST['referrer'] ?>"/>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" ALIGN="CENTER">
<TR>
	<TD WIDTH="479" CLASS="box_text_top">SureInvoice Login</TD>
</TR>
<TR>
	<TD CLASS="box_text_middle">
		Please login using your e-mail address and password.<BR><BR>
		<DIV ALIGN="CENTER">
			<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="2">
			<TR>
				<TD ALIGN="RIGHT"><STRONG>E-Mail Address:</STRONG></TD>
				<TD><INPUT NAME="login_email" TYPE="text" CLASS="input_text" VALUE="<?= $email ?>" SIZE="35"></TD>
			</TR>
			<TR>
				<TD ALIGN="RIGHT"><STRONG>Password:</STRONG></TD>
				<TD></TD>
			</TR>
			<TR>
				<TD COLSPAN="2" ALIGN="CENTER"><INPUT NAME="set_cookie" TYPE="checkbox" VALUE="Y">&nbsp;Remember You?<BR>(Requires cookies to be enabled)</TD>
			</TR>
			<TR>
				<TD COLSPAN="2" ALIGN="CENTER"><INPUT NAME="login_submit" TYPE="submit" CLASS="button" VALUE="Login"></TD>
			</TR>
			</TABLE><BR>
		</DIV>
	</TD>
</TR>
<TR>
		<TD CLASS="box_text_bottom">&nbsp;</TD>
	</TR>
</TABLE>
</FORM>
</BODY>
</HTML>
-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>SureInvoice</title>
<style type="text/css">
body {
	background-image: url(images/login_bg.jpg);
	background-repeat:repeat-x;
	margin:0;
	background-color: #FFFFFF;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #333333;	
}
#loginBox{
	background-image:url(images/login_form.jpg);
	width:414px;
	height:260px;
	margin:0 auto;
	margin-top:61px;
}
.loginLabel{
	display:block;
	float:left;
	width:100px;
	text-align:right;
	margin-left:25px;
	margin-bottom:4px;
	color:#263952;	
}
.loginInputs{
	width:200px;	
	margin-left:4px;
	margin-bottom:4px;
	border:1px solid #CCCCCC;
	padding:2px;
}

#loginNotice{
 	font-size:12px;
	font-weight:bold;
	color:#663333;
	margin-bottom:10px;
	display:block;
}
#loginArea{
	padding-top:70px;
	margin-left:20px;
}
#cookieNotice{
	font-size:10px;
}
#loginSubmit{
	margin-left:280px;
	width:80px;
	font-size:18px;
	color:#006699;
	margin-top:30px;
}
.error{
	color:#FF0000;
	font-weight:bold;
	margin-bottom:4px;
}
</style>
</head>
<body>
<div id="loginBox">
	<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" name="loginForm">
<input type="hidden" name="referrer" value="<?= $_REQUEST['referrer'] ?>"/>
		<div id="loginArea">
		<?  if(!$error_msg){ ?>
			<span id="loginNotice">Please login using your e-mail address and password.</span>
		<? } else { ?>
			<span id="loginNotice error"><?= displayError($error_msg, 'error'); ?></span>
		<? }?>	
			<label class="loginLabel" for="login_email">E-Mail Address:</label><input name="login_email" type="text" value="<?= $email ?>" class="loginInputs"  id="login_email"/><br />
			<label class="loginLabel" for="login_password">Password:</label><input name="login_password" type="password" class="loginInputs" id="login_password" />
			<div style="margin-left:125px;">
				<input name="set_cookie" type="checkbox" value="Y"  id="setCookie"/><label for="setCookie" id="setCookieLabel">Remember You?</label><div id="cookieNotice">(Requires cookies to be enabled)</div>
			</div>
			<input name="login_submit" type="submit" class="button" value="Login"  id="loginSubmit"/>
		</div>
	</form>
</div>
</body>
</html>
