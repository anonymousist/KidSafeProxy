<?php

/** 
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

newrule.php - Adds a new rule from the dashboard
uses login session rather than username / password request on the form
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


/*
Parameters 
*from* (get / post) - is where we came from - eg. dashboard / logviewer - default = dashboard 
*website* (get / post) - could be a url or a domain name - user entered so need to validate and change to domain (in get it will be url - otherwise user could use either url or domain name)
*/

$parms_allowed = array (
	'action' => 'alphanum',
	'from' => 'alphanum',
	'website' => 'website',
	'timeallowed'=>'reltime',
	'addtemplate'=>'alphanum',
	'addgroup'=>'alphanum'
	);


include ('kidsafe-config.php');		// configuration (eg. mysql login)


// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}


// Debug entry must exist - normally false
//$debug=true;
$debug=false;

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




$parms = new Parameters($parms_allowed);
// valid messages
// newpass, nopermission
if ($parms->getParm('action') == 'save')
{
	// create rule object with defaults - populate below
	$rule = new Rule();
	
	
	// This is user entered - so needs to be vetted
	// need better error message 
	$website = $parms->getParm('website');
	
	if ($website == '')
	{
		if ($debug) {print "Website needs to be specified\n";}
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Website needs to be specified"); 	
	}
	
	// check to see if this is a url rather than a domain / regexp
	// basic check looking for :// (could be http / https)
	if (preg_match ('#://#', $website))
	{
		// parse_url adds additional checks against the value - returns false on error
		$url_array = parse_url($website);
		if (isset($url_array['host'])) 
		{
			$site = $url_array['host'];
		}
		else 
		{
			if ($debug) {print "Error trying to add new site invalid url\n";}
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Error trying to add new site invalid url"); 
		}
	}
	// otherwise check this is a valid domain / regexp
	else
	{
		$site = $parms->validateParm ($website, 'website', 'domain');
	}
	
	// site should now have a domain entry - either extracted from url or from validation. If it does not then it will be '' so error
	if ($site == '')
	{
		if ($debug) {print "Invalid website / domain / expression \n";}
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Invalid website / domain / expression "); 	
	}
		
	$siteentry = $kdb->getSiteSitename($site);
	// Does sitename exist - if not null already have an entry
	if ($siteentry!=null)
	{
		// we need the siteid for the rules entry
		$siteid = $siteentry->getId();
	}
	// If doesn't exist need to add site entry
	else
	{
		// only sitename known - perhaps comments or default '' - siteid is autogen
		$siteentry = new Site(array('sitename'=>$site, 'comments'=>$parms->getParm('comments')));
		
		
		$kdb->insertSite($siteentry);
		if ($debug) {print "Added new site entry\n";}
		
		// read back in based on sitename
		$siteentry = $kdb->getSiteSitename($site);
		if ($siteentry != null)
		{
			// we need the siteid for the rules entry
			$siteid = $siteentry->getId();
		}
		else
		{
			// shouldn't get this. A db error should fail on previous step, but this checks we get the entry back
			if ($debug) {print "Error trying to add new site \n";}
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_INTERNAL, "Error saving / reading site from  database"); 
		}
	}
	
	if ($debug) {print "Site id is $siteid\n";}
	
	// shouldn't get here without a siteid - but add additional check it's a valid number (not checking the siteid exists)
	if (!isset($siteid) || $siteid<0)
	{
		if ($debug) {print "Error invalid siteid \n";}
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_INTERNAL, "Error invalid siteid");
		
	}
	// add it to the rule object
	$rule->setSiteid($siteid);

	
	// get template / group
	// First see if we have a template - and check it's not - add group 
	if ($parms->getParm('addtemplate') !='' && $parms->getParm('addpermission') != "Add individual group")
	{
		$add_request = $parms->getParm('addtemplate');
		// Load template - note this will error out if the template doesn't exist
		$this_template = $kdb->getTemplateName($add_request);
		$rule->setUsers($this_template->getUsers());
		$rule->setPermission($this_template->getPermission());
		$rule->setTemplate($this_template->getId());
	}
	elseif ($parms->getParm('addgroup') !='')
	{
		$add_request = $parms->getParm('addgroup');
		// group = users in rules table
		$rule->setUsers($add_request);
		// add permission - 9=allow 
		$rule->setPermission(9);
	}
	else // should have one of above - so shouldn't get this - only if form submitted without using kidsafe
	{
		if ($debug) {print "Error missing parameter timeallowed\n";}
		$err =  Errors::getInstance();
		$err->errorEvent(ERROR_PARAMETER, "Missing parameter addtemplate / addgroup");
	}
	
	
	if ($parms->getParm('timeallowed') == 'Always')
	{
		$rule->setValiduntil('0000-00-00');
	
	}
	// There must be a timeallowed value
	else  
	{
		$valid_until = strtotime($parms->getParm('timeallowed'));
		// if false then it's invalid and we don't continue 
		// we wouldn't include a false value on a form so only triggered by unauthorised attempts
		if ($valid_until == false)
		{
			if ($debug) {print "Error missing parameter timeallowed\n";}
			$err =  Errors::getInstance();
			$err->errorEvent(ERROR_PARAMETER, "Missing parameter timeallowed");
		}
		// set rule to time using mysql
		$rule->setValiduntil(gmdate("Y-m-d H:i:s", $valid_until));
		
	}
	
	// We don't add a priority - let it default to the standard in the Rule constructor

	// in future may want to check rule against existing rules - perhaps only if valid_until is unlimited as unlikely to be adding a rule with exact timestamp for temp rules
	

	// add the rule into the database
	$kdb->insertRule($rule);
	
	
	//-regenerate the kidsafe.rules file....
	$rules_file = new File ($rulesfilename);
	$rules_file->writeFile($kdb->getRulesFile());
	
	
	if ($debug) {print "Rule added\n\n";}
	
	
	$url = $parms->getParm('url');
	
	include("inc/dashboardheaders.php");
	
	print <<<EOT
$header
$login_banner
$main_banner
$main_menu
<h1>New rule added</h1>
<p>Click below to return to the dashboard.</p>
<p><a href="dashboard.php">Dashboard</a></p>.
$footer
EOT;
	exit (0);

	

	
	// Will now continue with page as though loading new - so in effect reload entry
}



else
{

	// Initial display - creating a new entry 
	// Most likely get here from New request with no details
	// May get here from "add from log viewer" in which case will have website
	
	// check for initial values (if no entry / invalid then it will return '')
	$from = $parms->getParm('from');
	// note that host is 'website' it could be a url or domain name(inc regexp)
	$website = $parms->getParm('website');
	
	
	// if addpermission is template then provide template selection - if group add group selection
	if ($parms->getParm('addpermission') != "custom")
	{
			
		// Get templates
		$templates = $kdb->getAllowTemplates();
		$addto = "<select id=\"addtemplate\" name=\"addtemplate\">\n";
		foreach ($templates as $thistemplate)
		{
			$addto.="<option value=\"".$thistemplate->getName()."\">".$thistemplate->getName()."</option>\n";
		}
		$addto .= "<option value=\"custom\">Add individual group</option>\n";
		$addto .= "</select>";
	}	
	
	$time_select = "<select name=\"timeallowed\">";
	foreach ($timeoptions as $thistime)
	{
		$time_select .= "<option value=\"$thistime\">$thistime</option>";
	}
	$time_select .= "<option value=\"Always\">Always</option></select>\n";	

	include("inc/dashboardheaders.php");

	print <<<EOT
$header
$login_banner
$main_banner
$main_menu
<h1>Add new rule to kidsafe</h1>

<p>
<form action="dashboardaddrule.php" method="post">
<input type="hidden" name="action" value="save">
<input type="hidden" name="from" value="$from">
Add host: <input type="text" name="website" value="$website"> <br>
Add to: $addto <br> 
or: <input type="addgroup" type="text" value=""> (only if "Add individual group" selected)<br>
Allow for: $time_select<br>
Comments: <input type="text" name="comments" value=""> <br>
&nbsp;<br>
<input type="submit" value="Add rule" />
</form>
</p>

$footer
EOT;
	exit (0);

}
	

?>



