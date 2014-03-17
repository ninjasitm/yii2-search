<?php
//class that sets up and retrieves, deletes and handles modifying of contact data
class Adder extends Helper
{
	//setup public data
	public $added = 0;
	public $l = null;
	
	//define constant data
	const dm = 'adder';

	//--------------public functions---------------\\
	public function __construct($db, $table=null, $log=true)
	{
		if($log)
		{
			if(class_exists("Logger"))
			{
				$this->l = new Logger(null, null, null, Logger::LT_DB);
			}
		}
// 		echo "Creating Adder object for $table in DB $db<br>";
		parent::__construct(self::dm, $db, $table);
		return true;
	}
	
	public function __destruct()
	{
		if($this->l && ($this->added >= 1))
		{
			$this->l->add_trans(parent::get_db(), parent::get_table(), "Add Item", "On ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." added (".$this->added.") items to table ".parent::get_table()." in DB ".parent::get_db());
			$this->added = 0;
		}
		parent::del(self::dm);
	}
	
	public function get_dm()
	{
		return self::dm;
	}
	
	//manipulate pivate data--------------------------------|
	public function get_added()
	{
		return $this->added;
	}
	
	public function newID()
	{
		parent::select(array("$this->id+1"), true, null, $this->id, false, 1);
		if(!parent::rows())
		{
			$m_id = 1;
		}
		else
		{
			$max_id = parent::result(DB::R_ASS);
			$m_id = $max_id["$this->id+1"];
		}
		parent::set($this->id, $m_id);
		return true;
	}

/*	public function entryexists($key)
	{
		parent::execute("SELECT $this->primary_key FROM $this->table WHERE $this->primary_key='".$this->getID()."'");
		return (parent::rows() == NULL) ? false : true;
	}*/
	//end section---------------------------------------------------|
	
	//database functions--------------------------------------------|
	public function add()
	{
		$ret_val = true;
		parent::set_csdm(self::dm);
		$a = parent::getval(self::dm);
		$fields = @array_keys($a);
		$values = @array_values($a);
		$this->added = 0;
		switch(empty($fields))
		{
			case true:
			$ret_val = false;
			break;
			
			default:
			if(!$this->add_recursive($fields, $values))
			{
				$ret_val =  false;
			}
			break;
		}
		parent::del(self::dm);
		parent::close();
		return $ret_val;
	}

// 	recursively add entries
	private final function add_recursive($fields, $values)
	{
		$ret_val = true;
		if(is_array($values[0]))
		{
			for($jdx = 0; $jdx < sizeof($values[0]); $jdx++)
			{
				for($kdx = 0; $kdx < sizeof($fields); $kdx++)
				{	
					switch(@is_array($values[$kdx][$jdx]))
					{
						case true:
						$data[] = serialize($values[$kdx][$jdx]);
						break;
					
						default:
						@$data[] = $values[$kdx][$jdx];
						break;
					}
				}
				$this->added = $this->added + 1;
// 				$data = array();		
			}
			if(parent::insert($fields, $data))
			{
				parent::free();
				$this->added = $this->added + 1;
				$ret_val = true;
			}
			else
			{
				$ret_val = false;
			}
			unset($data);	
		}
		else
		{
			if(parent::insert($fields, $values))
			{
				parent::free();
				$this->added = $this->added + 1;
 				$ret_val = true;
			}
			else
			{
				$ret_val = false;
			}
		}
		return $ret_val;		
	}
	//end section---------------------------------------------------|

	//end database functions--------------------------------------------|

	//private functions--------------------------------------------|
	
}
?>
