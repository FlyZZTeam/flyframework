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
 * Logging Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 */
class Log
{

	protected $_log_path;
	protected $_threshold	= 1;
	protected $_date_fmt	= 'Y-m-d H:i:s';
	protected $_enabled	= true;
	protected $_levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

	/**
	 * Constructor
	 */
	public function __construct()
	{
        $config = Fly::getConfig();
		$logPath = $config['log_path'];

		$this->_log_path = ($logPath != '') ? $logPath : Fly::app()->getBasePath().'/runtime';

		if (!is_dir($this->_log_path) || !FileHelper::isReallyWritable($this->_log_path)) {
			$this->_enabled = false;
		}

		if (is_numeric($config['log_threshold'])) {
			$this->_threshold = $config['log_threshold'];
		}

		if ($config['log_date_format'] != '') {
			$this->_date_fmt = $config['log_date_format'];
		}
	}

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string	the error level
	 * @param	string	the error message
	 * @param	bool	whether the error is a native PHP error
	 * @return	bool
	 */
	public function write($level = 'error', $msg, $category = 'application', $php_error = false)
	{
		if ($this->_enabled === false) {
			return false;
		}

		$level = strtoupper($level);

		if (!isset($this->_levels[$level]) || ($this->_levels[$level] > $this->_threshold)) {
		    return false;
		}

		$filepath = $this->_log_path.DIRECTORY_SEPARATOR.'log-'.date('Y-m-d').'.log';
		$message  = '';

		if (!$fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
			return false;
		}

		$message .= '['.$level.'] ['.$category.'] '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n";
		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, FILE_WRITE_MODE);
		return true;
	}

}