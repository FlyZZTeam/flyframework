<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Typography Helpers
 */
class TypographyHelper
{

    /**
     * Convert newlines to HTML line breaks except within PRE tags
     *
     * @param string
     * @return string
     */
    public static function nl2brExceptPre($str)
    {
        return Fly::loadClass('system.libraries.Typography')->nl2brExceptPre($str);
    }

    /**
     * Auto Typography Wrapper Function
     *
     * @param string
     * @param bool $strip_js_event_handlers whether to allow javascript event handlers
     * @param bool $reduce_linebreaks whether to reduce multiple instances of double newlines to two
     * @return string
     */
    public static function autoTypography($str, $strip_js_event_handlers = true, $reduce_linebreaks = false)
    {
        return Fly::loadClass('system.libraries.Typography')->autoTypography($str, $strip_js_event_handlers, $reduce_linebreaks);
    }

    /**
     * HTML Entities Decode
     *
     * @param string
     * @return string
     */
    public static function entityDecode($str, $charset = 'UTF-8')
    {
        return Fly::app()->Security->entityDecode($str, $charset);
    }
}