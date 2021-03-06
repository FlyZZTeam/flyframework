<?php

/**
 * The application path.
 */
//$config['basePath'] = '';

/**
 * Default Language
 * This determines which set of language files should be used. Make sure
 * there is an available translation if you intend to use something other
 * than english.
 */
$config['language'] = 'en_us';

/**
 * Default Character Set
 * This determines which character set is used by default in various methods
 * that require a character set to be provided.
 */
$config['charset'] = 'UTF-8';

/**
 * Enable/Disable System Hooks
 * If you would like to use the 'hooks' feature you must enable it by
 * setting this variable to TRUE (boolean).  See the user guide for details.
 */
$config['enableHooks'] = true;

/**
 * Enable Query Strings
 * By default CodeIgniter uses search-engine friendly segment based URLs:
 * example.com/who/what/where/
 *
 * By default CodeIgniter enables access to the $_GET array.  If for some
 * reason you would like to disable it, set 'allow_get_array' to FALSE.
 * You can optionally enable standard query string based URLs:
 * example.com?who=me&what=something&where=here
 * Options are: TRUE or FALSE (boolean)
 *
 * The other items let you set the query string 'words' that will
 * invoke your controllers and its functions:
 * example.com/index.php?c=controller&m=function
 *
 * Please note that some of the helpers won't work as expected when
 * this feature is enabled, since CodeIgniter is designed primarily to
 * use segment based URLs.
 */
$config['enableQueryStrings'] = true;

$config['aliasController'] = 'c';
$config['aliasAction'] = 'a';
$config['aliasModule'] = 'm';

/**
 * Cache Directory Path
 * Leave this BLANK unless you would like to set something other than the default
 * application/cache/ folder.  Use a full server path with trailing slash.
 */
$config['cachePath'] = './application/cache/';

/**
 * Encryption Key
 * If you use the Encryption class or the Session class you
 * MUST set an encryption key.  See the user guide for info.
 */
$config['encryptionKey'] = '';

/*
 * Set a prefix if you need to avoid collisions.
 */
$config['cookiePrefix'] = '';

/**
 * Set to .your-domain.com for site-wide cookies.
 */
$config['cookieDomain'] = '';

/**
 * Typically will be a forward slash.
 */
$config['cookiePath'] = '/';

/**
 * Cookies will only be set if a secure HTTPS connection exists.
 */
$config['cookieSecure'] = false;

/**
 * Cookie time expired.
 */
$config['cookieExpire'] = 7200;

/**
 * Cross Site Request Forgery
 * Enables a CSRF cookie token to be set. When set to TRUE, token will be
 * checked on a submitted form. If you are accepting user data, it is strongly
 * recommended CSRF protection be enabled.
 * 'csrfTokenName' = The token name
 * 'csrfCookieName' = The cookie name
 * 'csrfExpire' = The number in seconds the token should expire.
 */
$config['enableCsrfProtection'] = true;
$config['csrfTokenName'] = 'csrf_test_name';
$config['csrfCookieName'] = 'csrf_cookie_name';
$config['csrfExpire'] = 7200;

/**
 * Error Logging Threshold
 * If you have enabled error logging, you can set an error threshold to
 * determine what gets logged. Threshold options are:
 * You can enable error logging by setting a threshold over zero. The
 * threshold determines what gets logged. Threshold options are:
 *      0 = Disables logging, Error logging TURNED OFF
 *      1 = Error Messages (including PHP errors)
 *      2 = Debug Messages
 *      3 = Informational Messages
 *      4 = All Messages
 * For a live site you'll usually only enable Errors (1) to be logged otherwise
 * your log files will fill up very fast.
 */
$config['logThreshold'] = '4';

/**
 * Error Logging Directory Path
 * Leave this BLANK unless you would like to set something other than the default
 * application/runtime/ folder. Use a full server path with trailing slash.
 */
$config['logPath'] = '';

/**
 * Date Format for Logs
 * Each item that is logged has an associated date. You can use PHP date
 * codes to set your own date formatting
 */
$config['logDateFormat'] = 'Y-m-d H:i:s';

/**
 * Rewrite PHP Short Tags
 * If your PHP installation does not have short tag support enabled Fly
 * can rewrite the tags on-the-fly, enabling you to utilize that syntax
 * in your view files.  Options are TRUE or FALSE (boolean)
 */
$config['rewrite_short_tags'] = false;

/*
$config['hooks'] = array(
    'preSystem' => array(
        'alias' => 'application.models.acd',
        'class' => 'a',
        'function' => 'out',
    )
);
*/

/**
 * System timezone
 */
$config['timezone'] = 'PRC';

$config['import'] = array(
    'application.components.*',
    'application.models.*',
    'system.helpers.*',
    'system.libraries.*',
);

$config['aliases'] = array(
    'test' => './application/models/TestValidator',
);

$config['behaviors'] = array( //'TestBehavior' => 'TestBehavior',
);

/**
 * Module settings
 */

//$config['modulePath'] = '';

$config['preload'] = array();

//$config['modulePath'] = './application';

$config['components'] = array(
    'uri' => array(
        'showScriptName' => true,
        'urlSuffix' => '',
        /**
         * Allowed URL Characters
         * This lets you specify with a regular expression which characters are permitted
         * within your URLs.  When someone tries to submit a URL with disallowed
         * characters they will get a warning message.
         * As a security measure you are STRONGLY encouraged to restrict URLs to
         * as few characters as possible.  By default only these are allowed: a-z 0-9~%.:_-
         *
         * Leave blank to allow all characters -- but only if you are insane.
         * DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
         */
        'permittedUriChars' => 'a-z 0-9~%.:_\-',
        /**
         * URI PROTOCOL
         * This item determines which server global should be used to retrieve the
         * URI string.  The default setting of 'AUTO' works for most servers.
         * If your links do not seem to work, try one of the other delicious flavors:
         *      'AUTO' Default - auto detects
         *      'PATH_INFO' Uses the PATH_INFO
         *      'QUERY_STRING' Uses the QUERY_STRING
         *      'REQUEST_URI' Uses the REQUEST_URI
         *      'ORIG_PATH_INFO' Uses the ORIG_PATH_INFO
         * @var string
         */
        'uriProtocol' => 'AUTO',
    ),
    'httpRequest' => array(
        /**
         * By default Fly enables access to the $_GET array.
         * If for some reason you would like to disable it, set 'allowGetArray' to FALSE.
         */
        'allowGetArray' => true,
        /**
         * Determines whether the XSS filter is always active when GET, POST or COOKIE data is encountered
         * Set automatically based on config setting
         */
        'enableXssFilter' => false,
        /**
         * If your server is behind a reverse proxy, you must whitelist the proxy IP
         * addresses from which CodeIgniter should trust the HTTP_X_FORWARDED_FOR
         * header in order to properly identify the visitor's IP address.
         * Comma-delimited, e.g. '10.0.1.200,10.0.1.201'
         */
        'proxyIps' => '',
    ),
    'errorHandler' => array(//'errorAction' => '',
    ),
    'output' => array(
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
        'compressOutput' => false,
        /**
         * Cache Directory Path
         * Leave this BLANK unless you would like to set something other than the default
         * application/cache/ folder.  Use a full server path with trailing slash.
         */
        'cachePath' => '',
    ),
    'session' => array(
        'class' => 'DBHttpSession',
        'sessionTableName' => 'fly_session',
        //$lifetime, $path, $domain, $secure
        'cookieParams' => array(
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => '',
        )
    ),
    'Cache' => array(
        'class' => 'Cache',
        'adapter' => 'file',
        'components' => array(
            'CacheFile' => array('cachePath' => './application/cache/'),
            'CacheMemcached' => array(
                'servers' => array(
                    array(
                        'hostname' => '127.0.0.1',
                        'port' => 11211,
                        'weight' => 1
                    )
                )
            ),
        ),
    ),
);

/**
 * Custom params
 */
$config['params'] = array(
    'siteName' => 'Test FlyFramework',
    'email' => 'zz@flyzz.net',
);

return $config;
