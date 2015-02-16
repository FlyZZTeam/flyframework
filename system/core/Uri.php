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
 * URI Class
 *
 * Parses URIs and determines routing
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	URI
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/uri.html
 */
class Uri extends Component
{
    const URL_FORMAT_GET = 'get';
    const URL_FORMAT_PATH = 'path';

    /**
     * @var string the URL suffix used when in 'path' format.
     * For example, ".html" can be used so that the URL looks like pointing to a static HTML page. Defaults to empty.
     */
    public $urlSuffix = '';

    /**
     * @var boolean whether to show entry script name in the constructed URL. Defaults to true.
     */
    public $showScriptName = true;

    public $appendParams = true;


    /**
	 * List of cached uri segments
	 *
	 * @var array
	 * @access public
	 */
	var	$keyval			= array();
	/**
	 * Current uri string
	 *
	 * @var string
	 * @access public
	 */
	var $uri_string;
	/**
	 * List of uri segments
	 *
	 * @var array
	 * @access public
	 */
	private $segments		= array();
	/**
	 * Re-indexed list of uri segments
	 * Starts at 1 instead of 0
	 *
	 * @var array
	 * @access public
	 */
	private $rsegments		= array();

    private $_urlFormat = self::URL_FORMAT_PATH;
    private $_baseUrl;

	/**
	 * Constructor
	 *
	 * Simply globalizes the $RTR object.  The front
	 * loads the Router class early on so it's not available
	 * normally as other classes are.
	 *
	 * @access	public
	 */
	public function __construct()
	{
        $enableQueryStrings = Fly::app()->getConfig('enable_query_strings');
        if ($enableQueryStrings) {
            $this->_urlFormat = self::URL_FORMAT_GET;
        }
        Fly::log('debug', "URI Class Initialized");
	}


	// --------------------------------------------------------------------

	/**
	 * Get the URI String
	 * Modify:_fetch_uri_string -> fetchUriString
     *
	 * @access	private
	 * @return	string
	 */
	public function fetchUriString()
	{
		if (strtoupper(Fly::app()->getConfig('uri_protocol')) == 'AUTO') {
			// Is the request coming from the command line?
			if (php_sapi_name() == 'cli' || defined('STDIN')) {
				$this->setUriString($this->parseCliArgs());
				return;
			}

			// Let's try the REQUEST_URI first, this will work in most situations
			if ($uri = $this->detectUri()) {
				$this->setUriString($uri);
				return;
			}

			// Is there a PATH_INFO variable?
			// Note: some servers seem to have trouble with getenv() so we'll test it two ways
			$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
            if (trim($path, '/') != '' && $path != "/".SELF) {
				$this->setUriString($path);
				return;
			}

			// No PATH_INFO?... What about QUERY_STRING?
			$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
			if (trim($path, '/') != '') {
				$this->setUriString($path);
				return;
			}

			// As a last ditch effort lets try using the $_GET array
			if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '') {
				$this->setUriString(key($_GET));
				return;
			}

			// We've exhausted all our options...
			$this->uri_string = '';
			return;
		}

		$uri = strtoupper(Fly::app()->getConfig('uri_protocol'));

		if ($uri == 'REQUEST_URI') {
			$this->setUriString($this->detectUri());
			return;
		} else if ($uri == 'CLI') {
			$this->setUriString($this->parseCliArgs());
			return;
		}

		$path = (isset($_SERVER[$uri])) ? $_SERVER[$uri] : @getenv($uri);
		$this->setUriString($path);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the URI String
	 * Modify:_set_uri_string -> setUriString
     *
	 * @access	public
	 * @param 	string
	 * @return	string
	 */
	public function setUriString($str)
	{
		// Filter out control characters
		$str = StringHelper::removeInvisibleCharacters($str, FALSE);

		// If the URI contains only a slash we'll kill it
		$this->uri_string = ($str == '/') ? '' : $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Detects the URI
	 *
	 * This function will detect the URI automatically and fix the query string
	 * if necessary.
     *
     * Modify:_detect_uri -> detectUri
	 *
	 * @access	private
	 * @return	string
	 */
	private function detectUri()
	{
		if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME'])) {
			return '';
		}

		$uri = $_SERVER['REQUEST_URI'];
		if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
			$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		} else if (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
			$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		}

		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING server var and $_GET array.
		if (strncmp($uri, '?/', 2) === 0) {
			$uri = substr($uri, 2);
		}
		$parts = preg_split('#\?#i', $uri, 2);
		$uri = $parts[0];
		if (isset($parts[1])) {
			$_SERVER['QUERY_STRING'] = $parts[1];
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		} else {
			$_SERVER['QUERY_STRING'] = '';
			$_GET = array();
		}

		if ($uri == '/' || empty($uri)) {
			return '/';
		}

		$uri = parse_url($uri, PHP_URL_PATH);

		// Do some final cleaning of the URI and return it
		return str_replace(array('//', '../'), '/', trim($uri, '/'));
	}

	// --------------------------------------------------------------------

	/**
	 * Parse cli arguments
	 *
	 * Take each command line argument and assume it is a URI segment.
	 * Modfiy:_parse_cli_args -> parseCliArgs
     *
	 * @access	private
	 * @return	string
	 */
	private function parseCliArgs()
	{
		$args = array_slice($_SERVER['argv'], 1);

		return $args ? '/' . implode('/', $args) : '';
	}

	// --------------------------------------------------------------------

	/**
	 * Filter segments for malicious characters
	 *
     * Modify:_filter_uri -> filterUri
     *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function filterUri($str)
	{
		if ($str != '' && Fly::app()->getConfig('permitted_uri_chars') != '' &&
            Fly::app()->getConfig('enable_query_strings') == false) {
			// preg_quote() in PHP 5.3 escapes -, so the str_replace() and addition of - to preg_quote() is to maintain backwards
			// compatibility as many are unaware of how characters in the permitted_uri_chars will be parsed as a regex pattern

            if (!preg_match("|^[".str_replace(array('\\-', '\-'), '-', preg_quote(Fly::app()->getConfig('permitted_uri_chars'), '-'))."]+$|i", $str)) {
                throw new HttpException(400, Fly::t('fly', 'The URI you submitted has disallowed characters.'));
			}
		}

		// Convert programatic characters to entities
		$bad	= array('$',		'(',		')',		'%28',		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');

		return str_replace($bad, $good, $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove the suffix from the URL if needed
     *
	 * Modify:_remove_url_suffix -> removeUrlSuffix
     *
	 * @access	public
	 * @return	void
	 */
	public function removeUrlSuffix()
	{
        $suffix = $this->urlSuffix;//Fly::app()->getConfig('url_suffix');
		if  ($suffix != "") {
			$this->uri_string = preg_replace("|".preg_quote($suffix)."$|", "", $this->uri_string);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Explode the URI Segments. The individual segments will
	 * be stored in the $this->segments array.
     *
     * Modify:_explode_segments -> explodeSegments
	 *
	 * @access	public
	 * @return	void
	 */
	public function explodeSegments()
	{
		foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val)
		{
			// Filter segments for security
			$val = trim($this->filterUri($val));

			if ($val != '') {
				$this->segments[] = $val;
			}
		}
	}

	// --------------------------------------------------------------------
	/**
	 * Re-index Segments
	 *
	 * This function re-indexes the $this->segment array so that it
	 * starts at 1 rather than 0.  Doing so makes it simpler to
	 * use functions like $this->uri->segment(n) since there is
	 * a 1:1 relationship between the segment array and the actual segments.
     *
     * Modify:reindex_segments -> reindexSegments
	 *
	 * @access	public
	 * @return	void
	 */
	public function reindexSegments()
	{
		array_unshift($this->segments, null);
		array_unshift($this->rsegments, null);
		unset($this->segments[0]);
		unset($this->rsegments[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a URI Segment
	 *
	 * This function returns the URI segment based on the number provided.
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	public function segment($n, $no_result = false)
	{
		return (!isset($this->segments[$n])) ? $no_result : $this->segments[$n];
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a URI "routed" Segment
	 *
	 * This function returns the re-routed URI segment (assuming routing rules are used)
	 * based on the number provided.  If there is no routing this function returns the
	 * same result as $this->segment()
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	public function rsegment($n, $no_result = false)
	{
		return (!isset($this->rsegments[$n])) ? $no_result : $this->rsegments[$n];
	}

	// --------------------------------------------------------------------

	/**
	 * Generate a key value pair from the URI string
	 *
	 * This function generates and associative array of URI data starting
	 * at the supplied segment. For example, if this is your URI:
	 *
	 *	example.com/user/search/name/joe/location/UK/gender/male
	 *
	 * You can use this function to generate an array with this prototype:
	 *
	 * array (
	 *			name => joe
	 *			location => UK
	 *			gender => male
	 *		 )
     *
     * Modify:uri_to_assoc -> uriToAssoc
	 *
	 * @access	public
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @return	array
	 */
	public function uriToAssoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'segment');
	}
	/**
	 * Identical to above only it uses the re-routed segment array
     *
     * Modify:ruri_to_assoc -> ruriToAssoc
	 *
	 * @access 	public
	 * @param 	integer	the starting segment number
	 * @param 	array	an array of default values
	 * @return 	array
	 *
	 */
	public function ruriToAssoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'rsegment');
	}

	// --------------------------------------------------------------------

	/**
	 * Generate a key value pair from the URI string or Re-routed URI string
	 *
	 * @access	private
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @param	string	which array we should use
	 * @return	array
	 */
	private function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
	{
		if ($which == 'segment') {
			$total_segments = 'countSegments';
			$segment_array = 'getSegments';
		} else {
			$total_segments = 'countRsegments';
			$segment_array = 'getRsegments';
		}

		if (!is_numeric($n)) {
			return $default;
		}

		if (isset($this->keyval[$n])) {
			return $this->keyval[$n];
		}

		if ($this->$total_segments() < $n) {
			if (count($default) == 0) {
				return array();
			}

			$retval = array();
			foreach ($default as $val) {
				$retval[$val] = false;
			}
			return $retval;
		}

		$segments = array_slice($this->$segment_array(), ($n - 1));

        //key value
		$i = 0;
		$lastval = '';
		$retval  = array();
		foreach ($segments as $seg) {
			if ($i % 2) {
				$retval[$lastval] = $seg;
			} else {
				$retval[$seg] = false;
				$lastval = $seg;
			}
			$i++;
		}

		if (count($default) > 0) {
			foreach ($default as $val) {
				if (!array_key_exists($val, $retval)) {
					$retval[$val] = false;
				}
			}
		}

		// Cache the array for reuse
		$this->keyval[$n] = $retval;
		return $retval;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate a URI string from an associative array
	 *
     * Modify:assoc_to_uri -> assocToUri
	 *
	 * @access	public
	 * @param	array	an associative array of key/values
	 * @return	array
	 */
	public function assocToUri($array)
	{
		$temp = array();
		foreach ((array)$array as $key => $val) {
			$temp[] = $key;
			$temp[] = $val;
		}

		return implode('/', $temp);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a URI Segment and add a trailing slash
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	public function slashSegment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'segment');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a URI Segment and add a trailing slash
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	public function slashRsegment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'rsegment');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a URI Segment and add a trailing slash - helper function
	 *
	 * @access	private
	 * @param	integer
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function _slash_segment($n, $where = 'trailing', $which = 'segment')
	{
		$leading	= '/';
		$trailing	= '/';

		if ($where == 'trailing') {
			$leading	= '';
		} else if ($where == 'leading') {
			$trailing	= '';
		}

		return $leading.$this->$which($n).$trailing;
	}

	// --------------------------------------------------------------------

	/**
	 * Segment Array
	 *
	 * @access	public
	 * @return	array
	 */
	public function getSegments()
	{
		return $this->segments;
	}

    /**
     * Set Segments
     *
     * @param array $s
     */
    public function setSegments($s = array())
    {
        if (!is_array($s)) {
            $s = array();
        }
        $this->segments = $s;
    }

	// --------------------------------------------------------------------

	/**
	 * Routed Segment Array
	 *
	 * @access	public
	 * @return	array
	 */
	public function getRsegments()
	{
		return $this->rsegments;
	}

    /**
     * Set Routed Segments
     *
     * @param array $s
     */
    public function setRsegments($s = array())
    {
        if (!is_array($s)) {
            $s = array();
        }
        $this->rsegments = $s;
    }

	// --------------------------------------------------------------------

	/**
	 * Total number of segments
	 *
	 * @access	public
	 * @return	integer
	 */
	public function countSegments()
	{
		return count($this->segments);
	}

	// --------------------------------------------------------------------

	/**
	 * Total number of routed segments
	 *
	 * @access	public
	 * @return	integer
	 */
	public function countRsegments()
	{
		return count($this->rsegments);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the entire URI string
	 *
	 * @access	public
	 * @return	string
	 */
	public function getUriString()
	{
		return $this->uri_string;
	}


	// --------------------------------------------------------------------

	/**
	 * Fetch the entire Re-routed URI string
	 *
	 * @access	public
	 * @return	string
	 */
	public function getRuriString()
	{
		return '/'.implode('/', $this->getRsegmentArray());
	}

    /**
     * Returns the base URL of the application.
     * @return string the base URL of the application (the part after host name and before query string).
     * If {@link showScriptName} is true, it will include the script name part.
     * Otherwise, it will not, and the ending slashes are stripped off.
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl !== null) {
            return $this->_baseUrl;
        } else {
            if ($this->showScriptName) {
                $this->_baseUrl = Fly::app()->Request->getScriptUrl();
            } else {
                $this->_baseUrl = Fly::app()->Request->getBaseUrl();
            }
            return $this->_baseUrl;
        }
    }

    /**
     * Sets the base URL of the application (the part after host name and before query string).
     * This method is provided in case the {@link baseUrl} cannot be determined automatically.
     * The ending slashes should be stripped off. And you are also responsible to remove the script name
     * if you set {@link showScriptName} to be false.
     * @param string $value the base URL of the application
     * @since 1.1.1
     */
    public function setBaseUrl($value)
    {
        $this->_baseUrl = $value;
    }

    /**
     * Returns the URL format.
     * @return string the URL format. Defaults to 'path'. Valid values include 'path' and 'get'.
     * Please refer to the guide for more details about the difference between these two formats.
     */
    public function getUrlFormat()
    {
        return $this->_urlFormat;
    }

    /**
     * Sets the URL format.
     * @param string $value the URL format. It must be either 'path' or 'get'.
     */
    public function setUrlFormat($value)
    {
        if ($value === self::URL_FORMAT_PATH || $value === self::URL_FORMAT_GET) {
            $this->_urlFormat = $value;
        } else {
            throw new FlyException(Fly::t('Fly','Uri.urlFormat must be either "path" or "get".'));
        }
    }

    /**
     * Constructs a URL.
     * @param string $route the controller and the action (e.g. article/read)
     * @param array $params list of GET parameters (name=>value). Both the name and value will be URL-encoded.
     * If the name is '#', the corresponding value will be treated as an anchor
     * and will be appended at the end of the URL.
     * @param string $ampersand the token separating name-value pairs in the URL. Defaults to '&'.
     * @return string the constructed URL
     */
    public function createUrl($route, $params = array(), $ampersand = '&')
    {
        foreach ($params as $i => $param) {
            if ($param === null) {
                $params[$i] = '';
            }
        }

        if (isset($params['#'])) {
            $anchor = '#'.$params['#'];
            unset($params['#']);
        } else {
            $anchor = '';
        }

        $route = trim($route,'/');
        return $this->createUrlDefault($route, $params, $ampersand).$anchor;
    }

    /**
     * Creates a URL based on default settings.
     * @param string $route the controller and the action (e.g. article/read)
     * @param array $params list of GET parameters
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    protected function createUrlDefault($route, $params, $ampersand)
    {

        if ($this->getUrlFormat() === self::URL_FORMAT_PATH) {
            $url = rtrim($this->getBaseUrl().'/'.$route,'/');
            if ($this->appendParams) {
                $url = rtrim($url.'/'.$this->createPathInfo($params, '/', '/'), '/');
                return $route === '' ? $url : $url.$this->urlSuffix;
            } else {
                if ($route !== '') {
                    $url .= $this->urlSuffix;
                }
                $query = $this->createPathInfo($params, '=', $ampersand);
                return $query === '' ? $url : $url.'?'.$query;
            }
        } else {
            $url = $this->getBaseUrl();
            if (!$this->showScriptName) {
                $url .= '/';
            }
            if ($route !== '') {
                $subRoutes = explode('/', $route);
                $count = count($subRoutes);
                $ct = Fly::app()->getConfig('alias_controller');
                $dt = Fly::app()->getConfig('alias_module');
                $ft = Fly::app()->getConfig('alias_action');
                $query = '';
                if ($count === 1) {
                    $query = $ct.'='.$subRoutes[0];
                } else if ($count === 2) {
                    $query = $ct.'='.$subRoutes[0].$ampersand.$ft.'='.$subRoutes[1];
                } else {
                    $query = $dt.'='.$subRoutes[0].$ampersand.$ct.'='.$subRoutes[1].$ampersand.$ft.'='.$subRoutes[2];
                }
                $url .= '?'.$query;
                if(($query = $this->createPathInfo($params, '=', $ampersand)) !== '') {
                    $url .= $ampersand.$query;
                }
            } else if(($query = $this->createPathInfo($params, '=', $ampersand)) !== '') {
                $url .= '?'.$query;
            }
            return $url;
        }
    }

    /**
     * Creates a path info based on the given parameters.
     * @param array $params list of GET parameters
     * @param string $equal the separator between name and value
     * @param string $ampersand the separator between name-value pairs
     * @param string $key this is used internally.
     * @return string the created path info
     */
    public function createPathInfo($params, $equal, $ampersand, $key = null)
    {
        $pairs = array();
        foreach ($params as $k => $v) {
            if ($key !== null) {
                $k = $key.'['.$k.']';
            }

            if (is_array($v)) {
                $pairs[] = $this->createPathInfo($v, $equal, $ampersand, $k);
            } else {
                $pairs[] = urlencode($k).$equal.urlencode($v);
            }
        }
        return implode($ampersand, $pairs);
    }

}
// END URI Class

/* End of file Uri.php */
/* Location: ./system/core/Uri.php */