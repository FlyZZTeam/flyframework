<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */


/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 */
defined('FLY_DEBUG') || define('FLY_DEBUG', false);

/**
 * This constant defines whether exception handling should be enabled. Defaults to true.
 */
defined('FLY_ENABLE_EXCEPTION_HANDLER') || define('FLY_ENABLE_EXCEPTION_HANDLER', true);

/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 */
defined('FLY_ENABLE_ERROR_HANDLER') || define('FLY_ENABLE_ERROR_HANDLER', true);

/**
 * Fly framework path
 */
defined('FLY_PATH') || define('FLY_PATH', dirname(__FILE__));

defined('BASEPATH') || define('BASEPATH', FLY_PATH);

/**
 * PHP file ext.
 */
defined('EXT') || define('EXT', '.php');

/**
 * FlyBase is a helper class serving common framework functionalities.
 */
class FlyBase
{
    /**
     * @var array class map used by the Fly autoloading mechanism.
     * The array keys are the class names and the array values are the corresponding class file paths.
     */
    public static $classMap = array();
    public static $enableIncludePath = true;
    private static $_aliases = array('system' => FLY_PATH);
    private static $_imports = array(); // alias => class name or directory
    private static $_includePaths; // list of include paths
    private static $_app;
    private static $_instances = array(); //An instance of an object
    private static $_logger;

    /**
     * @return string the version of Fly framework
     */
    public static function getVersion()
    {
        return '1.0';
    }

    /**
     * Create module application.
     * @param $id
     * @param null $config
     * @param string $type
     * @return Application
     */
    public static function createModuleApplication($id, $config = null, $useOwnClass = false, $type = 'WebApplication')
    {
        $id = trim($id);
        if (!is_string($id) || $id === '') {
            throw new FlyException(Fly::t('fly', 'Invalid module application id.'));
        }

        $class = $type;
        if ($useOwnClass) {
            $class = ucfirst($id).$type;
        }
        if (!class_exists($class)) {
            throw new FlyException(Fly::t('fly', 'Class {className} not found', array('{className}' => $class)));
        }

        $config['moduleAppId'] = $id;
        $app = self::createApplication($class, $config);
        return $app;
    }

    /**
     * Returns the WebApplication.
     * @param null $config
     * @return WebApplication
     */
    public static function createWebApplication($config = null)
    {
        return self::createApplication('WebApplication', $config);
    }

    /**
     * Returns the Application.
     * @param $class
     * @param null $config
     * @return Application
     */
    public static function createApplication($class, $config = null)
    {
        return new $class($config);
    }

    public static function createComponent($config)
    {
        if (is_string($config)) {
            $type = $config;
            $config = array();
        } else if (isset($config['class'])) {
            $type = $config['class'];
            unset($config['class']);
        } else {
            throw new FlyException(Fly::t('fly', 'Object configuration must be an array containing a "class" element.'));
        }

        if (!class_exists($type, false)) {
            $type = Fly::import($type, true);
        }

        if (($n = func_num_args()) > 1) {
            $args = func_get_args();
            if ($n === 2) {
                $object = new $type($args[1]);
            } else if ($n === 3) {
                $object = new $type($args[1], $args[2]);
            } else if ($n === 4) {
                $object = new $type($args[1], $args[2], $args[3]);
            } else {
                unset($args[0]);
                $class = new ReflectionClass($type);

                // Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
                // $object=$class->newInstanceArgs($args);
                $object = call_user_func_array(array($class, 'newInstance'), $args);
            }
        } else {
            $object = new $type;
        }
        foreach ($config as $key => $value) {
            $setter = 'set'.$key;
            if (property_exists($object, $key) || method_exists($object, $setter)) {
                $object->$key = $value;
            }
        }
        return $object;
    }

    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return Application the application singleton, null if the singleton has not been created yet.
     */
    public static function app()
    {
        return self::$_app;
    }

    /**
     * @param Application $app the application instance. If this is null, the existing
     * application singleton will be removed.
     * @throws FlyException if multiple application instances are registered.
     */
    public static function setApplication($app)
    {
        if (self::$_app === null || $app === null) {
            self::$_app = $app;
        } else {
            throw new FlyException(Fly::t('fly', 'Fly application can only be created once.'));
        }
    }

    /**
     * @return string the path of the framework
     */
    public static function getFrameworkPath()
    {
        return FLY_PATH;
    }

    /**
     * Translates an alias into a file path.
     * Note, this method does not ensure the existence of the resulting file path.
     * It only checks if the root alias is valid or not.
     * @param string $alias alias (e.g. system.web.Controller)
     * @return mixed file path corresponding to the alias, false if the alias is invalid.
     */
    public static function getPathOfAlias($alias)
    {
        if (isset(self::$_aliases[$alias])) {
            return self::$_aliases[$alias];
        } else if (($pos = strpos($alias, '.')) !== false) {
            $rootAlias = substr($alias, 0, $pos);
            if (isset(self::$_aliases[$rootAlias])) {
                return self::$_aliases[$alias] = rtrim(self::$_aliases[$rootAlias].DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, substr($alias, $pos + 1)), '*'.DIRECTORY_SEPARATOR);
            }
            /*
            else if (self::$_app instanceof WebApplication) {
				if (self::$_app->findModule($rootAlias) !== null) {
					return self::getPathOfAlias($alias);
				}
			}*/
        }
        return false;
    }

    /**
     * Set a path alias.
     * Note, this method neither checks the existence of the path nor normalizes the path.
     * @param string $alias alias to the path
     * @param string $path the path corresponding to the alias. If this is null, the corresponding
     * path alias will be removed.
     */
    public static function setPathOfAlias($alias, $path)
    {
        if (empty($path)) {
            unset(self::$_aliases[$alias]);
        } else {
            self::$_aliases[$alias] = rtrim($path, '\\/');
        }
    }

    /**
     * Imports a class or a directory.
     *
     * Importing a class is like including the corresponding class file.
     * The main difference is that importing a class is much lighter because it only
     * includes the class file when the class is referenced the first time.
     *
     * Importing a directory is equivalent to adding a directory into the PHP include path.
     * If multiple directories are imported, the directories imported later will take
     * precedence in class file searching (i.e., they are added to the front of the PHP include path).
     *
     * Path aliases are used to import a class or directory. For example,
     * <ul>
     *   <li><code>application.components.GoogleMap</code>: import the <code>GoogleMap</code> class.</li>
     *   <li><code>application.components.*</code>: import the <code>components</code> directory.</li>
     * </ul>
     *
     *
     * @param string $alias path alias to be imported
     * @param boolean $forceInclude whether to include the class file immediately. If false, the class file
     * will be included only when the class is being used. This parameter is used only when
     * the path alias refers to a class.
     * @return string the class name or the directory that this alias refers to
     * @throws FlyException if the alias is invalid
     */
    public static function import($alias, $forceInclude = false)
    {
        if (isset(self::$_imports[$alias])) {
            return self::$_imports[$alias];
        }

        if (class_exists($alias, false) || interface_exists($alias, false)) {
            return self::$_imports[$alias] = $alias;
        }

        // a class name in PHP 5.3 namespace format
        if (($pos = strrpos($alias, '\\')) !== false) {
            $namespace = str_replace('\\', '.', ltrim(substr($alias, 0, $pos), '\\'));
            if (($path = self::getPathOfAlias($namespace)) !== false) {
                $classFile = $path.DIRECTORY_SEPARATOR.substr($alias, $pos + 1).'.php';
                if ($forceInclude) {
                    if (is_file($classFile)) {
                        require($classFile);
                    } else {
                        throw new FlyException(Fly::t('fly', 'Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.', array('{alias}' => $alias)));
                    }
                    self::$_imports[$alias] = $alias;
                } else {
                    self::$classMap[$alias] = $classFile;
                }
                return $alias;
            } else {
                throw new FlyException(Fly::t('fly', 'Alias "{alias}" is invalid. Make sure it points to an existing directory.',
                    array('{alias}' => $namespace)));
            }
        }

        // a simple class name
        if (($pos = strrpos($alias, '.')) === false) {
            if ($forceInclude && self::autoload($alias)) {
                self::$_imports[$alias] = $alias;
            }
            return $alias;
        }

        $className = (string)substr($alias, $pos + 1);
        $isClass = $className !== '*';

        if ($isClass && (class_exists($className, false) || interface_exists($className, false))) {
            return self::$_imports[$alias] = $className;
        }

        if (($path = self::getPathOfAlias($alias)) !== false) {
            if ($isClass) {
                if ($forceInclude) {
                    if (is_file($path.'.php')) {
                        require($path.'.php');
                    } else {
                        throw new FlyException(Fly::t('fly', 'Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.', array('{alias}' => $alias)));
                    }
                    self::$_imports[$alias] = $className;
                } else {
                    self::$classMap[$className] = $path.'.php';
                }
                return $className;
            } else {
                if (self::$_includePaths === null) {
                    self::$_includePaths = array_unique(explode(PATH_SEPARATOR, get_include_path()));
                    if (($pos = array_search('.', self::$_includePaths, true)) !== false) {
                        unset(self::$_includePaths[$pos]);
                    }
                }
                array_unshift(self::$_includePaths, $path);
                if (self::$enableIncludePath && set_include_path('.'.PATH_SEPARATOR.implode(PATH_SEPARATOR, self::$_includePaths)) === false) {
                    self::$enableIncludePath = false;
                }
                return self::$_imports[$alias] = $path;
            }
        } else {
            throw new FlyException(Fly::t('fly', 'Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
                array('{alias}' => $alias)));
        }
    }

    /**
     * Class autoload loader.
     * This method is provided to be invoked within an __autoload() magic method.
     * @param string $className class name
     * @return boolean whether the class has been loaded successfully
     */
    public static function autoload($className)
    {
        // use include so that the error PHP file may appear
        if (isset(self::$classMap[$className])) {
            include(self::$classMap[$className]);
        } else if (isset(self::$_coreClasses[$className])) {
            include(FLY_PATH.self::$_coreClasses[$className]);
        } else {
            // include class file relying on include_path
            // class without namespace
            if (strpos($className, '\\') === false) {
                if (self::$enableIncludePath === false) {
                    foreach (self::$_includePaths as $path) {
                        $classFile = $path.DIRECTORY_SEPARATOR.$className.'.php';
                        if (is_file($classFile)) {
                            include($classFile);
                            if (FLY_DEBUG && basename(realpath($classFile)) !== $className.'.php') {
                                throw new FlyException(Fly::t('fly', 'Class name "{class}" does not match class file "{file}".', array(
                                    '{class}' => $className,
                                    '{file}' => $classFile,
                                )));
                            }
                            break;
                        }
                    }
                } else {
                    include($className.'.php');
                }
            } else {
                // class name with namespace in PHP 5.3
                $namespace = str_replace('\\', '.', ltrim($className, '\\'));
                if (($path = self::getPathOfAlias($namespace)) !== false) {
                    include($path.'.php');
                } else {
                    return false;
                }
            }
            return class_exists($className, false) || interface_exists($className, false);
        }
        return true;
    }

    /**
     * Load configuration file
     * It will search 'application' and 'webroot'
     * if defined ENVIRONMENT,it will search 'ENVIRONMENT' on last dir.
     *
     * @param string $alias alias name
     * @param boolean $useSections if configuration values should be loaded into their own section
     * @param boolean $failGracefully true if errors should just return false, false if an error message should be displayed
     * @return boolean if the file was loaded correctly
     */
    public static function loadConfig($alias, $useSections = false, $failGracefully = false)
    {
        return Fly::app()->loadConfig($alias, $useSections, $failGracefully);
    }

    /**
     * Load Class
     * @param $class string
     * @return mixed
     */
    public static function &loadClass($class, $config = array())
    {
        if (!is_string($class)) {
            return null;
        }
        if ($class == '') {
            return null;
        }

        if (isset(self::$_instances[$class])) {
            return self::$_instances[$class];
        }
        Fly::import($class);
        $ary = explode('.', $class);
        if (count($ary) > 1) {
            $className = end($ary);
        } else {
            $className = $class;
        }
        if (class_exists($className)) {
            if (empty($config)) {
                self::$_instances[$class] = new $className;
            } else {
                self::$_instances[$class] = new $className($config);
            }
            return self::$_instances[$class];
        }
        return null;
    }

    /**
     * Returns the model
     * @param string $class The class name. e.g:xxModel,models.xxModel.
     * @param array $config The init config.
     * @return Model
     */
    public static function m($class, $config = array())
    {
        return self::loadClass($class, $config);
    }

    /**
     * Quick access to configuration
     *
     * @param string $key
     * @param string $category
     * @return mixed
     */
    public static function getConfig($key = '', $category = '')
    {
        return self::app()->getConfig($key, $category);
    }

    /**
     * Rapid configuration settings
     *
     * @param $key
     * @param $val
     */
    public static function setConfig($key, $val)
    {
        self::app()->setConfig($key, $val);
    }

    /**
     * Quick Assign config
     * @param array $array
     */
    public function assignConfig($array = array())
    {
        self::app()->assignConfig($array);
    }

    /**
     * Translates a message to the specified language.
     * @param $category
     */
    public static function t($category, $message, $params = array())
    {
        Fly::app()->getLocale()->load($category);
        $message = Fly::app()->getLocale()->line($message);
        if (empty($params)) {
            return $message;
        }

        $keys = array_keys($params);
        $values = array_values($params);
        $message = str_replace($keys, $values, $message);
        return $message;
    }

    /**
     * Writes system info.
     *
     * @param string $level error level
     * @param $msg message to be logged
     * @param string $category category of the message
     * @param bool $php_error php native error
     */
    public static function log($level = 'error', $msg, $category = 'application', $php_error = false)
    {
        if (self::$_logger === null) {
            self::$_logger = new Logger();
        }
        self::$_logger->write($level, $msg, $category, $php_error);
    }

    /**
     * Writes a trace message.
     * This method will only log a message when the application is in debug mode.
     * @param string $msg message to be logged
     * @param string $category category of the message
     */
    public static function trace($msg, $category = 'application')
    {
        if (FLY_DEBUG) {
            self::log('debug', $msg, $category);
        }
    }

    /**
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null) {
            return self::$_logger;
        } else {
            return self::$_logger = new Logger();
        }
    }

    /**
     * Sets the log object.
     * @param Logger $logger the log object.
     */
    public static function setLogger($logger)
    {
        self::$_logger = $logger;
    }

    /**
     * Returns HttpRequest
     * You must be in the application initialization is complete in order to use this method.
     * @return HttpRequest
     * @throws FlyException
     */
    public static function request()
    {
        if (!Fly::app()) {
            throw new FlyException(Fly::t('fly', 'The application has not been initialized, you must be in the application initialization is complete in order to use this method.'));
        }
        return Fly::app()->getRequest();
    }

    /**
     * Returns DB instance
     * @param string $id The active db.
     * @param string $params The db config.
     * @param null $activeRecordOverride use AR?
     * @return DBDriver|DBActiveRecord
     * @throws FlyException
     */
    public static function db($id = 'default', $params = '', $activeRecordOverride = null)
    {
        if (!Fly::app()) {
            throw new FlyException(Fly::t('fly', 'The application has not been initialized, you must be in the application initialization is complete in order to use this method.'));
        }
        return Fly::app()->getDB($id, $params, $activeRecordOverride);
    }

    /**
     * Returns Uri
     * @return Uri
     * @throws FlyException
     */
    public static function uri()
    {
        if (!Fly::app()) {
            throw new FlyException(Fly::t('fly', 'The application has not been initialized, you must be in the application initialization is complete in order to use this method.'));
        }
        return Fly::app()->getUri();
    }

    /**
     * Returns Router
     * @return Router
     * @throws FlyException
     */
    public static function router()
    {
        if (!Fly::app()) {
            throw new FlyException(Fly::t('fly', 'The application has not been initialized, you must be in the application initialization is complete in order to use this method.'));
        }
        return Fly::app()->getRouter();
    }

    /**
     * Returns a string that can be displayed on your Web page showing Powered-by-Fly information
     * @return string a string that can be displayed on your Web page showing Powered-by-Fly information
     */
    public static function powered()
    {
        return '';
    }

    /**
     * Determines if the current version of PHP is greater then the supplied value
     *
     * Since there are a few places where we conditionally test for PHP > 5
     * we'll set a static variable.
     *
     * @param string
     * @return bool TRUE if the current version is $version or higher
     */
    public static function isPhp($version = '5.0.0')
    {
        static $_is_php;
        $version = (string)$version;
        if (!isset($_is_php[$version])) {
            $_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? false : true;
        }
        return $_is_php[$version];
    }

    /**
     * @var array class map for core Fly classes.
     */
    private static $_coreClasses = array(
        'Component' => '/core/Component.php',
        'Module' => '/core/Module.php',
        'Application' => '/core/Application.php',
        'WebApplication' => '/core/WebApplication.php',
        'WebModule' => '/core/WebModule.php',
        'FlyException' => '/core/FlyException.php',
        'ExceptionEvent' => '/core/ExceptionEvent.php',
        'ErrorEvent' => '/core/ErrorEvent.php',
        'ErrorHandler' => '/core/ErrorHandler.php',
        'HttpException' => '/core/HttpException.php',
        'Benchmark' => '/core/Benchmark.php',
        'Hooks' => '/core/Hooks.php',
        'Uri' => '/core/Uri.php',
        'Security' => '/core/Security.php',
        'HttpRequest' => '/core/HttpRequest.php',
        'Controller' => '/core/Controller.php',
        'Router' => '/core/Router.php',
        'Logger' => '/core/Logger.php',
        'Locale' => '/core/Locale.php',
        'Output' => '/core/Output.php',
        'Validator' => '/validators/Validator.php',
        'InlineValidator' => '/validators/InlineValidator.php',
        'Driver' => '/core/Driver.php',
        'DriverManager' => '/core/Driver.php',
        'ApplicationComponent' => '/core/ApplicationComponent.php',
        'Behavior' => '/core/Behavior.php',
        'Model' => '/core/Model.php',
        'ModelEvent' => '/core/ModelEvent.php',
        'Profiler' => '/libraries/Profiler.php',
        'Zip' => '/libraries/Zip.php',
        'UnitTest' => '/libraries/UnitTest.php',
        'Cache' => '/libraries/cache/Cache.php',
        'CacheApc' => '/libraries/cache/drivers/CacheApc.php',
        'CacheDummy' => '/libraries/cache/drivers/CacheDummy.php',
        'CacheFile' => '/libraries/cache/drivers/CacheFile.php',
        'CacheMemcached' => '/libraries/cache/drivers/CacheMemcached.php',
        'HttpSession' => '/libraries/session/HttpSession.php',
        'DBHttpSession' => '/libraries/session/DBHttpSession.php',
        'HttpSessionIterator' => '/libraries/session/HttpSessionIterator.php',
        'Calendar' => '/libraries/Calendar.php',
        'Email' => '/libraries/Email.php',
        'Encrypt' => '/libraries/Encrypt.php',
        'Ftp' => '/libraries/Ftp.php',
        'Javascript' => '/libraries/Javascript.php',
        'Pagination' => '/libraries/Pagination.php',
        'Parser' => '/libraries/Parser.php',
        'Sha1' => '/libraries/Sha1.php',
        'Table' => '/libraries/Table.php',
        'Trackback' => '/libraries/Trackback.php',
        'Typography' => '/libraries/Typography.php',
        'Upload' => '/libraries/Upload.php',
        'UserAgent' => '/libraries/UserAgent.php',
        'XmlRpc' => '/libraries/XmlRpc.php',
        'XmlRpcServer' => '/libraries/XmlRpcServer.php',
        'Jquery' => '/libraries/javascript/Jquery.php',
        'ArrayHelper' => '/helpers/ArrayHelper.php',
        'CookieHelper' => '/helpers/CookieHelper.php',
        'DateHelper' => '/helpers/DateHelper.php',
        'FileHelper' => '/helpers/FileHelper.php',
        'FormHelper' => '/helpers/FormHelper.php',
        'HtmlHelper' => '/helpers/HtmlHelper.php',
        'InflectorHelper' => '/helpers/InflectorHelper.php',
        'NumberHelper' => '/helpers/NumberHelper.php',
        'SecurityHelper' => '/helpers/SecurityHelper.php',
        'StringHelper' => '/helpers/StringHelper.php',
        'TextHelper' => '/helpers/TextHelper.php',
        'TypographyHelper' => '/helpers/TypographyHelper.php',
        'UrlHelper' => '/helpers/UrlHelper.php',
        'XmlHelper' => '/helpers/XmlHelper.php',
        'CaptchaHelper' => '/helpers/CaptchaHelper.php',
        'DB' => '/database/DB.php',
        'DBActiveRecord' => '/database/DBActiveRecord.php',
        'DBCache' => '/database/DBCache.php',
        'DBDriver' => '/database/DBDriver.php',
        'DBResult' => '/database/DBResult.php',
        'DBUtility' => '/database/DBUtility.php',
        /*mysql*/
        'DBMysqlDriver' => '/database/drivers/mysql/DBMysqlDriver.php',
        'DBMysqlResult' => '/database/drivers/mysql/DBMysqlResult.php',
        'DBMysqlUtility' => '/database/drivers/mysql/DBMysqlUtility.php',
        /*mysqli*/
        'DBMysqliDriver' => '/database/drivers/mysqli/DBMysqliDriver.php',
        'DBMysqliResult' => '/database/drivers/mysqli/DBMysqliResult.php',
        'DBMysqliUtility' => '/database/drivers/mysqli/DBMysqliUtility.php',
        /*pdo*/
        'DBPdoDriver' => '/database/drivers/pdo/DBPdoDriver.php',
        'DBPdoResult' => '/database/drivers/pdo/DBPdoResult.php',
        'DBPdoUtility' => '/database/drivers/pdo/DBPdoUtility.php',
        /*sqlite*/
        'DBSqliteDriver' => '/database/drivers/sqlite/DBSqliteDriver.php',
        'DBSqliteResult' => '/database/drivers/sqlite/DBSqliteResult.php',
        'DBSqliteUtility' => '/database/drivers/sqlite/DBSqliteUtility.php',
        /*postgre*/
        'DBPostgreDriver' => '/database/drivers/postgre/DBPostgreDriver.php',
        'DBPostgreResult' => '/database/drivers/postgre/DBPostgreResult.php',
        'DBPostgreUtility' => '/database/drivers/postgre/DBPostgreUtility.php',
        /*sqlsrv*/
        'DBSqlsrvDriver' => '/database/drivers/sqlsrv/DBSqlsrvDriver.php',
        'DBSqlsrvResult' => '/database/drivers/sqlsrv/DBSqlsrvResult.php',
        'DBSqlsrvUtility' => '/database/drivers/sqlsrv/DBSqlsrvUtility.php',
        /*oci8*/
        'DBOci8Driver' => '/database/drivers/oci8/DBOci8Driver.php',
        'DBOci8Result' => '/database/drivers/oci8/DBOci8Result.php',
        'DBOci8Utility' => '/database/drivers/oci8/DBOci8Utility.php',
        /*odbc*/
        'DBOdbcDriver' => '/database/drivers/odbc/DBOdbcDriver.php',
        'DBOdbcResult' => '/database/drivers/odbc/DBOdbcResult.php',
        'DBOdbcUtility' => '/database/drivers/odbc/DBOdbcUtility.php',

    );
}

spl_autoload_register(array('FlyBase', 'autoload'));
require(FLY_PATH.'/core/Interfaces.php');

?>
