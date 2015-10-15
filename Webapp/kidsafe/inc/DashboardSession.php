<?php
/*** 
Handles the PHP session - uses serialised arrays stored in the session
***/

/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2013

This file is part of Kidsafe

This is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software.  If not, see <http://www.gnu.org/licenses/>.
**/

// Note all interaction with this must be before we send the HTML header
// handles deserialising arrays


// css tags that the customer should use in css file
// Whenever an entry is added - add this to overview.txt
define ("SESSION_STATUS_BEGIN", 0); 	// not yet initialised
define ("SESSION_STATUS_ACTIVE", 1);	// active session
define ("SESSION_STATUS_LOGOUT", 2);	// logged out


class DashboardSession extends PHPSession
{
	
	// parent constructor is used - creates the session

    
    
    // returns the session information as a hash array 
    // does not return entries that are stored as a serialised array - they need to be requested seperately 
    public function getSessionInfo () 
    {
    	// first check status - if not set then return empty array
    	$session_info = array();
    	$status = $this->getValue('status');
    	
    	if (!isset($status) || !is_int ($status)) 
    	{
    		$err =  Errors::getInstance();
    		$err->errorEvent(INFO_SESSION, "No session found"); 
    		return ($session_info);
    	}
    	$session_info['status'] = $status;
    	$session_info['username'] = $this->getValue('username');

    	
    	return ($session_info);
    }
    
    // status - track where we are
    public function setStatus ($new_status)
    {
		$this->setValue('status', $new_status);
    }
    
    public function setUsername ($username)
    {
		$this->setValue('username', $username);
    }

    public function getUsername ()
    {
		return $this->getValue('username');
    }    
    
    
}
?>
