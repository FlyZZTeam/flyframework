<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 *  HttpRequest encapsulates the $_SERVER variable and resolves its inconsistency among different Web servers.
 */
class HttpRequest extends Component
{

    /**
     * @var string IP address of the current user
     */
    var $ip_address = false;
    /**
     * @var string user agent (web browser) being used by the current user
     */
    var $user_agent = false;
    /**
     * @var bool If FALSE, then $_GET will be set to an empty array
     */
    public $allowGetArray = true;
    /**
     * @var bool If TRUE, then newlines are standardized
     */
    var $_standardize_newlines = true;
    /**
     * @var bool Determines whether the XSS filter is always active when GET, POST or COOKIE data is encountered
     * Set automatically based on config setting
     */
    var $enableXssFilter = false;
    /**
     * @var bool Enables a CSRF cookie token to be set.
     * Set automatically based on config setting
     */
    var $_enable_csrf = false;
    /**
     * @var string Set a prefix if you need to avoid collisions.
     */
    public $cookiePrefix = "";
    /**
     * @var string Set to .your-domain.com for site-wide cookies.
     */
    public $cookieDomain = "";
    /**
     * @var string Typically will be a forward slash.
     */
    public $cookiePath = "/";
    /**
     * @var bool Cookies will only be set if a secure HTTPS connection exists.
     */
    public $cookieSecure = false;
    /**
     * @var int Cookie time expired.
     */
    public $cookieExpire = 7200;
    /**
     * If your server is behind a reverse proxy, you must whitelist the proxy IP
     * addresses from which CodeIgniter should trust the HTTP_X_FORWARDED_FOR
     * header in order to properly identify the visitor's IP address.
     * Comma-delimited, e.g. '10.0.1.200,10.0.1.201'
     */
    public $proxyIps = '';
    /**
     * @var array List of all HTTP request headers
     */
    protected $headers = array();

    private $security;

    /**
     * @var int Custom port
     */
    private $_port;

    /**
     * @var string Schema and hostname part (with port number if needed) of the request URL
     */
    private $_hostUrl;

    private $_requestUri;
    private $_pathInfo;
    private $_scriptUrl;
    private $_restParams;
    private $_baseUrl;

    /**
     * Constructor
     *
     * Sets whether to globally enable the XSS processing
     * and whether to allow the $_GET array
     *
     * @return void
     */
    public function __construct()
    {
        Fly::log('debug', "HttpRequest Class Initialized");

        $this->_enable_csrf = (Fly::getConfig('enableCsrfProtection') === true);

        $this->security = FLY::app()->Security;

        // Sanitize global arrays
        $this->_sanitize_globals();
    }

    /**
     * Fetch from array
     *
     * This is a helper function to retrieve values from global arrays
     *
     * @param array
     * @param string
     * @param bool
     * @return string
     */
    private function _fetch_from_array(&$array, $index = '', $xss_clean = false)
    {
        if (!isset($array[$index])) {
            return false;
        }

        if ($xss_clean === true) {
            return $this->security->cleanXss($array[$index]);
        }

        return $array[$index];
    }

    /**
     * Fetch an item from the GET array
     *
     * @param string
     * @param bool
     * @return string
     */
    public function get($index = null, $default = null, $xss_clean = false)
    {
        // Check if a field has been provided
        if ($index === null AND !empty($_GET)) {
            $get = array();

            // loop through the full _GET array
            foreach (array_keys($_GET) as $key) {
                $get[$key] = $this->_fetch_from_array($_GET, $key, $xss_clean);
            }
            return $get;
        }
        $data = $this->_fetch_from_array($_GET, $index, $xss_clean);
        if ($data === false) {
            $data = $default;
        }
        return $data;
    }

    /**
     * Fetch an item from the POST array
     *
     * @param string
     * @param bool
     * @return string
     */
    public function post($index = null, $default = null, $xss_clean = false)
    {
        // Check if a field has been provided
        if ($index === null AND !empty($_POST)) {
            $post = array();

            // Loop through the full _POST array and return it
            foreach (array_keys($_POST) as $key) {
                $post[$key] = $this->_fetch_from_array($_POST, $key, $xss_clean);
            }
            return $post;
        }

        $data = $this->_fetch_from_array($_POST, $index, $xss_clean);
        if ($data === false) {
            $data = $default;
        }
        return $data;
    }

    /**
     * Fetch an item from either the GET array or the POST
     *
     * @param string $index The index key
     * @param bool $xss_clean XSS cleaning
     * @return string
     */
    public function getParam($index = '', $default = null, $xss_clean = false)
    {
        if (!isset($_POST[$index])) {
            return $this->get($index, $default, $xss_clean);
        } else {
            return $this->post($index, $default, $xss_clean);
        }
    }

    /**
     * Returns the named DELETE parameter value.
     * @param string $name the DELETE parameter name
     * @param mixed $defaultValue the default parameter value if the DELETE parameter does not exist.
     * @return mixed the DELETE parameter value
     */
    public function getDelete($name, $defaultValue = null, $xss_clean = false)
    {
        if ($this->isDeleteViaPostRequest()) {
            return $this->post($name, $defaultValue, $xss_clean);
        }

        if ($this->isDeleteRequest()) {
            $this->getRestParams();
            return isset($this->_restParams[$name]) ? $this->_restParams[$name] : $defaultValue;
        } else {
            return $defaultValue;
        }
    }

    /**
     * Returns the named PUT parameter value.
     * @param string $name the PUT parameter name
     * @param mixed $defaultValue the default parameter value if the PUT parameter does not exist.
     * @return mixed the PUT parameter value
     */
    public function getPut($name, $defaultValue = null, $xss_clean = false)
    {
        if ($this->isPutViaPostRequest()) {
            return $this->post($name, $defaultValue, $xss_clean);
        }

        if ($this->isPutRequest()) {
            $this->getRestParams();
            return isset($this->_restParams[$name]) ? $this->_restParams[$name] : $defaultValue;
        } else {
            return $defaultValue;
        }
    }

    /**
     * Returns request parameters. Typically PUT or DELETE.
     * @return array the request parameters
     */
    public function getRestParams()
    {
        if ($this->_restParams === null) {
            $result = array();
            if (function_exists('mb_parse_str')) {
                mb_parse_str($this->getRawBody(), $result);
            } else {
                parse_str($this->getRawBody(), $result);
            }
            $this->_restParams = $result;
        }
        return $this->_restParams;
    }

    /**
     * Returns the raw HTTP request body.
     * @return string the request body
     */
    public function getRawBody()
    {
        static $rawBody;
        if ($rawBody === null) {
            $rawBody = file_get_contents('php://input');
        }
        return $rawBody;
    }

    /**
     * Returns the currently requested URL.
     * This is the same as {@link getRequestUri}.
     * @return string part of the request URL after the host info.
     */
    public function getUrl()
    {
        return $this->getRequestUri();
    }

    /**
     * Fetch an item from the COOKIE array
     *
     * @param string
     * @param bool
     * @return string
     */
    function getCookie($index = '', $xss_clean = false)
    {
        return $this->_fetch_from_array($_COOKIE, $index, $xss_clean);
    }

    /**
     * Set cookie
     *
     * Accepts six parameter, or you can submit an associative
     * array in the first parameter containing all the values.
     *
     * @param mixed
     * @param string $value the value of the cookie
     * @param string $expire the number of seconds until expiration
     * @param string $domain the cookie domain.  Usually:  .yourdomain.com
     * @param string $path the cookie path
     * @param string $prefix the cookie prefix
     * @param bool $secure true makes the cookie secure
     * @return void
     */
    function setCookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false)
    {
        if (is_array($name)) {
            // always leave 'name' in last place, as the loop will break otherwise, due to $$item
            foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'name') as $item) {
                if (isset($name[$item])) {
                    $$item = $name[$item];
                }
            }
        }

        if ($prefix == '' AND $this->cookiePrefix != '') {
            $prefix = $this->cookiePrefix;
        }
        if ($domain == '' AND $this->cookieDomain != '') {
            $domain = $this->cookieDomain;
        }
        if ($path == '/' AND $this->cookiePath != '/') {
            $path = $this->cookiePath;
        }
        if ($secure == false AND $this->cookieSecure != false) {
            $secure = $this->cookieSecure;
        }

        if (!is_numeric($expire)) {
            if ($this->cookieExpire >= 0) {
                $expire = $this->cookieExpire;
            } else {
                $expire = time() - 86500;
            }
        } else {
            if ($expire >= 0) {
                $expire = time() + $expire;
            } else {
                $expire = time() - 86500;
            }
        }

        setcookie($prefix.$name, $value, $expire, $path, $domain, $secure);
    }

    /**
     * Fetch an item from the SERVER array
     *
     * @param string
     * @param bool
     * @return string
     */
    function getServer($index = '', $xss_clean = false)
    {
        return $this->_fetch_from_array($_SERVER, $index, $xss_clean);
    }

    /**
     * Fetch the IP Address
     *
     * @return string
     */
    public function getIpAddress()
    {
        if ($this->ip_address !== false) {
            return $this->ip_address;
        }

        $proxy_ips = $this->proxyIps;
        if (!empty($proxy_ips)) {
            $proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
            foreach (array(
                         'HTTP_X_FORWARDED_FOR',
                         'HTTP_CLIENT_IP',
                         'HTTP_X_CLIENT_IP',
                         'HTTP_X_CLUSTER_CLIENT_IP'
                     ) as $header) {
                if (($spoof = $this->getServer($header)) !== false) {
                    // Some proxies typically list the whole chain of IP
                    // addresses through which the client has reached us.
                    // e.g. client_ip, proxy_ip1, proxy_ip2, etc.
                    if (strpos($spoof, ',') !== false) {
                        $spoof = explode(',', $spoof, 2);
                        $spoof = $spoof[0];
                    }

                    if (!$this->validIp($spoof)) {
                        $spoof = false;
                    } else {
                        break;
                    }
                }
            }

            $this->ip_address = ($spoof !== false && in_array($_SERVER['REMOTE_ADDR'], $proxy_ips, true))
                ? $spoof : $_SERVER['REMOTE_ADDR'];
        } else {
            $this->ip_address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$this->validIp($this->ip_address)) {
            $this->ip_address = '0.0.0.0';
        }

        return $this->ip_address;
    }

    /**
     * Validate IP Address
     *
     * @param string
     * @param string $which ipv4 or ipv6
     * @return bool
     */
    public function validIp($ip, $which = '')
    {
        $which = strtolower($which);

        // First check if filter_var is available
        if (is_callable('filter_var')) {
            switch ($which) {
                case 'ipv4':
                    $flag = FILTER_FLAG_IPV4;
                    break;
                case 'ipv6':
                    $flag = FILTER_FLAG_IPV6;
                    break;
                default:
                    $flag = '';
                    break;
            }

            return (bool)filter_var($ip, FILTER_VALIDATE_IP, $flag);
        }

        if ($which !== 'ipv6' && $which !== 'ipv4') {
            if (strpos($ip, ':') !== false) {
                $which = 'ipv6';
            } elseif (strpos($ip, '.') !== false) {
                $which = 'ipv4';
            } else {
                return false;
            }
        }

        $func = '_valid_'.$which;
        return $this->$func($ip);
    }

    /**
     * Validate IPv4 Address
     *
     * Updated version suggested by Geert De Deckere
     *
     * @param string
     * @return bool
     */
    protected function _valid_ipv4($ip)
    {
        $ip_segments = explode('.', $ip);

        // Always 4 segments needed
        if (count($ip_segments) !== 4) {
            return false;
        }
        // IP can not start with 0
        if ($ip_segments[0][0] == '0') {
            return false;
        }

        // Check each segment
        foreach ($ip_segments as $segment) {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate IPv6 Address
     *
     * @param string
     * @return bool
     */
    protected function _valid_ipv6($str)
    {
        // 8 groups, separated by :
        // 0-ffff per group
        // one set of consecutive 0 groups can be collapsed to ::

        $groups = 8;
        $collapsed = false;

        $chunks = array_filter(
            preg_split('/(:{1,2})/', $str, null, PREG_SPLIT_DELIM_CAPTURE)
        );

        // Rule out easy nonsense
        if (current($chunks) == ':' OR end($chunks) == ':') {
            return false;
        }

        // PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
        if (strpos(end($chunks), '.') !== false) {
            $ipv4 = array_pop($chunks);

            if (!$this->_valid_ipv4($ipv4)) {
                return false;
            }

            $groups--;
        }

        while ($seg = array_pop($chunks)) {
            if ($seg[0] == ':') {
                if (--$groups == 0) {
                    return false; // too many groups
                }

                if (strlen($seg) > 2) {
                    return false; // long separator
                }

                if ($seg == '::') {
                    if ($collapsed) {
                        return false; // multiple collapsed
                    }

                    $collapsed = true;
                }
            } elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4) {
                return false; // invalid segment
            }
        }

        return $collapsed OR $groups == 1;
    }

    /**
     * Returns the schema and host part of the application URL.
     * The returned URL does not have an ending slash.
     * By default this is determined based on the user request information.
     * You may explicitly specify it by setting the {@link setHostUrl hostUrl} property.
     * @param string $schema schema to use (e.g. http, https). If empty, the schema used for the current request will be used.
     * @return string schema and hostname part (with port number if needed) of the request URL (e.g. http://www.yiiframework.com)
     * @see setHostUrl
     */
    public function getHostUrl($schema = '')
    {
        if ($this->_hostUrl === null) {
            if ($secure = $this->isSecureConnection()) {
                $http = 'https';
            } else {
                $http = 'http';
            }
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_hostUrl = $http.'://'.$_SERVER['HTTP_HOST'];
            } else {
                $this->_hostUrl = $http.'://'.$_SERVER['SERVER_NAME'];
                $port = $secure ? $this->getSecurePort() : $this->getPort();
                if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                    $this->_hostUrl .= ':'.$port;
                }
            }
        }
        if ($schema !== '') {
            $secure = $this->isSecureConnection();
            if ($secure && $schema === 'https' || !$secure && $schema === 'http') {
                return $this->_hostUrl;
            }

            $port = $schema === 'https' ? $this->getSecurePort() : $this->getPort();
            if ($port !== 80 && $schema === 'http' || $port !== 443 && $schema === 'https') {
                $port = ':'.$port;
            } else {
                $port = '';
            }
            $pos = strpos($this->_hostUrl, ':');
            return $schema.substr($this->_hostUrl, $pos, strcspn($this->_hostUrl, ':', $pos + 1) + 1).$port;
        } else {
            return $this->_hostUrl;
        }
    }

    /**
     * Sets the schema and host part of the application URL.
     * This setter is provided in case the schema and hostname cannot be determined
     * on certain Web servers.
     * @param string $value the schema and host part of the application URL.
     */
    public function setHostUrl($value)
    {
        $this->_hostUrl = rtrim($value, '/');
    }

    /**
     * User Agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        if ($this->user_agent !== false) {
            return $this->user_agent;
        }

        $this->user_agent = (!isset($_SERVER['HTTP_USER_AGENT'])) ? false : $_SERVER['HTTP_USER_AGENT'];

        return $this->user_agent;
    }

    /**
     * Returns the server name.
     * @return string server name
     */
    public function getServerName()
    {
        return $_SERVER['SERVER_NAME'];
    }

    /**
     * Returns the server port number.
     * @return integer server port number
     */
    public function getServerPort()
    {
        return $_SERVER['SERVER_PORT'];
    }

    /**
     * Returns the URL referrer, null if not present
     * @return string URL referrer, null if not present
     */
    public function getUrlReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * Returns the user host name, null if it cannot be determined.
     * @return string user host name, null if cannot be determined
     */
    public function getUserHost()
    {
        return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
    }

    /**
     * Returns information about the capabilities of user browser.
     * @param string $userAgent the user agent to be analyzed. Defaults to null, meaning using the
     * current User-Agent HTTP header information.
     * @return array user browser capabilities.
     * @see http://www.php.net/manual/en/function.get-browser.php
     */
    public function getBrowser($userAgent = null)
    {
        return get_browser($userAgent, true);
    }

    /**
     * Returns user browser accept types, null if not present.
     * @return string user browser accept types, null if not present
     */
    public function getAcceptTypes()
    {
        return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
    }

    /**
     * Returns the port to use for insecure requests.
     * Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     * You may explicitly specify it by setting the {@link setPort port} property.
     * @return integer port number for insecure requests.
     * @see setPort
     */
    public function getPort()
    {
        if ($this->_port === null) {
            $this->_port = !$this->isSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
        }
        return $this->_port;
    }

    /**
     * Sets the port to use for insecure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param integer $value port number.
     */
    public function setPort($value)
    {
        $this->_port = (int)$value;
        $this->_hostInfo = null;
    }

    private $_securePort;

    /**
     * Returns the port to use for secure requests.
     * Defaults to 443, or the port specified by the server if the current
     * request is secure.
     * You may explicitly specify it by setting the {@link setSecurePort securePort} property.
     * @return integer port number for secure requests.
     * @see setSecurePort
     */
    public function getSecurePort()
    {
        if ($this->_securePort === null)
            $this->_securePort = $this->isSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
        return $this->_securePort;
    }

    /**
     * Sets the port to use for secure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param integer $value port number.
     */
    public function setSecurePort($value)
    {
        $this->_securePort = (int)$value;
        $this->_hostInfo = null;
    }

    /**
     * Redirects the browser to the specified URL.
     * @param string $url URL to be redirected to. Note that when URL is not
     * absolute (not starting with "/") it will be relative to current request URL.
     * @param boolean $terminate whether to terminate the current application
     * @param integer $statusCode the HTTP status code. Defaults to 302. See {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
     * for details about HTTP status code.
     */
    public function redirect($url, $terminate = true, $statusCode = 302)
    {
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = $this->getHostUrl().$url;
        }
        header('Location: '.$url, true, $statusCode);
        if ($terminate) {
            exit();
        }
    }

    /**
     * Sanitize Globals
     *
     * This function does the following:
     *
     * Unsets $_GET data (if query strings are not enabled)
     *
     * Unsets all globals if register_globals is enabled
     *
     * Standardizes newline characters to \n
     *
     * @return    void
     */
    private function _sanitize_globals()
    {
        // It would be "wrong" to unset any of these GLOBALS.
        $protected = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            'GLOBALS',
            'HTTP_RAW_POST_DATA'
        );

        // Unset globals for securiy.
        // This is effectively the same as register_globals = off
        foreach (array($_GET, $_POST, $_COOKIE) as $global) {
            if (!is_array($global)) {
                if (!in_array($global, $protected)) {
                    global $$global;
                    $$global = null;
                }
            } else {
                foreach ($global as $key => $val) {
                    if (!in_array($key, $protected)) {
                        global $$key;
                        $$key = null;
                    }
                }
            }
        }

        // Is $_GET data allowed? If not we'll set the $_GET to an empty array
        if ($this->allowGetArray == false) {
            $_GET = array();
        } else {
            if (is_array($_GET) AND count($_GET) > 0) {
                foreach ($_GET as $key => $val) {
                    $_GET[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
                }
            }
        }

        // Clean $_POST Data
        if (is_array($_POST) AND count($_POST) > 0) {
            foreach ($_POST as $key => $val) {
                $_POST[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
        }

        // Clean $_COOKIE Data
        if (is_array($_COOKIE) AND count($_COOKIE) > 0) {
            // Also get rid of specially treated cookies that might be set by a server
            // or silly application, that are of no use to a CI application anyway
            // but that when present will trip our 'Disallowed Key Characters' alarm
            // http://www.ietf.org/rfc/rfc2109.txt
            // note that the key names below are single quoted strings, and are not PHP variables
            unset($_COOKIE['$Version']);
            unset($_COOKIE['$Path']);
            unset($_COOKIE['$Domain']);

            foreach ($_COOKIE as $key => $val) {
                $_COOKIE[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
        }

        // Sanitize PHP_SELF
        $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

        // CSRF Protection check on HTTP requests
        if ($this->_enable_csrf == true && !$this->isCliRequest()) {
            $this->security->csrfVerify();
        }

        Fly::log('debug', "Global POST and COOKIE data sanitized");
    }

    /**
     * Clean Input Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @param string
     * @return string
     */
    private function _clean_input_data($str)
    {
        if (is_array($str)) {
            $new_array = array();
            foreach ($str as $key => $val) {
                $new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
            return $new_array;
        }

        /* We strip slashes if magic quotes is on to keep things consistent

           NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
             it will probably not exist in future versions at all.
        */
        if (!Fly::isPhp('5.4') && get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }

        // Clean UTF-8 if supported
        if (Fly::app()->getEnableUtf8()) {
            $str = StringHelper::cleanUtf8String($str);
        }

        // Remove control characters
        $str = StringHelper::removeInvisibleCharacters($str);

        // Should we filter the input data?
        if ($this->enableXssFilter === true) {
            $str = $this->security->cleanXss($str);
        }

        // Standardize newlines if needed
        if ($this->_standardize_newlines == true) {
            if (strpos($str, "\r") !== false) {
                $str = str_replace(array("\r\n", "\r", "\r\n\n"), PHP_EOL, $str);
            }
        }

        return $str;
    }

    /**
     * Clean Keys
     *
     * This is a helper function. To prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @param string
     * @return string
     */
    private function _clean_input_keys($str)
    {
        if (!preg_match("/^[a-z0-9:_\/-]+$/i", $str)) {
            exit('Disallowed Key Characters.');
        }

        // Clean UTF-8 if supported
        if (Fly::app()->getEnableUtf8()) {
            $str = StringHelper::cleanUtf8String($str);
        }

        return $str;
    }

    /**
     * Request Headers
     *
     * In Apache, you can simply call apache_request_headers(), however for
     * people running other webservers the function is undefined.
     *
     * @param bool XSS cleaning
     *
     * @return array
     */
    public function requestHeaders($xss_clean = false)
    {
        // Look at Apache go!
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers['Content-Type'] = (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE');

            foreach ($_SERVER as $key => $val) {
                if (strncmp($key, 'HTTP_', 5) === 0) {
                    $headers[substr($key, 5)] = $this->_fetch_from_array($_SERVER, $key, $xss_clean);
                }
            }
        }

        // take SOME_HEADER and turn it into Some-Header
        foreach ($headers as $key => $val) {
            $key = str_replace('_', ' ', strtolower($key));
            $key = str_replace(' ', '-', ucwords($key));

            $this->headers[$key] = $val;
        }

        return $this->headers;
    }

    /**
     * Get Request Header
     *
     * Returns the value of a single member of the headers class member
     *
     * @param string array key for $this->headers
     * @param boolean XSS Clean or not
     * @return mixed FALSE on failure, string on success
     */
    public function getRequestHeader($index, $xss_clean = false)
    {
        if (empty($this->headers)) {
            $this->requestHeaders();
        }

        if (!isset($this->headers[$index])) {
            return false;
        }

        if ($xss_clean === true) {
            return $this->security->cleanXss($this->headers[$index]);
        }

        return $this->headers[$index];
    }

    /**
     * Returns the request URI portion for the currently requested URL.
     * This refers to the portion that is after the {@link hostInfo host info} part.
     * It includes the {@link queryString query string} part if any.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string the request URI portion for the currently requested URL.
     * @throws FlyException if the request URI cannot be determined due to improper server configuration
     */
    public function getRequestUri()
    {
        if ($this->_requestUri === null) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
                $this->_requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            } else if (isset($_SERVER['REQUEST_URI'])) {
                $this->_requestUri = $_SERVER['REQUEST_URI'];
                if (!empty($_SERVER['HTTP_HOST'])) {
                    if (strpos($this->_requestUri, $_SERVER['HTTP_HOST']) !== false) {
                        $this->_requestUri = preg_replace('/^\w+:\/\/[^\/]+/', '', $this->_requestUri);
                    }
                } else {
                    $this->_requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $this->_requestUri);
                }
            } else if (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
                $this->_requestUri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $this->_requestUri .= '?'.$_SERVER['QUERY_STRING'];
                }
            } else {
                throw new FlyException(Fly::t('fly', 'HttpRequest is unable to determine the request URI.'));
            }
        }

        return $this->_requestUri;
    }

    /**
     * Returns part of the request URL that is after the question mark.
     * @return string part of the request URL that is after the question mark
     */
    public function getQueryString()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * Is ajax Request?
     *
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header
     *
     * @return boolean
     */
    public function isAjaxequest()
    {
        return ($this->getServer('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest');
    }

    /**
     * Is cli Request?
     *
     * Test to see if a request was made from the command line
     *
     * @return bool
     */
    public function isCliRequest()
    {
        return (php_sapi_name() === 'cli' OR defined('STDIN'));
    }

    /**
     * Return if the request is sent via secure channel (https).
     * @return boolean if the request is sent via secure channel (https)
     */
    public function isSecureConnection()
    {
        return !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off');
    }

    /**
     * Returns whether this is an Adobe Flash or Adobe Flex request.
     * @return boolean whether this is an Adobe Flash or Adobe Flex request.
     */
    public function isFlashRequest()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) && (stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false);
    }

    /**
     * Returns the request type, such as GET, POST, HEAD, PUT, DELETE.
     * Request type can be manually set in POST requests with a parameter named _method. Useful
     * for RESTful request from older browsers which do not support PUT or DELETE
     * @return string request type, such as GET, POST, HEAD, PUT, DELETE.
     */
    public function getRequestType()
    {
        if (isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }

        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    }

    /**
     * Returns whether this is a POST request.
     * @return boolean whether this is a POST request.
     */
    public function isPostRequest()
    {
        return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST');
    }

    /**
     * Returns whether this is a DELETE request.
     * @return boolean whether this is a DELETE request.
     */
    public function isDeleteRequest()
    {
        return (isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'DELETE')) || $this->isDeleteViaPostRequest();
    }

    /**
     * Returns whether this is a DELETE request which was tunneled through POST.
     * @return boolean whether this is a DELETE request tunneled through POST.
     */
    protected function isDeleteViaPostRequest()
    {
        return isset($_POST['_method']) && !strcasecmp($_POST['_method'], 'DELETE');
    }

    /**
     * Returns whether this is a PUT request.
     * @return boolean whether this is a PUT request.
     */
    public function isPutRequest()
    {
        return (isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'PUT')) || $this->isPutViaPostRequest();
    }

    /**
     * Returns whether this is a PUT request which was tunneled through POST.
     * @return boolean whether this is a PUT request tunneled through POST.
     */
    protected function isPutViaPostRequest()
    {
        return isset($_POST['_method']) && !strcasecmp($_POST['_method'], 'PUT');
    }

    /**
     * Returns the relative URL of the entry script.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string the relative URL of the entry script.
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } else if (basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } else if (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } else if (($pos = strpos($_SERVER['PHP_SELF'], '/'.$scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos).'/'.$scriptName;
            } else if (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
            } else {
                throw new FlyException(Fly::t('fly', 'HttpRequest is unable to determine the entry script URL.'));
            }
        }
        return $this->_scriptUrl;
    }

    /**
     * Sets the relative URL for the application entry script.
     * This setter is provided in case the entry script URL cannot be determined
     * on certain Web servers.
     * @param string $value the relative URL for the application entry script.
     */
    public function setScriptUrl($value)
    {
        $this->_scriptUrl = '/'.trim($value, '/');
    }

    /**
     * Returns the path info of the currently requested URL.
     * This refers to the part that is after the entry script and before the question mark.
     * The starting and ending slashes are stripped off.
     * @return string part of the request URL that is after the entry script and before the question mark.
     * (in most cases it is not decoded).
     * @throws FlyException if the request URI cannot be determined due to improper server configuration
     */
    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $pathInfo = $this->getRequestUri();
            if (($pos = strpos($pathInfo, '?')) !== false) {
                $pathInfo = substr($pathInfo, 0, $pos);
            }

            $pathInfo = $this->decodePathInfo($pathInfo);

            $scriptUrl = $this->getScriptUrl();
            $baseUrl = $this->getBaseUrl();
            if (strpos($pathInfo, $scriptUrl) === 0) {
                $pathInfo = substr($pathInfo, strlen($scriptUrl));
            } else if ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
                $pathInfo = substr($pathInfo, strlen($baseUrl));
            } else if (strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
                $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
            } else {
                throw new FlyException(Fly::t('fly', 'HttpRequest is unable to determine the path info of the request.'));
            }

            $this->_pathInfo = trim($pathInfo, '/');
        }
        return $this->_pathInfo;
    }

    /**
     * Decodes the path info.
     * This method is an improved variant of the native urldecode() function and used in {@link getPathInfo getPathInfo()} to
     * decode the path part of the request URI. You may override this method to change the way the path info is being decoded.
     * @param string $pathInfo encoded path info
     * @return string decoded path info
     */
    protected function decodePathInfo($pathInfo)
    {
        $pathInfo = urldecode($pathInfo);

        // is it UTF-8?
        // http://w3.org/International/questions/qa-forms-utf-8.html
        if (preg_match('%^(?:
		   [\x09\x0A\x0D\x20-\x7E]            # ASCII
		 | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		 | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		 | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		 | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		 | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		 | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $pathInfo)
        ) {
            return $pathInfo;
        } else {
            return utf8_encode($pathInfo);
        }
    }

    /**
     * Returns the relative URL for the application.
     * This is similar to {@link getScriptUrl scriptUrl} except that
     * it does not have the script file name, and the ending slashes are stripped off.
     * @param boolean $absolute whether to return an absolute URL. Defaults to false, meaning returning a relative one.
     * @return string the relative URL for the application
     * @see setScriptUrl
     */
    public function getBaseUrl($absolute = false)
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $absolute ? $this->getHostUrl().$this->_baseUrl : $this->_baseUrl;
    }

    /**
     * Sets the relative URL for the application.
     * By default the URL is determined based on the entry script URL.
     * This setter is provided in case you want to change this behavior.
     * @param string $value the relative URL for the application
     */
    public function setBaseUrl($value)
    {
        $this->_baseUrl = $value;
    }

    /**
     * System URL
     *
     * @return string
     */
    public function getSystemUrl()
    {
        $x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", FLY_PATH));
        return $this->getBaseUrl().end($x).'/';
    }

    /**
     * Sends a file to user.
     * @param string $fileName file name
     * @param string $content content to be set.
     * @param string $mimeType mime type of the content. If null, it will be guessed automatically based on the given file name.
     * @param boolean $terminate whether to terminate the current application after calling this method
     */
    public function sendFile($filename = '', $data = '', $mimeType = null, $terminate = true)
    {
        if ($filename == '' || $data == '') {
            return false;
        }

        // Try to determine if the filename includes a file extension.
        // We need it in order to set the MIME type
        if (false === strpos($filename, '.')) {
            return false;
        }

        if ($mimeType) {
            $mime = $mimeType;
        } else {
            // Grab the file extension
            $x = explode('.', $filename);
            $extension = end($x);
            Fly::loadConfig('config.mimes', true);
            $mimes = Fly::getConfig('mimes');
            if (empty($mimes)) {
                return false;
            }

            // Set a default mime if we can't find it
            if (!isset($mimes[$extension])) {
                $mime = 'application/octet-stream';
            } else {
                $mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
            }
        }
        // Generate the server headers
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== false) {
            header('Content-Type: "'.$mime.'"');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header("Content-Transfer-Encoding: binary");
            header('Pragma: public');
            header("Content-Length: ".strlen($data));
        } else {
            header('Content-Type: "'.$mime.'"');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            header("Content-Length: ".strlen($data));
        }
        echo $data;
        if ($terminate) {
            exit();
        }
    }
}