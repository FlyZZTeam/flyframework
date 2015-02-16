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
 * CodeIgniter Typography Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/typography_helper.html
 */

class TypographyHelper
{

    /**
     * Convert newlines to HTML line breaks except within PRE tags
     *
     * @access	public
     * @param	string
     * @return	string
     */
	public static function nl2brExceptPre($str)
	{
        return Fly::loadClass('system.libraries.Typography')->nl2brExceptPre($str);
	}

    /**
     * Auto Typography Wrapper Function
     *
     *
     * @access	public
     * @param	string
     * @param	bool	whether to allow javascript event handlers
     * @param	bool	whether to reduce multiple instances of double newlines to two
     * @return	string
     */
	public static function autoTypography($str, $strip_js_event_handlers = TRUE, $reduce_linebreaks = FALSE)
	{
		return Fly::loadClass('system.libraries.Typography')->autoTypography($str, $strip_js_event_handlers, $reduce_linebreaks);
	}

    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     *
     * @access	public
     * @param	string
     * @return	string
     */
	public static function entityDecode($str, $charset='UTF-8')
    {
		return Fly::app()->Security->entityDecode($str, $charset);
	}

}