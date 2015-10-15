<?php

/** 
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

editrule.php - Editing a rule
**


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


//$debug=true;

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


// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=dashboard.php&message=notuser");
	exit (0);
}
// need to be admin - otherwise redirect to dashboard
elseif (!$user->isAdmin())
{
	header("Location: dashboard.php?message=nopermission");
	exit (0);
}	

// Username used to display back to user
$username = $user->getUsername();




$parms = new Parameters();
// valid messages
// newpass, nopermission
if ($parms->getParm('action') == 'save')
{
	// Saved changed entry
	$this_id = $parms->getParm('id');
	// if not supplied id then go to dashboard
	if ($this_id == "")
	{
		header("Location: dashboard.php?message=parameter");
		exit (0);
	}	
	// returns ruleobject $this_rule - use to check that id is valid
	$site = $kdb->getSiteSiteid($this_id);
	if ($site == null)
	{
		header("Location: dashboard.php?message=parameter");
		exit (0);	
	}
	// confirmed that site exists
	
	$site->setId($this_id);
	
	// sitename (host)
	$site->setSitename($parms->getParm('sitename'));
	// comments
	$site->setTitle($parms->getParm('title'));
	// comments
	$site->setComments($parms->getParm('comments'));
	
	// save entry
	$kdb->updateSite($site);
	
	// Updated site so regenerate the kidsafe.rules file
	$rules_file = new File ($rulesfilename);
	$rules_file->writeFile($kdb->getRulesFile());
	// Will now continue with page as though loading new - so in effect reload entry
}


// load current entry and show edit - loads even after save

$this_id = $parms->getParm('id');
// if not supplied id then go to dashboard
if ($this_id == "")
{
	header("Location: dashboard.php?message=parameter");
	exit (0);
}	

// returns ruleobject $this_rule
$this_site = $kdb->getSiteSiteid($this_id);

if ($this_site == null)
{
	header("Location: dashboard.php?message=parameter");
	exit (0);	
}

$html_form = "<form action=\"dashboardeditsite.php\" method=\"post\">\n";
$html_form .= "<input type=\"hidden\" name=\"action\" value=\"save\">\n";
$html_form .= "<input type=\"hidden\" name=\"id\" value=\"$this_id\">\n";

$html_form .= "Host: <input type=\"text\" name=\"sitename\" value=\"".$this_site->getSitename()."\"><br>\n";
$html_form .= "Title: <input type=\"text\" name=\"title\" value=\"".$this_site->getTitle()."\"><br>\n";
$html_form .= "Comments: <input type=\"text\" name=\"comments\" value=\"".$this_site->getComments()."\"><br>\n";

$html_form .= "<input type=\"submit\" value=\"Save\"><br>\n";
$html_form .= "</form>";

include("inc/dashboardheaders.php");

print <<< EOT

$header
$login_banner
$main_banner
$main_menu

<h1>Kidsafe - edit site</h1>

<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe - edit site</p>
	$html_form
</div>


$footer
EOT;
?>



