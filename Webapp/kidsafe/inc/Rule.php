<?php
/*** 
Rule class for kidsafe
***/

class Rule
{
	// default priority = 5000 is the default allow
	// use 3000 for deny before allow
	private $details = array('ruleid'=>null, 'siteid'=>0, 'users'=>'', 'permission'=>'', 'valid_until'=>'0', 'template'=>0, 'log'=>true, 'priority'=>5000, 'comments'=>'');
	
	
	public function __construct ($rule_details=null)
	{
		if (is_array($rule_details))
		{
			// load values from constructor over the default values
			foreach ($this->details as $key=>$value)
			{
				if (isset ($rule_details[$key])) {$this->details[$key] = $rule_details[$key];}
			}
		}
	}
	
	public function getId ()
	{
		return $this->details['ruleid'];
	}

	public function setId ($id)
	{
		$this->details['ruleid'] = $id;
	}
	
	public function getSiteid ()
	{
		return $this->details['siteid'];
	}

	public function setSiteid ($thissiteid)
	{
		$this->details['siteid'] = $thissiteid;
	}

	
	public function getUsers ()
	{
		return $this->details['users'];
	}
	
	public function setUsers ($thisusers)
	{
		$this->details['users'] = $thisusers;
	}
	
	public function getPermission ()
	{
		return $this->details['permission'];
	}
	
	public function setPermission ($thispermission)
	{
		// need to strip any spaces as otherwise resuts in corrupt file
		$this->details['permission'] = $thispermission;
	}
	
	public function getValiduntil ()
	{
		return $this->details['valid_until'];
	}
	
	public function setValiduntil ($thisvalid)
	{
		// If non-expiry then set to 0000-00-00 00:00
		if ($thisvalid == '*' || $thisvalid == 0)
		{
			$thisvalid = '0000-00-00 00:00';
		}
		$this->details['valid_until'] = $thisvalid;
	}
	
	
	public function getTemplate ()
	{
		return $this->details['template'];
	}

	public function setTemplate ($thistemplate)
	{
		$this->details['template'] = $thistemplate;
	}
	
	public function getLog ()
	{
		return $this->details['log'];
	}

	public function setLog ($status)
	{
		$this->details['log'] = $status;
	}
	
	public function getPriority ()
	{
		return $this->details['priority'];
	}
	
	public function setPriority ($thispriority)
	{
		$this->details['priority'] = $thispriority;
	}
	
	public function getComments ()
	{
		return $this->details['comments'];
	}
	
	public function setComments ($thiscomment)
	{
		$this->details['comments'] = $thiscomment;
	}
	
}
?>
