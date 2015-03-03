<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Database Utility Class
 */
class DBUtility
{
    var $data_cache = array();
    var $fields = array();
    var $keys = array();
    var $primary_keys = array();
    var $db_char_set = '';
    protected $db;

    /**
     * Constructor
     */
    public function __construct($db)
    {
        // Assign the main database object to $this->db
        if ($db != null && is_object($db)) {
            $this->db = $db;
        }
        Fly::log('debug', "Database Utility Class Initialized");
    }

    public static function shareInstance($db)
    {
        static $_instance;
        if (!$_instance) {
            $class = 'DB'.ucfirst($db->dbdriver).'Utility';
            $_instance = new $class($db);
        } else {
            $_instance->setDb($db);
        }
        return $_instance;
    }

    /**
     * Set db
     * @param $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * Create database
     * @param string $db_name the database name
     * @return bool
     */
    public function createDataBase($db_name)
    {
        $sql = $this->_createDataBase($db_name);

        if (is_bool($sql)) {
            return $sql;
        }

        return $this->db->query($sql);
    }

    /**
     * Drop database
     * @param string $db_name the database name
     * @return bool
     */
    public function dropDataBase($db_name)
    {
        $sql = $this->_dropDataBase($db_name);

        if (is_bool($sql)) {
            return $sql;
        }

        return $this->db->query($sql);
    }

    /**
     * Add Key
     * @param string $key
     * @param string $type
     * @return void
     */
    public function addKey($key = '', $primary = false)
    {
        if ($primary && is_array($key)) {
            foreach ($key as $one) {
                $this->addKey($one, $primary);
            }

            return;
        }

        if ($key == '') {
            throw new FlyException(Fly::t('fly', 'Key information is required for that operation.'));
        }

        if ($primary === true) {
            $this->primary_keys[] = $key;
        } else {
            $this->keys[] = $key;
        }
    }

    /**
     * Add Field
     * @param string $field collation
     * @return void
     */
    public function addField($field = '')
    {
        if ($field == '') {
            throw new FlyException(Fly::t('fly', 'Field information is required.'));
        }

        if (is_string($field)) {
            if ($field == 'id') {
                $this->addField(array(
                    'id' => array(
                        'type' => 'INT',
                        'constraint' => 9,
                        'auto_increment' => true
                    )
                ));
                $this->addKey('id', true);
            } else {
                if (strpos($field, ' ') === false) {
                    throw new FlyException(Fly::t('fly', 'Field information is required for that operation.'));
                }

                $this->fields[] = $field;
            }
        }

        if (is_array($field)) {
            $this->fields = array_merge($this->fields, $field);
        }
    }

    /**
     * Create Table
     * @param string $table the table name
     * @return bool
     */
    public function createTable($table = '', $if_not_exists = false)
    {
        if ($table == '') {
            throw new FlyException(Fly::t('fly', 'A table name is required for that operation.'));
        }

        if (count($this->fields) == 0) {
            throw new FlyException(Fly::t('fly', 'Field information is required.'));
        }
        $sql = $this->_createTable($this->db->dbprefix.$table, $this->fields, $this->primary_keys, $this->keys, $if_not_exists);
        $this->_reset();
        return $this->db->query($sql);
    }

    /**
     * Drop Table
     * @param string $table_name the table name
     * @return bool
     */
    public function dropTable($table_name)
    {
        $sql = $this->_dropTable($this->db->dbprefix.$table_name);
        if (is_bool($sql)) {
            return $sql;
        }

        return $this->db->query($sql);
    }

    /**
     * Rename Table
     * @param string $table_name the old table name
     * @param string $new_table_name the new table name
     * @return bool
     */
    public function renameTable($table_name, $new_table_name)
    {
        if ($table_name == '' OR $new_table_name == '') {
            throw new FlyException(Fly::t('fly', 'A table name is required for that operation.'));
        }

        $sql = $this->_renameTable($this->db->dbprefix.$table_name, $this->db->dbprefix.$new_table_name);
        return $this->db->query($sql);
    }

    /**
     * Column Add
     * @param string $table the table name
     * @param string $field the column name
     * @param string $after_field the column definition
     * @return bool
     */
    public function addColumn($table = '', $field = array(), $after_field = '')
    {
        if ($table == '') {
            throw new FlyException(Fly::t('fly', 'A table name is required for that operation.'));
        }

        // add field info into field array, but we can only do one at a time
        // so we cycle through

        foreach ($field as $k => $v) {
            $this->addField(array($k => $field[$k]));

            if (count($this->fields) == 0) {
                throw new FlyException(Fly::t('fly', 'Field information is required.'));
            }

            $sql = $this->_alterTable('ADD', $this->db->dbprefix.$table, $this->fields, $after_field);

            $this->_reset();

            if ($this->db->query($sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Column Drop
     * @param string $table the table name
     * @param string $column_name the column name
     * @return bool
     */
    function dropColumn($table = '', $column_name = '')
    {

        if ($table == '') {
            throw new FlyException(Fly::t('fly', 'A table name is required for that operation.'));
        }

        if ($column_name == '') {
            throw new FlyException(Fly::t('fly', 'A column name is required for that operation.'));
        }

        $sql = $this->_alterTable('DROP', $this->db->dbprefix.$table, $column_name);

        return $this->db->query($sql);
    }

    /**
     * Column Modify
     * @param string $table the table name
     * @param string $field the column information
     * @return bool
     */
    public function modifyColumn($table = '', $field = array())
    {
        if ($table == '') {
            throw new FlyException(Fly::t('fly', 'A table name is required for that operation.'));
        }

        // add field info into field array, but we can only do one at a time
        // so we cycle through

        foreach ($field as $k => $v) {
            // If no name provided, use the current name
            if (!isset($field[$k]['name'])) {
                $field[$k]['name'] = $k;
            }

            $this->addField(array($k => $field[$k]));

            if (count($this->fields) == 0) {
                throw new FlyException(Fly::t('fly', 'Field information is required.'));
            }

            $sql = $this->_alterTable('CHANGE', $this->db->dbprefix.$table, $this->fields);

            $this->_reset();

            if ($this->db->query($sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resets table creation vars
     * @return void
     */
    protected function _reset()
    {
        $this->fields = array();
        $this->keys = array();
        $this->primary_keys = array();
    }

    /**
     * Process Fields
     * @param mixed $fields the fields
     * @return string
     */
    protected function _processFields($fields)
    {
        $current_field_count = 0;
        $sql = '';

        foreach ($fields as $field => $attributes) {
            // Numeric field names aren't allowed in databases, so if the key is
            // numeric, we know it was assigned by PHP and the developer manually
            // entered the field information, so we'll simply add it to the list
            if (is_numeric($field)) {
                $sql .= "\n\t$attributes";
            } else {
                $attributes = array_change_key_case($attributes, CASE_UPPER);

                $sql .= "\n\t".$this->db->protectIdentifiers($field);

                if (array_key_exists('NAME', $attributes)) {
                    $sql .= ' '.$this->db->protectIdentifiers($attributes['NAME']).' ';
                }

                if (array_key_exists('TYPE', $attributes)) {
                    $sql .= ' '.$attributes['TYPE'];

                    if (array_key_exists('CONSTRAINT', $attributes)) {
                        switch ($attributes['TYPE']) {
                            case 'decimal':
                            case 'float':
                            case 'numeric':
                                $sql .= '('.implode(',', $attributes['CONSTRAINT']).')';
                                break;

                            case 'enum':
                            case 'set':
                                $sql .= '("'.implode('","', $attributes['CONSTRAINT']).'")';
                                break;

                            default:
                                $sql .= '('.$attributes['CONSTRAINT'].')';
                        }
                    }
                }

                if (array_key_exists('UNSIGNED', $attributes) && $attributes['UNSIGNED'] === true) {
                    $sql .= ' UNSIGNED';
                }

                if (array_key_exists('DEFAULT', $attributes)) {
                    $sql .= ' DEFAULT \''.$attributes['DEFAULT'].'\'';
                }

                if (array_key_exists('NULL', $attributes)) {
                    if ($attributes['NULL'] === true) {
                        $sql .= ' NULL';
                    } else {
                        $sql .= ' NOT NULL';
                    }
                }

                if (array_key_exists('AUTO_INCREMENT', $attributes) && $attributes['AUTO_INCREMENT'] === true) {
                    $sql .= ' AUTO_INCREMENT';
                }
            }

            // don't add a comma on the end of the last field
            if (++$current_field_count < count($fields)) {
                $sql .= ',';
            }
        }

        return $sql;
    }

    /**
     * List databases
     * @return bool
     */
    public function fetchDataBases()
    {
        // Is there a cached result?
        if (isset($this->data_cache['db_names'])) {
            return $this->data_cache['db_names'];
        }

        $query = $this->db->query($this->_listDataBases());
        $dbs = array();
        if ($query->getRowsCount() > 0) {
            foreach ($query->resultArray() as $row) {
                $dbs[] = current($row);
            }
        }

        $this->data_cache['db_names'] = $dbs;
        return $this->data_cache['db_names'];
    }

    /**
     * Determine if a particular database exists
     * @param string
     * @return boolean
     */
    public function isDataBaseExists($database_name)
    {
        // Some databases won't have access to the list_databases() function, so
        // this is intended to allow them to override with their own functions as
        // defined in $driver_utility.php
        if (method_exists($this, '_isDataBaseExists')) {
            return $this->_isDataBaseExists($database_name);
        } else {
            return (!in_array($database_name, $this->fetchDataBases())) ? false : true;
        }
    }

    /**
     * Optimize Table
     * @param string $table_name the table name
     * @return bool
     */
    public function optimizeTable($table_name)
    {
        $sql = $this->_optimizeTable($table_name);

        if (is_bool($sql)) {
            throw new FlyException(Fly::t('fly', 'db_must_use_set'));
        }

        $query = $this->db->query($sql);
        $res = $query->resultArray();

        // Note: Due to a bug in current() that affects some versions
        // of PHP we can not pass function call directly into it
        return current($res);
    }

    /**
     * Optimize Database
     * @return array
     */
    public function optimizeDataBase()
    {
        $result = array();
        foreach ($this->db->fetchTables() as $table_name) {
            $sql = $this->_optimizeTable($table_name);

            if (is_bool($sql)) {
                return $sql;
            }

            $query = $this->db->query($sql);

            // Build the result array...
            // Note: Due to a bug in current() that affects some versions
            // of PHP we can not pass function call directly into it
            $res = $query->resultArray();
            $res = current($res);
            $key = str_replace($this->db->database.'.', '', current($res));
            $keys = array_keys($res);
            unset($res[$keys[0]]);

            $result[$key] = $res;
        }

        return $result;
    }

    /**
     * Repair Table
     * @param string $table_name the table name
     * @return bool
     */
    public function repairTable($table_name)
    {
        $sql = $this->_repairTable($table_name);

        if (is_bool($sql)) {
            return $sql;
        }

        $query = $this->db->query($sql);

        // Note: Due to a bug in current() that affects some versions
        // of PHP we can not pass function call directly into it
        $res = $query->resultArray();
        return current($res);
    }

    /**
     * Generate CSV from a query result object
     * @param object $query The query result object
     * @param string $delim The delimiter - comma by default
     * @param string $newline The newline character - \n by default
     * @param string $enclosure The enclosure - double quote by default
     * @return string
     */
    public function getCsvFromResult($query, $delim = ",", $newline = "\n", $enclosure = '"')
    {
        if (!is_object($query) OR !method_exists($query, 'fetchFields')) {
            throw new FlyException(Fly::t('fly', 'You must submit a valid result object'));
        }

        $out = '';

        // First generate the headings from the table column names
        foreach ($query->fetchFields() as $name) {
            $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $name).$enclosure.$delim;
        }

        $out = rtrim($out);
        $out .= $newline;

        // Next blast through the result array and build out the rows
        foreach ($query->resultArray() as $row) {
            foreach ($row as $item) {
                $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $item).$enclosure.$delim;
            }
            $out = rtrim($out);
            $out .= $newline;
        }

        return $out;
    }

    /**
     * Generate XML data from a query result object
     * @param object $query The query result object
     * @param array $params An preferences
     * @return string
     */
    public function getXmlFromResult($query, $params = array())
    {
        if (!is_object($query) OR !method_exists($query, 'fetchFields')) {
            throw new FlyException(Fly::t('fly', 'You must submit a valid result object'));
        }

        // Set our default values
        foreach (array('root' => 'root', 'element' => 'element', 'newline' => "\n", 'tab' => "\t") as $key => $val) {
            if (!isset($params[$key])) {
                $params[$key] = $val;
            }
        }

        // Create variables for convenience
        extract($params);

        // Generate the result
        $xml = "<{$root}>".$newline;
        foreach ($query->resultArray() as $row) {
            $xml .= $tab."<{$element}>".$newline;

            foreach ($row as $key => $val) {
                $xml .= $tab.$tab."<{$key}>".XmlHelper::convert($val)."</{$key}>".$newline;
            }
            $xml .= $tab."</{$element}>".$newline;
        }
        $xml .= "</$root>".$newline;

        return $xml;
    }

    /**
     * Database Backup
     * @return void
     */
    public function backup($params = array())
    {
        // If the parameters have not been submitted as an
        // array then we know that it is simply the table
        // name, which is a valid short cut.
        if (is_string($params)) {
            $params = array('tables' => $params);
        }

        // Set up our default preferences
        $prefs = array(
            'tables' => array(),
            'ignore' => array(),
            'filename' => '',
            'format' => 'gzip', // gzip, zip, txt
            'add_drop' => true,
            'add_insert' => true,
            'newline' => "\n"
        );

        // Did the user submit any preferences? If so set them....
        if (count($params) > 0) {
            foreach ($prefs as $key => $val) {
                if (isset($params[$key])) {
                    $prefs[$key] = $params[$key];
                }
            }
        }

        // ------------------------------------------------------

        // Are we backing up a complete database or individual tables?
        // If no table names were submitted we'll fetch the entire table list
        if (count($prefs['tables']) == 0) {
            $prefs['tables'] = $this->db->fetchTables();
        }

        // ------------------------------------------------------

        // Validate the format
        if (!in_array($prefs['format'], array('gzip', 'zip', 'txt'), true)) {
            $prefs['format'] = 'txt';
        }

        // ------------------------------------------------------

        // Is the encoder supported?  If not, we'll either issue an
        // error or use plain text depending on the debug settings
        if (($prefs['format'] == 'gzip' AND !@function_exists('gzencode'))
            OR ($prefs['format'] == 'zip' AND !@function_exists('gzcompress'))
        ) {
            if ($this->db->db_debug) {
                return $this->db->displayError('db_unsuported_compression');
            }

            $prefs['format'] = 'txt';
        }

        // ------------------------------------------------------

        // Set the filename if not provided - Only needed with Zip files
        if ($prefs['filename'] == '' AND $prefs['format'] == 'zip') {
            $prefs['filename'] = (count($prefs['tables']) == 1) ? $prefs['tables'] : $this->db->database;
            $prefs['filename'] .= '_'.date('Y-m-d_H-i', time());
        }

        // ------------------------------------------------------

        // Was a Gzip file requested?
        if ($prefs['format'] == 'gzip') {
            return gzencode($this->_backup($prefs));
        }

        // ------------------------------------------------------

        // Was a text file requested?
        if ($prefs['format'] == 'txt') {
            return $this->_backup($prefs);
        }

        // ------------------------------------------------------

        // Was a Zip file requested?
        if ($prefs['format'] == 'zip') {
            // If they included the .zip file extension we'll remove it
            if (preg_match("|.+?\.zip$|", $prefs['filename'])) {
                $prefs['filename'] = str_replace('.zip', '', $prefs['filename']);
            }

            // Tack on the ".sql" file extension if needed
            if (!preg_match("|.+?\.sql$|", $prefs['filename'])) {
                $prefs['filename'] .= '.sql';
            }

            // Load the Zip class and output it
            $zip = Fly::app()->getComponent('Zip');
            $zip->addData($prefs['filename'], $this->_backup($prefs));
            return $zip->getZip();
        }
    }
}