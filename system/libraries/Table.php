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
 * HTML Table Generating Class
 *
 * Lets you create tables manually or from database result objects, or arrays.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	HTML Tables
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/uri.html
 */
class Table
{

	private $rows				= array();
    private $heading			= array();
    private $autoHeading		= TRUE;
    private $caption			= NULL;
    private $template			= NULL;
    private $newline			= "\n";
    private $emptyCells		= "";
    private	$function			= FALSE;

	public function __construct()
	{
		Fly::log('debug', "Table Class Initialized");
	}

	/**
	 * Set the template
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function setTemplate($template)
	{
		if (!is_array($template)) {
			return FALSE;
		}
		$this->template = $template;
	}

	/**
	 * Set the table heading
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	public function setHeading()
	{
		$args = func_get_args();
		$this->heading = $this->_prepArgs($args);
	}

	/**
	 * Set columns.  Takes a one-dimensional array as input and creates
	 * a multi-dimensional array with a depth equal to the number of
	 * columns.  This allows a single array with many elements to  be
	 * displayed in a table that has a fixed column count.
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	void
	 */
	public function makeColumns($array = array(), $col_limit = 0)
	{
		if (!is_array($array) || count($array) == 0) {
			return FALSE;
		}

		// Turn off the auto-heading feature since it's doubtful we
		// will want headings from a one-dimensional array
		$this->autoHeading = FALSE;

		if ($col_limit == 0) {
			return $array;
		}

		$new = array();
		while (count($array) > 0) {
			$temp = array_splice($array, 0, $col_limit);

			if (count($temp) < $col_limit) {
				for ($i = count($temp); $i < $col_limit; $i++) {
					$temp[] = '&nbsp;';
				}
			}

			$new[] = $temp;
		}

		return $new;
	}

	/**
	 * Set "empty" cells
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function setEmpty($value)
	{
		$this->emptyCells = $value;
	}

	/**
	 * Add a table row
	 *
	 * Can be passed as an array or discreet params
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function addRow()
	{
		$args = func_get_args();
		$this->rows[] = $this->_prepArgs($args);
	}

	/**
	 * Prep Args
	 *
	 * Ensures a standard associative array format for all cell data
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	private function _prepArgs($args)
	{
		// If there is no $args[0], skip this and treat as an associative array
		// This can happen if there is only a single key, for example this is passed to table->generate
		// array(array('foo'=>'bar'))
		if (isset($args[0]) AND (count($args) == 1 && is_array($args[0]))) {
			// args sent as indexed array
			if ( ! isset($args[0]['data'])) {
				foreach ($args[0] as $key => $val) {
					if (is_array($val) && isset($val['data'])) {
						$args[$key] = $val;
					} else {
						$args[$key] = array('data' => $val);
					}
				}
			}
		} else {
			foreach ($args as $key => $val) {
				if (!is_array($val)) {
					$args[$key] = array('data' => $val);
				}
			}
		}

		return $args;
	}

	/**
	 * Add a table caption
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setCaption($caption)
	{
		$this->caption = $caption;
	}

	/**
	 * Generate the table
	 *
	 * @access	public
	 * @param	mixed
	 * @return	string
	 */
	public function generate($table_data = NULL)
	{
		// The table data can optionally be passed to this function
		// either as a database result object or an array
		if (!is_null($table_data)) {
			if (is_object($table_data)) {
				$this->_setFromObject($table_data);
			} else if (is_array($table_data)) {
				$set_heading = (count($this->heading) == 0 && $this->autoHeading == FALSE) ? FALSE : TRUE;
				$this->_setFromArray($table_data, $set_heading);
			}
		}

		// Is there anything to display?  No?  Smite them!
		if (count($this->heading) == 0 && count($this->rows) == 0) {
			return 'Undefined table data';
		}

		// Compile and validate the template date
		$this->_compileTemplate();

		// set a custom cell manipulation function to a locally scoped variable so its callable
		$function = $this->function;

		// Build the table!

		$out = $this->template['table_open'];
		$out .= $this->newline;

		// Add any caption here
		if ($this->caption) {
			$out .= $this->newline;
			$out .= '<caption>' . $this->caption . '</caption>';
			$out .= $this->newline;
		}

		// Is there a table heading to display?
		if (count($this->heading) > 0) {
			$out .= $this->template['thead_open'];
			$out .= $this->newline;
			$out .= $this->template['heading_row_start'];
			$out .= $this->newline;

			foreach ($this->heading as $heading) {
				$temp = $this->template['heading_cell_start'];

				foreach ($heading as $key => $val) {
					if ($key != 'data') {
						$temp = str_replace('<th', "<th $key='$val'", $temp);
					}
				}

				$out .= $temp;
				$out .= isset($heading['data']) ? $heading['data'] : '';
				$out .= $this->template['heading_cell_end'];
			}

			$out .= $this->template['heading_row_end'];
			$out .= $this->newline;
			$out .= $this->template['thead_close'];
			$out .= $this->newline;
		}

		// Build the table rows
		if (count($this->rows) > 0) {
			$out .= $this->template['tbody_open'];
			$out .= $this->newline;

			$i = 1;
			foreach ($this->rows as $row) {
				if ( ! is_array($row)) {
					break;
				}

				// We use modulus to alternate the row colors
				$name = (fmod($i++, 2)) ? '' : 'alt_';

				$out .= $this->template['row_'.$name.'start'];
				$out .= $this->newline;

				foreach ($row as $cell) {
					$temp = $this->template['cell_'.$name.'start'];

					foreach ($cell as $key => $val) {
						if ($key != 'data') {
							$temp = str_replace('<td', "<td $key='$val'", $temp);
						}
					}

					$cell = isset($cell['data']) ? $cell['data'] : '';
					$out .= $temp;

					if ($cell === "" OR $cell === NULL) {
						$out .= $this->emptyCells;
					} else {
						if ($function !== FALSE && is_callable($function)) {
							$out .= call_user_func($function, $cell);
						} else {
							$out .= $cell;
						}
					}

					$out .= $this->template['cell_'.$name.'end'];
				}

				$out .= $this->template['row_'.$name.'end'];
				$out .= $this->newline;
			}

			$out .= $this->template['tbody_close'];
			$out .= $this->newline;
		}

		$out .= $this->template['table_close'];

		// Clear table class properties before generating the table
		$this->clear();

		return $out;
	}

	/**
	 * Clears the table arrays.  Useful if multiple tables are being generated
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear()
	{
		$this->rows				= array();
		$this->heading			= array();
		$this->autoHeading		= TRUE;
	}

	/**
	 * Set table data from a database result object
	 *
	 * @access	public
	 * @param	object
	 * @return	void
	 */
	private function _setFromObject($query)
	{
		if (!is_object($query)) {
			return FALSE;
		}

		// First generate the headings from the table column names
		if (count($this->heading) == 0) {
			if (!method_exists($query, 'fetchFields')) {
				return FALSE;
			}

			$this->heading = $this->_prepArgs($query->fetchFields());
		}

		// Next blast through the result array and build out the rows
        foreach ($query->resultArray() as $row) {
            $this->rows[] = $this->_prepArgs($row);
        }
	}

	/**
	 * Set table data from an array
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	private function _setFromArray($data, $set_heading = TRUE)
	{
		if (!is_array($data) || count($data) == 0) {
			return FALSE;
		}

		$i = 0;
		foreach ($data as $row) {
			// If a heading hasn't already been set we'll use the first row of the array as the heading
			if ($i == 0 && count($data) > 1 && count($this->heading) == 0 && $set_heading == TRUE) {
				$this->heading = $this->_prepArgs($row);
			} else {
				$this->rows[] = $this->_prepArgs($row);
			}

			$i++;
		}
	}

	/**
	 * Compile Template
	 *
	 * @access	private
	 * @return	void
	 */
	private function _compileTemplate()
	{
		if ($this->template == NULL) {
			$this->template = $this->_defaultTemplate();
			return;
		}

		$this->temp = $this->_defaultTemplate();
		foreach (array('table_open', 'thead_open', 'thead_close', 'heading_row_start', 'heading_row_end', 'heading_cell_start', 'heading_cell_end', 'tbody_open', 'tbody_close', 'row_start', 'row_end', 'cell_start', 'cell_end', 'row_alt_start', 'row_alt_end', 'cell_alt_start', 'cell_alt_end', 'table_close') as $val) {
			if (!isset($this->template[$val])) {
				$this->template[$val] = $this->temp[$val];
			}
		}
	}

	/**
	 * Default Template
	 *
	 * @access	private
	 * @return	void
	 */
	private function _defaultTemplate()
	{
		return  array (
						'table_open'			=> '<table border="0" cellpadding="4" cellspacing="0">',

						'thead_open'			=> '<thead>',
						'thead_close'			=> '</thead>',

						'heading_row_start'		=> '<tr>',
						'heading_row_end'		=> '</tr>',
						'heading_cell_start'	=> '<th>',
						'heading_cell_end'		=> '</th>',

						'tbody_open'			=> '<tbody>',
						'tbody_close'			=> '</tbody>',

						'row_start'				=> '<tr>',
						'row_end'				=> '</tr>',
						'cell_start'			=> '<td>',
						'cell_end'				=> '</td>',

						'row_alt_start'		=> '<tr>',
						'row_alt_end'			=> '</tr>',
						'cell_alt_start'		=> '<td>',
						'cell_alt_end'			=> '</td>',

						'table_close'			=> '</table>'
					);
	}


}