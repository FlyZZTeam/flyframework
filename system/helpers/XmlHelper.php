<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * XML Helpers
 */
class XmlHelper
{

    /**
     * Convert Reserved XML characters to Entities
     *
     * @param string
     * @return string
     */
    public static function convert($str, $protect_all = false)
    {
        $temp = '__TEMP_AMPERSANDS__';

        // Replace entities to temporary markers so that
        // ampersands won't get messed up
        $str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);

        if ($protect_all === true) {
            $str = preg_replace("/&(\w+);/", "$temp\\1;", $str);
        }

        $str = str_replace(array("&", "<", ">", "\"", "'", "-"),
            array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
            $str);

        // Decode the temp markers back to entities
        $str = preg_replace("/$temp(\d+);/", "&#\\1;", $str);

        if ($protect_all === true) {
            $str = preg_replace("/$temp(\w+);/", "&\\1;", $str);
        }

        return $str;
    }
}