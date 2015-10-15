<?php
/*** 
Template class for kidsafe
***/

class Template
{
	private $details = array('templateid'=>null, 'templatename'=>'', 'description'=>'', 'users'=>'', 'permission'=>'');
	
	public function __construct ($template_details=null)
	{
		if (is_array($template_details))
		{
			foreach ($this->details as $key=>$value)
			{
				if (isset ($template_details[$key])) {$this->details[$key] = $template_details[$key];}
			}
		}
	}

	public function getId ()
	{
		return $this->details['templateid'];
	}
	
	public function getName ()
	{
		return $this->details['templatename'];
	}
	
	public function getDescription ()
	{
		return $this->details['description'];
	}
	
	public function getUsers ()
	{
		return $this->details['users'];
	}
	
	public function getPermission ()
	{
		return $this->details['permission'];
	}



	
}
?>
