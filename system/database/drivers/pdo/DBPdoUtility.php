<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com
 * @since		Version 2.1.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * PDO Utility Class
 *
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/database/
 */
class DBPdoUtility extends DBUtility {

    /**
     * Create database
     *
     * @access	private
     * @param	string	the database name
     * @return	bool
     */
    protected function _createDataBase()
    {
        // PDO has no "create database" command since it's
        // designed to connect to an existing database
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return FALSE;
    }

    /**
     * Drop database
     *
     * @access	private
     * @param	string	the database name
     * @return	bool
     */
    protected function _dropDataBase($name)
    {
        // PDO has no "drop database" command since it's
        // designed to connect to an existing database
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return FALSE;
    }

    /**
     * Create Table
     *
     * @access	private
     * @param	string	the table name
     * @param	array	the fields
     * @param	mixed	primary key(s)
     * @param	mixed	key(s)
     * @param	boolean	should 'IF NOT EXISTS' be added to the SQL
     * @return	bool
     */
    protected function _createTable($table, $fields, $primary_keys, $keys, $if_not_exists)
    {
        $sql = 'CREATE TABLE ';

        if ($if_not_exists === TRUE) {
            $sql .= 'IF NOT EXISTS ';
        }

        $sql .= $this->db->escapeIdentifiers($table)." (";
        $current_field_count = 0;

        foreach ($fields as $field=>$attributes) {
            // Numeric field names aren't allowed in databases, so if the key is
            // numeric, we know it was assigned by PHP and the developer manually
            // entered the field information, so we'll simply add it to the list
            if (is_numeric($field)) {
                $sql .= "\n\t$attributes";
            } else {
                $attributes = array_change_key_case($attributes, CASE_UPPER);

                $sql .= "\n\t".$this->db->protectIdentifiers($field);

                $sql .=  ' '.$attributes['TYPE'];

                if (array_key_exists('CONSTRAINT', $attributes)) {
                    $sql .= '('.$attributes['CONSTRAINT'].')';
                }

                if (array_key_exists('UNSIGNED', $attributes) && $attributes['UNSIGNED'] === TRUE) {
                    $sql .= ' UNSIGNED';
                }

                if (array_key_exists('DEFAULT', $attributes)) {
                    $sql .= ' DEFAULT \''.$attributes['DEFAULT'].'\'';
                }

                if (array_key_exists('NULL', $attributes)) {
                    if ($attributes['NULL'] === TRUE) {
                        $sql .= ' NULL';
                    } else {
                        $sql .= ' NOT NULL';
                    }
                }

                if (array_key_exists('AUTO_INCREMENT', $attributes) && $attributes['AUTO_INCREMENT'] === TRUE) {
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
            $sql .= ",\n\tPRIMARY KEY (" . implode(', ', $primary_keys) . ")";
        }

        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $key) {
                if (is_array($key)) {
                    $key = $this->db->protectIdentifiers($key);
                } else {
                    $key = array($this->db->protectIdentifiers($key));
                }

                $sql .= ",\n\tFOREIGN KEY (" . implode(', ', $key) . ")";
            }
        }

        $sql .= "\n)";

        return $sql;
    }

    /**
     * Drop Table
     *
     * @access	private
     * @return	bool
     */
    protected function _dropTable($table)
    {
        // Not a supported PDO feature
        if ($this->db->db_debug) {
            return $this->db->displayError('db_unsuported_feature');
        }
        return FALSE;
    }

    /**
     * Alter table query
     *
     * Generates a platform-specific query so that a table can be altered
     * Called by add_column(), drop_column(), and column_alter(),
     *
     * @access	private
     * @param	string	the ALTER type (ADD, DROP, CHANGE)
     * @param	string	the column name
     * @param	string	the table name
     * @param	string	the column definition
     * @param	string	the default value
     * @param	boolean	should 'NOT NULL' be added
     * @param	string	the field after which we should add the new field
     * @return	object
     */
    protected function _alterTable($alter_type, $table, $fields, $after_field = '')
    //protected function _alterTable($alter_type, $table, $column_name, $column_definition = '', $default_value = '', $null = '', $after_field = '')
    {
        $sql = 'ALTER TABLE '.$this->db->protectIdentifiers($table)." $alter_type ";

        // DROP has everything it needs now.
        if ($alter_type == 'DROP') {
            return $sql.$this->db->protectIdentifiers($fields);
        }

        $sql .= $this->_processFields($fields);

        if ($after_field != '') {
            $sql .= ' AFTER ' . $this->db->protectIdentifiers($after_field);
        }

        /*
        $sql .= " $column_definition";

        if ($default_value != '') {
            $sql .= " DEFAULT \"$default_value\"";
        }

        if ($null === NULL) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }

        if ($after_field != '') {
            $sql .= ' AFTER ' . $this->db->protectIdentifiers($after_field);
        }*/

        return $sql;

    }

    /**
     * Rename a table
     *
     * Generates a platform-specific query so that a table can be renamed
     *
     * @access	private
     * @param	string	the old table name
     * @param	string	the new table name
     * @return	string
     */
    protected function _renameTable($table_name, $new_table_name)
    {
        $sql = 'ALTER TABLE '.$this->db->protectIdentifiers($table_name)." RENAME TO ".$this->db->protectIdentifiers($new_table_name);
        return $sql;
    }

	/**
	 * List databases
	 *
	 * @access	private
	 * @return	bool
	 */
	protected function _listDataBases()
	{
		// Not sure if PDO lets you list all databases...
		if ($this->db->db_debug) {
			return $this->db->displayError('db_unsuported_feature');
		}
		return FALSE;
	}

	/**
	 * Optimize table query
	 *
	 * Generates a platform-specific query so that a table can be optimized
	 *
	 * @access	private
	 * @param	string	the table name
	 * @return	object
	 */
	protected function _optimizeTable($table)
	{
		// Not a supported PDO feature
		if ($this->db->db_debug) {
			return $this->db->displayError('db_unsuported_feature');
		}
		return FALSE;
	}

	/**
	 * Repair table query
	 *
	 * Generates a platform-specific query so that a table can be repaired
	 *
	 * @access	private
	 * @param	string	the table name
	 * @return	object
	 */
	protected function _repairTable($table)
	{
		// Not a supported PDO feature
		if ($this->db->db_debug) {
			return $this->db->displayError('db_unsuported_feature');
		}
		return FALSE;
	}

	/**
	 * PDO Export
	 *
	 * @access	private
	 * @param	array	Preferences
	 * @return	mixed
	 */
	function _backup($params = array())
	{
		// Currently unsupported
        if ($this->db->db_debug) {
		    return $this->db->displayError('db_unsuported_feature');
        }
	}

}