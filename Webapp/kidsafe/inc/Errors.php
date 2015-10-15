<?php

// Provides conistant handling of error messages
// This is required for Database class and KidsafeDB class
// Also requires ErrorMsg class

/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2013

This class is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This class is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this code.  If not, see <http://www.gnu.org/licenses/>.
**/



// Error classes - each category is based on ERROR, but with +100 
// 101-199 = FATAL
// 201-299 = WARNING
// 301-399 = DEBUG
// 401-499 = INFO

//note 100, 200 etc. are defined as the error levels

define ('ERROR_CFG', 101);				// Error loading / reading config file
define ('ERROR_APP', 202);				// General application specific error 
define ('ERROR_SETTINGS', 103);			// Error with settings table
define ('ERROR_EXTERNAL', 104);			// Error loading external file (eg. template)
define ('ERROR_SESSION', 105);			// Error with session (eg. no session where required)
define ('ERROR_SECURITY', 106);			// Error with user date failing security check
define ('ERROR_PARAMETER', 107);		// Error with parameter provided by the customer
define ('ERROR_APP_1', 108);			// Application specific error 1 
define ('ERROR_APP_2', 109);			// Application specific error 2
define ('ERROR_DATABASE', 110);			// Other database error
define ('ERROR_NOTALLOWED', 111);		// Trying to use disabled feature
define ('ERROR_FILEREAD', 112);			// Trying to read critical file
define ('ERROR_FILEWRITE', 113);		// Trying to write critical file
define ('ERROR_INTERNAL', 199);			// Internal error (eg. required variable not defined)
define ('ERROR_LEVEL', 200);
define ('WARNING_CFG', 201);
define ('WARNING_APP', 202);
define ('WARNING_SETTINGS', 203);	
define ('WARNING_EXTERNAL', 204);
define ('WARNING_SESSION', 205);
define ('WARNING_SECURITY', 206);
define ('WARNING_PARAMETER', 207);
define ('WARNING_APP_1', 208);
define ('WARNING_APP_2', 209);
define ('WARNING_DATABASE', 210);
define ('WARNING_NOTALLOWED', 211);	
define ('WARNING_FILEREAD', 212);
define ('WARNING_FILEWRITE', 213);
define ('WARNING_INTERNAL', 299);
define ('WARNING_LEVEL', 300);
define ('DEBUG_CFG', 301);
define ('DEBUG_APP', 302);
define ('DEBUG_SETTINGS', 303);
define ('DEBUG_EXTERNAL', 304);
define ('DEBUG_SESSION', 305);
define ('DEBUG_SECURITY', 306);
define ('DEBUG_PARAMETER', 307);
define ('DEBUG_APP_1', 308);
define ('DEBUG_APP_2', 309);
define ('DEBUG_DATABASE', 310);
define ('DEBUG_NOTALLOWED', 311);
define ('DEBUG_FILEREAD', 312);
define ('DEBUG_FILEWRITE', 313);
define ('DEBUG_INTERNAL', 399);
define ('DEBUG_LEVEL', 400);
define ('INFO_CFG', 401);
define ('INFO_APP', 402);
define ('INFO_SETTINGS', 403);
define ('INFO_EXTERNAL', 404);
define ('INFO_SESSION', 405);
define ('INFO_SECURITY', 406);
define ('INFO_PARAMETER', 407);
define ('INFO_APP_1', 408);
define ('INFO_APP_2', 409);
define ('INFO_DATABASE', 410);
define ('INFO_NOTALLOWED', 411);
define ('INFO_FILEREAD', 412);
define ('INFO_FILEWRITE', 413);
define ('INFO_INTERNAL', 499);
define ('INFO_LEVEL', 500);

//require_once ($include_dir."ErrorMsg.php");

class Errors 
{
    private static $_instance;
    private $events= array();
    
    public function __construct () 
    {

    }
    
    // Uses singleton pattern to ensure only one exists
    public static function getInstance() 
    {
        if (empty(self::$_instance)) {self::$_instance = new Errors ();}
        return self::$_instance;
    }
    
    public function errorEvent ($error_num, $error_txt) 
    {
    	global $debug;
    	// handle fatals first as we don't need to store - we just die
        if ($error_num < ERROR_LEVEL)
        {
        	// die - if PHP warnings on then users will see this
        	// if not then it will just go into log
        	// get previous errors / info to include in output
        	// as this was fatal we provide all previous entries as they may be relevant
        	$previous_events = $this->listEvents(INFO_LEVEL);
        	if ($debug) {print "ERROR: ".$previous_events.$error_num." - ".$error_txt."\n\n\n";}
        	// note previous_events will already include a trailing \n
        	die ($previous_events.$error_num." - ".$error_txt);
        }
        
        if ($debug) {print "MESSAGE: $error_num :: $error_txt";}
        // store message
        $this->events[] = new ErrorMsg ($error_num, $error_txt);
        
    }                
    
    // returns number of Events at error level or above
    public function numEvents ($error_level)
    {
    	$num_events = 0;
    	foreach ($this->events as $this_event)
    	{
    		if ($this_event->getLevel() < $error_level) {$num_events++;}
    	}
    	return $num_events;
    }
    
    // returns with line breaks 
    // min_error_level is the minimum level which error was issued as  
    public function listEvents ($error_level) 
    {
    	$return_string = '';
    	foreach ($this->events as $this_event)
    	{
    		$this_text = $this_event->getMsg($error_level);
    		// only add if contains text
    		if ($this_text != '') {$return_string .= $this_text."\n";}
    	}
    	return ($return_string);
    }
}
?>

