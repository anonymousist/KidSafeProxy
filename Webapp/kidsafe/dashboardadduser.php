<?php
/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

adduser.php - add new user
**/

/*
This file is part of kidsafe.

kidsafe is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

kidsafe is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with kidsafe.  If not, see <http://www.gnu.org/licenses/>.
*/

//$debug = true;

if (!isset($debug)) {$debug = false;}

include ('kidsafe-config.php');

// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}

/*** Connect to database ***/
$db = new Database($dbsettings);
$kdb = new KidsafeDB($db);

if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

// Get parameters - check safe and return as object
// all values are included in array - even if not on url
$parms = new Parameters();

// used to set messages to provide to the user 
// including <br> on the end of each message will keep the messages separate for the user
$user_messages = '';

if ($parms->getParm('message') == 'passwordmatch')
{
	$user_message .= 'Passwords do not match<br>';
}


/** Check for login - or redirect to login.php **/
$session = new DashboardSession();
// are we logged in already?
if ($session->getUsername() == '') 
{
	//If not redirect to login page - then redirect here
	header("Location: dashboardlogin.php?redirect=adduser.php");
	exit (0);
}

// create user object - this is local user - not the one we are adding
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=password.php&message=notuser");
	exit (0);
}
// only admin can add user
elseif (!$user->isAdmin())
{
	header("Location: dashboard.php?message=nopermission");
	exit (0);
}	
// use this in future to show username (eg. logout link)
$username = $user->getUsername();

// If we have a username then adding entry, if not then prompt what to add
/** Adding entry **/ 
if ($parms->getParm('username')!='')
{
	// create user object 
	$this_user = new User();
	if ($this_user == null)
	{
		header("Location: dashboard.php?message=parameter");
		exit (0);	
	}

	$this_user->setUsername($parms->getParm('username'));
	
	$this_user->setFullname($parms->getParm('fullname'));
	
	// check password matches
	if ($parms->getParm('newpassword') != $parms->getParm('repeatpassword')) {
		// in future should instead recreate form with rest of details readded
		header("Location: adduser.php?message=passwordmatch");
		exit (0);
	}
	$this_user->setPassword(md5($parms->getParm('newpassword')));
	
	$this_user->setAccess($parms->getParm('access'));
	$this_user->setEnabled($parms->getParm('status'));
	// expiry is a value in secs - no need to convert
	$this_user->setLoginexpiry($parms->getParm('loginexpiry'));
	$this_user->setSupervisor($parms->getParm('supervisor'));
	$this_user->setAdmin($parms->getParm('admin'));
	
	
	// save entry
	$kdb->insertUser($this_user);
	
	include("inc/dashboardheaders.php");
	
	print <<<EOT
$header
$login_banner
$main_banner
$menu_banner
$main_menu

<h1>New user added</h1>
<p>Click below to see all users.</p>
<p><a href="listusers.php">Dashboard - List Users</a></p>.
$footer
EOT;
	exit (0);

	

}
/** Get user details **/
else
{	
	$html_form = "<form action=\"dashboardadduser.php\" method=\"post\">\n";
	
	$html_form .= "Username: <input type=\"text\" name=\"username\" value=\"\"><br>\n";
	
	$html_form .= "Full name: <input type=\"text\" name=\"fullname\" value=\"\"><br>\n";
	
	$html_form .= "Access <select name=\"access\">\n";
	foreach ($userlevelnames as $key=>$value)
	{
		$html_form .= "<option value=\"$key\">$value</option>\n";
	}
	$html_form .= "</select><br>\n";
	
	// don't show password (have another link for these from listusers.php)
	$html_form .= "Password: <input type=\"password\" name=\"newpassword\" value=\"\" size=\"30\"> <br>\n";
	$html_form .= "Repeat password: <input type=\"password\" name=\"repeatpassword\" value=\"\" size=\"30\"> <br>\n";
	
	
	// status = Enabled
	$html_form .= "Enabled <select name=\"status\">\n";
	$html_form .= "<option value=\"1\">True</option>\n";
	$html_form .= "<option value=\"0\">False</option>\n";
	$html_form .= "</select><br>\n";
	
	// int - secs max login time
	$html_form .= "Login expiry: <input type=\"text\" name=\"loginexpiry\" value=\"\"><br>\n";
	
	// supervisor
	$html_form .= "Supervisor <select name=\"supervisor\">\n";
	$html_form .= "<option value=\"1\">True</option>\n";
	$html_form .= "<option value=\"0\" selected=\"selected\">False</option>\n";
	$html_form .= "</select><br>\n";
	
	// admin
	$html_form .= "Admin <select name=\"admin\">\n";
	$html_form .= "<option value=\"1\">True</option>\n";
	$html_form .= "<option value=\"0\" selected=\"selected\">False</option>\n";
	$html_form .= "</select><br>\n";
	
	
	$html_form .= "<input type=\"submit\" value=\"Save\"><br>\n";
	$html_form .= "</form>";
	
	include("inc/dashboardheaders.php");
	
	print <<< EOT
$header
$login_banner
$main_banner
$menu_banner
$main_menu
<h1>Kidsafe - add user</h1>

<p style="float:right"><a href="dashboardlogout.php">Logout $username</a></p>

<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe - edit user</p>
	$html_form
</div>


$footer
EOT;
	exit (0);

}
	


?>
