<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * SQLSRV Utility Class
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class DBSqlsrvUtility extends DBUtility
{

    /**
     * Create database
     *
     * @access	private
     * @param	string	the database name
     * @return	bool
     */
    protected function _createDataBase($name)
    {
        return "CREATE DATABASE ".$name;
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
        return "DROP DATABASE ".$name;
    }

    /**
     * Drop Table
     *
     * @access	private
     * @return	bool
     */
    protected function _dropTable($table)
    {
        return "IF (EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND  TABLE_NAME = '"
        .$table."')) DROP TABLE [dbo].[".$table."]";
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
        $sql = '';
        if ($if_not_exists === TRUE) {
            $sql = "IF (NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND  TABLE_NAME = ".$this->db->escapeIdentifiers($table)."))";
        }
        $sql .=  " CREATE TABLE ".$this->db->escapeIdentifiers($table)." (";
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

                if (array_key_exists('NULL', $attributes) && $attributes['NULL'] === TRUE) {
                    $sql .= ' NULL';
                } else {
                    $sql .= ' NOT NULL';
                }

                if (array_key_exists('AUTO_INCREMENT', $attributes) && $attributes['AUTO_INCREMENT'] === TRUE) {
                    $sql .= ' IDENTITY(1,1)';
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
        return 'EXEC sp_rename '.$this->db->protectIdentifiers($table_name).", ".$this->db->protectIdentifiers($new_table_name);
    }

	/**
	 * List databases
	 *
	 * @access	private
	 * @return	bool
	 */
	protected function _listDataBases()
	{
		return "EXEC sp_helpdb"; // Can also be: EXEC sp_databases
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
		return FALSE; // Is this supported in MS SQL?
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
		return FALSE; // Is this supported in MS SQL?
	}

	/**
	 * MSSQL Export
	 *
	 * @access	private
	 * @param	array	Preferences
	 * @return	mixed
	 */
	protected function _backup($params = array())
	{
		// Currently unsupported
		return $this->db->display_error('db_unsuported_feature');
	}

}