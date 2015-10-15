<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

blocked.php - redirect page from squid
- forwards to addrule.php, permitaccess.php or login.php
**/


// url - full url to redirect to 


// host - (extract from url)
// (source - client ip address) - not provided with Squid 3.2 

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

if (!isset($debug)) {$debug = false;}

include ('kidsafe-config.php');		// configuration (eg. mysql login)

// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}

/*** Connect to database ***/
$db = new Database($dbsettings);
$kdb = new KidsafeDB($db);

//Get parameters - check safe and return as object
$parms = new Parameters();

if ($db->getStatus() != 1) {die ("Unable to connect to the database");}

// used to set messages to provide to the user (eg. 'proxy not disabled for local network');
// including <br> on the end of each message will keep the messages separate for the user
$user_messages = '';

if ($parms->getParm('url') == '')
{
	$user_messages .= 'No website specified in the redirect. <br>';
}

// Do we have an IP address from the parms - if so use that, if not try and find from the server (only if no proxy set for local connections)
if ($parms->getParm('source') != '')
{
	$ip = $parms->getParm('source');
}
else
{
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
}	


$url = $parms->getParm('url');
$host = $parms->getParm('host');

// if host not set then we decode $url
if ($host == '' && $url != '') 
{
	$url_array = parse_url($url);
	if (isset($url_array['host'])) {$host = $url_array['host'];}
}

$time_select = "<select name=\"timeallowed\">";
foreach ($timeoptions as $thistime)
{
	$time_select .= "<option value=\"$thistime\">$thistime</option>";
}
// separate variable with the "Always" option added
$time_select_always = $time_select."<option value=\"Always\">Always</option></select>\n";
$time_select .= "</select>\n";

// populate login field if ip address known
// if IP address is not known then they need to bypass proxy for local address so that we can see ip address - otherwise we will not be able to authorise
$loginprompt = '';
if ($ip != '')
{
	$loginprompt = <<<EOLF
<form action="login.php" method="post">
<input type="hidden" name="url" value="$url" />
Username: <input type="text" name="user" value="" size="30"> <br>
Password/PIN: <input type="password" name="password" value=""><br>
Login for: $time_select<br>
<input type="submit" value="Login" />
</form>
EOLF;

$permitprompt = <<<EOPF
 
<form action="permitaccess.php" method="post">
<input type="hidden" name="url" value="$url" />
Increase access level <input type="text" name="allowlevel" value="" size="30"> (number)<br>
Allow for: $time_select<br>
<input type="submit" value="Permit" />
</form>
EOPF;

}
else
{
	$loginprompt = "<p>Login is disabled as IP address is unknown</p><p><a href=\"http://www.penguintutor.com/kidsafe#configureclient\">Click here for details on how to configure the browser proxy to see the local IP address</a>.";
	
	$permitprompt = "<p>Increase in permissions is disabled as IP address is unknown</p><p><a href=\"http://www.penguintutor.com/kidsafe#configureclient\">Click here for details on how to configure the browser proxy to see the local IP address</a>.";
}


// we can use this even if we don't know the client IP address as we can add to all users
//$all_groups = $kdb->getGroupsAll();
// Get templates - also add option to add to individual group
$templates = $kdb->getAllowTemplates();
$addto_list = "<select id=\"addpermission\" name=\"addpermission\">\n";
foreach ($templates as $thistemplate)
{
	$addto_list.="<option value=\"".$thistemplate->getName()."\">".$thistemplate->getName()."</option>\n";
}
// add additional entry for "add group"
$addto_list .= "<option value=\"custom\">Add individual group</option>\n";
$addto_list .= "</select>";



$addprompt = <<<EOAF

<form action="addrule.php" method="post">
<input type="hidden" name="url" value="$url" />
<input type="hidden" name="host" value="$host" />
Add host: $host <br>
Add to: $addto_list <br>
Allow for: $time_select_always<br>
<input type="submit" value="Add rule" />
</form>
EOAF;

// setup and load headers
$title = "kidsafe - Website blocked";
include("inc/headers.php");


print <<< EOT
$header
$start
<h1>Website blocked</h1>

<div id="intro">
	<p>The website you are visiting is not one of the safe sites.</p>
	<p>$user_messages</p>
	<p>Site is $url</p>
</div>

<div id="mainlogin">
	<div id="login">
	<h2>Login</h2>
	<p>If you have a username for the Internet access please login below.</p>
	$loginprompt
	</div>
</div>

<div id="adminaccess">
	<div id="permituser">
	<h2>Allow user access</h2>
	<p>If you would like to view this site<br>
	just this once then<br>
	please ask your $adminname to allow this<br>
	using the form below:</p>
	$permitprompt
	</div>
	
	<div id="allowsite">
	<h2>Add website access</h2>
	<p>If you would like to be able to visit this site<br>
	now and in future please ask your $adminname to give permission<br>
	using the form below:</p>
	$addprompt
	</div>
</div>

$footer
EOT;
?>



