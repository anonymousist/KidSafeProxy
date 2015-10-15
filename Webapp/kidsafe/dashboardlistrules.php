<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

listrules.php - List all kidsafe rules
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
/*if ($parms->getParm('message') == '????')
{
	$user_messages .= "????\n";
}*/

// create user object
$user = $kdb->getUserUsername($session->getUsername());
// check we have valid user
if ($user == null) 
{
	header("Location: dashboardlogin.php?redirect=listrules.php&message=notuser");
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


$all_rules = $kdb->getRulesAll();
// all sites is hash array - key=siteid - value=siteobject
$all_sites = $kdb->getSitesAll();

$list_rules = "<table>\n";
$list_rules .= "<tr><th>Rule</th><th>Site</th><th>Users</th><th>Permission</th><th>Expiry</th><th>Template</th><th>Priority</th><th>Comments</th></tr>";

foreach ($all_rules as $this_rule)
{
	$list_rules .= "<tr>\n";
	$list_rules .= "<td><a href=\"dashboardeditrule.php?id=".$this_rule->getId()."\">".$this_rule->getId()."</a></td>\n";
	$this_siteid = $this_rule->getSiteid();
	$list_rules .= "<td title=\"".$all_sites[$this_siteid]->getSitename()."\"><a href=\"dashboardeditsite.php?id=$this_siteid\">".$all_sites[$this_siteid]->getSitename()."</a></td>\n";
	$list_rules .= "<td>".$this_rule->getUsers()."</td>";
	if ($this_rule->getPermission() == 0) {$permission = 'Deny';}
	elseif ($this_rule->getPermission() == 9) {$permission = 'Allow';}
	else {$permission = 'Restrictions';}
	$list_rules .= "<td>$permission</td>";
	if ($this_rule->getValiduntil() == 0) {$expiry = '';}
	else {$expiry = date("d.m.y H:i");}
	$list_rules .= "<td>$expiry</td>";
	$list_rules .= "<td>".$this_rule->getTemplate()."</td>";
	$list_rules .= "<td>".$this_rule->getPriority()."</td>";
	$list_rules .= "<td>".$this_rule->getComments()."</td>";
	$list_rules .= "</tr>\n";
}

$list_rules .= "</table>\n";

include("inc/dashboardheaders.php");

print <<< EOT
$header
$login_banner
$main_banner
$main_menu

<h1>Kidsafe dashboard - List rules</h1>

<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe dashboard - List rules</p>
	
	<p><a href="dashboardaddrule.php">Add a new rule</a></p>
	
	$list_rules
	
	<p><a href="dashboardaddrule.php">Add a new rule</a></p>
</div>


$footer
EOT;
?>



