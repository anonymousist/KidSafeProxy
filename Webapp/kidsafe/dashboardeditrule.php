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
	$rule = $kdb->getRuleRuleid($this_id);
	if ($rule == null)
	{
		header("Location: dashboard.php?message=parameter");
		exit (0);	
	}
	// confirmed that rule exists - now update the rule with the new details
	// don't check if it's changed - just overwrite with new details
	$rule->setId($this_id);
	
	// siteentry must already exist
	// parameter sites - holds siteid
	$rule->setSiteid($parms->getParm('sites'));
	
	// If we have a template and it's not '' or 'custom'
	// First see if we have a template
	if ($parms->getParm('template') !='' && $parms->getParm('template') !='custom')
	{
		$add_request = $parms->getParm('addtemplate');
		// Load template - note this will error out if the template doesn't exist
		$this_template = $kdb->getTemplateName($add_request);
		$rule->setUsers($this_template->getUsers());
		$rule->setPermission($this_template->getPermission());
		$rule->setTemplate($this_template->getId());
	}
	// otherwise look at custom permissions
	else
	{
		$rule->setUsers($parms->getParm('custom-groups'));
		$rule->setPermission($parms->getParm('permission'));
		// template 0 = template not used
		$rule->setTemplate(0);
	}
	
	$expiry = '';
	// add expiry (mysql datetime) - ignoring possible null values
	if ($parms->getParm('expiry') != '' && $parms->getParm('expiry') != '0000-00-00 00:00' && $parms->getParm('expiry') != '*' && $parms->getParm('expiry') != 0)
	{
		// Uses string to time to interpret date
		$epoch_expiry = strtotime($parms->getParm('expiry'));
		$expiry = date('Y-m-d h:j:s', $epoch_expiry);
	}
	else
	{
		$expiry = '0000-00-00 00:00';
	}
	$rule->setValiduntil($expiry);

	// log true / false
	$rule->setLog($parms->getParm('log'));
	
	// priority
	$rule->setPriority($parms->getParm('priority'));
	
	// comments
	$rule->setComments($parms->getParm('comments'));
	
	// save entry
	$kdb->updateRule($rule);
	
		
	// Updated rule so regenerate the kidsafe.rules file
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
$this_rule = $kdb->getRuleRuleid($this_id);

if ($this_rule == null)
{
	header("Location: dashboard.php?message=parameter");
	exit (0);	
}

// get a list of all site entries
$all_sites = $kdb->getSitesAll();

// Get templates
$templates = $kdb->getAllowTemplates();

$html_form = "<form action=\"dashboardeditrule.php\" method=\"post\">\n";
$html_form .= "<input type=\"hidden\" name=\"action\" value=\"save\">\n";
$html_form .= "<input type=\"hidden\" name=\"id\" value=\"$this_id\">\n";
$html_form .= "Host <select name=\"sites\">\n";
foreach ($all_sites as $key=>$this_site)
{
	if ($this_rule->getSiteid() == $key) {$html_form .= "<option value=\"$key\" selected=\"selected\">";}
	else {$html_form .= "<option value=\"$key\">";}
	// either title or sitename
	if ($this_site->getTitle() != "") {$html_form .= $this_site->getTitle();}
	else {$html_form .= $this_site->getSitename();}
	
	$html_form .= "</option><br>\n";
}
$html_form .= "</select><br>\n";


$html_form .= "Template: <select id=\"template\" name=\"template\">\n";
if ($this_rule->getTemplate() == 0) {$html_form .= "<option value=\"\" selected=\"selected\">\n";} // if no template selected then show first option as null (means look at specific rule) - same as "custom"
foreach ($templates as $thistemplate)
{
	if ($this_rule->getTemplate() == $thistemplate->getId())
	{
		$html_form.="<option value=\"".$thistemplate->getName()."\" selected=\"selected\">".$thistemplate->getName()."</option>\n";
	}
	else
	{
		$html_form.="<option value=\"".$thistemplate->getName()."\">".$thistemplate->getName()."</option>\n";
	}
}
$html_form .= "<option value=\"custom\">Add individual groups</option>\n";
$html_form .= "</select><br>\n";

$html_form .= "Groups: <input type=\"text\" name=\"custom-groups\" value=\"".$this_rule->getUsers()."\"><br>\n";

$html_form .= "Permission: <select name=\"permission\"><\n";
if ($this_rule->getPermission() == 9) 
{
	$html_form .= "<option value=\"9\" selected=\"selected\">Allow</option>\n";
	$html_form .= "<option value=\"0\">Deny</option>\n";
}
else
{
	$html_form .= "<option value=\"9\">Allow</option>\n";
	$html_form .= "<option value=\"0\" selected=\"selected\">Deny</option>\n";
}
$html_form .= "</select><br>\n";

// Display as an expiry rather than "length of time" - perhaps add javascript to allow different ways of providing expiry time
$html_form .= "Expiry: <input name=\"expiry\" value=\"".$this_rule->getValiduntil()."\"><br>\n";


$html_form .= "Log: <select name=\"log\"><\n";
if ($this_rule->getLog() == 1) 
{
	$html_form .= "<option value=\"1\" selected=\"selected\">True</option>\n";
	$html_form .= "<option value=\"0\">False</option>\n";
}
else
{
	$html_form .= "<option value=\"1\">True</option>\n";
	$html_form .= "<option value=\"0\" selected=\"selected\">False</option>\n";
}
$html_form .= "</select><br>\n";

$html_form .= "Priority: <input type=\"text\" name=\"priority\" value=\"".$this_rule->getPriority()."\"><br>\n";

// shows / edits comments on the rule (addrule.php did it on rule and if new site)
$html_form .= "Comments: <input type=\"text\" name=\"comments\" value=\"".$this_rule->getComments()."\"><br>\n";

$html_form .= "<input type=\"submit\" value=\"Save\"><br>\n";
$html_form .= "</form>";

include("inc/dashboardheaders.php");

print <<< EOT
$header
$login_banner
$main_banner
$main_menu

<h1>Kidsafe - edit rule</h1>

<p>$user_messages</p>

<div id="intro">
	<p>Kidsafe - edit rule</p>
	$html_form
</div>


$footer
EOT;
?>



