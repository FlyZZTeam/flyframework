<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Responsible for sending final output to browser
 */
class Output extends Component
{

    /**
     * @var string Current output string.
     */
    protected $finalOutput;
    /**
     * @var int Cache expiration time.
     */
    protected $cacheExpiration = 0;
    /**
     * @var array List of server headers.
     */
    protected $headers = array();
    /**
     * @var array List of mime types
     */
    protected $mimeTypes = array();
    /**
     * @var bool Determines wether profiler is enabled
     */
    protected $enableProfiler = false;
    /**
     * @var bool Determines if output compression is enabled
     */
    protected $_zlibOc = false;
    /**
     * @var array List of profiler sections
     */
    protected $_profilerSections = array();
    /**
     * @var bool Whether or not to parse variables like {elapsed_time} and {memory_usage}
     */
    protected $parseExecVars = true;
    /**
     * Output Compression
     * Enables Gzip output compression for faster page loads.  When enabled,
     * the output class will test whether your server supports Gzip.
     * Even if it does, however, not all browsers support compression
     * so enable only if you are reasonably sure your visitors can handle it.
     *
     * VERY IMPORTANT:  If you are getting a blank page when compression is enabled it
     * means you are prematurely outputting something to your browser. It could
     * even be a line of whitespace at the end of one of your scripts.  For
     * compression to work, nothing can be sent before the output buffer is called
     * by the output class.  Do not 'echo' any values with compression enabled.
     */
    public $compressOutput = false;
    /**
     * @var string The output cache path.
     */
    public $cachePath = '';


    /**
     * Constructor
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
     * Returns the current output string
     * @return string The output data.
     */
    public function getOutput()
    {
        return $this->finalOutput;
    }

    /**
     * Sets the output string
     * @param string $output
     * @return Output
     */
    public function setOutput($output)
    {
        $this->finalOutput = $output;
        return $this;
    }

    /**
     * Appends data onto the output string
     * @param string $output
     * @return Output
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
     * Lets you set a server header which will be outputted with the final display.
     * Note:  If a file is cached, headers will not be sent.  We need to figure out
     * how to permit header data to be saved with the cache data...
     * @param string $header The header string.
     * @param bool $replace The replace tag.
     * @return Output
     */
    public function setHeader($header, $replace = true)
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
     * @param string $mime_type extension of the file we're outputting
     * @return Output
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

        $this->headers[] = array($header, true);

        return $this;
    }

    /**
     * Set HTTP Status Header
     * @param int $code The status code
     * @param string $text The http status message.
     * @return Output
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

        $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;

        if (substr(php_sapi_name(), 0, 3) == 'cgi') {
            header("Status: {$code} {$text}", true);
        } else if ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0') {
            header($server_protocol." {$code} {$text}", true, $code);
        } else {
            header("HTTP/1.1 {$code} {$text}", true, $code);
        }

        return $this;
    }

    /**
     * Enable/disable Profiler
     * @param bool $val Allow profiler.
     * @return Output
     */
    public function setEnableProfiler($val = true)
    {
        $this->enableProfiler = (is_bool($val)) ? $val : true;

        return $this;
    }

    /**
     * Set Profiler Sections
     * Allows override of default / config settings for Profiler section display
     * @param array $sections The allow profiler actions.
     * @return Output
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
     * @param integer $time The cache time.
     * @return Output
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
     * @param string $output The output string.
     * @return mixed
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
        if ($this->cacheExpiration > 0 && $controller != null && !method_exists($controller, '_output')) {
            $this->writeCache($output);
        }

        // --------------------------------------------------------------------

        // Parse out the elapsed time and memory usage,
        // then swap the pseudo-variables with the data

        $elapsed = Fly::app()->Benchmark->elapsedTime('APPLICATION_START', 'APPLICATION_END');

        if ($this->parseExecVars === true) {
            $memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2).'MB';
            $output = str_replace('{elapsed_time}', $elapsed, $output);
            $output = str_replace('{memory_usage}', $memory, $output);
        }

        // --------------------------------------------------------------------

        // Is compression requested?
        if ($this->compressOutput === true && $this->_zlibOc == false) {
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
        if ($controller == null) {
            echo $output;
            Fly::log('debug', "Final output sent to browser");
            Fly::log('debug', "Total execution time: ".$elapsed);
            return true;
        }

        // --------------------------------------------------------------------

        // Do we need to generate profile data?
        // If so, load the Profile class and run it.
        if ($this->enableProfiler == true) {
            $profiler = Fly::app()->getComponent('Profiler');
            if (!empty($this->_profilerSections)) {
                $profiler->setSections($this->_profilerSections);
            }

            // If the output data contains closing </body> and </html> tags
            // we will remove them and add them back after we insert the profile data
            if (preg_match("|</body>.*?</html>|is", $output)) {
                $output = preg_replace("|</body>.*?</html>|is", '', $output);
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
            echo $output; // Send it to the browser!
        }

        Fly::log('debug', "Final output sent to browser");
        Fly::log('debug', "Total execution time: ".$elapsed);
    }

    /**
     * Write a Cache File
     * @param string $output The cache data.
     * @return void
     */
    public function writeCache($output)
    {
        $path = $this->cachePath;

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
     * @return bool
     */
    public function displayCache()
    {

        $cache_path = ($this->cachePath == '') ? Fly::app()->getBasePath().'/cache/' : $this->cachePath;

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