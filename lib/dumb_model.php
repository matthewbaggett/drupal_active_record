<?php
class active_record_dumb_model{
  static public function query($query, $type='StdClass', $key_by = null){
    $output = array();
    $result = db_query($query);
    foreach($result as $row){
      $new_obj = new $type();
      foreach($row as $column_name => $column_value){
        $new_obj->$column_name = $column_value;
      }
      if($key_by && property_exists($new_obj, $key_by)){
        $output[$new_obj->$key_by] = $new_obj;
      }else{
        $output[] = $new_obj;
      }
    }
    return $output;
  }

  static public function queryOne($query, $type='StdClass'){
    return end(self::query($query, $type));
  }
}