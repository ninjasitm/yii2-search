<?php
//class that sets up and retrieves, deletes and handles modifying of contact data
class Deleter extends Helper
{
	//setup public data
	public $deleted;
	public $l = null;

	//setup private data
	private $subj_ids = array();
	
	//define constant data
	const dm = 'deleter';
	const id_pool = 'id_pool';

	//--------------public functions---------------\\
	public function __construct($db, $table=null)
	{
		if(class_exists("Logger"))
		{
			$this->l = new Logger(null, null, null, Logger::LT_DB);
		}
		parent::__construct(self::dm, $db, $table);
	}
	
	public function __destruct()
	{
		if($this->l && ($this->deleted >= 1))
		{
			$this->l->add_trans(parent::get_db(), parent::get_table(), "Delete Item", "On ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." deleted ($this->deleted) items from table ".parent::get_table()." in DB ".parent::get_db()."<br<br> Deleted IDs: <br>(".implode(', ', $this->subj_ids).")");
		}
		parent::close();
// 		parent::del(self::dm);
	}
	
	
	public function set_id($id)
	{
		parent::set(self::dm.".".$id, $this->primary_key);
	}
	
	public function del_id($id)
	{
		parent::voidbatch(self::dm.".".$id, self::dm);
	}
	
	//returnt he current data member
	public function get_dm()
	{
		return self::dm;
	}
	
	//manipulate pivate data--------------------------------|
	public function get_deleted()
	{
		return $this->deleted;
	}
	
	public function get_size()
	{
		return sizeof(parent::get(self::dm));
	}
	
	public function exists()
	{
		parent::execute("SELECT ".parent::primary_key." FROM ".parent::table." WHERE `".$this->primary_key."`='".$this->getID()."' LIMIT 1");
		return (parent::rows() == NULL) ? false : true;
	}
	//end section---------------------------------------------------|
	
	//database functions--------------------------------------------|

	//function to handle deletions
	public function delete($xor='AND')
	{
		$ret_val = array();
		$to_delete = array();
		$ids = parent::getval(self::dm);
		if($ids)
		{
			foreach($ids as $cid=>$key)
			{
				if(parent::check($key, $cid, parent::get_db(), parent::get_table()) !== false)
				{
// 					echo $sql."<br>";
//					switch(gettype($cid))
//					{
//						case 'string':
//						$cid = "'$cid'";
//						break;
//					}
					$this->deleted++;
					$to_delete[$cid] = $key;
					$ret_val[$cid] = true;
				}
				else
				{
					$ret_val[$cid] = false;
				}
			}
			if(parent::remove(array_values($to_delete), array_keys($to_delete), parent::get_table(), parent::get_db(), $this->deleted, null, $xor))
			{
				$this->subj_ids = array_keys($to_delete);
				parent::free();
			}
		}
		else 
		{
			$ret_val = false;
		}
		parent::del(self::dm);
		return $ret_val;
	}
	//end section---------------------------------------------------|

	//end database functions--------------------------------------------|
	
	//----------------------information functions------------------------\\
	
	//get id for current item
	public function getID()
	{
		$id = parent::get($this->primary_key);
		return $id['value'];
	}
	//private functions--------------------------------------------|
	
	private function set_deleted($num=1)
	{
		$this->deleted+=$num;
	}
}
?>
