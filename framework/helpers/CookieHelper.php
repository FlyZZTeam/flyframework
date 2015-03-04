<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Cookie Helpers
 */
class CookieHelper
{

    /**
     * Set cookie
     * Accepts six parameter, or you can submit an associative
     * array in the first parameter containing all the values.
     * @param mixed
     * @param string $value the value of the cookie
     * @param string $expire the number of seconds until expiration
     * @param string $domain the cookie domain.  Usually:  .yourdomain.com
     * @param string $path the cookie path
     * @param string $prefix the cookie prefix
     * @return void
     */
    public static function setCookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false)
    {
        // Set the config file options
        Fly::app()->Request->setCookie($name, $value, $expire, $domain, $path, $prefix, $secure);
    }

    /**
     * Fetch an item from the COOKIE array
     * @param string
     * @param bool
     * @return mixed
     */
    public static function getCookie($index = '', $xss_clean = false)
    {
        $prefix = '';
        if (!isset($_COOKIE[$index]) && Fly::getConfig('cookie_prefix') != '') {
            $prefix = Fly::getConfig('cookie_prefix');
        }

        return Fly::app()->Request->getCookie($prefix.$index, $xss_clean);
    }

    /**
     * Delete a COOKIE
     * @param mixed
     * @param string $domain the cookie domain.  Usually:  .yourdomain.com
     * @param string $path the cookie path
     * @param string $prefix the cookie prefix
     * @return void
     */
    function deleteCookie($name = '', $domain = '', $path = '/', $prefix = '')
    {
        $this->setCookie($name, '', '', $domain, $path, $prefix);
    }
}