<?php 
class search{
	private $model;
	private $conditions;
	private $order;
	private $limit;
	private $offset = 0;

	public function __construct($model){
		$this->model = $model;
	}

	public function where($column, $value, $operation = null){
		if(!$operation){
			$operation = '=';
		}
		$this->conditions[] = new search_condition($column, $value, $operation);
		return $this;
	}

	public function limit($limit,$offset = 0){
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}

	public function order($column, $direction = 'DESC'){
		$this->order[] = array('column' => $column, 'direction' => $direction);
		return $this;
	}

	public function exec(){
		$select = db_select($this->model->get_table_name(), $this->model->get_table_alias());
		$select->fields($this->model->get_table_alias());
		
		// Add WHERE Conditions
		foreach((array) $this->conditions as $condition){
			$select->condition($condition->get_column(), $condition->get_value(), $condition->get_operation());
		}
		
		// Build ORDER SQL if relevent
		if($this->order){
			foreach($this->order as $order){
				$select->orderBy($order['column'], $order['direction']);
			}
		}
		
		// Build LIMIT SQL if relevent
		if($this->limit){
			$select->range($this->offset, $this->limit);
		}
		
		// If this is a versioned object, only get the latest one
		if($this->model instanceof versioned_active_record){
			$select->orderBy('version', 'ASC');
		}
		
		// Get objects
		$class = get_class($this->model);
		$response = $select->execute();
		$results = array();
		while($result = $response->fetchObject($class)){
			// If the item is versioned, we need to check if it uses logical deletion, and discard deleted rows. 
			if($this->model instanceof versioned_active_record && $this->model->use_logical_deletion()){
				if($result->deleted == 'No'){
					// Not deleted, add it.
					$results[$result->get_id()] = $result;
				}else{
					// Unset any older loaded-in version
					unset($results[$result->get_id()]);
				}
			}else{
				$results[$result->get_id()] = $result;
			}
		}
		return $results;
	}

	public function execOne(){
		// If this isn't a versioned active record, limit to 1.
		if(!$this->model instanceof versioned_active_record){
			$this->limit(1);
		}
		
		// Get all the corresponding items
		$results = $this->exec();
		
		// Return the first result. Yes, that is what reset() does. :|
		if(reset($results) !== NULL){
			return reset($results);
		}
		return FALSE;
	}
}