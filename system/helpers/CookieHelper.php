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
 * CodeIgniter Cookie Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/cookie_helper.html
 */

// ------------------------------------------------------------------------

class CookieHelper
{

    /**
     * Set cookie
     *
     * Accepts six parameter, or you can submit an associative
     * array in the first parameter containing all the values.
     *
     * @access	public
     * @param	mixed
     * @param	string	the value of the cookie
     * @param	string	the number of seconds until expiration
     * @param	string	the cookie domain.  Usually:  .yourdomain.com
     * @param	string	the cookie path
     * @param	string	the cookie prefix
     * @return	void
     */
    public static function setCookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false)
    {
        // Set the config file options
        Fly::app()->Request->setCookie($name, $value, $expire, $domain, $path, $prefix, $secure);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @access	public
     * @param	string
     * @param	bool
     * @return	mixed
     */
    public static function getCookie($index = '', $xss_clean = false)
    {
        $prefix = '';
        if ( ! isset($_COOKIE[$index]) && Fly::getConfig('cookie_prefix') != '') {
            $prefix = Fly::getConfig('cookie_prefix');
        }

        return Fly::app()->Request->getCookie($prefix.$index, $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Delete a COOKIE
     *
     * @param	mixed
     * @param	string	the cookie domain.  Usually:  .yourdomain.com
     * @param	string	the cookie path
     * @param	string	the cookie prefix
     * @return	void
     */
    function deleteCookie($name = '', $domain = '', $path = '/', $prefix = '')
    {
        $this->setCookie($name, '', '', $domain, $path, $prefix);
    }

}

/* End of file cookie_helper.php */
/* Location: ./system/helpers/cookie_helper.php */