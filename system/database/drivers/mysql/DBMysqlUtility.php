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
 * MySQL Utility Class
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class DBMysqlUtility extends DBUtility
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
     * Create Table
     *
     * @access	private
     * @param	string	the table name
     * @param	mixed	the fields
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

        $sql .= $this->_processFields($fields);

        if (count($primary_keys) > 0) {
            $key_name = $this->db->protectIdentifiers(implode('_', $primary_keys));
            $primary_keys = $this->db->protectIdentifiers($primary_keys);
            $sql .= ",\n\tPRIMARY KEY ".$key_name." (" . implode(', ', $primary_keys) . ")";
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

                $sql .= ",\n\tKEY {$key_name} (" . implode(', ', $key) . ")";
            }
        }

        $sql .= "\n) DEFAULT CHARACTER SET {$this->db->char_set} COLLATE {$this->db->dbcollat};";

        return $sql;
    }

    /**
     * Drop Table
     *
     * @access	private
     * @return	string
     */
    protected function _dropTable($table)
    {
        return "DROP TABLE IF EXISTS ".$this->db->escapeIdentifiers($table);
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
     * @param	array	fields
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
		return "SHOW DATABASES";
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
		return "OPTIMIZE TABLE ".$this->db->escapeIdentifiers($table);
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
		return "REPAIR TABLE ".$this->db->escapeIdentifiers($table);
	}

	/**
	 * MySQL Export
	 *
	 * @access	private
	 * @param	array	Preferences
	 * @return	mixed
	 */
	protected function _backup($params = array())
	{
		if (count($params) == 0) {
			return FALSE;
		}

		// Extract the prefs for simplicity
		extract($params);

		// Build the output
		$output = '';
		foreach ((array)$tables as $table) {
			// Is the table in the "ignore" list?
			if (in_array($table, (array)$ignore, TRUE)) {
				continue;
			}

			// Get the table schema
			$query = $this->db->query("SHOW CREATE TABLE `".$this->db->database.'`.`'.$table.'`');

			// No result means the table name was invalid
			if ($query === FALSE) {
				continue;
			}

			// Write out the table schema
			$output .= '#'.$newline.'# TABLE STRUCTURE FOR: '.$table.$newline.'#'.$newline.$newline;

			if ($add_drop == TRUE) {
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
			if ($add_insert == FALSE) {
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
										TRUE)
										) ? TRUE : FALSE;

				// Create a string of field names
				$field_str .= '`'.$field->name.'`, ';
				$i++;
			}

			// Trim off the end comma
			$field_str = preg_replace( "/, $/" , "" , $field_str);


			// Build the insert string
			foreach ($query->resultArray() as $row) {
				$val_str = '';

				$i = 0;
				foreach ($row as $v) {
					// Is the value NULL?
					if ($v === NULL) {
						$val_str .= 'NULL';
					} else {
						// Escape the data if it's not an integer
						if ($is_int[$i] == FALSE) {
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
				$val_str = preg_replace( "/, $/" , "" , $val_str);

				// Build the INSERT string
				$output .= 'INSERT INTO '.$table.' ('.$field_str.') VALUES ('.$val_str.');'.$newline;
			}

			$output .= $newline.$newline;
		}

		return $output;
	}
}