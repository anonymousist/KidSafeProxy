<?php
/*** gets logged in users - same parameters 

This can be called from static or ajax

***/

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

// Security - needs to be admin - otherwise just return blank
// also prevents this being called except through official route as user variable won't exist if called directly
// Just returns a blank page - no feedback etc. 
// Nobody should be trying to load this page directly
if (!isset($user) || !$user->isAdmin()) {exit;}


// Possible future options (not included)
// filter maxlines order

// result is stored in $html_session which should be used by the php script that calls this

// open file ($sessionfilename is in config file)
$sessionfile = fopen($sessionfilename, "r");

// store as html in this variable
$html_session = "<table id=\"activeusers\">\n<tr>\n";
$html_session .= "  <th>Computer</th>\n  <th>User</th>\n  <th>Permission</th>\n  <th>Login from</th>\n  <th>Login expiry</th>\n  <th>Operation system</th>\n <th>Browser type<br>(login only)</th>\n";
$html_session .= "</tr>\n";

while ($thisentry = fgets($sessionfile))
{
	// remove comments
	if (preg_match ('/^#/', $thisentry)) {continue;}
	
	// split into it's parts
	// 0 - ipaddress, 1 - permission, 2 = username, 3 = expiretime, 4 = logintime, 5 = OS - Browser
	// 5 includes spaces to split into OS and browser split on ' - '
	
	// only split into 6 (otherwise will be spliting the last field
	$thisentry_split = explode (' ', $thisentry, 6);

	// view all active sessions - filter any that have expired
	if ($thisentry_split[3] < time()) {continue;}	
	
	// Create html entry
	$html_session .= "<tr>\n";
	// hostname
	$html_session .= "  <td>".$thisentry_split[0]."</td>\n";
	// username (with link to edit)
	$html_session .= "  <td><a href\"dashboardedituser?username=".$thisentry_split[2].">".$thisentry_split[2]."</a></td>\n";
	// permission
	$html_session .= "  <td>".$thisentry_split[1]."</td>\n";
	// login start
	$html_session .= "  <td>".strftime ('%T %e %b %G', $thisentry_split[4])."</td>\n";
	// login expiry
	$html_session .= "  <td>".strftime ('%T %e %b %G', $thisentry_split[3])."</td>\n";
	
	// OS and browser
	$browser_split = explode (' - ', $thisentry_split[5], 2);
	// os
	$html_session .= "  <td>".$browser_split[0]."</td>\n";
	// browser
	$html_session .= "  <td>".$browser_split[1]."</td>\n";
	
	$html_session .= "</tr>\n";

}
fclose ($sessionfile);

$html_session .= "</table>\n";


?>
