<?php

namespace nitm\interfaces;

/**
	This is the interface for data and DB manipulation. This handles retrieval, creating and formatting of data
*/

interface DataInterface 
{	
	//Return special SQL used for checking activity
	//public static function activity();
	
	//Return an array with specific parameters that determine whether a record should contain something
	public static function has();
}

?>