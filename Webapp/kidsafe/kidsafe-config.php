<?php

/** 
kidsafe child safe proxy server using squid
see http://www.penguintutor.com/kidsafe

kidsafe-config.php - configuration file for php pages
- used by blocked.php, addsafe.php
**/

// What you call the admin by (eg. admin / parent / supervisor) 
$adminname = 'parent';


// path and filename to the rule and session files
$rulesfilename = '/opt/kidsafe/kidsafe.rules';
$sessionfilename = '/opt/kidsafe/kidsafe.session';

// logfile (used when viewing log)
$logfilename = '/var/log/squid3/kidsafe.log';

// what is the default deny rule number (log-default)
$defaultrule = 1;


// Mysql database settings
$dbsettings = array('hostname'=>'localhost', 'username'=>'kidsafe', 'password'=>'H386Nhdheinf67190hNHUkdhtodn137bbv', 'database'=>'kidsafe', 'tableprefix'=>'');

// choices for time to login / rule duration
$timeoptions = array('10 mins', '1 hour', '2 hours', '4 hours', '24 hours');


// friendly names for user levels (0 to 10)
// any not listed can still exist, but won't be able to create through edituser
$userlevelnames = array ('0'=>'Guest', '1'=>'Child', '2'=>'Child + apps', '5'=>'Teen', '6'=>'Teen + apps', '9'=>'Adult', '10'=>'Owner'); 


// Normally this is set to true which prevents connections for local host
// This is normally required to be True as otherwise it will allow incorrect configuration on client browsers in which case login won't work anyway
// Set to false if allow tunnel connections via ssh or to allow local computer to use proxy
$nolocal = False; 


?>
