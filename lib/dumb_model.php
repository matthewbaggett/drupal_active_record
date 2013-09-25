<?php
class active_record_dumb_model{
  static public function query($query, $type='StdClass'){
    $output = array();
    $result = db_query($query);
    foreach($result as $row){
      $new_obj = new $type();
      foreach($row as $column_name => $column_value){
        $new_obj->$column_name = $column_value;
      }
      $output[] = $new_obj;
    }
    return $output;
  }
}