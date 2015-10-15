<?php
/*** 
Handles the PHP session - treats as raw variables
***/
// Extended by DashboardSession.php

/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2013

This file is part of kidsafe.

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


// typical values 
// username


class PHPSession
{

	// establish session
    public function __construct () 
    {
    	// start session to get the session uid
    	session_start();
    }
    
    // returns a variable
    public function getValue ($key) 
    {
    	if(isset($_SESSION[$key])) {return $_SESSION[$key];}
    	else {return "";}
    }
    
    // returns a variable
    public function setValue ($key, $value) 
    {
    	$_SESSION[$key] = $value;
    }
    
    public function unsetValue ($key)
    {
    	unset($_SESSION[$key]);
    }

	// note if we destroy the session there is no way of recovering and recreating whilst still in the page
	// all variables will be lost
	// normally just leave the PHP session 
    public function destroySession ()
    {
    	session_destroy();
    }
    
}
?>
