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
 * CodeIgniter URL Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/url_helper.html
 */

class UrlHelper
{

    /**
     * Site URL
     *
     * Create a local URL based on your basepath. Segments can be passed via the
     * first parameter either as a string or an array.
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function createUrl($uri, $absolute = false)
    {
        if (is_array($uri)) {
            $route = isset($uri[0]) ? $uri[0] : '';
            $params = array_splice($uri, 1);
        } else {
            if (preg_match('!^\w+://! i', $uri)) {
                return $uri;
            }
            $route = $uri;
            $params = array();
        }
        if ($absolute) {
            $url = Fly::app()->Controller->createAbsoluteUrl($route, $params);
        } else {
            $url = Fly::app()->Controller->createUrl($route, $params);
        }
        return $url;
    }

    /**
     * Base URL
     *
     * Create a local URL based on your basepath.
     * Segments can be passed in as a string or an array, same as site_url
     * or a URL to a file can be passed in, e.g. to an image file.
     *
     * @access	public
     * @param string
     * @return	string
     */
    public static function getBaseUrl($absolute = false)
    {
        return Fly::app()->Request->getBaseUrl($absolute);
    }

    /**
     * Current URL
     *
     * Returns the full URL (including segments) of the page where this
     * function is placed
     *
     * @access	public
     * @return	string
     */
    public static function getCurrentUrl($absolute = false)
    {
        $url = Fly::app()->Request->getUrl();
        return $absolute ? Fly::app()->Request->getHostUrl() . $url : $url;
    }

    /**
     * URL String
     *
     * Returns the URI segments.
     *
     * @access	public
     * @return	string
     */
    public static function getUriString()
    {
        return Fly::app()->Uri->getUriString();
    }

    /**
     * Anchor Link
     *
     * Creates an anchor based on the local URL.
     *
     * @access	public
     * @param	string	the URL
     * @param	string	the link title
     * @param	mixed	any attributes
     * @return	string
     */
    public static function createAnchor($uri = '', $title = '', $attributes = '')
    {
        $title = (string) $title;

        $site_url = self::createUrl($uri, true);

        if ($title == '')
        {
            $title = $site_url;
        }

        if ($attributes != '')
        {
            $attributes = self::parseAttributes($attributes);
        }

        return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
    }

    /**
     * Anchor Link - Pop-up version
     *
     * Creates an anchor based on the local URL. The link
     * opens a new window based on the attributes specified.
     *
     * @access	public
     * @param	string	the URL
     * @param	string	the link title
     * @param	mixed	any attributes
     * @return	string
     */
	public static function createAnchorPopup($uri = '', $title = '', $attributes = FALSE)
	{
		$title = (string) $title;
        $site_url = self::createUrl($uri, true);

		if ($title == '')
		{
			$title = $site_url;
		}

		if ($attributes === FALSE)
		{
			return "<a href='javascript:void(0);' onclick=\"window.open('".$site_url."', '_blank');\">".$title."</a>";
		}

		if ( ! is_array($attributes))
		{
			$attributes = array();
		}

		foreach (array('width' => '800', 'height' => '600', 'scrollbars' => 'yes', 'status' => 'yes', 'resizable' => 'yes', 'screenx' => '0', 'screeny' => '0', ) as $key => $val)
		{
			$atts[$key] = ( ! isset($attributes[$key])) ? $val : $attributes[$key];
			unset($attributes[$key]);
		}

		if ($attributes != '')
		{
			$attributes = self::parseAttributes($attributes);
		}

		return "<a href='javascript:void(0);' onclick=\"window.open('".$site_url."', '_blank', '".self::parseAttributes($atts, TRUE)."');\"$attributes>".$title."</a>";
	}

    /**
     * Mailto Link
     *
     * @access	public
     * @param	string	the email address
     * @param	string	the link title
     * @param	mixed	any attributes
     * @return	string
     */
    public static function createMailTo($email, $title = '', $attributes = '')
    {
        $title = (string) $title;

        if ($title == "")
        {
            $title = $email;
        }

        $attributes = self::parseAttributes($attributes);

        return '<a href="mailto:'.$email.'"'.$attributes.'>'.$title.'</a>';
    }

    /**
     * Encoded Mailto Link
     *
     * Create a spam-protected mailto link written in Javascript
     *
     * @access	public
     * @param	string	the email address
     * @param	string	the link title
     * @param	mixed	any attributes
     * @return	string
     */
    public static function createSafeMailTo($email, $title = '', $attributes = '')
    {
        $title = (string) $title;

        if ($title == "")
        {
            $title = $email;
        }

        for ($i = 0; $i < 16; $i++)
        {
            $x[] = substr('<a href="mailto:', $i, 1);
        }

        for ($i = 0; $i < strlen($email); $i++)
        {
            $x[] = "|".ord(substr($email, $i, 1));
        }

        $x[] = '"';

        if ($attributes != '')
        {
            if (is_array($attributes))
            {
                foreach ($attributes as $key => $val)
                {
                    $x[] =  ' '.$key.'="';
                    for ($i = 0; $i < strlen($val); $i++)
                    {
                        $x[] = "|".ord(substr($val, $i, 1));
                    }
                    $x[] = '"';
                }
            }
            else
            {
                for ($i = 0; $i < strlen($attributes); $i++)
                {
                    $x[] = substr($attributes, $i, 1);
                }
            }
        }

        $x[] = '>';

        $temp = array();
        for ($i = 0; $i < strlen($title); $i++)
        {
            $ordinal = ord($title[$i]);

            if ($ordinal < 128)
            {
                $x[] = "|".$ordinal;
            }
            else
            {
                if (count($temp) == 0)
                {
                    $count = ($ordinal < 224) ? 2 : 3;
                }

                $temp[] = $ordinal;
                if (count($temp) == $count)
                {
                    $number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);
                    $x[] = "|".$number;
                    $count = 1;
                    $temp = array();
                }
            }
        }

        $x[] = '<'; $x[] = '/'; $x[] = 'a'; $x[] = '>';

        $x = array_reverse($x);
        ob_start();

    ?><script type="text/javascript">
    //<![CDATA[
    var l=new Array();
    <?php
    $i = 0;
    foreach ($x as $val){ ?>l[<?php echo $i++; ?>]='<?php echo $val; ?>';<?php } ?>

    for (var i = l.length-1; i >= 0; i=i-1){
    if (l[i].substring(0, 1) == '|') document.write("&#"+unescape(l[i].substring(1))+";");
    else document.write(unescape(l[i]));}
    //]]>
    </script><?php

        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    /**
     * Auto-linker
     *
     * Automatically links URL and Email addresses.
     * Note: There's a bit of extra code here to deal with
     * URLs or emails that end in a period.  We'll strip these
     * off and add them after the link.
     *
     * @access	public
     * @param	string	the string
     * @param	string	the type: email, url, or both
     * @param	bool	whether to create pop-up links
     * @return	string
     */
    public static function createAutoLink($str, $type = 'both', $popup = FALSE)
    {
        if ($type != 'email')
        {
            if (preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
            {
                $pop = ($popup == TRUE) ? " target=\"_blank\" " : "";

                for ($i = 0; $i < count($matches['0']); $i++)
                {
                    $period = '';
                    if (preg_match("|\.$|", $matches['6'][$i]))
                    {
                        $period = '.';
                        $matches['6'][$i] = substr($matches['6'][$i], 0, -1);
                    }

                    $str = str_replace($matches['0'][$i],
                                        $matches['1'][$i].'<a href="http'.
                                        $matches['4'][$i].'://'.
                                        $matches['5'][$i].
                                        $matches['6'][$i].'"'.$pop.'>http'.
                                        $matches['4'][$i].'://'.
                                        $matches['5'][$i].
                                        $matches['6'][$i].'</a>'.
                                        $period, $str);
                }
            }
        }

        if ($type != 'url')
        {
            if (preg_match_all("/([a-zA-Z0-9_\.\-\+]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", $str, $matches))
            {
                for ($i = 0; $i < count($matches['0']); $i++)
                {
                    $period = '';
                    if (preg_match("|\.$|", $matches['3'][$i]))
                    {
                        $period = '.';
                        $matches['3'][$i] = substr($matches['3'][$i], 0, -1);
                    }

                    $str = str_replace($matches['0'][$i], self::createSafeMailTo($matches['1'][$i].'@'.$matches['2'][$i].'.'.$matches['3'][$i]).$period, $str);
                }
            }
        }

        return $str;
    }

    /**
     * Prep URL
     *
     * Simply adds the http:// part if no scheme is included
     *
     * @access	public
     * @param	string	the URL
     * @return	string
     */
    public static function prepUrl($str = '')
    {
        if ($str == 'http://' || $str == '') {
            return '';
        }
        $url = parse_url($str);
        if (!$url || !isset($url['scheme'])) {
            $str = 'http://'.$str;
        }
        return $str;
    }

    /**
     * Create URL Title
     *
     * Takes a "title" string as input and creates a
     * human-friendly URL string with a "separator" string
     * as the word separator.
     *
     * @access	public
     * @param	string	the string
     * @param	string	the separator
     * @return	string
     */
    public static function createUrlTitle($str, $separator = '-', $lowercase = FALSE)
    {
        if ($separator == 'dash') {
            $separator = '-';
        } else if ($separator == 'underscore') {
            $separator = '_';
        }

        $q_separator = preg_quote($separator);

        $trans = array(
            '&.+?;'                 => '',
            '[^a-z0-9 _-]'          => '',
            '\s+'                   => $separator,
            '('.$q_separator.')+'   => $separator
        );

        $str = strip_tags($str);

        foreach ($trans as $key => $val) {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        if ($lowercase === TRUE) {
            $str = strtolower($str);
        }

        return trim($str, $separator);
    }

    /**
     * Header Redirect
     *
     * Header redirect in two flavors
     * For very fine grained control over headers, you could use the Output
     * Library's set_header() function.
     *
     * @access	public
     * @param	string	the URL
     * @param	string	the method: location or redirect
     * @return	string
     */
    public static function redirect($url, $terminate = true, $statusCode = 302)
    {
        Fly::app()->Controller->redirect($url, $terminate, $statusCode);
    }

    public static function refresh($terminate = true, $anchor='')
    {
        self::redirect(self::getCurrentUrl().$anchor, $terminate);
    }

    /**
     * Parse out the attributes
     *
     * Some of the functions use this
     *
     * @access	private
     * @param	array
     * @param	bool
     * @return	string
     */
    public static function parseAttributes($attributes, $javascript = FALSE)
    {
        if (is_string($attributes)) {
            return ($attributes != '') ? ' '.$attributes : '';
        }

        $att = '';
        foreach ($attributes as $key => $val) {
            if ($javascript == TRUE) {
                $att .= $key . '=' . $val . ',';
            } else {
                $att .= ' ' . $key . '="' . $val . '"';
            }
        }

        if ($javascript == TRUE AND $att != '') {
            $att = substr($att, 0, -1);
        }

        return $att;
    }
}