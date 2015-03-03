<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * MySQLi Utility Class
 */
class DBMysqliUtility extends DBUtility
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
                }

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

        return $sql;
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
        return "DROP TABLE IF EXISTS ".$this->db->_escapeIdentifiers($table);
    }

    /**
     * Alter table query
     * Generates a platform-specific query so that a table can be altered
     * @param string $alter_type the ALTER type (ADD, DROP, CHANGE)
     * @param string $table the column name
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
     * MySQLi Export
     * @param array $params Preferences
     * @return mixed
     */
    protected function _backup($params = array())
    {
        // Currently unsupported
        return $this->db->displayError('db_unsuported_feature');
    }
}