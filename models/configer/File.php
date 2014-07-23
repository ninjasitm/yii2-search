<?php

namespace nitm\models\configer;

use Yii;
use nitm\helpers\Directory;

/**
 * Text fiale parser for configer
 */
class File extends \yii\helpers\FileHelper
{
	public $sections;
	
	protected $canWrite;
	protected $contents;
	
	private $filemode = 0775;
	private $handle = false;
	
	public function canWrite()
	{
		return $this->canWrite;
	}
	
	public function prepare()
	{
		$this->canWrite = false;
		array_multisort($data, SORT_ASC);
		$container = stripslashes($container);
		if((file_exists($container)) && (sizeof($data) > 0))
		{
			foreach ($data as $key => $item)
			{
				if (is_array($item))
				{
					$sections .= "\n[{$key}]\n";
					foreach ($item as $key2 => $item2)
					{
						if (is_numeric($item2) || is_bool($item2))
						{
							$sections .= "{$key2} = {$item2}\n";
						}
						else
						{
							$sections .= "{$key2} = {$item2}\n";
						}
					}     
				}
				else
				{
					if(is_numeric($item) || is_bool($item))
					{
						$this->contents .= "{$key} = {$item}\n";
					}
					else
					{
						$this->contents .= "{$key} = {$item}\n";
					}
				}
			}
			$this->contents .= $sections;
			$this->canWrite = true;
		}
	}
	
	public function write($container, $backups=false)
	{
		$ret_val = false;
		if(is_resource($this->open($container, 'write')))
		{
			fwrite($this->handle, stripslashes($this->contents));
			$ret_val = true;
		}
		$this->close();
		if($this->backups && !empty($this->contents))
		{
			$backup_dir = "/backup/".date("F-j-Y")."/";
			$container_backup = ($container[0] == '@') ? $this->dir['default'].$backup_dir.substr($container, 1, strlen($container)) : dirname($container).$backup_dir.basename($container);
			$container_backup .= '.'.date("D_M_d_H_Y", strtotime('now')).$this->backupExtention;
			if(!is_dir(dirname($container_backup)))
			{
				mkdir(dirname($container_backup), $this->filemode, true);
			}
			fopen($container_backup, 'c');
			chmod($container_backup, $this->filemode);
			if(is_resource($this->open($container_backup, 'write')))
			{
				fwrite($this->handle, stripslashes($this->contents));
			}	
			$this->close();
		}
		return $ret_val;
	}
	 
	public function read($contents, $commentchar=';')
	{
		switch(!empty($this->contents))
		{
			case true:
			$section = '';
			$this->contents = array_filter((is_null($this->contents)) ? $contents : $this->contents);
			$commentchar = is_null($commentchar) ? ';' : $commentchar;
			$this->config['current']['sections'] = ['' => "Select section"];
			if(is_array($this->contents) && (sizeof($this->contents) > 0))
			{
				foreach($this->contents as $filedata) 
				{
					$dataline = trim($filedata);
					$firstchar = substr($dataline, 0, 1);
					if($firstchar!= $commentchar && $dataline != '') 
					{
						//It's an entry (not a comment and not a blank line)
						if($firstchar == '[' && substr($dataline, -1, 1) == ']') 
						{
							//It's a section
							$section = substr($dataline, 1, -1);
							$this->config['current']['sections'][$section] = $section;
							$ret_val[$section] = [];
						}
						else
						{
							$model = new Value(["value" => $value, "comment" => @$comment, 'sectionid' => $section, "name" => $key, "unique_name" => "$section.$key"]);
							//It's a key...
							$delimiter = strpos($dataline, '=');
							if($delimiter > 0) 
							{
								//...with a value
								$key = trim(substr($dataline, 0, $delimiter));
								$value = trim(substr($dataline, $delimiter + 1));
								if(substr($value, 1, 1) == '"' && substr($value, -1, 1) == '"') 
								{ 
									$value = substr($value, 1, -1); 
								}
								$model->value = $value;
								//$ret_val[$section][$key] = stripslashes($value);
								//we may return comments if we're updating
								$ret_val[$section][$key] = $model;
							}
							else
							{
								//we may return comments if we're updating
								//...without a value
								$ret_val[$section][trim($dataline)] = $model;
							}
						}
					}
				}
			}
			break;
		}
	}
	
	public function getFiles($in, $namesOnly)
	{
		$directory = new Directory();
		$ret_val = [];
		switch(is_dir($in))
		{
			case true:
			foreach(scandir($in) as $container)
			{
				switch($namesOnly)
				{
					case true:
					switch(is_dir($in.$container))
					{
						case true:
						$ret_val[$container] = $container;
						break;
					}
					break;
					
					default:
					switch(1)
					{
						case is_dir($in.$container):
						switch($multi)
						{
							case true:
							$ret_val[$container] = $directory->getFilesMatching($in.$container, $multi, $containers_objectsnly);
							break;
						}
						break;
						
						case $container == '..':
						case $container == '.':
						break;
						
						default:
						$info = pathinfo($in.$container);
						switch(1)
						{
							case true:
							$label = str_replace(['-', '_'], ' ', $info['filename']);
							$ret_val[$info['filename']] = $label;
							break;
						}
						break;
					}
					break;
				}
			}
			break;
		}
		return $ret_val;
	}
	
	public function load($file, $force)
	{
		$ret_val = '';
		switch(file_exists($file))
		{
			case true:
			switch((filemtime($file) >= fileatime($file)) || ($force === true))
			{
				case true: 
				if(is_resource($this->open($file, 'read')))
				{
					$this->contents = file($file, 1);
					$this->close();
					$ret_val = $this->contents;
				}
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	public function createSection($name)
	{
		$args['command'] = "sed -i '\$a\\\n\\n[%s]' ";
		return $this->command($command, [$name]);
	}
	
	public function createValue($section, $name, $value)
	{
		$args['command'] = "sed -i '/\[%s\]/a %s = %s' ";
		return $this->command($command, [$section, $name, $value]);
	}
	
	public function updateSection($section, $name)
	{
		$args['command'] = 'sed -i -e "s/^\[%s\]/%s/" ';
		return $this->command($command, [$section, $name]);	
	}
	
	public function updateValue($section, $name, $value)
	{
		$args['command'] = 'sed -i -e "/^\[%s\]/,/^$/{s/%s =.*/%s = %s/}" ';
		return $this->command($command, [$section, $name, $name, $value]);	
	}
	
	public function deleteSection($section, $name)
	{
		$args['command'] = "sed -i '/^\[%s\]/,/^$/d' ";
		return $this->command($command, [$section, $name]);	
	}
	
	public function deleteValue($section, $name, $value)
	{
		$args['command'] = "sed -i '/^\[%s\]/,/^$/{/^%s =.*/d}' ";
		return $this->command($command, [$section, $name, $name, $value]);	
	}
	
	public function deleteFile($file)
	{
		$args['command'] = "rm -f '%s'";
		return $this->command($command, [$file]);	
	}
	
	private function command($comand, $args=null)
	{
		$args['command'] = vsprintf($args['command'], array_map(function ($v) {return preg_quote($v, DIRECTORY_SEPARATOR);}, $args['args'])).' "'.$container.'.'.$this->types[$this->location].'"';
		exec($args['command'], $output, $cmd_ret_val);
		return $cmd_ret_val;
	}
	
	public function createFile($name)
	{
		$ret_val = false;
		switch(file_exists($name))
		{
			case false:
			switch($this->open($name, 'c'))
			{
				case true:
				$ret_val = true;
				chmod($name, $this->filemode);
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	private function open($container, $rw='read')
	{
		if(!$container)
		{
			die("Attempting to open an empty file\n$this->open($container, $rw, $mode) (2);");
		}
		//the handle that neds to be returned if succesful
		$ret_val = false;
		$continue = false;
		$flmode = null;
		switch($rw)
		{
			case 'this->canWrite':
			$mode = 'w';
			switch(file_exists($container))
			{
				case true:
				$this->handle = @fopen($container, $mode);
				switch(is_resource($this->handle))
				{
					case true:
					$continue = true;
					$flmode = LOCK_EX;
					break;
					
					default:
					@chmod($container, $this->filemode);
					$this->handle = @fopen($container, $mode);
					switch(is_resource($this->handle))
					{
						case true:
						$continue = true;
						$flmode = LOCK_EX;
						break;
						
						default:
						break;
					}
					break;
				}
				break;
			}
			switch(is_resource($this->handle))
			{
				case false:
				die("Cannot open $container for writing\n$this->open($container, $rw) (2);");
				break;
			}
			break;
		
			default:
			$mode = 'r';
			$this->handle = @fopen($container, $mode);
			switch(is_resource($this->handle))
			{
				case true:
				$continue = true;
				$flmode = LOCK_SH;
				break;
				
				default:
				//die("Cannot open $container for reading\n<br>$this->open($container, $rw) (2);");
				break;
			}
			break;
		}
		if($continue)
		{
			$interval = 10;
			while(flock($this->handle, $flmode) === false)
			{
				//sleep a little longer each time (in microseconds)
				usleep($interval+10);
			}
			$ret_val = $this->handle;
		}
		return $ret_val;
	}
	
	private function close()
	{
		if(is_resource($this->handle))
		{
			flock($this->handle, LOCK_UN);
			fclose($this->handle);
		}
	}
}
