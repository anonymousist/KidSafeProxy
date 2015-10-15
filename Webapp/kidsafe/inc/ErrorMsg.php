<?php
// class to hold an individual error msg
// must have Errors class included to provide constants?

/** Copyright Information (GPL 3)
Copyright Stewart Watkiss 2013

This class is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This class is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this code.  If not, see <http://www.gnu.org/licenses/>.
**/

class ErrorMsg 
{
	private $error_num;
	private $error_text;
	
	public function __construct($error_num, $error_txt)
	{
		$this->error_num = $error_num;
		$this->error_txt = $error_txt;
	}

	// gets msgs in a human readable format (num - text)
	// error_level is an optional parameter	
	// no error level return all
	// with int passed only return if error_num is less than the defined level
	public function getMsg ($error_level = INFO_LEVEL)
	{
		if ($this->error_num < $error_level) {return $this->error_num." - ".$this->error_txt;}
		else {return "";}
	}
	
	public function getLevel ()
	{
		return $this->error_num;
	}
	
}
	
