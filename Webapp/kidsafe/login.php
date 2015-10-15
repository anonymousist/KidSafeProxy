<?php
/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

login.php - login 
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


// if we restrict login expiry then give reason
$login_restrict = '';

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
	
	// duration = time to login relative time entry (eg. 2 hours) - we can login a computer forever (future), but not through this page
	if ($parms->getParm('timeallowed') == '')
	{
		loginFail('Invalid login duration');
	}
	// strtotime works out unixtime based on time (eg. +10mins adds 10 mins to time()
	$loginexpirytime = strtotime($parms->getParm('timeallowed'));
	
	// check duration against maximum permitted for this user
	if ($user->getLoginexpiry() != '0')
	{
		// if login is more than this user is allowed then we set to the user's max
		if ($loginexpirytime > time()+intval($user->getLoginexpiry()))
		{
			$loginexpirytime = time()+intval($user->getLoginexpiry());
			$login_restrict = "<p>Login restricted due to max user login length ".$user->getLoginexpiry()." secs.</p>";
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
	
	
	// get client information 
	// this is stored for the benefit of the dashboard - ignored by the proxy app helper
	$client_browser = get_browser(null, true);
	$browser_string = $client_browser['platform'].' - '.$client_browser['parent'];
	
	$login_time = time();
	
	// save session
	$session_file->addEntry ($ipaddress, $user->getAccess(), $user->getUsername(), $loginexpirytime, $login_time, $browser_string);
	
	$username = $user->getUsername();
	
	// If we get here we are logged in so can add entry
	if ($debug) {print "Login successful ".$user->getUsername()."\n";}
	
	if ($parms->getParm('url') != '')
	{
		$urllink = "<p>Follow the link below to go to the webpage, or back if no link shown.</p>\n<p><a href=\"".$parms->getParm('url')."\">".$parms->getParm('url')."</a></p>\n";
	}
	else
	{
		$urllink = '';
	}
	
	//localtime($loginexpirytime)
	$expiry_time = strftime ('%T %e %b %G', ($loginexpirytime));
	//$login_localtime = strftime ('%T %e %b %G', $login_time);
	
	// setup and load headers
	$title = "Logged in as $username";
	include("inc/headers.php");
	
			print <<<EOT
$header
$start
<h1>Logged in as $username</h1>

<!-- show time formatted as localtime if javascript enabled - otherwise as GMT -->
<script>
// create a locally formatted string - Date uses milisecs rather than secs so *1000
expiry = new Date ($loginexpirytime*1000);

// Show date and time separately as this does not display the timezone - whereas using .toLocaleString may or may not depending upon the browser - may also provide as dd/mm/yy rather than day dd MMM YYYY
document.writeln("<p>Login expires at: "+expiry.toLocaleDateString()+" "+expiry.toLocaleTimeString()+"</p>");
</script>
<noscript>
<p>Login expires at: $expiry_time - GMT</p>
</noscript>

$login_restrict
$message
$urllink
$footer
EOT;
	
	

}
else
{
	
	// Reach here then login details weren't provided - perhaps come direct to login page
	
	// url if not supplied will be empty - which we just forward on as empty ''
	$url = $parms->getParm('url');
	
	$time_select = "<select name=\"timeallowed\">";
	foreach ($timeoptions as $thistime)
	{
		$time_select .= "<option value=\"$thistime\">$thistime</option>";
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
<form action="login.php" method="post">
<input type="hidden" name="url" value="$url" />
Username: <input type="text" name="user" value="" size="30"> <br>
Password/PIN: <input type="password" name="password" value=""><br>
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
$title = "kidsafe Internet login";
include("inc/headers.php");


print <<< EOT
$header
$start
<h1>Kidsafe Internet login</h1>
<div id="mainlogin">
	<div id="login">
	<h2>Login</h2>
	$message
	<p>If you have a username for the Internet access please login below.</p>
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
	$title = "Login failed";
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


?>
