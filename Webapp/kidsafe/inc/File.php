<?php
/*** 
File class for kidsafe
***/

class File
{
	// headers are hardcoded here
	private $file_header = "# This is a system generated file\n# Do not edit it manually - instead use the PHP web page to configure the rules\n# See http://www.penguintutor.com/kidsafe for more information\n";
	private $filename;
	
	public function __construct ($filename)
	{
		$this->filename = $filename;
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
    		$err->errorEvent(ERROR_FILEWRITE, "Error writing to file $this->filename");
    		exit(0);
		}
	}
	
	
}
?>
