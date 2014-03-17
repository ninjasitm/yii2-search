<?php
//class that sets up and retrieves, deletes and handles modifying of contact data
class Updater extends Helper
{
	//setup public data
	public $updated = 0;
	public $l = null;
	public $status = null;
	public $u_data_size = 0;
	public $unique_func_used = 0;
	public $allow_update = false;
	public $new_table = 'new_update_table';

	//setup private data
	private $subj_ids = array();
	
	//protected data
	protected $ufields = array();
	protected $fail_on_error = false;
	
	//define constant data
	const dm = 'updater';
	const comparer = 'comparer';
	const id_pool = 'id_pool';
	const primary_identifier = 'updater_uni_id';
	const table_identifier = 'updater_uni_table';
	const database_identifier = 'updater_uni_db';
	
	//progress indicators
	const STAGE_1 = "Begin Construction";
	const STAGE_2 = "Set Compare Data";
	const STAGE_3 = "Set New Data With Unique Function";
	const STAGE_4 = "Check Data Through Comparision";
	const STAGE_5 = "Perform Update";
	const STAGE_6 = "Update Complete";

	//define private data
	private $compare = false;
	
	//--------------public functions---------------\\
	public function __construct($db, $table=NULL, $compare=false, $fail = false)
	{
		if(class_exists("Logger"))
		{
			$this->l = new Logger(null, null, null, Logger::LT_DB);
		}
		$this->compare = $compare;
		$this->status = self::STAGE_1;
		$this->fail_on_error = $fail;
		parent::__construct(self::dm, $db, $table, $compare);
		parent::clear(self::dm);
	}
	
	public function __destruct()
	{
		if($this->l && ($this->updated >= 1))
		{
			$this->l->add_trans(parent::get_db(), parent::get_table(), "Update Item", "On ".date("F j, Y @ g:i a")." user ".$this->l->cur_user." updated ($this->updated) items in table ".parent::get_table()." in DB ".parent::get_db()."<br><br> Updated IDs: <br>(".implode(', ', $this->subj_ids).")");
		}
// 		parent::del(self::dm);
// 		parent::del(self::comparer);
// 		
	}
	//manipulate pivate data--------------------------------|
	//set section
	public function get_ids()
	{
		$ret_val = parent::getval(parent::helper.'.'.self::dm.'.'.self::id_pool);
// 		if(is_array($ret_val) && (sizeof($ret_val) == 1))
// 		{
// 			$ret_val = $ret_val[0];
// 		}
		return $ret_val;
	}
	
	public function get_dm()
	{
		return self::dm;
	}
	
	public function get_cmp()
	{
		return self::comparer;
	}
	
	public function set_id($id)
	{
		if($id)
		{
			parent::app(parent::helper.'.'.self::dm.'.'.self::id_pool, $id);
		}
	}
	
	public function del_id($id)
	{
		if($id)
		{
			parent::del(parent::helper.'.'.self::dm.'.'.self::id_pool, $id);
		}
	}
	
	public function get_updated()
	{
		return $this->updated;
	}
	
	public function add_field($field, $id)
	{
		if(!$id)
		{
			$this->error("ERROR You need to specify an ID to me so that I can setup the field data Updater::add_field($field, $id); Status: $this->status", true);
		}
		settype($id, 'int');
		$fields = is_array($field) ? $field : array($field);
		foreach($fields as $field)
		{
			$this->ufields[$id][] = $field;
		}
	}
	
	public function del_field($field, $id)
	{
		if(!$id)
		{
			$this->error("ERROR You need to specify an ID to me so that I can delete the field Updater::del_field($field, $id); Status: $this->status", true);
		}
		$fields = is_array($field) ? $field : array($field);
		foreach($fields as $field)
		{
			if(isset($this->ufields[$id]))
			{
				if(($index = array_search($field, $this->ufields[$id], true)) !== false)
				{
					unset($this->ufields[$id][$index]);
				}
			}
			parent::del(self::dm.'.'.$field.'.'.$id);
		}
		if(@sizeof($this->ufields[$id]) == 0)
		{
			unset($this->ufields[$id]);
		}
	}
	
	public function reset()
	{
		parent::del(self::dm);
	}
	
	public function get_update_fields()
	{
		return $this->ufields;
	}
	
	public function get_size()
	{
		return parent::size(self::dm, true);
	}
	
	public function set_compare($cIdx, $data, $id)
	{
		if(!$id)
		{
			$this->error("ERROR You need to specify an ID to me so that I can setup the compare data properly Updater::set_compare($cIdx, $data, $id); Status: $this->status", true);
		}
		$cIdx = is_array($cIdx) ? $cIdx : array($cIdx);
		$data = is_array($data) ? $data : array($data);
		foreach($cIdx as $idx=>$dx)
		{
			if(parent::in_session(self::comparer.".".$dx.'.'.$id, $data[$idx], true) === false)
			{
				parent::set(self::comparer.".".$dx.'.'.$id, $data[$idx], true);
			}
		}
		$this->status = self::STAGE_2;
	}
	
	public function set_unique($cIdx, $data, $id, $af=false)
	{
		if(!$id)
		{
			$this->error("ERROR You need to specify an ID to me so that I can setup the unique data properly Updater::set_unique($cIdx, $data, $id); Status: $this->status", true);
		}
		$cIdx = is_array($cIdx) ? $cIdx : array($cIdx);
		$data = is_array($data) ? $data : array($data);
		foreach($cIdx as $idx=>$dx)
		{
			parent::set(self::dm.".".$dx.'.'.$id, $data[$idx]);
			if($af === true)
			{
				$this->add_field($dx, $id);
			}
		}
		$this->set_ID($id);
		$this->status = self::STAGE_3;
		$this->unique_func_used = $this->unique_func_used + 1;
	}
	
	public function is_sane()
	{
		$ret_val = false;
		switch($this->status)
		{
			case self::STAGE_3:
			$ret_val = ($this->unique_func_used >= sizeof($this->get_IDS()));
			break;
			
			case self::STAGE_4:
			$ret_val = $this->allow_update;
			break;
			
			case self::STAGE_5:
			case self::STAGE_6:
			$ret_val = sizeof($this->u_data_size >= 1);
			break;
			
			default:
			if($this->compare === true)
			{
				$ret_val = (0 < parent::size(parent::comparer));
			}
			else
			{
				$ret_val = ($this->unique_func_used >= sizeof($this->get_IDS()));
			}
			break;
		}
		return $ret_val;
	}
	//end section---------------------------------------------------|
	
	//database functions--------------------------------------------|

	//update database with values retrieved from session
	
	public function prepare()
	{
		if(!$this->is_sane())
		{
			$this->error("ERROR: Trying to prepare data in an unsafe environment Updater::prepare() Status: $this->status");
		}
		$this->status = self::STAGE_4;
		$ids = $this->get_ids();
		if(sizeof($ids) >= 1)
		{
			parent::set_csdm(self::dm);
			$u = parent::getval(self::dm);
			$pri = parent::get_cur_pri();
			foreach($ids as $id)
			{
				$ret_val[$id] = 0;
				if(parent::check($pri, $id, parent::get_DB(), parent::get_table()) === false)
				{
					$this->error("ERROR the specified ID $id is invalid for table ".parent::get_table()." Updater::prepare(); Status: $this->status");
					$ret_val[$id] = false;
					$this->del_ID($id);
					continue;
				}
				foreach($u as $field=>$data)
				{
// 					pr($field);
// 					echo "$field value == ".parent::getval($field)." comparer value == ".parent::getval(self::comparer.".".$field)."<br>";
// 					continue;
					if($this->compare === true)
					{
						if(($new = parent::getval(self::dm.'.'.$field.'.'.$id)) === ($old = parent::getval(self::comparer.".".$field.'.'.$id)))
						{
// 							echo "Deleteing $field<br>";
							$this->del_field($field, $id);
						}
						else
						{
// 							echo "Adding $field oldval ($old) new val ($new)<br>";
// 							echo "Get String ==  ".self::dm.'.'.$field.'.'.$id."<br>";
							$this->add_field($field, $id);
							$ret_val[$id] = 1;
						}
					}
					else
					{
// 						echo "Compare == false<br>";
// 						echo "Adding $field<br>";
						$this->add_field($field, $id);
						$ret_val[$id] = 1;
					}
					
				}
			}
			parent::del(self::comparer);
// 			pr($_SESSION);
			if(sizeof($this->ufields) >= 1)
			{
				$this->allow_update = true;
				return $ret_val;
			}
			else
			{
				return false;
			}
		}		
		else
		{
			return false;
		}
	}
	
	public function update()
	{
		if(!$this->is_sane())
		{
			$this->error("ERROR: Update environment not sane Updater::update(); Status: $this->status");
			return false;
		}
		if(sizeof($this->ufields) == 0)
		{
			parent::del(self::comparer);
			parent::del(parent::helper.'.'.self::id_pool);
// 			echo "Returning from update 1<br>";
			return false;
		}
		$this->status = self::STAGE_5;
		parent::set_csdm(self::dm);
		$ret_val = array();
		foreach($this->ufields as $id=>$ufields)
		{	
			$items_data = "";
			$udate = array();
			foreach($ufields as $field)
			{
				switch($field)
				{
					case $this->new_table:
					parent::set_table(parent::getval(self::dm.".".$this->new_table.'.'.$id));
					continue;
					break;
					
					default:					
					if(is_array($data = parent::getval(self::dm.".".$field.'.'.$id)))
					{
						$data = serialize($data);
					}
					$udata[$field] = $data;
					break;
				}
			}
			if(!parent::update(array_keys($udata), array_values($udata), array('key' => $this->primary_key, 'data' => $id)))
			{
				parent::free();
				$ret_val[$id] = 0;
				$this->del_ID($id);
			}
			else
			{
				$this->updated++;
				$ret_val[$id] = 1;
				$this->subj_ids[] = $id;
			}
		}
		parent::del(self::dm);
		parent::set(parent::comparer, array());
		parent::del(parent::helper.'.'.self::dm.'.'.self::id_pool);
		$this->ufields = array();
		$this->status = self::STAGE_6;
		parent::close();
		return $ret_val;
	}
	//end section---------------------------------------------------|

	//end database functions--------------------------------------------|
	
	//----------------------information functions------------------------\\

	//private functions--------------------------------------------|
	private function error($string, $die=false)
	{
		switch($this->fail_on_error)
		{
			case true:
			trigger_error($string, E_USER_ERROR);
			break;
			
			case false:
			switch($die)
			{
				case true:
				trigger_error($string, E_USER_ERROR);
				break;
				
				case false:
				trigger_error($string, E_USER_WARNING);
				break;
			}
			break;
		}
	}
}
?>
