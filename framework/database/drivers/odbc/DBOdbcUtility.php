<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ODBC Utility Class
 */
class DBOdbcUtility extends DBUtility
{

    /**
     * Create database
     * @param string $name the database name
     * @return bool
     */
    protected function _createDataBase($name)
    {
        // ODBC has no "create database" command since it's
        // designed to connect to an existing database
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return false;
    }

    /**
     * Drop database
     * @param string $name the database name
     * @return bool
     */
    protected function _dropDataBase($name)
    {
        // ODBC has no "drop database" command since it's
        // designed to connect to an existing database
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return false;
    }

    /**
     * Create Table
     * @param string $table the table name
     * @param array $fields the fields
     * @param mixed primary_keys primary key(s)
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
        $current_field_count = 0;

        foreach ($fields as $field => $attributes) {
            // Numeric field names aren't allowed in databases, so if the key is
            // numeric, we know it was assigned by PHP and the developer manually
            // entered the field information, so we'll simply add it to the list
            if (is_numeric($field)) {
                $sql .= "\n\t$attributes";
            } else {
                $attributes = array_change_key_case($attributes, CASE_UPPER);

                $sql .= "\n\t".$this->db->protectIdentifiers($field);

                $sql .= ' '.$attributes['TYPE'];

                if (array_key_exists('CONSTRAINT', $attributes)) {
                    $sql .= '('.$attributes['CONSTRAINT'].')';
                }

                if (array_key_exists('UNSIGNED', $attributes) && $attributes['UNSIGNED'] === true) {
                    $sql .= ' UNSIGNED';
                }

                if (array_key_exists('DEFAULT', $attributes)) {
                    $sql .= ' DEFAULT \''.$attributes['DEFAULT'].'\'';
                }

                if (array_key_exists('NULL', $attributes) && $attributes['NULL'] === true) {
                    $sql .= ' NULL';
                } else {
                    $sql .= ' NOT NULL';
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

        if (count($primary_keys) > 0) {
            $primary_keys = $this->db->protectIdentifiers($primary_keys);
            $sql .= ",\n\tPRIMARY KEY (".implode(', ', $primary_keys).")";
        }

        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $key) {
                if (is_array($key)) {
                    $key = $this->db->protectIdentifiers($key);
                } else {
                    $key = array($this->db->protectIdentifiers($key));
                }

                $sql .= ",\n\tFOREIGN KEY (".implode(', ', $key).")";
            }
        }

        $sql .= "\n)";

        return $sql;
    }

    /**
     * Drop Table
     * @return bool
     */
    protected function _dropTable($table)
    {
        // Not a supported ODBC feature
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return false;
    }

    /**
     * Alter table query
     * @param string $alter_type the ALTER type (ADD, DROP, CHANGE)
     * @param string $table the table name
     * @param string $fields the column definition
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
        // Not sure if ODBC lets you list all databases...
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return false;
    }

    /**
     * Optimize table query
     * @param string $table the table name
     * @return object
     */
    protected function _optimizeTable($table)
    {
        // Not a supported ODBC feature
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return false;
    }

    /**
     * Repair table query
     * @param string $table the table name
     * @return object
     */
    protected function _repairTable($table)
    {
        // Not a supported ODBC feature
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return false;
    }

    /**
     * ODBC Export
     * @param array $params Preferences
     * @return mixed
     */
    protected function _backup($params = array())
    {
        // Currently unsupported
        return $this->db->displayError('db_unsuported_feature');
    }
}