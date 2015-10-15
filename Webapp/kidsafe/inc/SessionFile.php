<?php
/*** 
SessionFile class for kidsafe
- Handles creating multiple sessions and updating the session file 
***/

class SessionFile
{
	private  $file_header = "# This is a system generated file\n# Do not edit it manually - instead use the PHP web page to login\n# See http://www.penguintutor.com/kidsafe for more information\n";
	private $filename;
	
	
	public function __construct ($filename)
	{
		$this->filename = $filename;
	}
	
	
	// Reads in file and returns all valid entries as a string (expired entries are ignored)
	public function readFile ()
	{
		global $debug;
		$all_entries = '';
		$fr = fopen($this->filename, 'r');
		if ($fr)
		{
			while (($buffer =fgets($fr, 4096)) !== false)
			{
				// If it's a comment / blank then ignore (we add std comments in writeFile)
				if (preg_match ('/^\s*$/', $buffer) || preg_match('/^\s*#/', $buffer))
				{
					continue;
				}
			// skip expired	entries
			$session = explode (' ', $buffer);
			if (!isset ($session[3]) || ($session[3] > 0 && $session[3] < time()))
			{
				continue;
			}
			$all_entries .= $buffer;
			// could also add check for invalid entries below
		}
		// if error then die - should not get this in production, but prevents us overwriting file if error
		if (!feof($fr)) 
			{
			if ($debug) {print "Error in readFile ".$this->filename."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_FILEREAD, "Error reading file $this->filename");
    		exit(0);
			}
		fclose ($fr);
		}
	return $all_entries;
	}
	
	public function addEntry ($ipaddress, $permission, $username, $expirytime, $logintime, $browser)
	{
		$newentry = "\n$ipaddress $permission $username $expirytime $logintime $browser";
		$this->writeFile($this->readFile().$newentry);
	}
	

	public function writeFile ($contents)
	{
		$fh = fopen($this->filename, 'w');
		if ($fh)
		{
			fwrite ($fh, $this->file_header);
			fwrite ($fh, $contents);
		}
		else 
		{
			if (isset($debug) && $debug) {print "Error in writeFile ".$this->filename."\n";}
    		$err =  Errors::getInstance();
    		$err->errorEvent(ERROR_FILEWRITE, "Error writing to file ".$this->filename);
    		exit(0);
		}
	}
	
}
?>
