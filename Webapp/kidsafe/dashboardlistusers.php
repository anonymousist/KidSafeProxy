<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

listusers.php - List all kidsafe users
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
	header("Location: dashboardlogin.php?redirect=listrules.php");
	exit (0);
}

$parms = new Parameters();
// valid messages
if ($parms->getParm('message') == 'unknownuser')
{
	$user_messages .= "User invalid\n";
}

// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=listusers.php&message=notuser");
	exit (0);
}
// admin can edit supervisor can view, but not edit
elseif (!$user->isAdmin() && !$user->isSupervisor())
{
	header("Location: dashboard.php?message=nopermission");
	exit (0);
}	

// Username used to display back to user
$username = $user->getUsername();

$all_users = $kdb->getUsersAll();


$list_users = "<table>\n";
// don't show - password, expiry (ie max login time)
$list_users .= "<tr><th>Username</th><th>Full name</th><th>Access level</th><th>Active</th><th>Supervisor</th><th>Admin</th><th>&nbsp;</td></tr>";

foreach ($all_users as $this_user)
{
	$list_users .= "<tr>\n";
	// only show link to edit if admin
	if ($user->isAdmin())
	{
		$list_users .= "<td><a href=\"dashboardedituser.php?username=".$this_user->getUsername()."\">".$this_user->getUsername()."</a></td>\n";
	}
	else
	{
		$list_users .= "<td>".$this_user->getUsername()."</td>\n";
	}
	$list_users .= "<td>".$this_user->getFullname()."</td>\n";
	
	// If access is defined under the friendly names show that - otherwise show the number
	$list_users .= "<td>".(isset($userlevelnames[$this_user->getAccess()])?$userlevelnames[$this_user->getAccess()]:$this_user->getAccess())."</td>\n";
	$list_users .= "<td>".($this_user->isEnabled()? 'True' : 'False')."</td>\n";
	$list_users .= "<td>".($this_user->isSupervisor()? 'True' : 'False')."</td>\n";	 
	$list_users .= "<td>".($this_user->isAdmin()? 'True' : 'False')."</td>\n";	
	
	// don't allow supervisor to change admin password, but can change others
	if ($user->isAdmin() || !$this_users->isAdmin())
	{
		$list_users .= "<td><a href=\"dashboardchguserpw.php?username=".$this_user->getUsername()."\">Change password</a></td>\n";
	}
	else
	{
		$list_users .= "<td>&nbsp;</td>\n";
	}
	
	$list_users .= "</tr>\n";
}

$list_users .= "</table>\n";


$add_user = '';
// display add user button only to admins
if ($user->isAdmin())
{
	$add_user = '<ul><li><a href="dashboardadduser.php">Add new user</a></li></ul>';
}

include("inc/dashboardheaders.php");

print <<< EOT

$header
$login_banner
$main_banner
$main_menu
<h1>Kidsafe dashboard - List users</h1>


<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe dashboard - List users</p>
	$list_users
</div>

<div>
	$add_user
</div>


$footer
EOT;
?>



