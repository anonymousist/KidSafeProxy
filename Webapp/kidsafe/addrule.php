<?php
/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe
Copyright Stewart Watkiss 2013

addrule.php - save page 
User must have admin = true
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

/* these apply to all posts */
// host - domain (as detected - see site later)
// timeallowed - requested time
// url - to redirect to

// source - client ip address - taken from $_SERVER rather than from post



/* these only apply to the initial post from blocked.php */
// addpermisssion - template to add or "Add individual group" (mapped to addgroup / addtemplate when 2nd submit)



/* these only apply to the repost after username password entered */
// add=stage2 to confirm this is submit with username&password
// username
// password
// site - actual string to store in DB (entry / .domain / regexp)
// comments - These comments are for the rule and the site - can be edited later from either if required
// addtemplate - template defining permission to add
// addgroup - group to add (only if template not selected)


// If the site is added through addrule.php then it's a basic option - without title and comments
// It can be edited later


//$debug = true;

if (!isset($debug)) {$debug = false;}

include ('kidsafe-config.php');

// autoload any classes as required
function __autoload($class_name) 
{
    include 'inc/'.$class_name.'.php';
}

/*** Connect to database ***/
$db = new Database($dbsettings);
$kdb = new KidsafeDB($db);

//Get parameters - check safe and return as object
// all values are included in array - even if not on url
$parms = new Parameters();

if ($db->getStatus() != 1) {die ("Unable to connect to the database");}


// If we have a password then adding entry, if not then prompt what to add

/** Adding entry **/ 
// First check that the password is correct - as otherwise we won't allow anything
// $password is already md5 encoded, as is the value in get_values so just do direct compare
if ($parms->getParm('add')=='stage2')
{
	// gets user object based on username
	$user = $kdb->getUserUsername($parms->getParm('username'));
	// check we got a user back 
	if ($user == null) 
	{
		if ($debug) {print "User doesn't exist ".$parms->getParm('username')."\n";}
		loginFail();
	}
	// Get username and password and check - first check shouldn't hit but additional check
	if ($user->getUsername() != $parms->getParm('username') || md5($parms->getParm('password')) != $user->getPassword()) 
	{
		if ($debug) {print "Login failure user: ".$parms->getParm('username')." password: ".$parms->getParm('password')." \n";}
		loginFail();
	}
	// check we have sufficient permission - ie. admin
	if (!$user->isAdmin())
	{
		noPermission();
	}
	
	// If we get here we are logged in so can add entry
	if ($debug) {print "Login successful ".$user->getUsername()." \n";}
	
	
	// create rule object with defaults - populate below
	$rule = new Rule();
	
	$siteentry = $kdb->getSiteSitename($parms->getParm('site'));
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
		$siteentry = new Site(array('sitename'=>$parms->getParm('site'), 'comments'=>$parms->getParm('comments')));
		
		
		$kdb->insertSite($siteentry);
		if ($debug) {print "Added new site entry\n";}
		
		// read back in based on sitename
		$siteentry = $kdb->getSiteSitename($parms->getParm('site'));
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
	// First see if we have a template
	if ($parms->getParm('addtemplate') !='')
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
	
	
	// setup and load headers
	$title = "kidsafe Rule added";
	include("inc/headers.php");
	
	
	print <<<EOT
$header
$start
<h1>New rule added</h1>
<p>Click below to continue to the website.</p>
<p><a href="$url">$url</a></p>.
$footer
EOT;
	exit (0);

	

}
/** Prompt to confirm what to add and get username / password **/
else
{

	// Initial display - creating a new entry - ask for username and password and confirm details
	
	// check for initial values (if no entry / invalid then it will return ''
	$url = $url = $parms->getParm('url');
	$host = $parms->getParm('host');
	$timeallowed = $parms->getParm('timeallowed');
	
	// if addpermission is template then provide template selection - if group add group selection
	
	//if ($parms->getParm('addpermission') != "Add individual group")
	if ($parms->getParm('addpermission') != "custom")
	{
			
		// Get templates
		$templates = $kdb->getAllowTemplates();
		$addto = "<select id=\"addtemplate\" name=\"addtemplate\">\n";
		foreach ($templates as $thistemplate)
		{
			if ($thistemplate->getName() == $parms->getParm('addpermission'))
			{
				$addto.="<option value=\"".$thistemplate->getName()."\" selected=\"selected\">".$thistemplate->getName()."</option>\n";
			}
			else
			{
				$addto.="<option value=\"".$thistemplate->getName()."\">".$thistemplate->getName()."</option>\n";
			}
		}
		$addto .= "</select>";
	}
	else
	// add to specific group (text entry)
	{
		
		$addto = "<input type=\"addgroup\" type=\"text\" value=\"\">\n";
	}
	
	
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
	// separate variable with the "Always" option added
	if ($timeallowed == 'Always')
	{
		$time_select .= "<option value=\"Always\" selected=\"selected\">Always</option></select>\n";
	}
	else
	{
		$time_select .= "<option value=\"Always\">Always</option></select>\n";	
	}

	
	// setup and load headers
	$title = "Add new rule to kidsafe";
	include("inc/headers.php");

	print <<<EOT
$header
$start
<h1>Add new rule to kidsafe</h1>

<p>
<form action="addrule.php" method="post">
<input type="hidden" name="add" value="stage2">
<input type="hidden" name="url" value="$url">
Requested domain is $host <br>
Add host: <input type="text" name="site" value="$host"> <br>
Add to: $addto <br>
Allow for: $time_select<br>
Comments: <input type="text" name="comments" value=""> <br>
&nbsp;<br>
Username: <input type="text" name="username" value=""> <br>
Password: <input type="password" name="password" value=""> <br>

<input type="submit" value="Add rule" />
</form>
</p>

$footer
EOT;
	exit (0);

}
	
	
/******* Functions *****/
function loginFail ()
{
// username / password incorrect 
// not very user friendly telling the user to use the browser back button, so room for improvement in future


// setup and load headers
$title = "kidsafe Username / password incorrect";
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

// setup and load headers
$title = "kidsafe Insufficient permission";
include("inc/headers.php");

	print <<< EOT
$header
$start
<h1>Insufficient permission</h1>
<p>You do not have sufficient permissions to edit the rules table.</p>
$footer
EOT;
exit (0);
}



?>
