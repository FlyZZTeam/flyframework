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
 * CodeIgniter Security Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/security_helper.html
 */

class SecurityHelper
{

    /**
     * XSS Filtering
     *
     * @access	public
     * @param	string
     * @param	bool	whether or not the content is an image file
     * @return	string
     */
    public static function cleanXss($str, $is_image = FALSE)
    {
        return Fly::app()->Security->cleanXss($str, $is_image);
    }

    /**
     * Sanitize Filename
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function sanitizeFileName($filename)
    {
        return Fly::app()->security->sanitizeFileName($filename);
    }

    /**
     * Hash encode a string
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function hash($str, $type = 'sha1')
    {
        if ($type == 'sha1') {
            return sha1($str);
        } else {
            return md5($str);
        }
    }

    /**
     * Strip Image Tags
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function stripImageTags($str)
    {
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);

        return $str;
    }

    /**
     * Convert PHP tags to entities
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function encodePhpTags($str)
    {
        return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
    }

}