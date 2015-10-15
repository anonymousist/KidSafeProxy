<?php
/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

permitaccess.php - pseudo login
This allows a supervisor to give temporary elevated permissions to another user
User must have supervisor = true
- directed from blocked.php
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


// Initial parms
// url
// allowlevel
// timeallowed


// After authentication also have
// username
// password
// duration - relative time entry (eg. 2 hours) - we can login a computer forever, but not through this


include ('kidsafe-config.php');


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

$session_file = new SessionFile ($sessionfilename);

//Get parameters - check safe and return as array
// all values are included in array - even if not on url
$parms = new Parameters();
//$get_values = getPostParms();
if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

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
	
	// check we have sufficient permission - ie. supervisor
	if (!$user->isSupervisor())
	{
		noPermission();
	}
	
	
	// check permission (only tested for int - so need to check it's between 1 (no point in 0) and 9
	// we don't check this until after checking supervisor - must be valid login from admin first
	// don't allow upgrade to 10
	$permission_req = $parms->getParm('allowlevel');
	if ($permission_req < 1 || $permission_req > 9) {noPermission();}
	
	
	// duration = time to login relative time entry (eg. 2 hours) - do allow login a computer forever, but not through this page
	if ($parms->getParm('timeallowed') == '')
	{
		loginFail('Invalid login duration');
	}
	$loginexpirytime = strtotime($parms->getParm('timeallowed'));
	
	// Note that duration is set to maximum of the admin user rather than the regular user
	// We don't neccessarily know who the other user is to be able to apply specific user details in any meaningful way
	// check duration against maximum permitted for this user
	if ($user->getLoginexpiry() != 0)
	{
		// if login is more than this user is allowed then we set to the admin user's max
		if ($loginexpirytime > time()+$user->getLoginexpiry())
		{
			$loginexpirytime = time()+$user->getLoginexpiry();
		}
	}
	
	// Get IP address from the server - which means they must have excluded proxy for local access
	// can't neccessarily trust user provided ip address, although perhaps in future may need to add option to get address from user in case they can't exclude proxy (eg. Midori)
	$ipaddress = $_SERVER['REMOTE_ADDR'];
	// check this isn't the local ip address on the 
	if ($ipaddress == $_SERVER['SERVER_ADDR'])
	{
		if ($nolocal == True)
		{
			// shouldn't get here as blocked.php will have provided the reconfigure proxy message
			print "You must configure bypass proxy for local networks in your web-browser\n";
			exit (0);
		}
		// even if local is allowed we still provide a warning in case it's a misconfigured client.
		$message .= "<p>WARNING: If you are not on the proxy computer please set your browser to bypass proxy for local network to allow login. </p>\n";
	}
	
	// save session
	$session_file->addEntry ($ipaddress, $permission_req, $user->getUsername()."-".$permission_req, $loginexpirytime);
	
	// username is set to adminuser followed by - and permission level eg. admin-4
	$username = $user->getUsername()."-".$permission_req;
	
	// If we get here we are logged in so can add entry
	if ($debug) {print "Login successful $username \n";}
	
	if ($parms->getParm('url') != '')
	{
		$urllink = "<p>Follow the link below to go to the webpage, or back if no link shown.</p>\n<p><a href=\"".$parms->getParm('url')."\">".$parms->getParm('url')."</a></p>\n";
	}
	else
	{
		$urllink = '';
	}
	
	
	// setup and load headers
	$title = "Kidsafe logged in as $username";
	include("inc/headers.php");

	
	print <<<EOT
$header
$start
<h1>Logged in as $username</h1>
$message
$urllink
$footer
EOT;
	
	

}
else
{
	
	// Reach here then login details weren't provided - ie initial click through
	
	// url if not supplied will be empty - which we just forward on as empty ''
	$url = $parms->getParm('url');
	$req_level = $parms->getParm('allowlevel');
	$timeallowed = $parms->getParm('timeallowed');
	
	$time_select = "<select name=\"timeallowed\">";
	foreach ($timeoptions as $thistime)
	{
		if ($thistime == $timeallowed)
		{
			$time_select .= "<option value=\"$thistime\" selected=\"selected\">$thistime</option>";
		}
		else
		{
			$time_select .= "<option value=\"$thistime\">$thistime</option>";
		}
	}
	$time_select .= "</select>\n";

	// Check user proxy settings
	if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR'] || $nolocal == False)
	{
		// add warning message if it's a local entry
		if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) 
		{
			$message .= "<p>WARNING: If you are not on the proxy computer please set your browser to bypass proxy for local network to allow login. </p>\n";
		}
		
		$loginprompt = <<<EOLF
<form action="permitaccess.php" method="post">
<input type="hidden" name="url" value="$url" />
Username: <input type="text" name="user" value="" size="30"> <br>
Password/PIN: <input type="password" name="password" value=""><br>
Increase access level <input type="text" name="allowlevel" value="$req_level" size="30"> (number)<br>
Login for: $time_select<br>
<input type="submit" value="Login" />
</form>
EOLF;
	}
	else 
	{
		$loginprompt = "<p>Login is disabled as IP address is unknown</p><p><a href=\"http://www.penguintutor.com/kidsafe#configureclient\">Click here for details on how to configure the browser proxy to see the local IP address</a>.";
	}
	
	
/** Provide login prompt **/

// setup and load headers
$title = "kidsafe - Website blocked";
include("inc/headers.php");


print <<< EOT
$header
$start
<h1>Website blocked</h1>
<div id="mainlogin">
	<div id="login">
	<h2>Login</h2>
	$message
	<p>Login below to authorise increased permission level.</p>
	$loginprompt
	</div>
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

// setup and load headers
$title = "Kidsafe Username / password incorrect";
include("inc/headers.php");


print <<< EOT
$header
$start
<h1>Username / password incorrect</h1>
<p>The supplied username or password is invalid. Use your browser back button to try again.</p>
$footer
EOT;
exit (0);

}



function noPermission ()
{

// not very user friendly telling the user to use the browser back button, so room for improvement in future
// correct a for 'an' if adminname begins with a vowel (only check lower case - as if capital then probably gramatically incorrect anyway
if ($adminname.startswith('a') || $adminname.startswith('e') || $adminname.startswith('i') || $adminname.startswith('o') || $adminname.startswith('u'))
{
	$adminref = "an $adminame";
}
else
{
	$adminref = "a $adminname";
}

// setup and load headers
$title = "Kidsafe - Insufficient permission";
include("inc/headers.php");


	print <<< EOT
$header
$start
<h1>Insufficient permission</h1>
<p>You do not have sufficient permissions to provide this access. Only $adminref can increase the permission.</p>
$footer
EOT;
exit (0);
}


function noLevel ()
{

// not very user friendly leaving the user to use the browser back button, so room for improvement in future

// setup and load headers
$title = "Kidsafe Invalid level";
include("inc/headers.php");


	print <<< EOT
$header
$start
<h1>Invalid level</h1>
<p>Invalid level specified. Please choose a permission level between 1 and 9 (9 is highest).</p>
$footer
EOT;
exit (0);
}


?>
