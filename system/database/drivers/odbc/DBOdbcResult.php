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
 * ODBC Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class DBOdbcResult extends DBResult {

	/**
	 * Number of rows in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	public function getRowsCount()
	{
		return @odbc_num_rows($this->result_id);
	}

	/**
	 * Number of fields in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	public function getFieldsCount()
	{
		return @odbc_num_fields($this->result_id);
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
		for ($i = 0; $i < $this->getFieldsCount(); $i++) {
			$field_names[]	= odbc_field_name($this->result_id, $i);
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
		for ($i = 0; $i < $this->getFieldsCount(); $i++) {
			$item = array();
			$item['name']		= odbc_field_name($this->result_id, $i);
			$item['type']		= odbc_field_type($this->result_id, $i);
			$item['max_length']	= odbc_field_len($this->result_id, $i);
			$item['primary_key'] = 0;
			$item['default']		= '';

			$retval[] = $item;
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
			odbc_free_result($this->result_id);
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
		return FALSE;
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
		if (function_exists('odbc_fetch_object')) {
			return odbc_fetch_array($this->result_id);
		} else {
			return $this->_odbcFetchArray($this->result_id);
		}
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
		if (function_exists('odbc_fetch_object')) {
			return odbc_fetch_object($this->result_id);
		} else {
			return $this->_odbcFetchObject($this->result_id);
		}
	}


	/**
	 * Result - object
	 *
	 * subsititutes the odbc_fetch_object function when
	 * not available (odbc_fetch_object requires unixODBC)
	 *
	 * @access	private
	 * @return	object
	 */
	protected function _odbcFetchObject(& $odbc_result) {
		$rs = array();
		$rs_obj = FALSE;
		if (odbc_fetch_into($odbc_result, $rs)) {
			foreach ($rs as $k=>$v) {
				$field_name= odbc_field_name($odbc_result, $k+1);
				$rs_obj->$field_name = $v;
			}
		}
		return $rs_obj;
	}


	/**
	 * Result - array
	 *
	 * subsititutes the odbc_fetch_array function when
	 * not available (odbc_fetch_array requires unixODBC)
	 *
	 * @access	private
	 * @return	array
	 */
	protected function _odbcFetchArray(& $odbc_result) {
		$rs = array();
		$rs_assoc = FALSE;
		if (odbc_fetch_into($odbc_result, $rs)) {
			$rs_assoc=array();
			foreach ($rs as $k=>$v) {
				$field_name= odbc_field_name($odbc_result, $k+1);
				$rs_assoc[$field_name] = $v;
			}
		}
		return $rs_assoc;
	}

}