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
 * Output Class
 *
 * Responsible for sending final output to browser
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Output
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/output.html
 */
class Output
{

	/**
	 * Current output string
	 *
	 * @var string
	 * @access 	protected
	 */
	protected $finalOutput;
	/**
	 * Cache expiration time
	 *
	 * @var int
	 * @access 	protected
	 */
	protected $cacheExpiration	= 0;
	/**
	 * List of server headers
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $headers			= array();
	/**
	 * List of mime types
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $mimeTypes		= array();
	/**
	 * Determines wether profiler is enabled
	 *
	 * @var book
	 * @access 	protected
	 */
	protected $enableProfiler	= FALSE;
	/**
	 * Determines if output compression is enabled
	 *
	 * @var bool
	 * @access 	protected
	 */
	protected $_zlibOc			= FALSE;
	/**
	 * List of profiler sections
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $_profilerSections = array();
	/**
	 * Whether or not to parse variables like {elapsed_time} and {memory_usage}
	 *
	 * @var bool
	 * @access 	protected
	 */
	protected $parseExecVars	= TRUE;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->_zlibOc = @ini_get('zlib.output_compression');

        Fly::loadConfig('config.mimes', true, true);
		$this->mimeTypes = Fly::getConfig('mimes');

		Fly::log('debug', "Output Class Initialized");
	}

	/**
	 * Get Output
	 *
	 * Returns the current output string
	 *
	 * @access	public
	 * @return	string
	 */
	public function getOutput()
	{
		return $this->finalOutput;
	}

	/**
	 * Set Output
	 *
	 * Sets the output string
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setOutput($output)
	{
		$this->finalOutput = $output;
		return $this;
	}

	/**
	 * Append Output
	 *
	 * Appends data onto the output string
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function appendOutput($output)
	{
		if ($this->finalOutput == '') {
			$this->finalOutput = $output;
		} else {
			$this->finalOutput .= $output;
		}
		return $this;
	}

	/**
	 * Set Header
	 *
	 * Lets you set a server header which will be outputted with the final display.
	 *
	 * Note:  If a file is cached, headers will not be sent.  We need to figure out
	 * how to permit header data to be saved with the cache data...
	 *
	 * @access	public
	 * @param	string
	 * @param 	bool
	 * @return	void
	 */
	public function setHeader($header, $replace = TRUE)
	{
		// If zlib.output_compression is enabled it will compress the output,
		// but it will not modify the content-length header to compensate for
		// the reduction, causing the browser to hang waiting for more data.
		// We'll just skip content-length in those cases.

		if ($this->_zlibOc && strncasecmp($header, 'content-length', 14) == 0) {
			return;
		}
		$this->headers[] = array($header, $replace);
		return $this;
	}

	/**
	 * Set Content Type Header
	 *
	 * @access	public
	 * @param	string	extension of the file we're outputting
	 * @return	void
	 */
	public function setContentType($mime_type)
	{
		if (strpos($mime_type, '/') === false) {
			$extension = ltrim($mime_type, '.');

			// Is this extension supported?
			if (isset($this->mimeTypes[$extension])) {
				$mime_type =& $this->mimeTypes[$extension];

				if (is_array($mime_type)) {
					$mime_type = current($mime_type);
				}
			}
		}

		$header = 'Content-Type: '.$mime_type;

		$this->headers[] = array($header, TRUE);

		return $this;
	}

	/**
	 * Set HTTP Status Header
	 * moved to Common procedural functions in 1.7.2
	 *
	 * @access	public
	 * @param	int		the status code
	 * @param	string
	 * @return	void
	 */
	public function setStatusHeader($code = 200, $text = '')
	{
        $stati = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            118 => 'Connection timed out',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            210 => 'Content Different',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            310 => 'Too many Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I’m a teapot',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway ou Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            507 => 'Insufficient storage',
            509 => 'Bandwidth Limit Exceeded',
        );



        if ($code == '' || !is_numeric($code)) {
            throw new HttpException(500, Fly::t('fly', 'Status codes must be numeric'));
        }

        if (isset($stati[$code]) && $text == '') {
            $text = $stati[$code];
        }

        if ($text == '') {
            throw new HttpException(500, Fly::t('fly', 'No status text available.  Please check your status code number or supply your own message text.'));
        }

        $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

        if (substr(php_sapi_name(), 0, 3) == 'cgi') {
            header("Status: {$code} {$text}", TRUE);
        } else if ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0') {
            header($server_protocol." {$code} {$text}", TRUE, $code);
        } else {
            header("HTTP/1.1 {$code} {$text}", TRUE, $code);
        }

		return $this;
	}

	/**
	 * Enable/disable Profiler
	 *
	 * @access	public
	 * @param	bool
	 * @return	void
	 */
	public function setEnableProfiler($val = true)
	{
		$this->enableProfiler = (is_bool($val)) ? $val : true;

		return $this;
	}

	/**
	 * Set Profiler Sections
	 *
	 * Allows override of default / config settings for Profiler section display
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function setProfilerSections($sections)
	{
		foreach ($sections as $section => $enable) {
			$this->_profilerSections[$section] = ($enable !== false) ? true : false;
		}
		return $this;
	}

	/**
	 * Set Cache
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	public function cache($time)
	{
		$this->cacheExpiration = (!is_numeric($time)) ? 0 : $time;

		return $this;
	}

	/**
	 * Display Output
	 *
	 * All "view" data is automatically put into this variable by the controller class:
	 *
	 * $this->finalOutput
	 *
	 * This function sends the finalized output data to the browser along
	 * with any server headers and profile data.  It also stops the
	 * benchmark timer so the page rendering speed and memory usage can be shown.
	 *
	 * @access	public
	 * @param 	string
	 * @return	mixed
	 */
	public function display($output = '')
	{

		// Set the output data
		if ($output == '') {
			$output =& $this->finalOutput;
		}

        if ($output == '' || strlen($output) < 1) {
            return false;
        }

		// --------------------------------------------------------------------

		// Do we need to write a cache file?  Only if the controller does not have its
		// own _output() method and we are not dealing with a cache file, which we
        $controller = Fly::app()->Controller;
		if ($this->cacheExpiration > 0 && $controller != null && ! method_exists($controller, '_output')) {
			$this->writeCache($output);
		}

		// --------------------------------------------------------------------

		// Parse out the elapsed time and memory usage,
		// then swap the pseudo-variables with the data

		$elapsed = Fly::app()->Benchmark->elapsedTime('APPLICATION_START', 'APPLICATION_END');

		if ($this->parseExecVars === true) {
			$memory	 = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';
			$output = str_replace('{elapsed_time}', $elapsed, $output);
			$output = str_replace('{memory_usage}', $memory, $output);
		}

		// --------------------------------------------------------------------

		// Is compression requested?
		if (Fly::getConfig('compress_output') === true && $this->_zlibOc == false) {
			if (extension_loaded('zlib')) {
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
					ob_start('ob_gzhandler');
				}
			}
		}

		// --------------------------------------------------------------------

		// Are there any server headers to send?
		if (count($this->headers) > 0) {
			foreach ($this->headers as $header) {
				@header($header[0], $header[1]);
			}
		}

		// --------------------------------------------------------------------

		// Does the $CI object exist?
		// If not we know we are dealing with a cache file so we'll
		// simply echo out the data and exit.
		if ($controller == null)
		{
			echo $output;
			Fly::log('debug', "Final output sent to browser");
			Fly::log('debug', "Total execution time: ".$elapsed);
			return true;
		}

		// --------------------------------------------------------------------

		// Do we need to generate profile data?
		// If so, load the Profile class and run it.
		if ($this->enableProfiler == true)
		{
            $profiler = Fly::app()->getComponent('Profiler');
			if (!empty($this->_profilerSections)) {
				$profiler->setSections($this->_profilerSections);
			}

			// If the output data contains closing </body> and </html> tags
			// we will remove them and add them back after we insert the profile data
			if (preg_match("|</body>.*?</html>|is", $output)) {
				$output  = preg_replace("|</body>.*?</html>|is", '', $output);
				$output .= $profiler->run();
				$output .= '</body></html>';
			} else {
				$output .= $profiler->run();
			}
		}

		// --------------------------------------------------------------------

		// Does the controller contain a function named _output()?
		// If so send the output there.  Otherwise, echo it.
		if (method_exists($controller, '_output')) {
            $controller->_output($output);
		} else {
			echo $output;  // Send it to the browser!
		}

		Fly::log('debug', "Final output sent to browser");
		Fly::log('debug', "Total execution time: ".$elapsed);
	}

	/**
	 * Write a Cache File
	 *
	 * @access	public
	 * @param 	string
	 * @return	void
	 */
	public function writeCache($output)
	{
		$path = Fly::getConfig('cache_path');

		$cache_path = ($path == '') ? Fly::app()->getBasePath().'/cache/' : $path;

		if (!is_dir($cache_path) || !FileHelper::isReallyWritable($cache_path)) {
			Fly::log('error', "Unable to write cache file: ".$cache_path);
			return;
		}

        $uri = Fly::app()->Request->getUrl();

		$cache_path .= md5($uri);

		if (!$fp = @fopen($cache_path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
			Fly::log('error', "Unable to write cache file: ".$cache_path);
			return;
		}

		$expire = time() + ($this->cacheExpiration * 60);

		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $expire.'TS--->'.$output);
			flock($fp, LOCK_UN);
		} else {
			Fly::log('error', "Unable to secure a file lock for file at: ".$cache_path);
			return;
		}
		fclose($fp);
		@chmod($cache_path, FILE_WRITE_MODE);

		Fly::log('debug', "Cache file written: ".$cache_path);
	}

	/**
	 * Update/serve a cached file
	 *
	 * @access	public
	 * @param 	object	config class
	 * @param 	object	uri class
	 * @return	void
	 */
	public function displayCache()
	{
		$cache_path = (Fly::getConfig('cache_path') == '') ? Fly::app()->getBasePath().'/cache/' : Fly::getConfig('cache_path');

		// Build the file path.  The file name is an MD5 hash of the full URI
		$uri = Fly::app()->Request->getUrl();

		$filepath = $cache_path.md5($uri);

		if (!@file_exists($filepath)) {
			return false;
		}

		if (!$fp = @fopen($filepath, FOPEN_READ)) {
			return false;
		}

		flock($fp, LOCK_SH);

		$cache = '';
		if (filesize($filepath) > 0) {
			$cache = fread($fp, filesize($filepath));
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		// Strip out the embedded timestamp
		if (!preg_match("/(\d+TS--->)/", $cache, $match)) {
			return false;
		}

		// Has the file expired? If so we'll delete it.
		if (time() >= trim(str_replace('TS--->', '', $match['1']))) {
			if (FileHelper::isReallyWritable($cache_path)) {
				@unlink($filepath);
				Fly::log('debug', "Cache file has expired. File deleted");
				return false;
			}
		}

		// Display the cache
		$this->display(str_replace($match['0'], '', $cache));
		Fly::log('debug', "Cache file is current. Sending it to browser.");
		return true;
	}


}