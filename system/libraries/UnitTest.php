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
 * @since		Version 1.3.1
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Unit Testing Class
 *
 * Simple testing class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	UnitTesting
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/uri.html
 */
class UnitTest
{

	var $active					= TRUE;
	var $results				= array();
	var $strict					= FALSE;
	var $_template				= NULL;
	var $_templateRows			= NULL;
	var $_testItemsVisible	= array();

	public function __construct()
	{
		// These are the default items visible when a test is run.
		$this->_testItemsVisible = array (
							'testName',
							'testDataType',
							'resDataType',
							'result',
							'file',
							'line',
							'notes'
						);

		Fly::log('debug', "Unit Testing Class Initialized");
	}

	/**
	 * Run the tests
	 *
	 * Runs the supplied tests
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function setTestItems($items = array())
	{
		if (!empty($items) && is_array($items)) {
			$this->_testItemsVisible = $items;
		}
	}

	/**
	 * Run the tests
	 *
	 * Runs the supplied tests
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @param	string
	 * @return	string
	 */
	public function run($test, $expected = TRUE, $test_name = 'undefined', $notes = '')
	{
		if ($this->active == FALSE) {
			return FALSE;
		}

		if (in_array($expected, array('is_object', 'is_string', 'is_bool', 'is_true', 'is_false', 'is_int', 'is_numeric', 'is_float', 'is_double', 'is_array', 'is_null'), TRUE)) {
			$expected = str_replace('is_float', 'is_double', $expected);
			$result = ($expected($test)) ? TRUE : FALSE;
			$extype = str_replace(array('true', 'false'), 'bool', str_replace('is_', '', $expected));
		} else {
			if ($this->strict == TRUE) {
				$result = ($test === $expected) ? TRUE : FALSE;
            } else {
				$result = ($test == $expected) ? TRUE : FALSE;
            }

			$extype = gettype($expected);
		}

		$back = $this->_backtrace();

		$report[] = array (
							'testName'			=> $test_name,
							'testDataType'		=> gettype($test),
							'resDataType'		=> $extype,
							'result'			=> ($result === TRUE) ? 'passed' : 'failed',
							'file'				=> $back['file'],
							'line'				=> $back['line'],
							'notes'				=> $notes
						);

		$this->results[] = $report;

		return($this->report($this->result($report)));
	}

	/**
	 * Generate a report
	 *
	 * Displays a table with the test data
	 *
	 * @access	public
	 * @return	string
	 */
	public function report($result = array())
	{
		if (count($result) == 0) {
			$result = $this->result();
		}

		$this->_parseTemplate();

		$r = '';
		foreach ($result as $res) {
			$table = '';

			foreach ($res as $key => $val) {
				if ($key == Fly::t('unit_test', 'ut_result')) {
					if ($val == Fly::t('unit_test', 'ut_passed')) {
						$val = '<span style="color: #0C0;">'.$val.'</span>';
					} else if ($val == Fly::t('unit_test', 'ut_failed')) {
						$val = '<span style="color: #C00;">'.$val.'</span>';
					}
				}

				$temp = $this->_templateRows;
				$temp = str_replace('{item}', $key, $temp);
				$temp = str_replace('{result}', $val, $temp);
				$table .= $temp;
			}

			$r .= str_replace('{rows}', $table, $this->_template);
		}

		return $r;
	}

	/**
	 * Use strict comparison
	 *
	 * Causes the evaluation to use === rather than ==
	 *
	 * @access	public
	 * @param	bool
	 * @return	null
	 */
	public function useStrict($state = TRUE)
	{
		$this->strict = ($state == FALSE) ? FALSE : TRUE;
	}

	/**
	 * Make Unit testing active
	 *
	 * Enables/disables unit testing
	 *
	 * @access	public
	 * @param	bool
	 * @return	null
	 */
	function active($state = TRUE)
	{
		$this->active = ($state == FALSE) ? FALSE : TRUE;
	}

	/**
	 * Result Array
	 *
	 * Returns the raw result data
	 *
	 * @access	public
	 * @return	array
	 */
	public function result($results = array())
	{
		if (count($results) == 0) {
			$results = $this->results;
		}

		$retval = array();
		foreach ($results as $result) {
			$temp = array();
			foreach ($result as $key => $val) {
				if (!in_array($key, $this->_testItemsVisible)) {
					continue;
				}

				if (is_array($val)) {
					foreach ($val as $k => $v) {
						if (FALSE !== ($line = Fly::t('unit_test', strtolower('ut_'.$v)))) {
							$v = $line;
						}
						$temp[Fly::t('unit_test', 'ut_'.$k)] = $v;
					}
				} else {
					if (FALSE !== ($line = Fly::t('unit_test', strtolower('ut_'.$val)))) {
						$val = $line;
					}
					$temp[Fly::t('unit_test', 'ut_'.$key)] = $val;
				}
			}

			$retval[] = $temp;
		}

		return $retval;
	}

	/**
	 * Set the template
	 *
	 * This lets us set the template to be used to display results
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setTemplate($template)
	{
		$this->_template = $template;
	}

	/**
	 * Generate a backtrace
	 *
	 * This lets us show file names and line numbers
	 *
	 * @access	private
	 * @return	array
	 */
	protected function _backtrace()
	{
		if (function_exists('debug_backtrace')) {
			$back = debug_backtrace();

			$file = ( ! isset($back['1']['file'])) ? '' : $back['1']['file'];
			$line = ( ! isset($back['1']['line'])) ? '' : $back['1']['line'];

			return array('file' => $file, 'line' => $line);
		}
		return array('file' => 'Unknown', 'line' => 'Unknown');
	}

	/**
	 * Get Default Template
	 *
	 * @access	private
	 * @return	string
	 */
	protected function _defaultTemplate()
	{
		$this->_template = "\n".'<table style="width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;">';
		$this->_template .= '{rows}';
		$this->_template .= "\n".'</table>';

		$this->_templateRows = "\n\t".'<tr>';
		$this->_templateRows .= "\n\t\t".'<th style="text-align: left; border-bottom:1px solid #CCC;">{item}</th>';
		$this->_templateRows .= "\n\t\t".'<td style="border-bottom:1px solid #CCC;">{result}</td>';
		$this->_templateRows .= "\n\t".'</tr>';
	}

	/**
	 * Parse Template
	 *
	 * Harvests the data within the template {pseudo-variables}
	 *
	 * @access	private
	 * @return	void
	 */
	protected function _parseTemplate()
	{
		if (!is_null($this->_templateRows)) {
			return;
		}

		if (is_null($this->_template)) {
			$this->_defaultTemplate();
			return;
		}

		if (!preg_match("/\{rows\}(.*?)\{\/rows\}/si", $this->_template, $match)) {
			$this->_defaultTemplate();
			return;
		}

		$this->_templateRows = $match['1'];
		$this->_template = str_replace($match['0'], '{rows}', $this->_template);
	}

}

/**
 * Helper functions to test boolean true/false
 *
 *
 * @access	private
 * @return	bool
 */
function is_true($test)
{
	return (is_bool($test) AND $test === TRUE) ? TRUE : FALSE;
}
function is_false($test)
{
	return (is_bool($test) AND $test === FALSE) ? TRUE : FALSE;
}
