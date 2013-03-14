<?php 

class active_record{
	static protected $MYSQL_FORMAT = "Y-m-d H:i:s";
	
	protected $_columns_to_save_down;
		
	/**
	 * GetAll - Get all items.
	 * Legacy Support - Depricated
	 * 
	 * @param integer $limit Limit number of results
	 * @param string $order Column to sort by
	 * @param string $order_direction Order to sort by
	 * @return Array of items
	 */
	static public function getAll($limit = null, $order = null, $order_direction = "ASC"){
		$name = get_called_class();
		$result = $name::factory()->findByColumn(null, null, null, $limit, $order, $order_direction);
		return $result;
	}
	
	/**
	 * Start a search on this type of active record
	 * @return search
	 */
	static public function search(){
		$class = get_called_class();
		return new search(new $class);
	}
	
	/**
	 * Generic Factory contructor
	 * @return unknown
	 */
	public static function factory(){
		$name = get_called_class();
		return new $name();
	}
	
	/**
	 * Overridable __construct call
	 */
	public function __construct(){
		
	}
	
	/**
	 * Overridable __post_construct call
	 */
	public function __post_construct(){
		
	}
	
	/**
	 * Find an item by the Primary Key ID
	 * @param integer $id
	 */
	static public function loadById($id){
		$name = get_called_class();
		return $name::factory()->getById($id);
	}
	
	/**
	 * Find an item by the Primary Key ID
	 * @param integer $id
	 */
	public function getById($id){
		return $this->search()->where($this->get_table_primary_key(), $id)->execOne();
	}
	
	/**
	 * SearchByColumn - Find items by the column specified
	 * Legacy Support - Depricated
	 *
	 * @param string $column Column to search by
	 * @param string $value Value of column to search by
	 * @param string $operator Operator. Defaults to equals
	 * @param integer $limit Limit number of results
	 * @param string $order Column to sort by
	 * @param string $order_direction Order to sort by
	 * @return Ambigous <multitype:, multitype:unknown mixed >
	 */
	static public function searchByColumn($column, $value, $operator = null, $limit = null, $order = null, $order_direction = "ASC"){
		$name = get_called_class();
		return $name::factory()->findByColumn($column, $value, $operator, $limit, $order, $order_direction);
	}
	
	/**
	 * FindByColumn - Find items by the column specified
	 * Legacy Support - Depricated
	 * 
	 * @param string $column Column to search by
	 * @param string $value Value of column to search by
	 * @param string $operator Operator. Defaults to equals
	 * @param integer $limit Limit number of results
	 * @param string $order Column to sort by
	 * @param string $order_direction Order to sort by
	 * @return Ambigous <multitype:, multitype:unknown mixed >
	 */
	public function findByColumn($column=null, $value=null, $operator = null, $limit = null, $order = null, $order_direction = "ASC"){
		$s = $this->search();
		$s->where($column, $value, $operator);
		if($limit){
			$s->limit($limit);
		}
		if($order){
			$s->order($order, $order_direction);
		}

		return $s->exec();
	}
	
	/**
	 * Get the short alias name of a table.
	 * 
	 * @param string $table_name Optional table name
	 * @return string Table alias
	 */
	public function get_table_alias($table_name = null){
		if(!$table_name){
			$table_name = $this->get_table_name();
		}
		$bits = explode("_", $table_name);
		$alias = '';
		foreach($bits as $bit){
			$alias.=strtolower(substr($bit,0,1));
		}
		return $alias;
	}
	
	/**
	 * Get the table name
	 * @return string Table Name
	 */
	public function get_table_name(){
		return $this->_table;
	}

	/**
	 * Get table primary key column name
	 * 
	 * @return string
	 */
	public function get_table_primary_key(){
		$keys_search = db_query("SHOW INDEX FROM {$this->_table} WHERE Key_name = 'PRIMARY'");
		$keys = $keys_search->fetchAll();
		$primary_key = $keys[0]->Column_name;
		return $primary_key;
	}
	
	/**
	 * Get object ID
	 * @return integer
	 */
	public function get_id(){
		$col = $this->get_table_primary_key();
		if(property_exists($this,$col)){
			$id = $this->$col;
			if($id > 0){
				return $id;
			}
		}
		return FALSE;
	}
	
	/**
	 * Work out which columns should be saved down.
	 */
	protected function _calculate_save_down_rows(){
		if(!$this->_columns_to_save_down){
			foreach(get_object_vars($this) as $potential_column => $discard){
				switch($potential_column){
					case 'table':
					case substr($potential_column,0,1) == "_":
						// Not a valid column
						break;
					default:
						$this->_columns_to_save_down[] = $potential_column;
						break;
				}
			}
		}
	}
	
	/**
	 * Load an object from data fed to us as an array (or similar.)
	 * @param array $row
	 * @return active_record
	 */
	public function loadFromRow($row){
		$this->_columns_to_save_down = array_keys((array) $row);
		foreach($row as $column => &$value){
			$this->$column = &$value;
		}
		$this->__post_construct();
		return $this;
	}
	
	/**
	 * Save the selected record. 
	 * This will do an INSERT or UPDATE as appropriate
	 * @return active_record
	 */
	public function save(){
		// Calculate row to save_down
		$this->_calculate_save_down_rows();
		$primary_key_column = $this->get_table_primary_key();
		
		// Make an array out of the objects columns.
		$data = array();
		foreach($this->_columns_to_save_down as $column){
			// Never update the primary key. Bad bad bad.
			if($column != $primary_key_column){
				$data["`{$column}`"] = $this->$column;
			}
		}
		
		// If we already have an ID, this is an update.
		if($this->get_id()){
			$save_sql = db_update($this->_table);
			$save_sql->fields($data);
			$save_sql->condition($primary_key_column, $this->$primary_key_column);
			$save_sql->execute();
		}else{ // Else, we're an insert.
			$insert_sql = db_insert($this->_table);
			$insert_sql->fields($data);
			$new_id = $insert_sql->execute();
			$this->$primary_key_column = $new_id;
			$this->reload();
		}
		return $this;
	}
	
	/**
	 * Reload the selected record
	 * @return active_record
	 */
	public function reload(){
		$item = $this->getById($this->get_id());
		$this->loadFromRow($item);
		return $this;
	}

	/**
	 * Delete the selected record
	 * @return boolean
	 */
	public function delete(){
		db_delete($this->get_table_name())
			->condition($this->get_table_primary_key(), $this->get_id())
			->execute();
		return TRUE;
	}
	
	/**
	 * Take a string and make it websafe - Slugified.
	 * @param string $label
	 * @return string
	 */
	protected function _slugify($label){
		$label = preg_replace("/[^A-Za-z0-9 ]/", '', $label);
		$label = str_replace(" ", "-", $label);
		$label = urlencode($label);
		return $label;
	}

	/**
	 * Pull a database record by the slug we're given.
	 *
	 * @param $slug string Slug
	 *
	 * @return mixed
	 */
	static public function get_by_slug($slug){
		$slug_parts = explode("-", $slug, 2);
		return self::loadById($slug_parts[0]);
	}
}