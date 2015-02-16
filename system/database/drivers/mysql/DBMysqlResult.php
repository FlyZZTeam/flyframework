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

// --------------------------------------------------------------------

/**
 * MySQL Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class DBMysqlResult extends DBResult
{

	/**
	 * Number of rows in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	public function getRowsCount()
	{
		return @mysql_num_rows($this->result_id);
	}

	/**
	 * Number of fields in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	public function getFieldsCount()
	{
		return @mysql_num_fields($this->result_id);
	}

	/**
	 * Fetch Field Names
	 *
	 * Generates an array of column names
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchFields()
	{
		$field_names = array();
		while ($field = mysql_fetch_field($this->result_id)) {
			$field_names[] = $field->name;
		}

		return $field_names;
	}

	/**
	 * Field data
	 *
	 * Generates an array of objects containing field meta-data
	 *
	 * @access	public
	 * @return	array
	 */
	public function getFieldData()
	{
		$retval = array();
		while ($field = mysql_fetch_field($this->result_id)) {
			$retval[] = $field;
		}

		return $retval;
	}

	/**
	 * Free the result
	 *
	 * @return	null
	 */
	public function freeResult()
	{
		if (is_resource($this->result_id)) {
			mysql_free_result($this->result_id);
			$this->result_id = FALSE;
		}
	}

	/**
	 * Data Seek
	 *
	 * Moves the internal pointer to the desired offset.  We call
	 * this internally before fetching results to make sure the
	 * result set starts at zero
	 *
	 * @access	private
	 * @return	array
	 */
	protected function _dataSeek($n = 0)
	{
		return mysql_data_seek($this->result_id, $n);
	}

	/**
	 * Result - associative array
	 *
	 * Returns the result set as an array
	 *
	 * @access	private
	 * @return	array
	 */
	protected function _fetchAssoc()
	{
		return mysql_fetch_assoc($this->result_id);
	}

	/**
	 * Result - object
	 *
	 * Returns the result set as an object
	 *
	 * @access	private
	 * @return	object
	 */
	protected function _fetchObject()
	{
		return mysql_fetch_object($this->result_id);
	}

}