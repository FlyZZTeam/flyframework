<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Application is the base class for all application classes.
 *
 * An application serves as the global context that the user request
 * is being processed. It manages a set of application components that
 * provide specific functionalities to the whole application.
 *
 */
abstract class Application extends Module
{
    /**
     * @var string The application name.The default name is "Fly Application".
     */
    public $name = 'Fly Application';
    /**
     * @var string The language that the application is written in.
     */
    public $sourceLanguage = 'en_us';
    /**
     * @var string The time reference.Defaults to "local".
     */
    public $timeReference = 'local';
    /**
     * @var string the charset currently used for the application. Defaults to 'UTF-8'.
     */
    public $charset = 'UTF-8';
    /**
     * @var string The application path.
     * You can set,for example:"./application".
     */
    private $_basePath;
    /**
     * @var array The application's config.
     */
    private $_appConfig = array();
    /**
     * @var string The unique identifier for the application.
     */
    private $_id;
    /**
     * @var string The language that the user is using and the application should be targeted to.
     * Defaults to the {@link sourceLanguage source language}.
     */
    private $_language;
    /**
     * @var string The home page url.
     */
    private $_homeUrl;
    /**
     * @var bool The application is ended.
     */
    private $_ended = false;

    /**
     * Constructor.
     * @param mixed $config application configuration.
     * If a string, it is treated as the path of the file that contains the configuration;
     * If an array, it is the actual configuration information.
     * Please make sure you specify the {@link getBasePath basePath} property in the configuration,
     * which should point to the directory containing all application logic, template and data.
     * If not, the directory will be defaulted to 'application'.
     */
    public function __construct($config = null)
    {
        Fly::setApplication($this);

        $this->registerCoreComponents();

        //System error and FlyException handler
        $this->initSystemHandlers();
        $this->startTimer();
        if (is_string($config)) {
            $config = require($config);
        }

        if (!isset($config['timezone']) || $config['timezone'] == '') {
            $config['timezone'] = 'PRC';
        }

        if (isset($config['id']) && is_string($config['id']) && $config['id'] !== '') {
            $this->_id = trim($config['id']);
        }

        //System timezone
        $this->setTimeZone($config['timezone']);

        // Set application dir
        if (isset($config['base_path'])) {
            $this->setBasePath($config['base_path']);
            unset($config['base_path']);
        } else {
            $this->setBasePath('application');
        }

        Fly::setPathOfAlias('application', $this->getBasePath());
        Fly::setPathOfAlias('webroot', dirname($_SERVER['SCRIPT_FILENAME']));
        Fly::setPathOfAlias('ext', $this->getBasePath().DIRECTORY_SEPARATOR.'extensions');

        $this->initConfigure($config);

        // Load the framework constants
        $configDir = $this->_basePath.'/config/';
        if (defined('ENVIRONMENT') AND file_exists($configDir.ENVIRONMENT.'/constants.php')) {
            require($configDir.ENVIRONMENT.'/constants.php');
        } else {
            require($configDir.'constants.php');
        }

        Fly::log('debug', 'Application Class Initialized');

        $this->attachBehaviors($this->behaviors);

        $this->preloadComponents();
        $this->initPHP();
        $this->initHooks();
    }

    /**
     * Runs the application.
     * This method loads static application components. Derived classes usually overrides this
     * method to do more application-specific tasks.
     */
    public function run()
    {
        if ($this->hasEventHandler('onBeginRequest')) {
            $this->onBeginRequest(new Event($this));
        }

        register_shutdown_function(array($this, 'end'), 0, false);
        $this->processRequest();

        if ($this->hasEventHandler('onEndRequest')) {
            $this->onEndRequest(new Event($this));
        }
    }

    /**
     * Terminates the application.
     * This method replaces PHP's exit() function by calling
     * {@link onEndRequest} before exiting.
     * @param integer $status exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param boolean $exit whether to exit the current request.
     * It defaults to true, meaning the PHP's exit() function will be called at the end of this method.
     */
    public function end($status = 0, $exit = true)
    {
        if ($this->hasEventHandler('onEndRequest')) {
            $this->onEndRequest(new Event($this));
        }
        $this->getDBManager()->closeAll();
        if ($exit) {
            exit($status);
        }
    }

    /**
     * Trigger right BEFORE the application processes the request.
     * @param Event $event the event parameter
     */
    public function onBeginRequest($event)
    {
        $this->trigger('onBeginRequest', $event);
    }

    /**
     * Trigger right AFTER the application processes the request.
     * @param Event $event the event parameter
     */
    public function onEndRequest($event)
    {
        if (!$this->_ended) {
            $this->_ended = true;
            $this->trigger('onEndRequest', $event);
        }
    }

    /**
     * Processes the request.
     * This is the place where the actual request processing work is done.
     * Derived classes should override this method.
     */
    abstract public function processRequest();

    /**
     * Set application id
     * @param $id string
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Return application id
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set configure item
     * @param string $key
     * @param mixed $val
     */
    public function setConfig($key, $val)
    {
        $this->_appConfig[$key] = $val;
    }

    /**
     * Assign config
     * @param array $array
     */
    public function assignConfig($array = array())
    {
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                $this->setConfig($key, $val);
            }
        }
    }

    /**
     * Return application's config
     * When $key and $category is empty,this method will return all data.
     * @param string $key the configure item name
     * @param string $category the configure index name
     * @return mixed bool OR string OR array
     */
    public function getConfig($key = '', $category = '')
    {
        if ($key == '' && $category == '') {
            return $this->_appConfig;
        }

        if ($category == '') {
            if (!isset($this->_appConfig[$key])) {
                return false;
            }
            $pref = $this->_appConfig[$key];
        } else {
            if (!isset($this->_appConfig[$category])) {
                return false;
            }

            if ($key == '') {
                return $this->_appConfig[$category];
            }

            if (!isset($this->_appConfig[$category][$key])) {
                return false;
            }

            $pref = $this->_appConfig[$category][$key];
        }

        return $pref;
    }

    /**
     * Load configuration file.
     * It will search 'application' and 'webroot'.
     * if defined ENVIRONMENT,it will search 'ENVIRONMENT' on last dir.
     * @param string $alias alias name
     * @param boolean $useSections if configuration values should be loaded into their own section
     * @param boolean $failGracefully true if errors should just return false, false if an error message should be displayed
     * @return boolean if the file was loaded correctly
     */
    public function loadConfig($alias, $useSections = false, $failGracefully = false)
    {
        static $loadedConfigs = array();
        if (empty($alias)) {
            return false;
        }
        $hasLoaded = false;
        $isFound = false;
        $aliasAry = array();
        $subAlias = explode('.', $alias);
        $count = count($subAlias);
        $file = $subAlias[$count - 1];
        if (defined('ENVIRONMENT')) {
            $subAlias[$count] = $file;
            $subAlias[$count - 1] = ENVIRONMENT;
            $aliasAry[] = implode('.', $subAlias);
        }
        $aliasAry[] = $alias;

        $searchRootAlias = array('application', 'webroot');

        foreach ($searchRootAlias as $root) {
            foreach ($aliasAry as $val) {
                $path = Fly::getPathOfAlias($root.'.'.$val).EXT;
                if (in_array($path, $loadedConfigs, true)) {
                    $hasLoaded = true;
                    continue;
                }
                if (file_exists($path)) {
                    $isFound = true;
                    break;
                }
            }

            if ($isFound == false) {
                continue;
            }

            $config = include($path);

            if (!isset($config) || !is_array($config)) {
                if ($failGracefully === true) {
                    return false;
                }
                throw new FlyException(Fly::t('fly', 'Your '.$path.' file does not appear to contain a valid configuration array.'));
            }

            if ($useSections === true) {
                if (isset($this->_appConfig[$file])) {
                    $this->_appConfig[$file] = array_merge($this->_appConfig[$file], $config);
                } else {
                    $this->_appConfig[$file] = $config;
                }
            } else {
                $this->_appConfig = array_merge($this->_appConfig, $config);
            }

            $loadedConfigs[] = $path;
            unset($config);

            $hasLoaded = true;
            Fly::log('debug', 'Config file loaded: '.$path);
            break;
        }

        if ($hasLoaded === false) {
            if ($failGracefully === true) {
                return false;
            }
            throw new FlyException(Fly::t('fly', 'The configuration file '.$file.'.php does not exist.'));
        }
        return true;
    }

    /**
     * Initializes the error handlers.
     */
    protected function initSystemHandlers()
    {
        if (FLY_ENABLE_EXCEPTION_HANDLER) {
            set_exception_handler(array($this, 'handleException'));
        }

        if (FLY_ENABLE_ERROR_HANDLER) {
            set_error_handler(array($this, 'handleError'));
        }
    }

    /**
     * Init php settings and versions difference control.
     * Set php script execution time.
     */
    protected function initPHP()
    {
        if (!Fly::isPhp('5.3')) {
            // Kill magic quotes
            @set_magic_quotes_runtime(0);
        }

        // Set a liberal script execution time limit
        if (function_exists("set_time_limit") == true && @ini_get("safe_mode") == 0) {
            @set_time_limit(300);
        }
        $this->getUnicode();
    }

    /**
     * Init application configure
     * @param array $config
     */
    protected function initConfigure($config)
    {
        if (is_array($config)) {
            $this->assignConfig($config);
            $this->configure($config);
        } else {
            throw new FlyException(Fly::t('fly', 'The configuration file can not be empty'));
        }
    }

    /**
     * Init Hooks
     */
    protected function initHooks()
    {
        $hook = $this->getHooks();
        $hook->callHook('startApplication');
    }

    /**
     * Start the timer... tick tock tick tock...
     */
    protected function startTimer()
    {
        $benchmark = $this->getBenchmark();
        $benchmark->mark('APPLICATION_START');
        $benchmark->mark('BASE_CLASS_INITIALIZATION_START');
    }

    /**
     * Returns the root path of the application.
     * @return string the root directory of the application. Defaults to 'application'.
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * Sets the root directory of the application.
     * This method can only be invoked at the begin of the constructor.
     * @param string $path the root directory of the application.
     * @throws FlyException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        if (($this->_basePath = realpath($path)) === false || !is_dir($this->_basePath)) {
            throw new FlyException(Fly::t('fly', 'Application base path "{path}" is not a valid directory.',
                array('{path}' => $path)));
        }
    }

    /**
     * Return Benchmark component
     * @return mixed
     */
    public function getBenchmark()
    {
        return $this->getComponent('Benchmark');
    }

    /**
     * Return Hooks component
     * @return mixed
     */
    public function getHooks()
    {
        return $this->getComponent('Hooks');
    }

    /**
     * Return security component
     * @return mixed
     */
    public function getSecurity()
    {
        return $this->getComponent('Security');
    }

    /**
     * Return UTF-8 component
     * @return mixed
     */
    public function getUnicode()
    {
        return $this->getComponent('Utf8');
    }

    /**
     * Return Uri component
     * @return mixed
     */
    public function getUri()
    {
        return $this->getComponent('Uri');
    }

    /**
     * Return Router component
     * @return mixed
     */
    public function getRouter()
    {
        return $this->getComponent('Router');
    }

    /**
     * Return ExceptionHandler component
     * @return mixed
     */
    public function getExceptionHandler()
    {
        return $this->getComponent('ExceptionHandler');
    }

    /**
     * Return ErrorHandler component
     * @return mixed
     */
    public function getErrorHandler()
    {
        return $this->getComponent('errorHandler');
    }

    /**
     * Return Language component
     * i18n does not currently support
     * @return mixed
     */
    public function getLang()
    {
        return $this->getComponent('Lang');
    }

    /**
     * Return Output component
     * @return mixed
     */
    public function getOutput()
    {
        return $this->getComponent('Output');
    }

    /**
     * Returns the request component.
     * @return HttpRequest the request component
     */
    public function getRequest()
    {
        return $this->getComponent('HttpRequest');
    }

    /**
     * Returns the Validator component.
     * @return Validator component
     */
    public function getValidator()
    {
        return $this->getComponent('Validator');
    }

    /**
     * Returns the DB component.
     * @return DB component
     */
    public function getDBManager()
    {
        return $this->getComponent('DB');
    }

    /**
     * Returns the database instance.
     * @param string $id
     * @param string $params
     * @param null $activeRecordOverride
     * @return mixed
     */
    public function getDB($id = 'default', $params = '', $activeRecordOverride = null)
    {
        return $this->getDBManager()->getDbInstance($id, $params, $activeRecordOverride);
    }

    /**
     * Returns the database utils instance.
     * @param string $id
     * @param string $params
     * @param null $activeRecordOverride
     * @return mixed
     */
    public function getDBUtil($id = 'default', $params = '', $activeRecordOverride = null)
    {
        return $this->getDBManager()->getDbUtilityInstance($id, $params, $activeRecordOverride);
    }

    /**
     * Return UnitTest Component.
     * @return UnitTest
     */
    public function getUnitTest()
    {
        return $this->getComponent('UnitTest');
    }

    /**
     * Return Cache Component.
     * @return Cache
     */
    public function getCache()
    {
        return $this->getComponent('Cache');
    }

    /**
     * Returns file search paths
     * @return array
     */
    public function getSearchPaths()
    {
        $searchRootAlias = array('application', 'system', 'webroot');
        $paths = array();
        foreach ($searchRootAlias as $root) {
            $path = Fly::getPathOfAlias($root);
            if (is_dir($path)) {
                $paths[] = $path;
            }
        }
        return $paths;
    }

    /**
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * @return string the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     */
    public function getTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    /**
     * Returns the language that the user is using and the application should be targeted to.
     * @return string the language that the user is using and the application should be targeted to.
     * Defaults to the {@link sourceLanguage source language}.
     */
    public function getLanguage()
    {
        return $this->_language === null ? $this->sourceLanguage : $this->_language;
    }

    /**
     * Specifies which language the application is targeted to.
     * @param string $language the user language
     * If it is null, the {@link sourceLanguage} will be used.
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    /**
     * Returns the localized version of a specified file.
     * @param string $srcFile the original file
     * @param string $srcLanguage the language that the original file is in. If null, the application {@link sourceLanguage source language} is used.
     * @param string $language the desired language that the file should be localized to. If null, the {@link getLanguage application language} will be used.
     * @return string the matching localized file. The original file is returned if no localized version is found
     * or if source language is the same as the desired language.
     */
    public function findLocalizedFile($srcFile, $srcLanguage = null, $language = null)
    {
        if ($srcLanguage === null) {
            $srcLanguage = $this->sourceLanguage;
        }
        if ($language === null) {
            $language = $this->getLanguage();
        }
        if ($language === $srcLanguage) {
            return $srcFile;
        }
        $desiredFile = dirname($srcFile).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.basename($srcFile);
        return is_file($desiredFile) ? $desiredFile : $srcFile;
    }

    /**
     * Returns the directory that contains the application modules.
     * @return string the directory that contains the application modules. Defaults to the 'modules' subdirectory of {@link basePath}.
     */
    public function getModulePath()
    {
        if ($this->_modulePath !== null) {
            return $this->_modulePath;
        } else {
            $this->_modulePath = $this->getBasePath().DIRECTORY_SEPARATOR.'modules';
            if ($this->_id && $this->_id !== '') {
                $this->_modulePath .= DIRECTORY_SEPARATOR.$this->_id;
                if (!is_dir($this->_modulePath)) {
                    throw new FlyException(Fly::t('fly', 'The module path "{path}" is not a valid directory.',
                        array('{path}' => $this->_modulePath)));
                }
                Fly::setPathOfAlias($this->_id, $this->_modulePath);
            }
            return $this->_modulePath;
        }
    }

    /**
     * Sets the directory that contains the application modules.
     * @param string $value the directory that contains the application modules.
     * @throws FlyException if the directory is invalid
     */
    public function setModulePath($value)
    {
        if (($this->_modulePath = realpath($value)) === false || !is_dir($this->_modulePath)) {
            throw new FlyException(Fly::t('fly', 'The module path "{path}" is not a valid directory.',
                array('{path}' => $value)));
        }
        if ($this->_id && $this->_id !== '') {
            $this->_modulePath .= DIRECTORY_SEPARATOR.$this->_id;
            if (!is_dir($this->_modulePath)) {
                throw new FlyException(Fly::t('fly', 'The module path "{path}" is not a valid directory.',
                    array('{path}' => $this->_modulePath)));
            }
            Fly::setPathOfAlias($this->_id, $this->_modulePath);
        }
    }

    /**
     * Creates a relative URL based on the given controller and action information.
     * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
     * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    public function createUrl($route, $params = array(), $ampersand = '&')
    {
        return $this->Uri->createUrl($route, $params, $ampersand);
    }

    /**
     * Creates an absolute URL based on the given controller and action information.
     * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
     * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
     * @param string $schema schema to use (e.g. http, https). If empty, the schema used for the current request will be used.
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    public function createAbsoluteUrl($route, $params = array(), $schema = '', $ampersand = '&')
    {
        $url = $this->createUrl($route, $params, $ampersand);
        if (strpos($url, 'http') === 0) {
            return $url;
        } else {
            return $this->Request->getHostUrl($schema).$url;
        }
    }

    /**
     * Returns the relative URL for the application.
     * This is a shortcut method to {@link HttpRequest::getBaseUrl()}.
     * @param boolean $absolute whether to return an absolute URL. Defaults to false, meaning returning a relative one.
     * @return string the relative URL for the application
     * @see HttpRequest::getBaseUrl()
     * @return string the constructed URL
     */
    public function getBaseUrl($absolute = false)
    {
        return $this->Request->getBaseUrl($absolute);
    }

    /**
     * @return string the homepage URL
     */
    public function getHomeUrl()
    {
        if ($this->_homeUrl === null) {
            if ($this->Uri->showScriptName) {
                return $this->getRequest()->getScriptUrl();
            } else {
                return $this->getRequest()->getBaseUrl().'/';
            }
        } else {
            return $this->_homeUrl;
        }
    }

    /**
     * @param string $value the homepage URL
     */
    public function setHomeUrl($value)
    {
        $this->_homeUrl = $value;
    }

    /**
     * Displays the captured PHP error.
     * This method displays the error in HTML when there is
     * no active error handler.
     * @param integer $code error code
     * @param string $message error message
     * @param string $file error file
     * @param string $line error line
     */
    public function displayError($code, $message, $file, $line)
    {
        if (FLY_DEBUG) {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message ($file:$line)</p>\n";
            echo '<pre>';

            $trace = debug_backtrace();

            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3) {
                $trace = array_slice($trace, 3);
            }

            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $t['file'] = 'unknown';
                }
                if (!isset($t['line'])) {
                    $t['line'] = 0;
                }
                if (!isset($t['function'])) {
                    $t['function'] = 'unknown';
                }
                echo "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    echo get_class($t['object']).'->';
                }
                echo "{$t['function']}()\n";
            }

            echo '</pre>';
        } else {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message</p>\n";
        }
    }

    /**
     * Displays the uncaught PHP exception.
     * This method displays the exception in HTML when there is
     * no active error handler.
     * @param Exception $exception the uncaught exception
     */
    public function displayException($exception)
    {
        if (FLY_DEBUG) {
            echo '<h1>'.get_class($exception)."</h1>\n";
            echo '<p>'.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().')</p>';
            echo '<pre>'.$exception->getTraceAsString().'</pre>';
        } else {
            echo '<h1>'.get_class($exception)."</h1>\n";
            echo '<p>'.$exception->getMessage().'</p>';
        }
    }

    /**
     * Handles uncaught PHP FlyException.
     * This method is implemented as a PHP exception handler. It requires
     * that constant FLY_ENABLE_EXCEPTION_HANDLER be defined true.
     * This method will first trigger an {@link onException} event.
     * If the exception is not handled by any event handler, it will call
     * {@link getErrorHandler errorHandler} to process the exception.
     * The application will be terminated by this method.
     * @param Exception $exception exception that is not caught
     */
    public function handleException($exception)
    {
        // disable error capturing to avoid recursive errors
        restore_error_handler();
        restore_exception_handler();

        $category = 'system.'.get_class($exception);
        if ($exception instanceof HttpException) {
            $category .= '.'.$exception->statusCode;
        }

        // php <5.2 doesn't support string conversion auto-magically
        $message = $exception->__toString();
        if (isset($_SERVER['REQUEST_URI'])) {
            $message .= "\nREQUEST_URI=".$_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $message .= "\nHTTP_REFERER=".$_SERVER['HTTP_REFERER'];
        }
        $message .= "\n---";
        Fly::log('error', $message, $category);

        try {
            $event = new ExceptionEvent($this, $exception);
            $this->onException($event);
            if (!$event->handled) {
                // try an error handler
                if (($handler = $this->getErrorHandler()) !== null) {
                    $handler->handle($event);
                } else {
                    $this->displayException($exception);
                }
            }
        } catch (Exception $e) {
            $this->displayException($e);
        }

        try {
            $this->end(1);
        } catch (Exception $e) {
            // use the most primitive way to log error
            $msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
            $msg .= $e->getTraceAsString()."\n";
            $msg .= "Previous exception:\n";
            $msg .= get_class($exception).': '.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().")\n";
            $msg .= $exception->getTraceAsString()."\n";
            $msg .= '$_SERVER='.var_export($_SERVER, true);
            error_log($msg);
            exit(1);
        }
    }

    /**
     * Handles PHP execution errors such as warnings, notices.
     * This method is implemented as a PHP error handler. It requires
     * that constant FLY_ENABLE_ERROR_HANDLER be defined true.
     * This method will first trigger an {@link onError} event.
     * If the error is not handled by any event handler, it will call
     * {@link getErrorHandler errorHandler} to process the error.
     * The application will be terminated by this method.
     * @param integer $code the level of the error raised
     * @param string $message the error message
     * @param string $file the filename that the error was raised in
     * @param integer $line the line number the error was raised at
     */
    public function handleError($code, $message, $file, $line)
    {
        if ($code & error_reporting()) {
            // disable error capturing to avoid recursive errors
            restore_error_handler();
            restore_exception_handler();

            $log = "$message ($file:$line)\nStack trace:\n";
            $trace = debug_backtrace();

            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3) {
                $trace = array_slice($trace, 3);
            }

            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $t['file'] = 'unknown';
                }
                if (!isset($t['line'])) {
                    $t['line'] = 0;
                }
                if (!isset($t['function'])) {
                    $t['function'] = 'unknown';
                }
                $log .= "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    $log .= get_class($t['object']).'->';
                }
                $log .= "{$t['function']}()\n";
            }
            if (isset($_SERVER['REQUEST_URI'])) {
                $log .= 'REQUEST_URI='.$_SERVER['REQUEST_URI'];
            }
            Fly::log('error', $log, 'php');

            try {
                Fly::import('ErrorEvent', true);
                $event = new ErrorEvent($this, $code, $message, $file, $line);
                $this->onError($event);
                if (!$event->handled) {
                    // try an error handler
                    if (($handler = $this->getErrorHandler()) !== null) {
                        $handler->handle($event);
                    } else {
                        $this->displayError($code, $message, $file, $line);
                    }
                }
            } catch (Exception $e) {
                $this->displayException($e);
            }

            try {
                $this->end(1);
            } catch (Exception $e) {
                // use the most primitive way to log error
                $msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
                $msg .= $e->getTraceAsString()."\n";
                $msg .= "Previous error:\n";
                $msg .= $log."\n";
                $msg .= '$_SERVER='.var_export($_SERVER, true);
                error_log($msg);
                exit(1);
            }
        }
    }

    /**
     * Trigger when an uncaught PHP exception occurs.
     * An event handler can set the {@link ExceptionEvent::handled handled}
     * property of the event parameter to be true to indicate no further error
     * handling is needed. Otherwise, the {@link getErrorHandler errorHandler}
     * application component will continue processing the error.
     * @param ExceptionEvent $event event parameter
     */
    public function onException($event)
    {
        $this->trigger('onException', $event);
    }

    /**
     * Trigger when a PHP execution error occurs.
     * An event handler can set the {@link ErrorEvent::handled handled}
     * property of the event parameter to be true to indicate no further error
     * handling is needed. Otherwise, the {@link getErrorHandler errorHandler}
     * application component will continue processing the error.
     * @param ErrorEvent $event event parameter
     */
    public function onError($event)
    {
        $this->trigger('onError', $event);
    }

    /**
     * Registers the core application components.
     * @see setComponents
     */
    protected function registerCoreComponents()
    {
        $components = array(
            'errorHandler' => array(
                'class' => 'ErrorHandler',
            ),
            'validator' => array(
                'class' => 'Validator',
            ),
        );
        $this->setComponents($components);
    }
}