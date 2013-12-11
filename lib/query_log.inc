<?php

class query_log{
    static private $log;

    /**
     * Prints a SQL string from a DBTNG SelectQuery object.
     *
     * Includes quoted arguments.
     *
     * Stolen from Devel, heavilly modified.
     *
     * @param $query
     *   An object that implements the SelectQueryInterface interface.
     * @return
     *   The query string
     */
    static private function dpq($query) {
        if (user_access('access devel information')) {
            if(method_exists($query, 'preExecute')){
              $query->preExecute();
            }

            $sql = (string) $query;
            $quoted = array();
            $connection = Database::getConnection();
            if(method_exists($query,'arguments')){
                foreach ((array)$query->arguments() as $key => $val) {
                    $quoted[$key] = $connection->quote($val);
                }
            }

            // Special case for insertion
            if($query instanceof InsertQuery_mysql){
                $class = new ReflectionClass("InsertQuery_mysql");
                $property = $class->getProperty("insertValues");
                $property->setAccessible(true);
                $placeholders = $property->getValue($query);
                $placeholders = end($placeholders);
                foreach($placeholders as $k => $v){
                    $quoted[':db_insert_placeholder_' . $k] = $v;
                }
            }
            // TODO: Handle logging update queries better.
            /*
            if($query instanceof UpdateQuery){
              $class = new ReflectionClass("UpdateQuery");
              $expressionFieldsProperty = $class->getProperty("expressionFields");
              $expressionFieldsProperty->setAccessible(true);
              $expressionFields = $expressionFieldsProperty->getValue($query);
              echo "Expression Fields:";
              var_dump($expressionFields);


              /*foreach ($expressionFields as $field => $data) {
                if (!empty($data['arguments'])) {
                  $update_values += $data['arguments'];
                }
                unset($fields[$field]);
              }

            }*/
            $sql = strtr($sql, $quoted);
            return $sql;
        }
        return NULL;
    }

    /**
     * Add a query to the log
     *
     * @param $query
     *
     * @return query_log_item
     */
    static public function add($query, $comment = null){
        $sql = self::dpq($query);
        $pointer = count(self::$log) + 1;
        self::$log[$pointer] = new query_log_item($sql, $comment);
        if(function_exists('debug_backtrace') && variable_get('active_record_query_log_use_backtrace', false)){
          $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
          if(isset($trace[2])){
            self::$log[$pointer]->setCallPoint($trace[2]);
          }
        }
        return self::$log[$pointer];
    }

    static public function render(){
        if(function_exists('debug_backtrace') && variable_get('active_record_query_log_use_backtrace', false)){
          $do_trace = true;
        }else{
          $do_trace = false;
        }

        drupal_add_css(drupal_get_path('module','active_record') . "/css/query-log.css");
        $output = '';
        $output.= t("Processed :q_count queries in :q_time seconds", array(
            ':q_count' => count(self::$log),
            ':q_time' => self::total_time()
        ));
        $execution_ms_total = 0;
        $execution_count_total = 0;
        $table_body = "<tbody>";
        foreach(self::$log as $i => $log_item){
            $execution_ms = $log_item->execution_time*1000;
            $execution_ms_total += $execution_ms;
            $execution_count_total++;
            $ms = number_format($execution_ms,2);
            $table_body.= '<tr ' . (($ms > 100)?'style="color: red"':'') . '>';
            $table_body.= "<td>{$ms}ms</td>";
            $table_body.= "<td>{$log_item->comment}</td>";
            if($do_trace && is_array($log_item->trace)){
              $file_path = str_replace(DRUPAL_ROOT, "", $log_item->trace['file']);
              $table_body.= "<td>{$file_path}:{$log_item->trace['line']} in {$log_item->trace['class']}::{$log_item->trace['function']}</td>";
            }
            $table_body.= "<td>{$log_item->sql}</td>";
            $table_body.= "</tr>";
        }
        $table_body.= "</tbody>";

        $output.= "<table class=\"active_record_query_log\">";
        $output.= "<thead>";
        $output.= '<tr>';
        $output.= '<td>' . t("Exec Time") . '</td>';
        $output.= '<td>' . t("Comment") . '</td>';
        if($do_trace){
            $output.= '<td>' . t("Called by") . '</td>';
        }
        $output.= '<td>' . t("Query") . '</td>';
        $output.= '</tr>';
        $output.= '<tr>';
        $output.= '<td>' . number_format($execution_ms_total,2) . 'ms total' . '</td>';
        $output.= '<td></td>';
        if($do_trace){
            $output.= '<td></td>';
        }
        $output.= '<td>Total executed: ' . $execution_count_total . '</td>';

        $output.= '</tr>';
        $output.= "</thead>";
        $output.= $table_body;
        $output.= "</table>";
        return $output;
    }

    static private function total_time(){
        $total = 0;
        foreach(self::$log as $log_item){
            $total+= $log_item->execution_time;
        }
        return $total;
    }
}

class query_log_item{
    public $sql;
    public $execution_time;
    public $comment;
    public $trace;
    private $time_created;

    public function __construct($sql, $comment = null){
        $this->sql = $sql;
        $this->comment;
        $this->time_created = microtime(true);
    }

    public function completed(){
        $this->execution_time = microtime(true) - $this->time_created;
        return $this;
    }

    public function set_comment($comment){
        $this->comment = $comment;
        return $this;
    }

    public function setCallPoint($trace){
      $this->trace = $trace;
      // var_dump($this);exit;
      return $this;
    }

}

