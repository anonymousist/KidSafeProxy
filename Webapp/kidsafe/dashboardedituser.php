<?php

/** 
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

edituser.php - Editing a user
**


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

include ('kidsafe-config.php');		// configuration (eg. mysql login)


// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}


//$debug=true;

/*** Connect to database ***/
$db = new Database($dbsettings);
$kdb = new KidsafeDB($db);

if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

// used to set messages to provide to the user (eg. 'proxy not disabled for local network');
// including <br> on the end of each message will keep the messages separate for the user
$user_messages = '';



/** Check for login - or redirect to login.php **/
$session = new DashboardSession();
// are we logged in already?
if ($session->getUsername() == '') 
{
	//If not redirect to login page - then redirect here
	header("Location: dashboardlogin.php?redirect=dashboard.php");
	exit (0);
}


// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=dashboard.php&message=notuser");
	exit (0);
}
// need to be admin - otherwise redirect to dashboard - supervisor cannot edit, but can reset password - except for admin user
elseif (!$user->isAdmin())
{
	header("Location: dashboard.php?message=nopermission");
	exit (0);
}	

// Username used to display back to user
$username = $user->getUsername();




$parms = new Parameters();
// valid messages
// newpass, nopermission
if ($parms->getParm('action') == 'save')
{
	// Saved changed entry
	$this_username = $parms->getParm('username');
	// if not supplied id then go to dashboard
	if ($this_username == "")
	{
		header("Location: dashboard.php?message=parameter");
		exit (0);
	}	
	// returns user object - use to check that username is valid
	$this_user = $kdb->getUserUsername($this_username);
	if ($this_user == null)
	{
		header("Location: dashboard.php?message=parameter");
		exit (0);	
	}
	// confirmed that user exists
	
	
	$this_user->setFullname($parms->getParm('fullname'));
	$this_user->setAccess($parms->getParm('access'));
	$this_user->setEnabled($parms->getParm('status'));
	// expiry is a value in secs - no need to convert
	$this_user->setLoginexpiry($parms->getParm('loginexpiry'));
	$this_user->setSupervisor($parms->getParm('supervisor'));
	$this_user->setAdmin($parms->getParm('admin'));
	
	// note no password - we keep whatever is in the current entry (use chguserpw.php)
	
	// save entry
	$kdb->updateUser($this_user);
	
	// users are always checked against db so no need to generate file
	// Will now continue with page as though loading new - so in effect reload entry
}


// load current entry and show edit - loads even after save

$this_username = $parms->getParm('username');
// if not supplied id then go to dashboard
if ($this_username == "")
{
	header("Location: dashboard.php?message=parameter");
	exit (0);
}	

// returns ruleobject $this_rule
$this_user = $kdb->getUserUsername($this_username);

if ($this_user == null)
{
	header("Location: dashboard.php?message=parameter");
	exit (0);	
}

$html_form = "<form action=\"dashboardedituser.php\" method=\"post\">\n";
$html_form .= "<input type=\"hidden\" name=\"action\" value=\"save\">\n";
$html_form .= "<input type=\"hidden\" name=\"username\" value=\"$this_username\">\n";

$html_form .= "Username: $this_username<br>\n";

$html_form .= "Full name: <input type=\"text\" name=\"fullname\" value=\"".$this_user->getFullname()."\"><br>\n";

$html_form .= "Access <select name=\"access\">\n";
foreach ($userlevelnames as $key=>$value)
{
	$html_form .= "<option value=\"$key\"".(($this_user->getAccess() == $key)? "selected=\"selected\"":"").">$value</option>\n";
}
$html_form .= "</select><br>\n";

// don't show password (have another link for these from listusers.php)

// status = Enabled
$html_form .= "Enabled <select name=\"status\">\n";
$html_form .= "<option value=\"1\"".(($this_user->isEnabled())? "selected=\"selected\"":"").">True</option>\n";
$html_form .= "<option value=\"0\"".(($this_user->isEnabled())?"": "selected=\"selected\"").">False</option>\n";

$html_form .= "</select><br>\n";

// int - secs max login time
$html_form .= "Login expiry: <input type=\"text\" name=\"loginexpiry\" value=\"".$this_user->getLoginexpiry()."\"><br>\n";


// supervisor
$html_form .= "Supervisor <select name=\"supervisor\">\n";
$html_form .= "<option value=\"1\"".(($this_user->isSupervisor())? "selected=\"selected\"":"").">True</option>\n";
$html_form .= "<option value=\"0\"".(($this_user->isSupervisor())?"": "selected=\"selected\"").">False</option>\n";
$html_form .= "</select><br>\n";



// admin
$html_form .= "Admin <select name=\"admin\">\n";
$html_form .= "<option value=\"1\"".(($this_user->isAdmin())? "selected=\"selected\"":"").">True</option>\n";
$html_form .= "<option value=\"0\"".(($this_user->isAdmin())?"": "selected=\"selected\"").">False</option>\n";
$html_form .= "</select><br>\n";


$html_form .= "<input type=\"submit\" value=\"Save\"><br>\n";
$html_form .= "</form>";

include("inc/dashboardheaders.php");

print <<< EOT

$header
$login_banner
$main_banner
$main_menu
<h1>Kidsafe - edit user</h1>

<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe - edit user</p>
	$html_form
</div>


$footer
EOT;
?>



