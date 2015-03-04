<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * MySQL Utility Class
 */
class DBMysqlUtility extends DBUtility
{

    /**
     * Create database
     * @param string $name the database name
     * @return bool
     */
    protected function _createDataBase($name)
    {
        return "CREATE DATABASE ".$name;
    }

    /**
     * Drop database
     * @param string $name the database name
     * @return bool
     */
    protected function _dropDataBase($name)
    {
        return "DROP DATABASE ".$name;
    }

    /**
     * Create Table
     * @param string $table the table name
     * @param mixed $fields the fields
     * @param mixed $primary_keys primary key(s)
     * @param mixed $keys key(s)
     * @param boolean $if_not_exists should 'IF NOT EXISTS' be added to the SQL
     * @return bool
     */
    protected function _createTable($table, $fields, $primary_keys, $keys, $if_not_exists)
    {
        $sql = 'CREATE TABLE ';

        if ($if_not_exists === true) {
            $sql .= 'IF NOT EXISTS ';
        }

        $sql .= $this->db->escapeIdentifiers($table)." (";

        $sql .= $this->_processFields($fields);

        if (count($primary_keys) > 0) {
            $key_name = $this->db->protectIdentifiers(implode('_', $primary_keys));
            $primary_keys = $this->db->protectIdentifiers($primary_keys);
            $sql .= ",\n\tPRIMARY KEY ".$key_name." (".implode(', ', $primary_keys).")";
        }

        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $key) {
                if (is_array($key)) {
                    $key_name = $this->db->protectIdentifiers(implode('_', $key));
                    $key = $this->db->protectIdentifiers($key);
                } else {
                    $key_name = $this->db->protectIdentifiers($key);
                    $key = array($key_name);
                }

                $sql .= ",\n\tKEY {$key_name} (".implode(', ', $key).")";
            }
        }

        $sql .= "\n) DEFAULT CHARACTER SET {$this->db->char_set} COLLATE {$this->db->dbcollat};";

        return $sql;
    }

    /**
     * Drop Table
     * @return string
     */
    protected function _dropTable($table)
    {
        return "DROP TABLE IF EXISTS ".$this->db->escapeIdentifiers($table);
    }

    /**
     * Alter table query
     * Generates a platform-specific query so that a table can be altered
     * @param string $alter_type the ALTER type (ADD, DROP, CHANGE)
     * @param string $talbe the table name
     * @param array $fields fields
     * @param string $after_field the field after which we should add the new field
     * @return object
     */
    protected function _alterTable($alter_type, $table, $fields, $after_field = '')
    {
        $sql = 'ALTER TABLE '.$this->db->protectIdentifiers($table)." $alter_type ";

        // DROP has everything it needs now.
        if ($alter_type == 'DROP') {
            return $sql.$this->db->protectIdentifiers($fields);
        }

        $sql .= $this->_processFields($fields);

        if ($after_field != '') {
            $sql .= ' AFTER '.$this->db->protectIdentifiers($after_field);
        }

        return $sql;
    }

    /**
     * Rename a table
     * Generates a platform-specific query so that a table can be renamed
     * @param string $table_name the old table name
     * @param string $new_table_name the new table name
     * @return string
     */
    protected function _renameTable($table_name, $new_table_name)
    {
        $sql = 'ALTER TABLE '.$this->db->protectIdentifiers($table_name)." RENAME TO ".$this->db->protectIdentifiers($new_table_name);
        return $sql;
    }

    /**
     * List databases
     * @return bool
     */
    protected function _listDataBases()
    {
        return "SHOW DATABASES";
    }

    /**
     * Optimize table query
     * Generates a platform-specific query so that a table can be optimized
     * @param string $table the table name
     * @return object
     */
    protected function _optimizeTable($table)
    {
        return "OPTIMIZE TABLE ".$this->db->escapeIdentifiers($table);
    }

    /**
     * Repair table query
     * Generates a platform-specific query so that a table can be repaired
     * @param string $table the table name
     * @return object
     */
    protected function _repairTable($table)
    {
        return "REPAIR TABLE ".$this->db->escapeIdentifiers($table);
    }

    /**
     * MySQL Export
     * @param array $params Preferences
     * @return mixed
     */
    protected function _backup($params = array())
    {
        if (count($params) == 0) {
            return false;
        }

        // Extract the prefs for simplicity
        extract($params);

        // Build the output
        $output = '';
        foreach ((array)$tables as $table) {
            // Is the table in the "ignore" list?
            if (in_array($table, (array)$ignore, true)) {
                continue;
            }

            // Get the table schema
            $query = $this->db->query("SHOW CREATE TABLE `".$this->db->database.'`.`'.$table.'`');

            // No result means the table name was invalid
            if ($query === false) {
                continue;
            }

            // Write out the table schema
            $output .= '#'.$newline.'# TABLE STRUCTURE FOR: '.$table.$newline.'#'.$newline.$newline;

            if ($add_drop == true) {
                $output .= 'DROP TABLE IF EXISTS '.$table.';'.$newline.$newline;
            }

            $i = 0;
            $result = $query->resultArray();
            foreach ($result[0] as $val) {
                if ($i++ % 2) {
                    $output .= $val.';'.$newline.$newline;
                }
            }

            // If inserts are not needed we're done...
            if ($add_insert == false) {
                continue;
            }

            // Grab all the data from the current table
            $query = $this->db->query("SELECT * FROM $table");

            if ($query->getRowsCount() == 0) {
                continue;
            }

            // Fetch the field names and determine if the field is an
            // integer type.  We use this info to decide whether to
            // surround the data with quotes or not

            $i = 0;
            $field_str = '';
            $is_int = array();
            while ($field = mysql_fetch_field($query->result_id)) {
                // Most versions of MySQL store timestamp as a string
                $is_int[$i] = (in_array(
                    strtolower(mysql_field_type($query->result_id, $i)),
                    array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'), //, 'timestamp'),
                    true)
                ) ? true : false;

                // Create a string of field names
                $field_str .= '`'.$field->name.'`, ';
                $i++;
            }

            // Trim off the end comma
            $field_str = preg_replace("/, $/", "", $field_str);

            // Build the insert string
            foreach ($query->resultArray() as $row) {
                $val_str = '';

                $i = 0;
                foreach ($row as $v) {
                    // Is the value NULL?
                    if ($v === null) {
                        $val_str .= 'NULL';
                    } else {
                        // Escape the data if it's not an integer
                        if ($is_int[$i] == false) {
                            $val_str .= $this->db->escape($v);
                        } else {
                            $val_str .= $v;
                        }
                    }

                    // Append a comma
                    $val_str .= ', ';
                    $i++;
                }

                // Remove the comma at the end of the string
                $val_str = preg_replace("/, $/", "", $val_str);

                // Build the INSERT string
                $output .= 'INSERT INTO '.$table.' ('.$field_str.') VALUES ('.$val_str.');'.$newline;
            }

            $output .= $newline.$newline;
        }

        return $output;
    }
}