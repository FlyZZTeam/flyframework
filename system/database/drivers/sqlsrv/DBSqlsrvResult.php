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
 * SQLSRV Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class DBSqlsrvResult extends DBResult
{

	/**
	 * Number of rows in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	public function getRowsCount()
	{
		return @sqlsrv_num_rows($this->result_id);
	}

	/**
	 * Number of fields in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	public function getFieldsCount()
	{
		return @sqlsrv_num_fields($this->result_id);
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
		foreach(sqlsrv_field_metadata($this->result_id) as $offset => $field) {
			$field_names[] = $field['Name'];
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
		foreach(sqlsrv_field_metadata($this->result_id) as $offset => $field) {
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
			sqlsrv_free_stmt($this->result_id);
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
	public function _dataSeek($n = 0)
	{
		// Not implemented
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
		return sqlsrv_fetch_array($this->result_id, SQLSRV_FETCH_ASSOC);
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
		return sqlsrv_fetch_object($this->result_id);
	}

}