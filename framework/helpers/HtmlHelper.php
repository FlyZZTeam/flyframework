<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * HTML Helpers
 */
class HtmlHelper
{

    /**
     * Heading
     *
     * Generates an HTML heading tag.  First param is the data.
     * Second param is the size of the heading tag.
     *
     * @param string
     * @param integer
     * @return string
     */
    public static function heading($data = '', $h = '1', $attributes = '')
    {
        $attributes = ($attributes != '') ? ' '.$attributes : $attributes;
        return "<h".$h.$attributes.">".$data."</h".$h.">";
    }

    /**
     * Unordered List
     *
     * Generates an HTML unordered list from an single or multi-dimensional array.
     *
     * @param array
     * @param mixed
     * @return string
     */
    public static function ul($list, $attributes = '')
    {
        return self::setList('ul', $list, $attributes);
    }

    /**
     * Ordered List
     *
     * Generates an HTML ordered list from an single or multi-dimensional array.
     *
     * @param array
     * @param mixed
     * @return string
     */
    public static function ol($list, $attributes = '')
    {
        return self::setList('ol', $list, $attributes);
    }

    /**
     * Generates the list
     *
     * Generates an HTML ordered list from an single or multi-dimensional array.
     *
     * @param string
     * @param mixed
     * @param mixed
     * @param integer
     * @return string
     */
    public static function setList($type = 'ul', $list, $attributes = '', $depth = 0)
    {
        // If an array wasn't submitted there's nothing to do...
        if (!is_array($list)) {
            return $list;
        }

        // Set the indentation based on the depth
        $out = str_repeat(" ", $depth);

        // Were any attributes submitted?  If so generate a string
        if (is_array($attributes)) {
            $atts = '';
            foreach ($attributes as $key => $val) {
                $atts .= ' '.$key.'="'.$val.'"';
            }
            $attributes = $atts;
        } else if (is_string($attributes) AND strlen($attributes) > 0) {
            $attributes = ' '.$attributes;
        }

        // Write the opening list tag
        $out .= "<".$type.$attributes.">\n";

        // Cycle through the list elements.  If an array is
        // encountered we will recursively call _list()

        static $_last_list_item = '';

        foreach ($list as $key => $val) {
            $_last_list_item = $key;

            $out .= str_repeat(" ", $depth + 2);
            $out .= "<li>";

            if (!is_array($val)) {
                $out .= $val;
            } else {
                $out .= $_last_list_item."\n";
                $out .= self::setList($type, $val, '', $depth + 4);
                $out .= str_repeat(" ", $depth + 2);
            }

            $out .= "</li>\n";
        }

        // Set the indentation for the closing tag
        $out .= str_repeat(" ", $depth);

        // Write the closing list tag
        $out .= "</".$type.">\n";

        return $out;
    }

    /**
     * Generates HTML BR tags based on number supplied
     *
     * @param integer
     * @return string
     */
    public static function br($num = 1)
    {
        return str_repeat("<br />", $num);
    }

    /**
     * Image
     * Generates an <img /> element
     * @param mixed
     * @return string
     */
    public static function img($src = '')
    {
        if (!is_array($src)) {
            $src = array('src' => $src);
        }

        // If there is no alt attribute defined, set it to an empty string
        if (!isset($src['alt'])) {
            $src['alt'] = '';
        }

        $img = '<img';

        foreach ($src as $k => $v) {

            if ($k == 'src' && strpos($v, '://') === false) {
                $img .= ' src="'.$v.'"';
            } else {
                $img .= " $k=\"$v\"";
            }
        }
        $img .= '/>';
        return $img;
    }

    /**
     * Doctype
     *
     * Generates a page document type declaration
     *
     * Valid options are xhtml-11, xhtml-strict, xhtml-trans, xhtml-frame,
     * html4-strict, html4-trans, and html4-frame.  Values are saved in the
     * doctypes config file.
     *
     * @param string $type The doctype to be generated
     * @return string
     */
    public static function doctype($type = 'xhtml1-strict')
    {
        Fly::loadConfig('config.doctypes', true);
        $doctypes = Fly::getConfig('doctypes');
        if (!is_array($doctypes)) {
            return false;
        }

        if (isset($doctypes[$type])) {
            return $doctypes[$type];
        } else {
            return false;
        }
    }

    /**
     * Link
     *
     * Generates link to a CSS file
     *
     * @param mixed $href stylesheet hrefs or an array
     * @param string $rel
     * @param string $type
     * @param string $title
     * @param string $media
     * @return string
     */
    public static function linkTag($href = '', $rel = 'stylesheet', $type = 'text/css', $title = '', $media = '')
    {

        $link = '<link ';

        if (is_array($href)) {
            foreach ($href as $k => $v) {
                if ($k == 'href' && strpos($v, '://') === false) {
                    $link .= 'href="'.$v.'" ';
                } else {
                    $link .= "$k=\"$v\" ";
                }
            }

            $link .= "/>";
        } else {
            $link .= 'href="'.$href.'" ';
            $link .= 'rel="'.$rel.'" type="'.$type.'" ';
            if ($media != '') {
                $link .= 'media="'.$media.'" ';
            }

            if ($title != '') {
                $link .= 'title="'.$title.'" ';
            }

            $link .= '/>';
        }

        return $link;
    }

    /**
     * Generates meta tags from an array of key/values
     * @param array
     * @return string
     */
    public static function meta($name = '', $content = '', $type = 'name', $newline = "\n")
    {
        // Since we allow the data to be passes as a string, a simple array
        // or a multidimensional one, we need to do a little prepping.
        if (!is_array($name)) {
            $name = array(array('name' => $name, 'content' => $content, 'type' => $type, 'newline' => $newline));
        } else {
            // Turn single array into multidimensional
            if (isset($name['name'])) {
                $name = array($name);
            }
        }

        $str = '';
        foreach ($name as $meta) {
            $type = (!isset($meta['type']) || $meta['type'] == 'name') ? 'name' : 'http-equiv';
            $name = (!isset($meta['name'])) ? '' : $meta['name'];
            $content = (!isset($meta['content'])) ? '' : $meta['content'];
            $newline = (!isset($meta['newline'])) ? "\n" : $meta['newline'];

            $str .= '<meta '.$type.'="'.$name.'" content="'.$content.'" />'.$newline;
        }

        return $str;
    }

    /**
     * Generates non-breaking space entities based on number supplied
     * @param integer
     * @return string
     */
    public static function nbs($num = 1)
    {
        return str_repeat("&nbsp;", $num);
    }

    /**
     *
     * @param $var
     * @return array|string
     */
    public static function encode($var)
    {
        if (is_array($var)) {
            return array_map(array('HtmlHelper', 'encode'), $var);
        } else {
            return htmlspecialchars($var, ENT_QUOTES, FLy::getConfig('charset'));
        }
    }
}