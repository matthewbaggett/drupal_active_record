<?php

class active_record
{
    static protected $MYSQL_FORMAT = "Y-m-d H:i:s";
    protected $_label_column = 'name';
    protected $_columns_to_save_down;
    private $_indexes = array();

    /**
     * get_all - Get all items.
     *
     * @param integer $limit Limit number of results
     * @param string $order Column to sort by
     * @param string $order_direction Order to sort by
     *
     * @throws exception
     * @return Array of items
     */
    static public function get_all($limit = null, $order = null, $order_direction = "ASC")
    {
        $name = get_called_class();
        $query = $name::search();
        if ($query instanceof search) {
            if ($limit) {
                $query->limit($limit);
            }
            if ($order) {
                $query->order($order, $order_direction);
            }
            $result = $query->exec();
            return $result;
        } else {
            throw new exception("Failed to instantiate an object of type active_record with name {$name}");
        }
    }

    /**
     * GetAll - Get all items.
     * Legacy Support - Deprecated
     *
     * @param integer $limit Limit number of results
     * @param string $order Column to sort by
     * @param string $order_direction Order to sort by
     * @return Array of items
     */
    static public function getAll($limit = null, $order = null, $order_direction = "ASC")
    {
        watchdog("active_record", "active_record::getAll() is deprecated, please use get_all()");
        return self::get_all($limit, $order, $order_direction);
    }

    /**
     * Start a search on this type of active record
     * @return search
     */
    static public function search()
    {
        $class = get_called_class();
        return new search(new $class);
    }

    /**
     * Generic Factory constructor
     * @return active_record
     */
    public static function factory()
    {
        $name = get_called_class();
        return new $name();
    }

    /**
     * Override-able __construct call
     */
    public function __construct()
    {
        global $active_record_cache;
        $class = get_called_class();
        $this->_indexes = $active_record_cache[$class]['_indexes'];
    }

    public function __destruct()
    {
        global $active_record_cache;
        $class = get_called_class();
        $active_record_cache[$class]['_indexes'] = $this->_indexes;
    }

    /**
     * Override-able calls
     */
    public function __post_construct()
    {
    }

    public function __pre_save()
    {
    }

    public function __post_save()
    {
    }

    /**
     * Find an item by the Primary Key ID
     * @param integer $id
     * @return active_record
     */
    static public function loadById($id)
    {
        $name = get_called_class();
        return $name::factory()->getById($id);
    }

    /**
     * Find an item by the Primary Key ID
     *
     * @param integer $id
     *
     * @return active_record
     */
    public function getById($id)
    {
        return $this->search()->where($this->get_table_primary_key(), $id)->execOne();
    }

    /**
     * SearchByColumn - Find items by the column specified
     * Legacy Support - Deprecated
     *
     * @param string $column Column to search by
     * @param string $value Value of column to search by
     * @param string $operator Operator. Defaults to equals
     * @param integer $limit Limit number of results
     * @param string $order Column to sort by
     * @param string $order_direction Order to sort by
     * @return Ambiguous <multitype:, multitype:unknown mixed >
     */
    static public function searchByColumn($column, $value, $operator = null, $limit = null, $order = null, $order_direction = "ASC")
    {
        $name = get_called_class();
        return $name::factory()->findByColumn($column, $value, $operator, $limit, $order, $order_direction);
    }

    /**
     * FindByColumn - Find items by the column specified
     * Legacy Support - Deprecated
     *
     * @param string $column Column to search by
     * @param string $value Value of column to search by
     * @param string $operator Operator. Defaults to equals
     * @param integer $limit Limit number of results
     * @param string $order Column to sort by
     * @param string $order_direction Order to sort by
     * @return Ambiguous <multitype:, multitype:unknown mixed >
     */
    public function findByColumn($column = null, $value = null, $operator = null, $limit = null, $order = null, $order_direction = "ASC")
    {
        $s = $this->search();
        $s->where($column, $value, $operator);
        if ($limit) {
            $s->limit($limit);
        }
        if ($order) {
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
    public function get_table_alias($table_name = null)
    {
        if (!$table_name) {
            $table_name = $this->get_table_name();
        }
        $bits = explode("_", $table_name);
        $alias = '';
        foreach ($bits as $bit) {
            $alias .= strtolower(substr($bit, 0, 1));
        }
        return $alias;
    }

    /**
     * Get the table name
     *
     * @return string Table Name
     */
    public function get_table_name()
    {
        return $this->_table;
    }

    protected function get_table_indexes($key_name = 'PRIMARY')
    {
        if (!$this->_indexes[$key_name]) {
            $keys_search = db_query("SHOW INDEX FROM {$this->_table} WHERE Key_name = '{$key_name}'");
            $keys = $keys_search->fetchAll();
            $this->_indexes[$key_name] = $keys;
        }
        return $this->_indexes[$key_name];
    }

    /**
     * Get table primary key column name
     *
     * @return string
     */
    public function get_table_primary_key()
    {
        $keys = $this->get_table_indexes('PRIMARY');
        $primary_key = $keys[0]->Column_name;
        return $primary_key;
    }

    /**
     * Get a unique key to use as an index
     *
     * @return string
     */
    public function get_primary_key_index()
    {
        $keys = $this->get_table_indexes('PRIMARY');
        $columns = array();
        foreach ($keys as $key) {
            $columns[$key->Column_name] = $key->Column_name;
        }
        $keys = array();
        foreach ($columns as $column) {
            $keys[] = $this->$column;
        }
        return implode("-", $keys);;
    }

    /**
     * Get object ID
     * @return integer
     */
    public function get_id()
    {
        $col = $this->get_table_primary_key();
        if (property_exists($this, $col)) {
            $id = $this->$col;
            if ($id > 0) {
                return $id;
            }
        }
        return false;
    }

    /**
     * Get a label for the object. Perhaps a Name or Description field.
     * @return string
     */
    public function get_label()
    {
        if (property_exists($this, '_label_column')) {
            if (property_exists($this, $this->_label_column)) {
                $lable_column = $this->_label_column;
                return $this->$lable_column;
            }
        }
        if (property_exists($this, 'name')) {
            return $this->name;
        }
        if (property_exists($this, 'description')) {
            return $this->description;
        }
        return "No label for " . get_called_class() . " ID " . $this->get_id();
    }

    /**
     * Work out which columns should be saved down.
     */
    protected function _calculate_save_down_rows()
    {
        if (!$this->_columns_to_save_down) {
            foreach (get_object_vars($this) as $potential_column => $discard) {
                switch ($potential_column) {
                    case 'table':
                    case substr($potential_column, 0, 1) == "_":
                        // Not a valid column
                        break;
                    default:
                        $this->_columns_to_save_down[] = $potential_column;
                        break;
                }
            }
        }
        return $this->_columns_to_save_down;
    }

    /**
     * Load an object from data fed to us as an array (or similar.)
     *
     * @param array $row
     *
     * @return active_record
     */
    public function loadFromRow($row)
    {
        // Loop over the columns, sanitise and store it into the new properties of this object.
        foreach ($row as $column => &$value) {
            // Only save columns beginning with a normal letter.
            if (preg_match('/^[a-z]/i', $column)) {
                $this->$column = & $value;
            }
        }
        $this->__post_construct();
        return $this;
    }

    /**
     * Save the selected record.
     * This will do an INSERT or UPDATE as appropriate
     *
     * @param boolean $automatic_reload Whether or not to automatically reload
     *
     * @return active_record
     */
    public function save($automatic_reload = true)
    {
        $this->__pre_save();
        // Calculate row to save_down
        $this->_calculate_save_down_rows();
        $primary_key_column = $this->get_table_primary_key();

        // Make an array out of the objects columns.
        $data = array();
        foreach ($this->_columns_to_save_down as $column) {
            // Never update the primary key. Bad bad bad.
            if ($column != $primary_key_column) {
                $data["`{$column}`"] = $this->$column;
            }
        }

        // If we already have an ID, this is an update.
        if ($this->get_id()) {
            $save_sql = db_update($this->_table);
            $save_sql->fields($data);
            $save_sql->condition($primary_key_column, $this->$primary_key_column);
            $save_sql->execute();
        } else { // Else, we're an insert.
            $insert_sql = db_insert($this->_table);
            $insert_sql->fields($data);
            $new_id = $insert_sql->execute();
            $this->$primary_key_column = $new_id;

        }
        if ($automatic_reload) {
            $this->reload();
        }
        $this->__post_save();
        return $this;
    }

    /**
     * Reload the selected record
     * @return active_record
     */
    public function reload()
    {
        $item = $this->getById($this->get_id());
        $this->loadFromRow($item);
        return $this;
    }

    /**
     * Delete the selected record
     * @return boolean
     */
    public function delete()
    {
        db_delete($this->get_table_name())
            ->condition($this->get_table_primary_key(), $this->get_id())
            ->execute();
        return true;
    }

    /**
     * Take a string and make it websafe - Slugified.
     * Taken from symfony's jobeet tutorial.
     * @param string $text
     * @return string
     */
    protected function _slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Pull a database record by the slug we're given.
     *
     * @param $slug string Slug
     *
     * @return mixed
     */
    static public function get_by_slug($slug)
    {
        $slug_parts = explode("-", $slug, 2);
        return self::loadById($slug_parts[0]);
    }

    /**
     * Recast an object from a parent class to an extending class, if active_record_class is present
     *
     * @return active_record
     * @throws exception
     */
    public function __recast()
    {
        // If the object has a property called active_record_class, it can potentially be recast at runtime. There are some dependencies though
        if (property_exists($this, 'active_record_class')) {
            if ($this->active_record_class !== get_called_class()) {
                if (!class_exists($this->active_record_class)) {
                    throw new exception("Active Record Class: {$this->active_record_class} does not exist.");
                }
                if (!is_subclass_of($this->active_record_class, get_called_class())) {
                    throw new exception("Active Record Class: " . $this->active_record_class . " must extend " . get_called_class());
                }
                $recast_class = $this->active_record_class;
                $new_this = new $recast_class();
                $new_this->loadFromRow((array)$this);
                return $new_this;
            }
        }
        return $this;
    }

    /**
     * Generate a suitable magic_form for this object, if magic_form library installed
     *
     * @return magic_form
     * @throws exception
     */
    static public function magic_form()
    {
        if (function_exists('magic_forms_init')) {
            $form = self::factory()->_get_magic_form();
            return $form;
        } else {
            throw new exception("Magic forms is not installed, cannot call active_record::magic_form()");
        }
    }

    /**
     * Process the magic form...
     *
     * @return magic_form
     * @throws exception
     */
    public function _get_magic_form()
    {
        if (module_exists('magic_forms')) {
            $form = new magic_form();
            $columns = $this->_interogate_db_for_columns();
            foreach ($columns as $column) {
                // Default type is text.
                $type = 'magic_form_field_text';

                // primary key column is always omitted
                if ($column['Field'] == $this->get_table_primary_key()) {
                    continue;
                }

                // Ignore Auto_Increment primary keys
                if ($column['Extra'] == 'auto_increment') {
                    continue;
                }

                // Ignore logical deletion column
                if ($column['Field'] == 'deleted') {
                    continue;
                }

                // uid column is always invisible
                if ($column['Field'] == 'uid') {
                    continue;
                }

                // Remote key
                if (isset($column['Constraint'])) {
                    $type = 'magic_form_field_select';
                }


                // Set the value, if set.
                if (property_exists($this, $column['Field'])) {
                    $value = $this->$column['Field'];
                    if (is_array($value) || is_object($value)) {
                        $value = pretty_print_json(json_encode($value));
                    }
                } else {
                    $value = null;
                }

                // Do something useful with default values.
                if (isset($column['Default'])) {
                    $default_value = $column['Default'];
                } else {
                    $default_value = null;
                }

                // If the value is long, and the field is a text field, make it a textarea
                if (strlen($value) > 100 || strpos($value, "\n") !== FALSE) {
                    $type = 'magic_form_field_textarea';
                }

                // Create the new field and add it to the form.
                /* @var $new_field magic_form_field */
                $new_field = new $type(strtolower($column['Field']), $column['Field']);

                // Remote key options
                if (isset($column['Constraint'])) {
                    $contraint_options = db_select($column['Constraint']['Table'], 'a')
                        ->fields('a', array('name', $column['Constraint']['Column']))
                        ->execute()
                        ->fetchAll();
                    foreach ($contraint_options as $contraint_option) {
                        $contraint_option = (array)$contraint_option;
                        $new_field->add_option(reset($contraint_option), end($contraint_option));
                    }
                }

                // Set the value & default
                $new_field->set_value($value);
                $new_field->set_default_value($default_value);

                // Add to the form
                $form->add_field($new_field);
            }

            // Add save button
            $save = new magic_form_field_submit('save', 'Save', 'Save');
            $form->add_field($save);

            // Sort out passing variables
            $that = $this;
            global $user;

            // Create a simple handler
            $form->submit(function (magic_form $form) use ($that, $user) {
                $object_type = get_class($that);
                $object = new $object_type;
                /* @var $object active_record */

                // Attempt to load by the ID given to us
                $field = $form->get_field($object->get_table_primary_key());
                if ($field instanceof magic_form_field) {
                    $value = $field->get_value();
                    $object->loadById($value);
                }

                // Attempt to read in all the variables
                foreach ($object->get_table_headings() as $heading) {
                    $field = $form->get_field($heading);
                    if ($field instanceof magic_form_field) {
                        echo $heading;
                        krumo($field);
                        $object->$heading = $field->get_value();
                    }
                    if ($heading == 'uid') {
                        $object->uid = $user->uid;
                    }
                }

                // Save object.
                $object->save();

                // If Submit Destination is set, redirect to it.
                if ($form->get_submit_destination()) {
                    header("Location: {$form->get_submit_destination()}");
                    exit;
                }
            });

            // Return the form
            return $form;
        } else {
            throw new exception("Magic forms is not installed, cannot call active_record::magic_form()");
        }
    }

    /**
     * @return array
     */
    private function _interogate_db_for_columns()
    {
        $table = $this->get_table_name();
        $sql = "SHOW COLUMNS FROM `$table`";
        $fields = array();
        $result = db_query($sql);

        foreach ($result->fetchAll() as $row) {
            $fields[] = (array)$row;
        }

        foreach ($fields as &$field) {
            // TODO: Refactor out this raw SQL.
            $constraint_query_sql = "
                select
                    TABLE_NAME,
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                from INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                where TABLE_NAME = '{$table}'
                  and COLUMN_NAME = '{$field['Field']}'
            ";
            $constraint_query = db_query($constraint_query_sql);

            foreach ($constraint_query->fetchAll() as $constraint_query_row) {
                if ($constraint_query_row->REFERENCED_TABLE_NAME !== null && $constraint_query_row->REFERENCED_COLUMN_NAME !== null) {
                    $field['Constraint'] = array(
                        'Table' => $constraint_query_row->REFERENCED_TABLE_NAME,
                        'Column' => $constraint_query_row->REFERENCED_COLUMN_NAME,
                    );
                }
            }
        }
        return $fields;
    }

    /**
     * Get URL slug.
     *
     * @return string
     */
    public function get_slug()
    {
        return $this->get_id() . "-" . $this->_slugify($this->get_label());
    }

    public function get_table_headings()
    {
        return $this->_calculate_save_down_rows();
    }

    public function get_table_rows($anticipated_rows = null)
    {
        $rows = array();
        foreach (self::get_all() as $item) {
            /* @var $item active_record */
            $rows[] = $item->__toArray($anticipated_rows);
        }
        return $rows;
    }

    public function get_table()
    {
        $table = new StdClass();
        $table->header = $this->get_table_headings();
        $table->rows = $this->get_table_rows($table->header);
        $table->empty = t("No :class available", array(':class' => get_called_class()));
        return $table;
    }

    public function __toArray($anticipated_rows = null)
    {
        $array = array();
        foreach (get_object_vars($this) as $k => $v) {
            if ($anticipated_rows === null || in_array($k, $anticipated_rows)) {
                $array[$k] = $v;
            }
        }
        return $array;
    }
}