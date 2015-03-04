<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Postgre Utility Class
 */
class DBPostgreUtility extends DBUtility
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
     * @param array $fields the fields
     * @param mixed $primary_keys primary key(s)
     * @param mixed $keys key(s)
     * @param boolean $if_not_exists should 'IF NOT EXISTS' be added to the SQL
     * @return bool
     */
    protected function _createTable($table, $fields, $primary_keys, $keys, $if_not_exists)
    {
        $sql = 'CREATE TABLE ';

        if ($if_not_exists === true) {
            if ($this->db->isTableExists($table)) {
                return "SELECT * FROM $table"; // Needs to return innocous but valid SQL statement
            }
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

                $is_unsigned = (array_key_exists('UNSIGNED', $attributes) && $attributes['UNSIGNED'] === true);

                // Convert datatypes to be PostgreSQL-compatible
                switch (strtoupper($attributes['TYPE'])) {
                    case 'TINYINT':
                        $attributes['TYPE'] = 'SMALLINT';
                        break;
                    case 'SMALLINT':
                        $attributes['TYPE'] = ($is_unsigned) ? 'INTEGER' : 'SMALLINT';
                        break;
                    case 'MEDIUMINT':
                        $attributes['TYPE'] = 'INTEGER';
                        break;
                    case 'INT':
                        $attributes['TYPE'] = ($is_unsigned) ? 'BIGINT' : 'INTEGER';
                        break;
                    case 'BIGINT':
                        $attributes['TYPE'] = ($is_unsigned) ? 'NUMERIC' : 'BIGINT';
                        break;
                    case 'DOUBLE':
                        $attributes['TYPE'] = 'DOUBLE PRECISION';
                        break;
                    case 'DATETIME':
                        $attributes['TYPE'] = 'TIMESTAMP';
                        break;
                    case 'LONGTEXT':
                        $attributes['TYPE'] = 'TEXT';
                        break;
                    case 'BLOB':
                        $attributes['TYPE'] = 'BYTEA';
                        break;
                }

                // If this is an auto-incrementing primary key, use the serial data type instead
                if (in_array($field, $primary_keys) && array_key_exists('AUTO_INCREMENT', $attributes)
                    && $attributes['AUTO_INCREMENT'] === true
                ) {
                    $sql .= ' SERIAL';
                } else {
                    $sql .= ' '.$attributes['TYPE'];
                }

                // Modified to prevent constraints with integer data types
                if (array_key_exists('CONSTRAINT', $attributes) && strpos($attributes['TYPE'], 'INT') === false) {
                    $sql .= '('.$attributes['CONSTRAINT'].')';
                }

                if (array_key_exists('DEFAULT', $attributes)) {
                    $sql .= ' DEFAULT \''.$attributes['DEFAULT'].'\'';
                }

                if (array_key_exists('NULL', $attributes) && $attributes['NULL'] === true) {
                    $sql .= ' NULL';
                } else {
                    $sql .= ' NOT NULL';
                }

                // Added new attribute to create unqite fields. Also works with MySQL
                if (array_key_exists('UNIQUE', $attributes) && $attributes['UNIQUE'] === true) {
                    $sql .= ' UNIQUE';
                }
            }

            // don't add a comma on the end of the last field
            if (++$current_field_count < count($fields)) {
                $sql .= ',';
            }
        }

        if (count($primary_keys) > 0) {
            // Something seems to break when passing an array to _protect_identifiers()
            foreach ($primary_keys as $index => $key) {
                $primary_keys[$index] = $this->db->protectIdentifiers($key);
            }

            $sql .= ",\n\tPRIMARY KEY (".implode(', ', $primary_keys).")";
        }

        $sql .= "\n);";

        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $key) {
                if (is_array($key)) {
                    $key = $this->db->protectIdentifiers($key);
                } else {
                    $key = array($this->db->protectIdentifiers($key));
                }

                foreach ($key as $field) {
                    $sql .= "CREATE INDEX ".$table."_".str_replace(array(
                            '"',
                            "'"
                        ), '', $field)."_index ON $table ($field); ";
                }
            }
        }
        return $sql;
    }

    /**
     * Drop Table
     * @return bool
     */
    protected function _dropTable($table)
    {
        return "DROP TABLE IF EXISTS ".$this->db->escapeIdentifiers($table)." CASCADE";
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
        return "SELECT datname FROM pg_database";
    }

    /**
     * Optimize table query
     * Is table optimization supported in Postgre?
     * @param string $table the table name
     * @return object
     */
    protected function _optimizeTable($table)
    {
        return false;
    }

    /**
     * Repair table query
     * Are table repairs supported in Postgre?
     * @param string $table the table name
     * @return object
     */
    protected function _repairTable($table)
    {
        return false;
    }

    /**
     * Postgre Export
     * @param array $params Preferences
     * @return mixed
     */
    protected function _backup($params = array())
    {
        // Currently unsupported
        return $this->db->displayError('db_unsuported_feature');
    }
}