<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Utf8 Class
 *
 * Provides support for UTF-8 environments
 */
class Utf8
{

    /**
     * Constructor
     *
     * Determines if UTF-8 support is to be enabled
     */
    public function __construct()
    {
        Fly::log('debug', "Utf8 Class Initialized");
        $charset = strtoupper(Fly::getConfig('charset'));
        if (
            preg_match('/./u', 'é') === 1 // PCRE must support UTF-8
            && function_exists('iconv') // iconv must be installed
            && ini_get('mbstring.func_overload') != 1 // Multibyte string function overloading cannot be enabled
            && $charset == 'UTF-8' // Application charset must be UTF-8
        ) {
            Fly::log('debug', "UTF-8 Support Enabled");

            define('UTF8_ENABLED', true);

            // set internal encoding for multibyte string functions if necessary
            // and set a flag so we don't have to repeatedly use extension_loaded()
            // or function_exists()
            if (extension_loaded('mbstring')) {
                define('MB_ENABLED', true);
                mb_internal_encoding('UTF-8');
            } else {
                define('MB_ENABLED', false);
            }
        } else {
            Fly::log('debug', "UTF-8 Support Disabled");
            define('UTF8_ENABLED', false);
        }
    }

    /**
     * Clean UTF-8 strings
     *
     * Ensures strings are UTF-8
     *
     * @param string
     * @return string
     */
    public function cleanString($str)
    {
        if ($this->_is_ascii($str) === false) {
            $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }
        return $str;
    }

    /**
     * Remove ASCII control characters
     *
     * Removes all ASCII control characters except horizontal tabs,
     * line feeds, and carriage returns, as all others can cause
     * problems in XML
     *
     * @param string
     * @return string
     */
    public function safeAsciiForXml($str)
    {
        return StringHelper::removeInvisibleCharacters($str, false);
    }

    /**
     * Convert to UTF-8
     *
     * Attempts to convert a string to UTF-8
     *
     * @param string
     * @param string - input encoding
     * @return string
     */
    function convertToUtf8($str, $encoding)
    {
        if (function_exists('iconv')) {
            $str = @iconv($encoding, 'UTF-8', $str);
        } else if (function_exists('mb_convert_encoding')) {
            $str = @mb_convert_encoding($str, 'UTF-8', $encoding);
        } else {
            return false;
        }

        return $str;
    }

    /**
     * Is ASCII?
     *
     * Tests if a string is standard 7-bit ASCII or not
     *
     * @param string
     * @return bool
     */
    function _is_ascii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
    }
}
