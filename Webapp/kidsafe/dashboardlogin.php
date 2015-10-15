<?php
/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

dashboardlogin.php - login 
- directed from dashboard.php - and similar pages
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



include ('kidsafe-config.php');
//include ('kidsafe-includes.php');

// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}

/** Warning do not run in debug on a production system - exposes more information */
//$debug = True;
if (!isset($debug)){$debug=false;}

/*** Connect to database ***/
$db = new Database($dbsettings);
$kdb = new KidsafeDB($db);

//$get_values = getPostParms();
if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

// php session - we don't check for login status if come to this page we need to relogin
$session = new DashboardSession ();


//Get parameters - check safe and return as array
// all values are included in array - even if not on url
$parms = new Parameters();

// Allow messages to be sent to the web page
$message = '';


// If we have a username & password then login attempt, if not then prompt

/** logging in **/ 
// First check that the password is correct - as otherwise we won't allow anything
// $password is already md5 encoded, as is the value in get_values so just do direct compare
if ($parms->getParm('user')!='' && $parms->getParm('password') != '')
{
	
	$username = $parms->getParm('user');
	$password = $parms->getParm('password');
	
	if ($debug) {print "Login attempt $username / $password";}
	
	// gets user object based on username
	$user = $kdb->getUserUsername($username);
	// check we got a user back 
	if ($user == null) 
	{
		if ($debug) {print "No matching user found\n";}
		loginFail('usernamepassword');
	}
	// Get username and password and check - first check shouldn't hit but additional check
	if ($user->getusername() != $username || md5($password) != $user->getPassword()) 	
	{
		if ($debug) {print "Login fail ".$user->getUsername()."\n";}	
		loginFail('usernamepassword');
	}

	
	/* don't need to be admin / supervisor - but normal user can only change password etc.*/


	// setup session
	$session->setUsername($username);

	$redirect = $parms->getParm('redirect');
	// if redirect blank then go to dashboard
	if ($redirect == '') {$redirect = "dashboard.php";}

	// redirect to page
	header("Location: ".$redirect);

}
else
{
	
	// Reach here then login details weren't provided - perhaps come direct to login page
	
	// url if not supplied will be empty - which we just forward on as empty ''
	$redirect = $parms->getParm('redirect');

	$loginprompt = <<<EOLF
<form action="dashboardlogin.php" method="post">
<input type="hidden" name="redirect" value="$redirect" />
Username: <input type="text" name="user" value="" size="30"> <br>
Password/PIN: <input type="password" name="password" value=""><br>
<input type="submit" value="Login" />
</form>
EOLF;

include("inc/dashboardheaders.php");
	
/** Provide login prompt **/
	
print <<< EOT
$header
$login_banner
$main_banner

	<div id="login">
	<h2>Login</h2>
	$message
	<p>If you have a username for the Internet access please login below.</p>
	$loginprompt
	</div>
$footer
EOT;

exit (0);

}
	
	
	
	
/******* Functions *****/


// reason - don't do anything different with the $reason yet 
// to add in future
// valid entries - usernamepassword / duration / ipaddress
function loginFail ($reason)
{
// username / password incorrect 
// not very user friendly telling the user to use the browser back button, so room for improvement in future


	include("inc/dashboardheaders.php");

	print <<< EOT
$header
$login_banner
$main_banner
<title>Username / password incorrect</title>
</head>
<body>
<h1>Username / password incorrect</h1>
<p>The supplied username or password is invalid. Use your browser back button to try again.</p>
$footer
EOT;
exit (0);

}





?>
