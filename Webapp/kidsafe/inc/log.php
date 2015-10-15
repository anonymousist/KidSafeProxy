<?php
/*** gets log entries - same parameters as viewlog.php
but as php values rather than as parameters

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




// filter 
// start
// maxlines ???
// order

// open file ($logfilename is in config file)
$logfile = fopen($logfilename, "r");
if ($start != 0) {fseek($logfile, $start);}

while ($thisentry = fgets($logfile))
{
	// check to match filter is done later
	
	// split rule so we can check for appropriate log entry type and add span
	// 0 - date, 1 - time, 2 = Not used, 3 = type(eg. ACCEPT / REJECT), 4 = log message
	$thisentry_split = explode (' ', $thisentry, 5);
	// logentrytype - set to accept, reject or '' depending upon the type of rule - could also assign other rules - is used as class within span (log-other / log-accept / log-reject)
	$logentrytype = 'other';
	// split to find rule number
	$thisentry_parts = '';
	
	// Need site and rule number for link forward
	$ruleno = 0;
	$website = '';
	
	if (isset($thisentry_split[4])) 
	{
		$message = explode (' ', trim($thisentry_split[4]));
		// 0 - Src IPaddress, 1 - Not used, 2 - destination address:port, 3 - rule:ruleno
		if (isset ($message[2]))
		{
			$dest = explode (':', trim($message[2]));
			$website = $dest[0];
		}
		if (isset ($message[3]))
		{
			$rule = explode (':', trim($message[3]));
			if (isset($rule[1])) {$ruleno = $rule[1];}
		}
	}
	if (isset ($thisentry_split[3]) && $thisentry_split[3] == 'ACCEPT') {$logentrytype = 'accept';}
	// special case - if this is the default rule
	elseif (isset ($thisentry_split[3]) && $thisentry_split[3] == 'REJECT' && $ruleno == '1') {$logentrytype = 'default';}
	elseif (isset ($thisentry_split[3]) && $thisentry_split[3] == 'REJECT') {$logentrytype = 'reject';} 
	
	// entry with added span and <br>\n
	// If default rule link to dashboardaddrule.php - otherwise link to editrule.php
	if ($logentrytype == 'default')
	{
		$html_logentry = "<span class=\"log-$logentrytype\"><a href=\"dashboardaddrule.php?website=$website\">$thisentry</a></span><br>\n";
	}
	else
	{
		$html_logentry = "<span class=\"log-$logentrytype\"><a href=\"editrule.php?id=$ruleno\">$thisentry</a></span><br>\n";
	}
	
	// first is it view all - if so don't need to test
	if ($filter == 'all')
	{
		// add span if this is accept or reject
		$logentries[] = $html_logentry;
		continue;
	}
	// reject entry
	elseif (($filter == 'access' || $filter == 'reject') && isset ($thisentry_split[3]) && $thisentry_split[3] == "REJECT") 
	{
		$logentries[] = $html_logentry;
	}
	elseif (($filter == 'access' || $filter == 'accept') && isset ($thisentry_split[3]) && $thisentry_split[3] == "ACCEPT")
	{
		$logentries[] = $html_logentry;
	}
}
fclose ($logfile);

$html_log = '';

// Need at least 1 entry
if (count ($logentries) > 1)
{
	// order
	if ($order == 'oldest')
	{
		$direction = 1;
		if ($maxlines != 0 && count($logentries) > $maxlines)
		{
			$startpos = count($logentries) - $maxlines -1;
			$endpos = count($logentries) - 1;
		}
		else 
		{
			$startpos = 0;
			$endpos = count($logentries) - 1;
		}
	}
	else
	{
		$direction = -1;
		if ($maxlines != 0 && count($logentries) > $maxlines)
		{
			$endpos = count($logentries) - $maxlines -1;
			$startpos = count($logentries) - 1;
		}
		else 
		{
			$endpos = 0;
			$startpos = count($logentries) - 1;
		}
	}
	
	//$html_log .= "Start $startpos, End $endpos, Direction $direction";
	
	
	for ($i=$startpos; $i!=$endpos; $i+=$direction)
	{
		$html_log .= $logentries[$i];
	}
}






?>
