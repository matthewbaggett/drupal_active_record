<?php

class query_log{
    static private $log;

    /**
     * Prints a SQL string from a DBTNG SelectQuery object.
     *
     * Includes quoted arguments.
     *
     * @param $query
     *   An object that implements the SelectQueryInterface interface.
     * @return
     *   The query string
     */
    static private function dpq($query) {
        if (user_access('access devel information')) {
            $query->preExecute();

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
    static public function add($query){
        $sql = self::dpq($query);
        $pointer = count(self::$log) + 1;
        self::$log[$pointer] = new query_log_item($sql);
        return self::$log[$pointer];
    }

    static public function render(){
        $output = '';
        $output.= t("Processed :q_count queries in :q_time seconds", array(
            ':q_count' => count(self::$log),
            ':q_time' => self::total_time()
        ));
        $output.= "<table>";
        $output.= "<thead>";
        $output.= '<tr>';
        $output.= '<td>' . t("Exec Time") . '</td>';
        $output.= '<td>' . t("Query") . '</td>';
        $output.= '</tr>';
        $output.= "</thead>";
        $output.= "<tbody>";
        foreach(self::$log as $i => $log_item){
            $ms = number_format($log_item->execution_time*1000,2);
            $output.= '<tr ' . (($ms > 100)?'style="color: red"':'') . '>';
            $output.= "<td>{$ms}ms</td>";
            $output.= '<td>' . $log_item->sql . '</td>';
            $output.= '</tr>';
        }
        $output.= "</tbody>";
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
    private $time_created;

    public function __construct($sql){
        $this->sql = $sql;
        $this->time_created = microtime(true);
    }

    public function completed(){
        $this->execution_time = microtime(true) - $this->time_created;
        return $this;
    }
}

