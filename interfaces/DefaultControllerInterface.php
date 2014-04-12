<?php

namespace nitm\interfaces;

/**
	This is the interface for data and DB manipulation. This handles retrieval, creating and formatting of data
*/

interface DefaultControllerInterface 
{	
	//Return an array with specific parameters that determine what this controller suports
	public static function has();
}

?>