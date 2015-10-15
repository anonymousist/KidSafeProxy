<?php
/*** 
Encapsulate requests for access to the database
This way calling code does not need to worry about building sql statements etc.
***/

class KidsafeDB
{
	// stores Database class instance
    private $qdb_object;
    
    // store table prefix - rather than get from Database Class each time
    private $table_prefix;
    
   
    /* needs to be passed the database object for class Database */
    function __construct ($db_object) 
    {
    	$this->db_object = $db_object;
    	$this->table_prefix = $this->db_object->getTablePrefix();
    }
    
    // Creates the select string and then calls Database
    // returns hash array of key value pairs
    function getSettingsAll ()
    {
    	global $debug;
    	
    	$settings = array();
    	$select_string = "select settings_key,settings_value from ".$this->table_prefix.'settings';
    	$settings = $this->db_object->getKeyValue ($select_string, "settings_key", "settings_value");
    	return $settings;
    }
    
    
    // returns user object or null
    function getUserUsername ($username)
    {
    	
    	global $debug;
    	    	
    	$sql = "Select * from ".$this->table_prefix."users where username=\"$username\"";
   	
    	// get result
    	$result = $this->db_object->getRow ($sql);
    	
    	
    	// check for errors
    	if (isset ($result['ERRORS'])) 
    	{
    		if ($debug) 
    		{
    			$err =  Errors::getInstance();
    			$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);
    			// not needed as we exit anyway, but removes risk of failure
    			exit(0);
    		}
    	}
    	
    	// now create an object based on result
    	if (!isset ($result['username']) || $result['username'] != $username)
    	{
    		return null;
    	}
    	
    	return (new User($result));
    }

    
    // password should be md5 hashed 
    function setUserPassword ($username, $password)
    {
    	
    	global $debug;
    	    	
    	$sql = "Update ".$this->table_prefix."users set password=\"$password\" where username=\"$username\"";
   	
    	// This should not even be allowed for debug normally as it includes password (although should be hashed)
    	//if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	
    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }

    // update existing rule - ruleid must be included
    function updateUser ($userobject)
    {
    	global $debug;
    	
    	// check userid is not null
    	// although from a user perspective we use username - we use userid here in case we want to allow change to username in future
    	// should not ever get this error - this implies we are trying to create a new user when supposed to be editing an existing user entry
    	// this would be an error in the calling code
    	if ($userobject->getId() == null) 
    	{
    		if ($debug) {print "Error in updateUser - userid not provided when required \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_INTERNAL, "Internal error user id does not exist during an update request"); 
    	}
    	
    	// includes password, but password would not normally be changed through here, but included for consistancy if we add this capability in future
    	$sql = "UPDATE ".$this->table_prefix."users set username=\"".$userobject->getUsername()."\", accesslevel=\"".$userobject->getAccess()."\", fullname=\"".$userobject->getFullname()."\", password=\"".$userobject->getPassword()."\", loginexpiry=\"".$userobject->getLoginexpiry()."\", status=\"".(int)$userobject->isEnabled()."\", supervisor=\"".(int)$userobject->isSupervisor()."\", admin=\"".(int)$userobject->isAdmin()."\" WHERE userid=\"".$userobject->getId()."\"";
    	
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in updateUser \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }

    
    
    
    
    // returns true on success 
    // due to error level chosen will exit application on error (but could change to warning)
    function updateSetting ($key, $value)
    {
    	global $debug;
    	
    	$sql = "update ".$this->table_prefix."settings set settings_value='$value' where settings_key='$key'";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }
    
    // returns true on success 
    // due to error level chosen will exit application on error (but could change to warning)
    function insertSetting ($key, $value)
    {
    	global $debug;
    	
    	$sql = "insert into ".$this->table_prefix.'settings'." (settings_value, settings_key) value('$value', '$key')";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in insertSetting ".$temp_array['ERRORS']."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }

    // returns allow templates only as array of template objects
    function getAllowTemplates ()
    {
    	$objects = array();
    	$select_string = "select templateid, templatename, description, users, permission from ".$this->table_prefix.'templates where permission!=0';
    	$temp_array = $this->db_object->getRowsAll ($select_string);
    	if (isset ($debug) && $debug) {print "SQL: \n".$select_string."\n\n";}

    	
    	// convert into template objects
    	foreach ($temp_array as $this_entry)
    	{
    		array_push($objects, new Template($this_entry));
    	}
    	return $objects;
    	
    }



    
    function getTemplateId ($this_templateid)
    {
    	$objects = array();
    	$select_string = "select templateid, templatename, description, users, permission from ".$this->table_prefix."templates where templateid='$this_templateid'";
    	$this_entry = $this->db_object->getRow ($select_string);
    	if (isset ($debug) && $debug) {print "SQL: \n".$select_string."\n\n";}

    	if (isset ($this_entry['templateid']) && $this_entry['templateid'] == $this_templateid)
    	{
    		return (new Template($this_entry));
    	}
    	else
    	{
    		if ($debug) {print "Error in getTemplateId ".$temp_array['ERRORS']."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading getTemplateId $templateid"+$temp_array['ERRORS']);
    	}
  	
    }    
    
    // No checking this is valid - will error if entry doesn't exist
    function getTemplateName ($this_templatename)
    {
    	global $debug;
    	$objects = array();
    	$select_string = "select templateid, templatename, description, users, permission from ".$this->table_prefix."templates where templatename='$this_templatename'";
    	$this_entry = $this->db_object->getRow ($select_string);
    	if (isset ($debug) && $debug) {print "SQL: \n".$select_string."\n\n";}

    	if (isset ($this_entry['templatename']) && $this_entry['templatename'] == $this_templatename)
    	{
    		return (new Template($this_entry));
    	}
    	else
    	{
    		if (isset($debug) && $debug) {print "Error in getTemplateName ".$this_entry['ERRORS']."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading getTemplateName $this_templatename".$this_entry['ERRORS']);
    	}
  	
    }
    
    
    // returns groupid and groupname - needs to be updated to return group object
    function getGroupsIdAll ()
    {
    	global $debug;
    	
    	$values = array();
    	$select_string = "select groupid,groupname from ".$this->table_prefix.'groups';
    	$values = $this->db_object->getKeyValue ($select_string, "groupid", "groupname");
    	return $values;
    }



	// Get site entry - based on sitename
	// This is also used to determine if an entry already exists
    // returns site object or null
    function getSiteSitename ($sitename)
    {
    	
    	global $debug;
    	    	
    	$sql = "Select * from ".$this->table_prefix."sites where sitename='$sitename'";
   	
    	// get result
    	$result = $this->db_object->getRow ($sql);
    	
    	
    	// check for errors
    	if (isset ($result['ERRORS'])) 
    	{
    		if ($debug) 
    		{
    			$err =  Errors::getInstance();
    			$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);
    			// not needed as we exit anyway, but removes risk of failure
    			exit(0);
    		}
    	}
    	
    	// now create an object based on result
    	if (!isset ($result['sitename']) || $result['sitename'] != $sitename)
    	{
    		return null;
    	}
    	
    	return (new Site($result));
    }	    

    
	// Get site entry - based on ID
    // returns site object or null
    function getSiteSiteid ($siteid)
    {
    	
    	global $debug;
    	    	
    	$sql = "Select * from ".$this->table_prefix."sites where siteid='$siteid'";
   	
    	// get result
    	$result = $this->db_object->getRow ($sql);
    	
    	
    	// check for errors
    	if (isset ($result['ERRORS'])) 
    	{
    		if ($debug) 
    		{
    			$err =  Errors::getInstance();
    			$err->errorEvent(ERROR_DATABASE, "Error reading database"+$temp_array['ERRORS']);
    			// not needed as we exit anyway, but removes risk of failure
    			exit(0);
    		}
    	}
    	
    	// now create an object based on result
    	if (!isset ($result['siteid']) || $result['siteid'] != $siteid)
    	{
    		return null;
    	}
    	
    	return (new Site($result));
    }	    

    
    
    // update existing rule - ruleid must be included
    function updateRule ($ruleobject)
    {
    	global $debug;
    	
    	// check ruleid is not null 
    	// should not ever get this error - this implies we are trying to create a new rule when it's an existing rule entry
    	// this would be an error in the calling code
    	if ($ruleobject->getId() == null) 
    	{
    		if ($debug) {print "Error in updateRule - ruleid not provided when required \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_INTERNAL, "Internal error rule id does not exist during an update request"); 
    	}
    	
    	$sql = "UPDATE ".$this->table_prefix."rules set siteid=\"".$ruleobject->getSiteid()."\", users=\"".$ruleobject->getUsers()."\", permission=\"".$ruleobject->getPermission()."\", valid_until=\"".$ruleobject->getValiduntil()."\", template=\"".$ruleobject->getTemplate()."\", log=\"".(int)$ruleobject->getLog()."\", priority=\"".$ruleobject->getPriority()."\", comments=\"".$ruleobject->getComments()."\" WHERE ruleid=\"".$ruleobject->getId()."\"";
    	
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in updateRule \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }

    
    // insert a new site - siteid must be null
    function insertSite ($siteobject)
    {
    	global $debug;
    	
    	// check siteid is null 
    	// should not ever get this error - this implies we are trying to create a new site when it's an existing site entry
    	// this would be an error in the calling code
    	if ($siteobject->getId() != null) 
    	{
    		if ($debug) {print "Error in insertSite - siteid provided when not expected \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_INTERNAL, "Internal error site id exists during a insert request"); 
    	}
 	
    	
    	$sql = "insert into ".$this->table_prefix."sites (sitename, title, comments) values ('".$siteobject->getSitename()."', '".$siteobject->getTitle()."', '".$siteobject->getComments()."')";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS']) && $temp_array['ERRORS']!='') 
    	{
    		if ($debug) {print "Error in insert Site \n".$temp_array['ERRORS']."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error ocurred writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }


    // insert a new user - userid must be null
    function insertUser ($userobject)
    {
    	global $debug;
    	
    	// check userid is null 
    	// should not ever get this error - this implies we are trying to create a new site when it's an existing site entry
    	// this would be an error in the calling code
    	if ($userobject->getId() != null) 
    	{
    		if ($debug) {print "Error in insertUser - userid provided when not expected \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_INTERNAL, "Internal error user id exists during a insert request"); 
    	}
 	
    	
    	$sql = "insert into ".$this->table_prefix."users (username, fullname, accesslevel, password, status, loginexpiry, supervisor, admin) values ('".$userobject->getUsername()."', '".$userobject->getFullname()."', '".$userobject->getaccess()."', '".$userobject->getPassword()."', '".$userobject->isEnabled()."', '".$userobject->getloginexpiry()."', '".$userobject->isSupervisor()."', '".$userobject->isAdmin()."')";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS']) && $temp_array['ERRORS']!='') 
    	{
    		if ($debug) {print "Error in insert User \n".$temp_array['ERRORS']."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error ocurred writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }


    
    
    
    // update existing site - siteid must be included
    function updateSite ($siteobject)
    {
    	global $debug;
    	
    	// check siteid is not null 
    	// should not ever get this error - this implies we are trying to create a new rule when it's an existing rule entry
    	// this would be an error in the calling code
    	if ($siteobject->getId() == null) 
    	{
    		if ($debug) {print "Error in updateSite - siteid not provided when required \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_INTERNAL, "Internal error rule id does not exist during an update request"); 
    	}
    	
    	$sql = "UPDATE ".$this->table_prefix."sites set sitename=\"".$siteobject->getSitename()."\", title=\"".$siteobject->getTitle()."\", comments=\"".$siteobject->getComments()."\" WHERE siteid=\"".$siteobject->getId()."\"";
    	
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in updateRule \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }






    
    // insert a new rule - ruleid must be null
    function insertRule ($ruleobject)
    {
    	global $debug;
    	
    	// check ruleid is null 
    	// should not ever get this error - this implies we are trying to create a new rule when it's an existing rule entry
    	// this would be an error in the calling code
    	if ($ruleobject->getId() != null) 
    	{
    		if ($debug) {print "Error in insertRule - ruleid provided when not expected \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_INTERNAL, "Internal error rule id exists during a insert request"); 
    	}
    	
    	
    	// miss rulesid (that is autogen)
    	$sql = "insert into ".$this->table_prefix."rules (siteid, users, permission, valid_until, template, log, priority, comments) values ('".$ruleobject->getSiteid()."', '".$ruleobject->getUsers()."', '".$ruleobject->getPermission()."', '".$ruleobject->getValiduntil()."', '".$ruleobject->getTemplate()."', '".$ruleobject->getLog()."', '".$ruleobject->getPriority()."', '".$ruleobject->getComments()."')";
    	if (isset ($debug) && $debug) {print "SQL: \n".$sql."\n\n";}
    	
    	$temp_array = $this->db_object->updateRow($sql);
    	    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in insertRule \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error writing to database"+$temp_array['ERRORS']); 
    	}
    	
    	return true;
    }
    

    // returns rules in same format as used in the files
    // put in DB class as we can do join and make mysql work the associations
    function getRulesFile ()
    {
    	global $debug;
    	
    	$output = '';
    	$select_string = "SELECT sitename,ruleid,permission,users,valid_until,log FROM ".$this->table_prefix."rules LEFT OUTER JOIN ".$this->table_prefix."sites on ".$this->table_prefix."rules.siteid=".$this->table_prefix."sites.siteid ORDER BY priority, ruleid";
    	
    	if (isset($debug) && $debug) {print "SQL: $select_string\n";}
    	
    	$temp_array = $this->db_object->getRowsAll ($select_string);
    	
    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in getRulesAll \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database".$temp_array['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}
    	
    	foreach ($temp_array as $this_entry)
    	{
    		// need to check for time 0000-00-00 - strtotime will convert this to a null
    		if ($this_entry['valid_until'] == "0000-00-00 00:00:00")
    		{
    			$expirytime = '0';
    		}
    		else
    		{
    			$expirytime = strtotime($this_entry['valid_until']);
    		}
    		// get values in text file format
    		// converts time to unixtime - bool is stored as tinyint so no need to convert - adds \n
    		$output .= $this_entry['sitename']." ".$this_entry['ruleid']." ".$this_entry['permission']." ".$this_entry['users']." $expirytime ".$this_entry['log']."\n";
    	}
    	
    	return $output;
    }


    // returns rules as array of rules
    function getRulesAll ()
    {
    	global $debug;
    	
    	$output = array();
    	$select_string = "SELECT * FROM ".$this->table_prefix."rules ORDER BY priority, ruleid";
    	
    	if (isset($debug) && $debug) {print "SQL: $select_string\n";}
    	
    	$temp_array = $this->db_object->getRowsAll ($select_string);
    	
    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in getRulesAll \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database".$temp_array['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}
    	
    	foreach ($temp_array as $this_entry)
    	{
    		// need to check for time 0000-00-00 - strtotime will convert this to a null
    		if ($this_entry['valid_until'] == "0000-00-00 00:00:00")
    		{
    			$expirytime = '0';
    		}
    		else
    		{
    			$expirytime = strtotime($this_entry['valid_until']);
    		}
    		
    		
    		//$output .= $this_entry['sitename']." ".$this_entry['ruleid']." ".$this_entry['permission']." ".$this_entry['users']." $expirytime ".$this_entry['log']."\n";
    		$output[$this_entry['ruleid']] = new Rule (array ('ruleid'=> $this_entry['ruleid'], 'siteid'=>$this_entry['siteid'], 'users'=>$this_entry['users'],'permission'=>$this_entry['permission'], 'valid_until'=>$expirytime, 'template'=>$this_entry['template'], 'log'=>$this_entry['log'], 'priority'=>$this_entry['priority'], 'comments'=>$this_entry['comments']));
    	}
    	
    	return $output;
    }


    // returns rules as array of rules
    function getRuleRuleid ($ruleid)
    {
    	global $debug;
    	
    	$output = array();
    	$select_string = "SELECT * FROM ".$this->table_prefix."rules WHERE ruleid=$ruleid";
    	
    	if (isset($debug) && $debug) {print "SQL: $select_string\n";}
    
    	$this_mysql_entry = $this->db_object->getRow ($select_string);
    	
    	// check for errors
    	if (isset ($this_mysql_entry['ERRORS'])) 
    	{
    		if ($debug) {print "Error in getRule \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database".$this_mysql_entry['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}

    	if (!isset($this_mysql_entry['ruleid']) || $this_mysql_entry['ruleid'] != $ruleid)
		{
			return null;
		}
    	
		// need to check for time 0000-00-00 - strtotime will convert this to a null
		if ($this_mysql_entry['valid_until'] == "0000-00-00 00:00:00")
		{
			$expirytime = '0';
		}
		else
		{
			$expirytime = strtotime($this_mysql_entry['valid_until']);
		}
		
		

		$output = new Rule (array ('ruleid'=> $this_mysql_entry['ruleid'], 'siteid'=>$this_mysql_entry['siteid'], 'users'=>$this_mysql_entry['users'],'permission'=>$this_mysql_entry['permission'], 'valid_until'=>$expirytime, 'template'=>$this_mysql_entry['template'], 'log'=>$this_mysql_entry['log'], 'priority'=>$this_mysql_entry['priority'], 'comments'=>$this_mysql_entry['comments']));
    	
    	return $output;
    }

    
    
    

    // returns sites as array of sites
    function getSitesAll ()
    {
    	global $debug;
    	
    	$output = array();
    	$select_string = "SELECT * FROM ".$this->table_prefix."sites ORDER BY siteid";
    	
    	if (isset($debug) && $debug) {print "SQL: $select_string\n";}
    	
    	$temp_array = $this->db_object->getRowsAll ($select_string);
    	
    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in getSitesAll \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database".$temp_array['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}
    	
    	foreach ($temp_array as $this_entry)
    	{

    		$output[$this_entry['siteid']] = new Site (array ('siteid'=> $this_entry['siteid'], 'sitename'=>$this_entry['sitename'], 'title'=>$this_entry['title'], 'comments'=>$this_entry['comments']));
    	}
    	
    	return $output;
    }



    
    // returns users as array of users (key is username)
    function getUsersAll ()
    {
    	global $debug;
    	
    	$output = array();
    	$select_string = "SELECT * FROM ".$this->table_prefix."users ORDER BY userid";
    	
    	if (isset($debug) && $debug) {print "SQL: $select_string\n";}
    	
    	$temp_array = $this->db_object->getRowsAll ($select_string);
    	
    	
    	// check for errors
    	if (isset ($temp_array['ERRORS'])) 
    	{
    		if ($debug) {print "Error in getUsersAll \n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_DATABASE, "Error reading database".$temp_array['ERRORS']);
    		// not needed as we exit anyway, but removes risk of failure
    		exit(0);
    	}
    	
    	foreach ($temp_array as $this_entry)
    	{

    		$output[$this_entry['username']] = new User (array ('userid'=> $this_entry['userid'], 'username'=>$this_entry['username'], 'accesslevel'=>$this_entry['accesslevel'], 'fullname'=>$this_entry['fullname'], 'password'=>$this_entry['password'], 'status'=>$this_entry['status'], 'loginexpiry'=>$this_entry['loginexpiry'], 'supervisor'=>$this_entry['supervisor'], 'admin'=>$this_entry['admin']));
    	}
    	return $output;
    }


    
    
    
} 



?>
