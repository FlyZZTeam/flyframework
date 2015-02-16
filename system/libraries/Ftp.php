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
 * FTP Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/ftp.html
 */
class Ftp
{

	public $hostName	= '';
	public $userName	= 'anonymous';
	public $password	= '';
	public $port		= 21;
	public $passive	    = TRUE;
	public $debug		= FALSE;
	private $connId	    = FALSE;


	/**
	 * Constructor - Sets Preferences
	 *
	 * The constructor can be passed an array of config values
	 */
	public function __construct($config = array())
	{
		if (count($config) > 0) {
			$this->initialize($config);
		}

		Fly::log('debug', "FTP Class Initialized");
	}

	/**
	 * Initialize preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function initialize($config = array())
	{
		foreach ($config as $key => $val) {
			if (isset($this->$key)) {
				$this->$key = $val;
			}
		}

		// Prep the hostname
		$this->hostName = preg_replace('|.+?://|', '', $this->hostName);
	}

	/**
	 * FTP Connect
	 *
	 * @access	public
	 * @param	array	 the connection values
	 * @return	bool
	 */
	public function connect($config = array())
	{
		if (count($config) > 0) {
			$this->initialize($config);
		}

		if (FALSE === ($this->connId = @ftp_connect($this->hostName, $this->port))) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_connect');
			}
			return FALSE;
		}

		if (!$this->_login()) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_login');
			}
			return FALSE;
		}

		// Set passive mode if needed
		if ($this->passive == TRUE) {
			ftp_pasv($this->connId, TRUE);
		}

		return TRUE;
	}

	/**
	 * FTP Login
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _login()
	{
		return @ftp_login($this->connId, $this->userName, $this->password);
	}

	/**
	 * Validates the connection ID
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _isConn()
	{
		if (!is_resource($this->connId)) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_no_connection');
			}
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Change directory
	 *
	 * The second parameter lets us momentarily turn off debugging so that
	 * this function can be used to test for the existence of a folder
	 * without throwing an error.  There's no FTP equivalent to is_dir()
	 * so we do it by trying to change to a particular directory.
	 * Internally, this parameter is only used by the "mirror" function below.
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	public function changeDir($path = '', $supress_debug = FALSE)
	{
		if ($path == '' || !$this->_isConn()) {
			return FALSE;
		}

		$result = @ftp_chdir($this->connId, $path);

		if ($result === FALSE) {
			if ($this->debug == TRUE && $supress_debug == FALSE) {
				$this->_error('ftp_unable_to_changedir');
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Create a directory
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function mkdir($path = '', $permissions = NULL)
	{
		if ($path == '' || ! $this->_isConn()) {
			return FALSE;
		}

		$result = @ftp_mkdir($this->connId, $path);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_makdir');
			}
			return FALSE;
		}

		// Set file permissions if needed
		if (!is_null($permissions)) {
			$this->chmod($path, (int)$permissions);
		}

		return TRUE;
	}

	/**
	 * Upload a file to the server
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL)
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		if (!file_exists($locpath)) {
			$this->_error('ftp_no_source_file');
			return FALSE;
		}

		// Set the mode if not specified
		if ($mode == 'auto') {
			// Get the file extension so we can set the upload type
			$ext = $this->_getExt($locpath);
			$mode = $this->_setType($ext);
		}

		$mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

		$result = @ftp_put($this->connId, $rempath, $locpath, $mode);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_upload');
			}
			return FALSE;
		}

		// Set file permissions if needed
		if (!is_null($permissions)) {
			$this->chmod($rempath, (int)$permissions);
		}

		return TRUE;
	}

	/**
	 * Download a file from a remote server to the local server
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function download($rempath, $locpath, $mode = 'auto')
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		// Set the mode if not specified
		if ($mode == 'auto') {
			// Get the file extension so we can set the upload type
			$ext = $this->_getExt($rempath);
			$mode = $this->_setType($ext);
		}

		$mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

		$result = @ftp_get($this->connId, $locpath, $rempath, $mode);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_download');
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Rename (or move) a file
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	public function rename($old_file, $new_file, $move = FALSE)
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		$result = @ftp_rename($this->connId, $old_file, $new_file);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$msg = ($move == FALSE) ? 'ftp_unable_to_rename' : 'ftp_unable_to_move';

				$this->_error($msg);
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Move a file
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function move($old_file, $new_file)
	{
		return $this->rename($old_file, $new_file, TRUE);
	}

	/**
	 * Rename (or move) a file
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function deleteFile($filepath)
	{
		if ( ! $this->_isConn()) {
			return FALSE;
		}

		$result = @ftp_delete($this->connId, $filepath);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_delete');
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Delete a folder and recursively delete everything (including sub-folders)
	 * containted within it.
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function deleteDir($filepath)
    {
		if (!$this->_isConn()) {
			return FALSE;
		}

		// Add a trailing slash to the file path if needed
		$filepath = preg_replace("/(.+?)\/*$/", "\\1/",  $filepath);

		$list = $this->listFiles($filepath);

		if ($list !== FALSE && count($list) > 0) {
			foreach ($list as $item) {
				// If we can't delete the item it's probaly a folder so
				// we'll recursively call delete_dir()
				if (!@ftp_delete($this->connId, $item)) {
					$this->deleteDir($item);
				}
			}
		}

		$result = @ftp_rmdir($this->connId, $filepath);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_delete');
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Set file permissions
	 *
	 * @access	public
	 * @param	string	the file path
	 * @param	string	the permissions
	 * @return	bool
	 */
	public function chmod($path, $perm)
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		// Permissions can only be set when running PHP 5
		if (!function_exists('ftp_chmod')) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_chmod');
			}
			return FALSE;
		}

		$result = @ftp_chmod($this->connId, $perm, $path);

		if ($result === FALSE) {
			if ($this->debug == TRUE) {
				$this->_error('ftp_unable_to_chmod');
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * FTP List files in the specified directory
	 *
	 * @access	public
	 * @return	array
	 */
	public function listFiles($path = '.')
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		return ftp_nlist($this->connId, $path);
	}

	/**
	 * Read a directory and recreate it remotely
	 *
	 * This function recursively reads a folder and everything it contains (including
	 * sub-folders) and creates a mirror via FTP based on it.  Whatever the directory structure
	 * of the original file path will be recreated on the server.
	 *
	 * @access	public
	 * @param	string	path to source with trailing slash
	 * @param	string	path to destination - include the base folder with trailing slash
	 * @return	bool
	 */
	function mirror($locpath, $rempath)
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		// Open the local file path
		if ($fp = @opendir($locpath)) {
			// Attempt to open the remote file path.
			if ( ! $this->changeDir($rempath, TRUE)) {
				// If it doesn't exist we'll attempt to create the direcotory
				if ( ! $this->mkdir($rempath) OR ! $this->changeDir($rempath)) {
					return FALSE;
				}
			}

			// Recursively read the local directory
			while (FALSE !== ($file = readdir($fp))) {
				if (@is_dir($locpath.$file) && substr($file, 0, 1) != '.') {
					$this->mirror($locpath.$file."/", $rempath.$file."/");
				} else if (substr($file, 0, 1) != ".") {
					// Get the file extension so we can se the upload type
					$ext = $this->_getExt($file);
					$mode = $this->_setType($ext);

					$this->upload($locpath.$file, $rempath.$file, $mode);
				}
			}
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Extract the file extension
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _getExt($filename)
	{
		if (FALSE === strpos($filename, '.')) {
			return 'txt';
		}

		$x = explode('.', $filename);
		return end($x);
	}

	/**
	 * Set the upload type
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _setType($ext)
	{
		$text_types = array(
							'txt',
							'text',
							'php',
							'phps',
							'php4',
							'js',
							'css',
							'htm',
							'html',
							'phtml',
							'shtml',
							'log',
							'xml'
							);


		return (in_array($ext, $text_types)) ? 'ascii' : 'binary';
	}

	/**
	 * Close the connection
	 *
	 * @access	public
	 * @param	string	path to source
	 * @param	string	path to destination
	 * @return	bool
	 */
	public function close()
	{
		if (!$this->_isConn()) {
			return FALSE;
		}

		@ftp_close($this->connId);
        return TRUE;
	}

	/**
	 * Display error message
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	private function _error($line)
    {
		Fly::app()->Lang->load('ftp');
		throw new FlyException(Fly::app()->Lang->line($line));
	}


}