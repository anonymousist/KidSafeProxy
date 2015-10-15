<?php
/*** 
User class for kidsafe
***/

class User
{
	private $details = array('userid'=>null, 'username'=>'', 'accesslevel'=>'', 'fullname'=>'', 'password'=>'', 'status'=>false, 'loginexpiry'=>'*', 'supervisor'=>false, 'admin'=>false);
	
	
	public function __construct ($user_details=null)
	{
		if (is_array($user_details))
		{
			// load values from constructor over the default values
			foreach ($this->details as $key=>$value)
			{
				$this->details[$key] = $user_details[$key];
			}
		}
	}

	public function getId ()
	{
		return $this->details['userid'];
	}

	public function setId ($id)
	{
		$this->details['userid'] = $id;
	}
	
	public function getUsername ()
	{
		return $this->details['username'];
	}
	
	public function setUsername ($username)
	{
		$this->details['username'] = $username;
	}
	
	public function getAccess ()
	{
		return $this->details['accesslevel'];
	}
	
	public function setAccess ($access)
	{
		$this->details['accesslevel'] = $access;
	}
	
	
	public function getFullname ()
	{
		return $this->details['fullname'];
	}
	
	public function setFullname ($fullname)
	{
		$this->details['fullname'] = $fullname;
	}
	
	
	public function getPassword ()
	{
		return $this->details['password'];
	}
	
	public function setPassword ($password)
	{
		$this->details['password'] = $password;
	}
	
	// returns num seconds maximum login time, or 0
	public function getLoginexpiry ()
	{
		return $this->details['loginexpiry'];
	}
	
	
	public function setLoginexpiry ($expiry)
	{
		$this->details['loginexpiry'] = $expiry;
	}
	

	// The boolean function use is rather than get
	public function isEnabled ()
	{
		return (bool)$this->details['status'];
	}
	
	public function setEnabled ($status)
	{
		$this->details['status'] = $status;
	}

	
	
	public function isSupervisor ()
	{
		return (bool)$this->details['supervisor'];
	}

	
	public function setSupervisor ($status)
	{
		$this->details['supervisor'] = $status;
	} 
	
	
	public function isAdmin ()
	{
		return (bool)$this->details['admin'];
	}
	
	
	public function setAdmin ($status)
	{
		$this->details['admin'] = $status;
	}
	
	
}
?>
