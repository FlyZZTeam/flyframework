<?php
/**
 * APPLICATION ENVIRONMENT
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */

define('ENVIRONMENT', 'development');

/**
 * ERROR REPORTING
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
if (ENVIRONMENT == 'development') {
    define('FLY_DEBUG', true);
    error_reporting(E_ALL);
}

require('./framework/Fly.php');
$a = require('./application/config/config.php');
Fly::createWebApplication($a)->run();