<?php

namespace nitm\models;

class Stats
{

	public function memoryUsage($peak=false)
	{
		$unit=array('b','kb','mb','gb','tb','pb');
		$real_mem = ($peak===true) ? memory_get_peak_usage() : memory_get_usage();
		return @round($real_mem/pow(1024,($i=floor(log($real_mem,1024)))),2).' '.$unit[$i];
	}
}

?>
