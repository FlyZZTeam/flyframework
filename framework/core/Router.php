<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Parses URIs and determines routing
 */
class Router
{

    /**
     * @var int The normal router request.
     */
    const ROUTER_REQUEST_NORMAL = 1;
    /**
     * @var int The module router request.
     */
    const ROUTER_REQUEST_MODULE = 2;
    /**
     * @var array List of routes
     */
    var $routes = array();
    /**
     * @var array List of error routes
     */
    var $error_routes = array();
    /**
     * @var string Current class name
     */
    var $class = '';
    /**
     * @var string Current method name
     */
    var $method = 'index';
    /**
     * @var string Sub-directory that contains the requested controller class
     */
    var $module = '';
    /**
     * @var string Default controller (and method if specific)
     */
    var $default_controller;

    /**
     * Constructor
     *
     * Runs the route mapping function.
     */
    public function __construct()
    {
        $this->uri = Fly::app()->getUri();
        Fly::log('debug', "Router Class Initialized");
        $this->setRouting();
    }

    /**
     * Set the route mapping
     *
     * This function determines what should be served based on the URI request,
     * as well as any "routes" that have been set in the routing config file.
     *
     * @return void
     */
    public function setRouting()
    {
        // Are query strings enabled in the config file?  Normally CI doesn't utilize query strings
        // since URI segments are more search-engine friendly, but they can optionally be used.
        // If this feature is enabled, we will gather the directory/class/method a little differently
        $segments = array();
        $enableQueryStrings = Fly::getConfig('enableQueryStrings');
        $ct = Fly::getConfig('aliasController');
        $dt = Fly::getConfig('aliasModule');
        $ft = Fly::getConfig('aliasAction');

        if ($enableQueryStrings === true && isset($_GET[$ct])) {

            if (isset($_GET[$dt])) {
                $this->setModule(trim($this->uri->filterUri($_GET[$dt])));
                $segments[] = $this->fetchModule();
            }

            if (isset($_GET[$ct])) {
                $this->setClass(trim($this->uri->filterUri($_GET[$ct])));
                $segments[] = $this->fetchClass();
            }

            if (isset($_GET[$ft])) {
                $this->setMethod(trim($this->uri->filterUri($_GET[$ft])));
                $segments[] = $this->fetchMethod();
            }
        }

        // Load the routes.php file.
        Fly::app()->loadConfig('config.routes', true, true);
        $route = Fly::app()->getConfig('routes');
        $this->routes = (!isset($route) || !is_array($route)) ? array() : $route;
        unset($route);

        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = (!isset($this->routes['default_controller']) || $this->routes['default_controller'] == '') ? false : $this->routes['default_controller'];

        // Were there any query string segments?  If so, we'll validate them and bail out since we're done.
        if (count($segments) > 0) {
            $r = $this->validateRequest($segments);
            if ($r === null) {
                return array();
            }
            if (isset($r['segments'])) {
                return $r['segments'];
            }
            return array();
        }

        // Fetch the complete URI string
        $this->uri->fetchUriString();

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri->getUriString() == '') {
            return $this->setDefaultController();
        }

        // Do we need to remove the URL suffix?
        $this->uri->removeUrlSuffix();

        // Compile the segments into an array
        $this->uri->explodeSegments();

        // Parse any custom routing that may exist
        $this->parseRoutes();

        // Re-index the segment array so that it starts with 1 rather than 0
        $this->uri->reindexSegments();
    }

    /**
     * Set the default controller
     * @return void
     */
    private function setDefaultController()
    {
        if ($this->default_controller === false) {
            throw new FlyException(Fly::t('fly', "Unable to determine what should be displayed. A default route has not been specified in the routing file."));
        }

        // Is the method being specified?
        if (strpos($this->default_controller, '/') !== false) {
            $x = explode('/', $this->default_controller);
            $this->setClass($x[0]);
            $this->setMethod($x[1]);
            $this->setRequest($x);
        } else {
            $this->setClass($this->default_controller);
            $this->setMethod('index');
            $this->setRequest(array($this->default_controller, 'index'));
        }

        // re-index the routed segments array so it starts with 1 rather than 0
        $this->uri->reindexSegments();

        Fly::log('debug', "No URI present. Default controller set.");
    }

    /**
     * Set the Route
     *
     * This function takes an array of URI segments as
     * input, and sets the current class/method
     *
     * @param array $segments The router segments.
     * @return void
     */
    private function setRequest($segments = array())
    {
        $r = $this->validateRequest($segments);
        if ($r === null) {
            return $this->setDefaultController();
        }
        $segments = $r['segments'];
        $requestType = $r['requestType'];
        //$count = count($segments);
        if ($requestType === self::ROUTER_REQUEST_NORMAL) {
            $this->setClass($segments[0]);

            if (isset($segments[1])) {
                // A standard method request
                $this->setMethod($segments[1]);
            } else {
                // This lets the "routed" segment array identify that the default
                // index method is being used.
                $segments[1] = 'index';
            }
        } else {
            $this->setModule($segments[0]);
            $this->setClass($segments[1]);
            if (isset($segments[2])) {
                // A standard method request
                $this->setMethod($segments[2]);
            }
        }

        // Update our "routed" segment array to contain the segments.
        // Note: If there is no custom routing, this array will be
        // identical to $this->uri->segments
        $this->uri->setRsegments($segments);

        $num = 3;
        if ($requestType === self::ROUTER_REQUEST_MODULE) {
            $num = 4;
        }
        $params = $this->uri->ruriToAssoc($num);
        foreach ($params as $key => $val) {
            $_GET[$key] = $val;
        }
    }

    /**
     * Validates the supplied segments.  Attempts to determine the path to
     * the controller.
     *
     * @param array $segments The uri segments.
     * @return array The correct segments.
     */
    private function validateRequest($segments)
    {
        if (count($segments) == 0) {
            return null;
        }

        // Does the requested controller exist in the root folder?
        $fileName = Fly::app()->getControllerName($segments[0]).EXT;
        if (file_exists(Fly::app()->getControllerPath().DIRECTORY_SEPARATOR.$fileName)) {
            return array(
                'requestType' => self::ROUTER_REQUEST_NORMAL,
                'segments' => $segments
            );
        }
        // Is the controller in a sub-folder?
        $filePaths = Fly::app()->getModuleControllerPaths($segments[0]);
        $filePath = '';
        foreach ($filePaths as $val) {
            if (is_dir($val)) {
                $filePath = $val;
                break;
            }
        }

        if ($filePath !== '') {
            // Set the directory and remove it from the segment array
            //$this->setModule($segments[0]);
            $newSegments = array_slice($segments, 1);

            if (count($newSegments) > 0) {
                // Does the requested controller exist in the sub-folder?
                $fileName = Fly::app()->getControllerName($newSegments[0]).EXT;
                $filePath .= DIRECTORY_SEPARATOR.$fileName;
                if (!file_exists($filePath)) {
                    if (!empty($this->routes['404_override'])) {
                        $x = explode('/', $this->routes['404_override']);
                        $this->setModule('');
                        $this->setClass($x[0]);
                        $this->setMethod(isset($x[1]) ? $x[1] : 'index');
                        return array(
                            'requestType' => self::ROUTER_REQUEST_NORMAL,
                            'segments' => $x,
                        );
                    } else {
                        throw new HttpException(404, $this->fetchModule().'/'.$newSegments[0]);
                    }
                }
            } else {
                // Is the method being specified in the route?
                if (strpos($this->default_controller, '/') !== false) {
                    $x = explode('/', $this->default_controller);
                    $this->setClass($x[0]);
                    $this->setMethod($x[1]);
                } else {
                    $this->setClass($this->default_controller);
                    $this->setMethod('index');
                }

                // Does the default controller exist in the sub-folder?
                if (!file_exists($filePath.DIRECTORY_SEPARATOR.$this->default_controller.EXT)) {
                    $this->module = '';
                    return null;
                }
            }
            return array(
                'requestType' => self::ROUTER_REQUEST_MODULE,
                'segments' => $segments
            );
        }

        // If we've gotten this far it means that the URI does not correlate to a valid
        // controller class.  We will now see if there is an override
        if (!empty($this->routes['404_override'])) {
            $x = explode('/', $this->routes['404_override']);

            $this->setClass($x[0]);
            $this->setMethod(isset($x[1]) ? $x[1] : 'index');

            return array(
                'requestType' => self::ROUTER_REQUEST_NORMAL,
                'segments' => $x
            );
        }

        // Nothing else to do at this point but show a 404
        throw new HttpException(404, $segments[0]);
    }

    /**
     *  Parse Routes
     *
     * This function matches any routes that may exist in
     * the config/routes.php file against the URI to
     * determine if the class/method need to be remapped.
     *
     * @return    void
     */
    private function parseRoutes()
    {
        // Turn the segment array into a URI string
        $uri = implode('/', $this->uri->getSegments());
        // Is there a literal match?  If so we're done
        if (isset($this->routes[$uri])) {
            return $this->setRequest(explode('/', $this->routes[$uri]));
        }

        // Loop through the route array looking for wild-cards
        foreach ($this->routes as $key => $val) {
            // Convert wild-cards to RegEx
            $key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));
            // Does the RegEx match?
            if (preg_match('#^'.$key.'$#', $uri)) {
                // Do we have a back-reference?
                if (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }

                return $this->setRequest(explode('/', $val));
            }
        }

        // If we got this far it means we didn't encounter a
        // matching route so we'll set the site default route
        $this->setRequest($this->uri->getSegments());
    }

    /**
     * Set the class name
     * @param string $class The class name.
     * @return void
     */
    public function setClass($class)
    {
        $this->class = str_replace(array('/', '.'), '', $class);
    }

    /**
     * Fetch the current class
     * @return    string The class name.
     */
    public function fetchClass()
    {
        return $this->class;
    }

    /**
     *  Set the method name
     * @param $method string The method name.
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     *  Fetch the current method
     * @return string The method name.
     */
    public function fetchMethod()
    {
        if ($this->method == $this->fetchClass()) {
            return 'index';
        }
        return $this->method;
    }

    /**
     *  Set the directory name
     * @param string $dir The module name.
     * @return void
     */
    public function setModule($dir)
    {
        $this->module = str_replace(array('/', '.'), '', $dir);
    }

    /**
     * Fetch the sub-directory (if any) that contains the requested controller class
     * @return    string
     */
    public function fetchModule()
    {
        return $this->module;
    }

    /**
     * Return Routes rule
     * @param $key string
     * @return string
     */
    public function getRoutes($key)
    {
        return isset($this->routes[$key]) ? $this->routes[$key] : '';
    }

    /**
     *  Set the controller overrides
     * @param array
     */
    public function setOverrides($routing)
    {
        if (!is_array($routing)) {
            return;
        }

        if (isset($routing['aliasModule'])) {
            $this->setModule($routing['aliasModule']);
        }

        if (isset($routing['aliasController']) && $routing['aliasController'] != '') {
            $this->setClass($routing['aliasController']);
        }

        if (isset($routing['aliasAction'])) {
            $routing['aliasAction'] = ($routing['aliasAction'] == '') ? 'index' : $routing['aliasAction'];
            $this->setMethod($routing['aliasAction']);
        }
    }

    /**
     * @return string the route string,like{moduleID/controllerID/action}
     */
    public function getRouting()
    {
        $route = '';
        $module = $this->fetchModule();
        $class = $this->fetchClass();
        $action = $this->fetchMethod();
        if ($module !== null && $module !== '') {
            $route = $module;
        }
        if ($class !== null && $class !== '') {
            if ($route === '') {
                $route = $class;
            } else {
                $route .= '/'.$class;
            }
        }

        if ($action !== null && $action !== '') {
            $route .= '/'.$action;
        } else {
            $route .= '/index';
        }
        return $route;
    }
}