<?php
/*** 
Site class for kidsafe
***/

class Site
{
	private $details = array('siteid'=>null, 'sitename'=>'', 'title'=>'', 'comments'=>'');
	
	
	public function __construct ($user_details=null)
	{
		if (is_array($user_details))
		{
			// load values from constructor over the default values
			foreach ($this->details as $key=>$value)
			{
				if (isset ($user_details[$key])) {$this->details[$key] = $user_details[$key];}
			}
		}
	}
	
	public function getId ()
	{
		return $this->details['siteid'];
	}

	public function setId ($id)
	{
		$this->details['siteid'] = $id;
	}

	
	public function getSitename ()
	{
		return $this->details['sitename'];
	}

	public function setSitename ($sitename)
	{
		$this->details['sitename'] = $sitename;
	}

	
	public function getTitle ()
	{
		return $this->details['title'];
	}
	
	public function setTitle ($title)
	{
		$this->details['title'] = $title;
	}
	
	public function getComments ()
	{
		return $this->details['comments'];
	}
	
	public function setComments ($comments)
	{
		$this->details['comments'] = $comments;
	}
	
}
?>
