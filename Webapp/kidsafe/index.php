<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

index.php - quick urls and logout option
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
// Use for getting appropriate permissions based on logged in session
//$db = new Database($dbsettings);
//$kdb = new KidsafeDB($db);

//if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

// used to set messages to provide to the user (eg. 'proxy not disabled for local network');
// including <br> on the end of each message will keep the messages separate for the user
$user_messages = '';

// Read in active sessions
$session_file = new SessionFile($sessionfilename);
// there are some classes for handling sessions, but we only need a basic check
// and don't want the overhead of handling all the additional classes and methods
// when this doesn't need that level of detail - and it's a commonly used page
$sessions = split ("\n", $session_file->readFile());

// note need to check that ip is not the same as the proxy (in which case they haven't set bypass for local
$ip=$_SERVER['REMOTE_ADDR'];
if ($ip == $_SERVER['SERVER_ADDR'])
{
	// if nolocal then don't allow login
	if ($nolocal == True)
	{
		$ip = '';
	}
	// if nolocal false then allow tunnelled proxy connections
	// We add warning in either case which can prompt user if it doesn't work
	$user_messages .= 'WARNING: Please set your browser to bypass proxy for local network to allow login. <br>';
}


// don't provide a logout option if we are not logged in
$logout_text = '';

// check to see if a user is logged in
$logged_in = false;
foreach ($sessions as $thissession)
{
	// compare start of session with IP address of this client + space
	if (!strncmp($thissession, $_SERVER['REMOTE_ADDR'].' ', strlen($_SERVER['REMOTE_ADDR'])+1))
	{
		$logged_in = true;
		break;
	}
}

// used in login page
$time_select = "<select name=\"timeallowed\">";
foreach ($timeoptions as $thistime)
{
	$time_select .= "<option value=\"$thistime\">$thistime</option>";
}
$time_select .= "</select>\n";



$loginprompt = '';

// If logged in give a logout option
if ($logged_in) 
{
	$logout_text = "<a href=\"logout.php\">logout</a>\n";
}
// if not give a login option
else
{
	// populate login field if ip address known
	// if IP address is not known then they need to bypass proxy for local address so that we can see ip address - otherwise we will not be able to authorise
	$thisurl = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
	

	if ($ip != '')
	{
		$loginprompt = <<<EOLF
	<form action="login.php" method="post">
	<input type="hidden" name="url" value="$thisurl" />
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

}



// In future we can add user friendly links to preferred sites here
$indexpage = "";

// only show loginform if not logged in
if ($loginprompt != '')
{
	$loginform = "<h2>Login</h2>\n	<p>If you have a username for the Internet please login below.</p>$loginprompt\n";
}

// setup and load headers
$title = "Kidsafe - Making the Internet safer";
include("inc/headers.php");


print <<< EOT
$header
$start
<h1>Kidsafe - Making the Internet safer</h1>


<p style="float:right">$logout_text</p>

<div id="messages">
$user_messages
</div>

<div id="mainlogin">
	<div id="login">
	$loginprompt
	</div>
</div>

<div id="management">
<h2>Manage your account</h2>
<ul>
<li><a href="dashboard.php">Dashboard</a></li>
</ul>

</div>

$footer
EOT;
?>



