<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

getusers.php - AJAX version of showing user sessions
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

/** Parameters **/
// Possible future options (not included)
// filter maxlines order

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
	//header("Location: dashboardlogin.php?redirect=dashboard.php");
	// can't redirect within ajax so just return message
	print "Not logged in";
	exit (0);
}

$parms = new Parameters();


// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	print "Invalid user";
	exit (0);
}
// only admin / supervisor can view log
elseif (!$user->isAdmin() && !$user->isSupervisor())
{
	print "Insufficient permission";
	exit (0);
}	

// read in parameters
/* maxlines not recommended for ajax as it could result in gaps in log view although can be used to prevent excessive log entries killing browser session*/
/* Instead maxlines should be used on original, but allow multiple additional entries */
/* If used then will restrict number of lines returned within getlog */
// Not currently implemented (possible future option)
/*$maxlines = $parms->getParm('maxlines');
if ($maxlines == '') {$maxlines = 0;}*/


// Username used to display back to user
//$username = $user->getUsername();


// store all filtered log entries into an array
$logentries = array();

include ("inc/activeusers.php");

//Pass straight back as formatted table
print $html_session;
?>



