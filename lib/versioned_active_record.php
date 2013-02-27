<?php 

class versioned_active_record extends active_record{
	public $version;
	public $created_date;
	public $created_uid;
	
	public function get_created_user(){
		$user = user_active_record::search()->where('uid', $this->created_uid)->execOne();
		return $user;
	}
	
	public function delete(){
		if($this->use_logical_deletion()){
			$this->deleted = 'Yes';
			$this->save();
			return TRUE;
		}else{
			return parent::delete();
		}
	}
	
	/**
	 * Test to see if this object uses logical deletion
	 * @return boolean
	 */
	public function use_logical_deletion(){
		if(isset($this->_cfg_deleteable)){
			if($this->_cfg_deleteable == TRUE){
				return TRUE;
			}
		}
		return FALSE;
	}
	
	public function save(){
		if(isset($this->version)){
			$this->version = $this->version + 1;
		}else{
			$this->version = 1;
		}
		$this->created_date = date(self::$MYSQL_FORMAT);
		$this->created_uid = $GLOBALS['user']->uid;
	
		// Calculate row to save_down
		$this->_calculate_save_down_rows();
		
		$primary_key_column = $this->get_table_primary_key();
		
		// Make an array out of the objects columns.
		$data = array();
		foreach($this->_columns_to_save_down as $column){
			$data["`{$column}`"] = $this->$column;
		}
		
		// Insert new version
		$insert_sql = db_insert($this->_table);
		$insert_sql->fields($data);
		
		$new_id = $insert_sql->execute();
		$this->$primary_key_column = $new_id;
		$this->reload();
		
		return $this;
	}
}