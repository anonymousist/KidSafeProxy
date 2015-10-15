<?php
/*** 
Parameters class for kidsafe
// handles processing of get / post parameters with additional security checking

Due to a refactor of the code this has now changed how it works
- preferred method is to state up front all parameters and parameter types required by the page on the constructor hash array
- alternative for compatibility with the older code is to leave blank and pick up entries from default list - in future this will be removed to make the code cleaner

Using constructor the parms can be either get or post - first look at get then post (one one parm found we stick with that type)

Also consider making the tests more accurate - datetime etc.

***/


class Parameters
{
	private $parms = array();
	// parms allowed in get - does not have to exist
	// calling code should handle if value does not exist ''
	// value is type of entry expected for checks - does not check for correct values - only for invalid attempts (eg. invalid chars)
	// valid values = domain (fqdn or ip address - including regexp / dot prefix), reltime (any relative time format - eg. 2 hours), url (full url including http(s):\\), website (either url or domain - as you may enter on url bar in browser) relurl (relative url eg. page.php), alphanum (strip spaces / no special chars), text (allows special chars - but convert any <> to &lt; / &gt; to prevent html code), int = integer (as per intval - this means won't accept 0 as a value)
	
	// need to create a proper datetime check
	
	// parameters used in get
	private $getparms = array ('host'=>'domain', 'timeallowed'=>'reltime', 'url'=>'url', 'source'=>'domain', 'addpermission'=>'alphanum', 'redirect'=>'relurl', 'message'=>'alphanum', 'id'=>'alphanum', 'username'=>'alphanum', 'filter'=>'alphanum', 'start'=>'int', 'maxlines'=>'int', 'order'=>'alphanum');
	 
	private $postparms = array ('host'=>'domain', 'site'=>'domain', 'timeallowed'=>'reltime', 'url'=>'url', 'source'=>'domain', 'addgroup'=>'alphanum', 'addtemplate'=>'alphanum', 'user'=>'alphanum', 'username'=>'alphanum', 'password'=>'alphanum', 'site'=>'domain', 'comments'=>'text', 'add'=>'alphanum', 'duration'=>'reltime', 'addpermission'=>'alphanum', 'allowlevel'=>'int', 'newpassword'=>'alphanum', 'repeatpassword'=>'alphanum', 'id'=>'alphanum', 'sites'=>'int', 'template'=>'alphanum', 'custom-groups'=>'alphanum', 'expiry'=>'datetime', 'action'=>'alphanum', 'permission'=>'int', 'log'=>'bool', 'priority'=>'int', 'sitename'=>'domain', 'title'=>'text', 'fullname'=>'text', 'access'=>'int', 'status'=>'bool', 'loginexpiry'=>'int', 'supervisor'=>'bool', 'admin'=>'bool');
	
	// set type to 'get' if we have 1 or more get entries
	// if not get entries, but are post set to 'post'
	// otherwise leave blank
	private $type = ''; 
	
	
	// constructor - default is an empty array (depreciated)
	// should provide all parameters and types in constructor array
	public function __construct ($page_parms=array())
	{
		// legacy mode 
		// all code from here to end legacy mode can be removed when all pages upgraded
		if (empty($page_parms))
		{
			// handle get first - if no get then post (can't have both)
			foreach ($this->getparms as $key=>$value)
			{
				if (isset ($_GET[$key]))
				{
					$this->type = 'get';
					$this->parms[$key] = $this->_checkParm ($_GET[$key], $key, $value);
				}
			}
			if ($this->type != 'get')
			{
				foreach ($this->postparms as $key=>$value)
				{
					if (isset ($_POST[$key]))
					{
						$this->type = 'post';
						$this->parms[$key] = $this->_checkParm ($_POST[$key], $key, $value);
					}
				}
			}

		}
		// end legacy mode
		else // new method - preferred - all required parameters are in $page_parms
		{
			// first try get against all parms
			foreach ($page_parms as $key=>$value)
			{
				if (isset ($_GET[$key]))
				{
					// set type to get - means we won't check post if we find any of the parms in get
					$this->type = 'get';
					$this->parms[$key] = $this->_checkParm ($_GET[$key], $key, $value);
				}
			}
			// if we didn't find any gets now try posts
			if ($this->type != 'get')
			{
				foreach ($page_parms as $key=>$value)
				{
					if (isset ($_POST[$key]))
					{
						$this->type = 'post';
						$this->parms[$key] = $this->_checkParm ($_POST[$key], $key, $value);
					}
				}
			}
		}
		if (isset($debug) && $debug) 
		{
			foreach ($this->parms as $key => $value)
			{
				print ("Parm $key = $value \n");
			}
		}
	}

	// returns the paramter to the calling code
	// if no entry returns ''	
	public function getParm ($parm)
	{
		if (isset ($this->parms[$parm])) { return $this->parms[$parm];}
		else {return "";}
	}
	

	
	// revalidate parameter using stated type
	// can be used to provide a more detailed validation or validation after further processing
	// value is instead of parm 
	public function validateParm ($value, $parm, $type)
	{
		return $this->_checkParm ($value, $parm, $type);
	}
	
	

	// perform basic security checking
	// any failures then we enter a '' into the value
	function _checkParm ($value, $parmname, $parmtype)
	{
		if ($parmtype=='url')
		{
			
			$status = $this->_checkUrl ($value);
			// if error code
			if ($status[0] != 0)
			{
				if ($status[0] == 1) {return '';}
				else
				{
					$message = $status[1];
					if (isset($debug) && $debug) {print "Error in parameter $parmname - $message\n";}
					$err =  Errors::getInstance();
					$err->errorEvent(ERROR_PARAMETER, "Error in parameter $parmname - $message\n"); 
					return '';
				}
			}
			else 
			{
				// we have now verfied url as being safe
				return $status[1];
			}
			
		}
		elseif ($parmtype == 'relurl')
		{
			$unsafe_page = $value;
			// check that this is only has allowed characters (either  alphanumeric normal characters and .(* beginning only) - or it's a regexp)
			if (preg_match('/^[\w-\.]+$/', $unsafe_page))
			{
				return $unsafe_page;
			}
			else 
			{
				return "";
			}
		}
		elseif ($parmtype == 'domain')
		{
			$status = $this->_checkDomain ($value);
			// if error code
			if ($status[0] != 0)
			{
				if ($status[0] == 1) {return '';}
				else
				{
					$message = $status[1];
					if (isset($debug) && $debug) {print "Error in parameter $parmname - $message\n";}
					$err =  Errors::getInstance();
					$err->errorEvent(ERROR_PARAMETER, "Error in parameter $parmname - $message\n"); 
					return '';
				}
			}
			else
			{
				return $status[1];
			}
		}
		// rel time - just check for xx mins / xx hours etc. actually use strtotime to parse 
		elseif ($parmtype == 'reltime')
		{
			$unsafe_time = $value;
			// check that this is only has allowed characters (either  alphanumeric normal characters and .(* beginning only) - or it's a regexp)
			// just allow minutes or hours - don't do days or secs
			if (preg_match('/^\d+\s*(min(utes)?)|(hours?)$/', $unsafe_time))
			{
				return $unsafe_time;
			}
			// always
			elseif ($unsafe_time == 'Always')
			{
				return 'Always';
			}
			else
			{
				return '';
			}
		}
		// alphanum and -_ and special case just '*'
		elseif ($parmtype == 'alphanum')
		{
			// just allow printable chars (\w)
			if (preg_match('/^[\w-_]+$/', $value))
			{
				return $value;
			}
			elseif ($value == '*')
			{
				return $value;
			}
			else
			{
				return '';
			}
		}
		// just strip out <> - replace with &lt;&gt;
		elseif ($parmtype == 'text')
		{
			$unsafe_text = $value;
			$unsafe_text = preg_replace ('/</', '&lt;', $unsafe_text);
			$unsafe_text = preg_replace ('/>/', '&gt;', $unsafe_text);
			return $unsafe_text;
		}
		// Need to properly test datetime - currently just using datetime
		// needs to allow * or 0 (equivalant to 0000-00-00 00:00)
		elseif ($parmtype == 'datetime')
		{
			$unsafe_text = $value;
			$unsafe_text = preg_replace ('/</', '&lt;', $unsafe_text);
			$unsafe_text = preg_replace ('/>/', '&gt;', $unsafe_text);
			return $unsafe_text;
		}
		// use intval()
		elseif ($parmtype == 'int')
		{
			$int_value = intval($value);
			if ($int_value != 0) {return $int_value;}
			else {return '';}
		}
		// bool - allow 1 0 true false
		// default for invalid = false (therefore calling functions should default as false = safe value - eg. Admin only if true
		elseif ($parmtype == 'bool')
		{
			if ($value == 'true' || $value == '1')
			{
				return true;
			}
			else {return false;}
		}
		elseif ($parmtype=='website')
		{
			// check for a url first - if not check for domain
			$status = $this->_checkUrl ($value);
			if ($status[0] == 0)
			{
				return $status[1];
			}
			$status = $this->_checkDomain ($value);
			if ($status[0] == 0)
			{
				return $status[1];
			}
			else
			{
				$message = $status[1];
				if (isset($debug) && $debug) {print "Error in parameter $parmname - Not a url or domain\n";}
				$err =  Errors::getInstance();
				$err->errorEvent(ERROR_PARAMETER, "Error in parameter $parmname - Not a url or domain\n"); 
				return '';
			}
		}
		// possible invalid type
		return "";
	}
	

	// _checkUrl and _checkDomain have been created as their own functions to reduce redundancy and
	// complexity in the _checkParm function - this could be done with the others for consistancy
	
	// returns array with (successcode, "Message")
	// successcode = 0 for successful, otherwise 1 don't issue message
	// 2 issue error message
	function _checkUrl ($value)
	{
		// if empty (eg. gone direct to addrule.php) then return ''
		if ($value == '') {return array(1,'No value');}
		// urls are encoded so we decode
		$unsafe_url = urldecode($value);
		
		
		// check url is valid (# used as regular expression delimeter so we don't have to escape /) and separate out the domain part 
		$url_array = parse_url($unsafe_url);
		// check that this is a http / https (do not allow file://)
		if ($url_array == false || !isset($url_array['scheme']) || ($url_array['scheme']!= 'http' && $url_array['scheme']!= 'https'))
		{
			return (array('2', "Unrecognised scheme\n$value"));
		}
		
		// check no < are in the url which could be used for xss 
		// if invalid then we set to '' - also set ['error'] to provide a message back
		if (preg_match ('/&lt;|</', $unsafe_url)) 
		{
			return (array('2', "Invalid character"));
		}
		// if passwed above tests then valid url
		else
		{
			return (array(0, $unsafe_url));
		}
	}
	
	
	// check for domain entry
	function _checkDomain ($value)
	{
		$unsafe_host = $value;
		// check that this is only has allowed characters (either  alphanumeric normal characters and .(* beginning only) - or it's a regexp)
		if (preg_match('/^\*?[\w-\.]+$/', $unsafe_host))
		{
			return (array (0, $unsafe_host));
		}
		// check if it's a regexp (just test regexp doesn't give a false (different from 0 if it doesn't match)
		elseif (strpos ($unsafe_host, '/') === 0)
		{
			// reutrns false on invalid - 0 on no match, 1 on match
			if (@preg_match($unsafe_host, '') === false)
			{
				return (array (2,"Not a valid domain or regular epxression"));
			}
			else 
			{
				return (array (0, $unsafe_host));
			}
		}
	}

	
	
}
?>
