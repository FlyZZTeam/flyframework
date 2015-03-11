<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * URL Helpers
 */
class UrlHelper
{

    /**
     * Create a local URL based on your basepath. Segments can be passed via the
     * first parameter either as a string or an array.
     * @param string
     * @return string
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
     * Create a local URL based on your basepath.
     * Segments can be passed in as a string or an array, same as site_url
     * or a URL to a file can be passed in, e.g. to an image file.
     * @param string
     * @return string
     */
    public static function getBaseUrl($absolute = false)
    {
        return Fly::app()->Request->getBaseUrl($absolute);
    }

    /**
     * Returns the full URL (including segments) of the page where this
     * function is placed
     *
     * @return string
     */
    public static function getCurrentUrl($absolute = false)
    {
        $url = Fly::app()->Request->getUrl();
        return $absolute ? Fly::app()->Request->getHostUrl().$url : $url;
    }

    /**
     * URL String
     *
     * Returns the URI segments.
     *
     * @return string
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
     * @param string $uri the URL
     * @param string $title the link title
     * @param mixed $attributes any attributes
     * @return string
     */
    public static function createAnchor($uri = '', $title = '', $attributes = '')
    {
        $title = (string)$title;

        $site_url = self::createUrl($uri, true);

        if ($title == '') {
            $title = $site_url;
        }

        if ($attributes != '') {
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
     * @param string $uri the URL
     * @param string $title the link title
     * @param mixed $attributes any attributes
     * @return    string
     */
    public static function createAnchorPopup($uri = '', $title = '', $attributes = false)
    {
        $title = (string)$title;
        $site_url = self::createUrl($uri, true);

        if ($title == '') {
            $title = $site_url;
        }

        if ($attributes === false) {
            return "<a href='javascript:void(0);' onclick=\"window.open('".$site_url."', '_blank');\">".$title."</a>";
        }

        if (!is_array($attributes)) {
            $attributes = array();
        }

        foreach (array(
                     'width' => '800',
                     'height' => '600',
                     'scrollbars' => 'yes',
                     'status' => 'yes',
                     'resizable' => 'yes',
                     'screenx' => '0',
                     'screeny' => '0',
                 ) as $key => $val) {
            $atts[$key] = (!isset($attributes[$key])) ? $val : $attributes[$key];
            unset($attributes[$key]);
        }

        if ($attributes != '') {
            $attributes = self::parseAttributes($attributes);
        }

        return "<a href='javascript:void(0);' onclick=\"window.open('".$site_url."', '_blank', '".self::parseAttributes($atts, true)."');\"$attributes>".$title."</a>";
    }

    /**
     * Mailto Link
     * @param string $email the email address
     * @param string $title the link title
     * @param mixed $attributes any attributes
     * @return string
     */
    public static function createMailTo($email, $title = '', $attributes = '')
    {
        $title = (string)$title;

        if ($title == "") {
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
     * @param string $email the email address
     * @param string $title the link title
     * @param mixed $attributes any attributes
     * @return string
     */
    public static function createSafeMailTo($email, $title = '', $attributes = '')
    {
        $title = (string)$title;

        if ($title == "") {
            $title = $email;
        }

        for ($i = 0; $i < 16; $i++) {
            $x[] = substr('<a href="mailto:', $i, 1);
        }

        for ($i = 0; $i < strlen($email); $i++) {
            $x[] = "|".ord(substr($email, $i, 1));
        }

        $x[] = '"';

        if ($attributes != '') {
            if (is_array($attributes)) {
                foreach ($attributes as $key => $val) {
                    $x[] = ' '.$key.'="';
                    for ($i = 0; $i < strlen($val); $i++) {
                        $x[] = "|".ord(substr($val, $i, 1));
                    }
                    $x[] = '"';
                }
            } else {
                for ($i = 0; $i < strlen($attributes); $i++) {
                    $x[] = substr($attributes, $i, 1);
                }
            }
        }

        $x[] = '>';

        $temp = array();
        for ($i = 0; $i < strlen($title); $i++) {
            $ordinal = ord($title[$i]);

            if ($ordinal < 128) {
                $x[] = "|".$ordinal;
            } else {
                if (count($temp) == 0) {
                    $count = ($ordinal < 224) ? 2 : 3;
                }

                $temp[] = $ordinal;
                if (count($temp) == $count) {
                    $number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);
                    $x[] = "|".$number;
                    $count = 1;
                    $temp = array();
                }
            }
        }

        $x[] = '<';
        $x[] = '/';
        $x[] = 'a';
        $x[] = '>';

        $x = array_reverse($x);
        ob_start();

        ?>
        <script type="text/javascript">
    //<![CDATA[
    var l=new Array();
    <?php
            $i = 0;
            foreach ($x as $val) {
                ?>l[<?php echo $i++; ?>]='<?php echo $val; ?>';<?php } ?>

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
     * @param string $str the string
     * @param string $type the type: email, url, or both
     * @param bool $popup whether to create pop-up links
     * @return string
     */
    public static function createAutoLink($str, $type = 'both', $popup = false)
    {
        if ($type != 'email') {
            if (preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches)) {
                $pop = ($popup == true) ? " target=\"_blank\" " : "";

                for ($i = 0; $i < count($matches['0']); $i++) {
                    $period = '';
                    if (preg_match("|\.$|", $matches['6'][$i])) {
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

        if ($type != 'url') {
            if (preg_match_all("/([a-zA-Z0-9_\.\-\+]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", $str, $matches)) {
                for ($i = 0; $i < count($matches['0']); $i++) {
                    $period = '';
                    if (preg_match("|\.$|", $matches['3'][$i])) {
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
     * @param string $str the URL
     * @return string
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
     * @param string $str the string
     * @param string $separator the separator
     * @return string
     */
    public static function createUrlTitle($str, $separator = '-', $lowercase = false)
    {
        if ($separator == 'dash') {
            $separator = '-';
        } else if ($separator == 'underscore') {
            $separator = '_';
        }

        $q_separator = preg_quote($separator);

        $trans = array(
            '&.+?;' => '',
            '[^a-z0-9 _-]' => '',
            '\s+' => $separator,
            '('.$q_separator.')+' => $separator
        );

        $str = strip_tags($str);

        foreach ($trans as $key => $val) {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        if ($lowercase === true) {
            $str = strtolower($str);
        }

        return trim($str, $separator);
    }

    /**
     * Header Redirect
     *
     * @param string $url the URL
     * @param string $terminate the method: location or redirect
     * @return string
     */
    public static function redirect($url, $terminate = true, $statusCode = 302)
    {
        Fly::app()->Controller->redirect($url, $terminate, $statusCode);
    }

    public static function refresh($terminate = true, $anchor = '')
    {
        self::redirect(self::getCurrentUrl().$anchor, $terminate);
    }

    /**
     * Parse out the attributes
     *
     * Some of the functions use this
     *
     * @param array
     * @param bool
     * @return string
     */
    public static function parseAttributes($attributes, $javascript = false)
    {
        if (is_string($attributes)) {
            return ($attributes != '') ? ' '.$attributes : '';
        }

        $att = '';
        foreach ($attributes as $key => $val) {
            if ($javascript == true) {
                $att .= $key.'='.$val.',';
            } else {
                $att .= ' '.$key.'="'.$val.'"';
            }
        }

        if ($javascript == true AND $att != '') {
            $att = substr($att, 0, -1);
        }

        return $att;
    }
}