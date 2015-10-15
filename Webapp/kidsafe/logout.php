<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

logout.php - logout of the squid session
not related to the dashboard session
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



// used to set messages to provide to the user (eg. 'proxy not disabled for local network');
// including <br> on the end of each message will keep the messages separate for the user
$user_messages = '';

// Read in active sessions
$session_file = new SessionFile($sessionfilename);
// there are some classes for handling sessions, but we only need a basic check
// and don't want the overhead of handling all the additional classes and methods
// when this doesn't need that level of detail - and it's a commonly used page
$sessions = split ("\n", $session_file->readFile());

$updated_session = '';

// read through each session - drop any that match this IP address
foreach ($sessions as $thissession)
{
	// compare start of session + space with IP address of this client 
	if (strncmp($thissession, $_SERVER['REMOTE_ADDR'].' ', strlen($_SERVER['REMOTE_ADDR'])+1)!=0)
	{
		$updated_session .= $thissession."\n";
	}
}
$session_file->writeFile($updated_session);

// setup and load headers
$title = "Kidsafe - logout";
include("inc/headers.php");

print <<< EOT
$header
$start
<h1>Logged out</h1>

<div id="intro">
<ul>
<li><a href="index.php">Index page</a></li>
</ul>
</div>

$footer
EOT;
?>



