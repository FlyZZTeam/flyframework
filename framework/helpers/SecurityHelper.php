<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Security Helpers
 */
class SecurityHelper
{

    /**
     * XSS Filtering
     *
     * @param string
     * @param bool $is_image whether or not the content is an image file
     * @return string
     */
    public static function cleanXss($str, $is_image = false)
    {
        return Fly::app()->Security->cleanXss($str, $is_image);
    }

    /**
     * Sanitize Filename
     *
     * @param string
     * @return string
     */
    public static function sanitizeFileName($filename)
    {
        return Fly::app()->security->sanitizeFileName($filename);
    }

    /**
     * Hash encode a string
     *
     * @param string
     * @return string
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
     * @param string
     * @return string
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
     * @param string
     * @return string
     */
    public static function encodePhpTags($str)
    {
        return str_replace(array('<?php', '<?PHP', '<?', '?>'), array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
    }
}