<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

password.php - Change user password (own user)
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
	header("Location: dashboardlogin.php?redirect=password.php");
	exit (0);
}

// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=password.php&message=notuser");
	exit (0);
}

// Username used to display back to user
$username = $user->getUsername();

$parms = new Parameters();
// Do we have a password (old, new and repeat new)
if ($parms->getParm('password')!='')
{
	$password = $parms->getParm('password');
	// check that password is correct
	if (md5($password) != $user->getPassword()) {passwordChg("Incorrect password");}
	
	// check password matches
	if ($parms->getParm('newpassword') != $parms->getParm('repeatpassword')) {passwordChg("Passwords do not match");}
	$newpassword = $parms->getParm('newpassword');

	// set password in database
	$kdb->setUserPassword ($username, md5($newpassword));

	// redirect to dashboard page - with message password changed
	header("Location: dashboard.php?message=newpass");
}
else
{
	passwordChg($user_messages, $username);
}


include("inc/dashboardheaders.php");

function passwordChg ($message, $username)
{
	print <<< EOT

$header	
$login_banner
$main_banner
$main_menu

<h1>Kidsafe change password</h1>

<div id="intro">
	<p>$message</p>
	<form action="dashboardpassword.php" method="post">
	Current password: <input type="password" name="password" value="" size="30"> <br>
	New password: <input type="password" name="newpassword" value="" size="30"> <br>
	Repeat password: <input type="password" name="repeatpassword" value="" size="30"> <br>
	<input type="submit" value="Change password" />
	</form>
	
	    
</div>

$footer
EOT;
exit (0);
}

?>



