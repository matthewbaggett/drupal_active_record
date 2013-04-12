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
		
		// If we are NOT ordered, and we ARE weighted, order by Weight ASC
		if(count($this->order) == 0 && $this->model instanceof versioned_active_record && $this->model->use_weighting()){
			$this->order[] = array('column' => 'weight', 'direction' => 'ASC');
		}
		
		// Build ORDER SQL if relevent
		if($this->model instanceof versioned_active_record){
			// If this is a versioned object, we'll sort it in PHP, and use MySQL to do the heavy lifting on the version instead.
			$select->orderBy('version', 'ASC');
		}else{
			if($this->order){
				foreach($this->order as $order){
					$select->orderBy($order['column'], $order['direction']);
				}
			}
		}
		
		// Build LIMIT SQL if relevent
		if($this->limit){
			$select->range($this->offset, $this->limit);
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
				$results[$result->get_primary_key_index()] = $result;
			}
		}



		// Check for active_record_class and recast as needed
		foreach($results as $key => $result){
			$results[$key] = $result->__recast();
		}

		// Call __post_construct on each of the newly constructed objects.
		foreach($results as $result){
			$result->__post_construct();
		}
		
		// If this is a versioned object, its time do do the heavy lifting on the result.
		if($this->model instanceof versioned_active_record && count($this->order) > 0){
			// An array to put our computed results in.
			$sorted_results = array();
			// Get the first order from the $this->order array
			$order = reset($this->order);
			// Cycle through the results
			foreach($results as $result){
				// Generate the key we're gonna order against. Add the ID to the end, to prevent key collision
				$key = $result->$order['column'] . "-" . $result->get_id();
				$sorted_results[$key] = $result;
			}
			// Sort by the key
			ksort($sorted_results);
			// If we're sorting DESCending, flip the array now.
			if(strtoupper($order['direction']) == 'DESC'){
				$sorted_results = array_reverse($sorted_results);
			}
			// Return sorted array
			return $sorted_results;
		}else{
			// Just return the array as-it-comes from MySQL.
			return $results;
		}
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
	
	public function count(){
		return count($this->exec());	
	}
}