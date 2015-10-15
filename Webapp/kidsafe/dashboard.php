<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

dashboard.php - The dashboard for managing kidsafe
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

include ('kidsafe-config.php');		// configuration (eg. mysql login)


// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}


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

$parms = new Parameters();
// valid messages
// newpass, nopermission, parameter
if ($parms->getParm('message') == 'newpass')
{
	$user_messages .= "Password successfully changed\n";
}
elseif ($parms->getParm('message') == 'nopermission')
{
	$user_messages .= "Insufficient permission\n";
}
elseif ($parms->getParm('message') == 'parameter')
{
	$user_messages .= "Missing or invalid parameter\n";
}

// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=dashboard.php&message=notuser");
	exit (0);
}

// Username used to display back to user
$username = $user->getUsername();

// don't need to be admin - but limited features for normal users (eg. change password)


include("inc/dashboardheaders.php");

// set to blank if we are not an admin - change if we are
$html_admin = '';

// admin features
if ($user->isAdmin())
{
	// activeusers - does not show anything until refer to $html_session
	include("inc/activeusers.php");
	$html_admin = "<div id=\"activeuserdiv\">\n        <h2>Users logged in</h2>\n        $html_session\n    </div>\n";
}
	


// don't use $header - as custom javascript code required
print <<< EOT
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kidsafe dashboard</title>
<link href="kidsafe.css" rel="stylesheet" type="text/css">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="dashboard.js"></script>
</head>
<body>


$login_banner
$main_banner
$main_menu

	
<div id="content">
	<h1>Kidsafe dashboard</h1>
	
	<p>$user_messages</p>

	<div id="intro">
		<p>Kidsafe configuration dashboard</p>
	</div>
	
	$html_admin

</div>

$footer
EOT;
?>



