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
 * CodeIgniter XML Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/xml_helper.html
 */

class XmlHelper
{

    /**
     * Convert Reserved XML characters to Entities
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function convert($str, $protect_all = FALSE)
    {
        $temp = '__TEMP_AMPERSANDS__';

        // Replace entities to temporary markers so that
        // ampersands won't get messed up
        $str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);

        if ($protect_all === true) {
            $str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
        }

        $str = str_replace(array("&","<",">","\"", "'", "-"),
                            array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
                            $str);

        // Decode the temp markers back to entities
        $str = preg_replace("/$temp(\d+);/","&#\\1;",$str);

        if ($protect_all === true) {
            $str = preg_replace("/$temp(\w+);/","&\\1;", $str);
        }

        return $str;
    }
}