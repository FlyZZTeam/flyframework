<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * String Helpers
 */
class StringHelper
{

    /**
     * Trim Slashes
     *
     * Removes any leading/trailing slashes from a string:
     *
     * /this/that/theother/
     *
     * becomes:
     *
     * this/that/theother
     *
     * @param string
     * @return string
     */
    public static function trimSlashes($str)
    {
        return trim($str, '/');
    }

    /**
     * Strip Slashes
     *
     * Removes slashes contained in a string or in an array
     *
     * @param mixed string or array
     * @return mixed string or array
     */
    public static function stripSlashes($str)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = self::stripSlashes($val);
            }
        } else {
            $str = stripslashes($str);
        }

        return $str;
    }

    /**
     * Strip Quotes
     *
     * Removes single and double quotes from a string
     *
     * @param string
     * @return string
     */
    public static function stripQuotes($str)
    {
        return str_replace(array('"', "'"), '', $str);
    }

    /**
     * Quotes to Entities
     *
     * Converts single and double quotes to entities
     *
     * @param string
     * @return string
     */
    public static function quotesToEntities($str)
    {
        return str_replace(array("\'", "\"", "'", '"'), array("&#39;", "&quot;", "&#39;", "&quot;"), $str);
    }

    /**
     * Reduce Double Slashes
     *
     * Converts double slashes in a string to a single slash,
     * except those found in http://
     *
     * http://www.some-site.com//index.php
     *
     * becomes:
     *
     * http://www.some-site.com/index.php
     *
     * @param string
     * @return string
     */
    public static function reduceDoubleSlashes($str)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $str);
    }

    /**
     * Reduce Multiples
     *
     * Reduces multiple instances of a particular character.  Example:
     *
     * Fred, Bill,, Joe, Jimmy
     *
     * becomes:
     *
     * Fred, Bill, Joe, Jimmy
     *
     * @param string
     * @param string $character the character you wish to reduce
     * @param bool $trim TRUE/FALSE - whether to trim the character from the beginning/end
     * @return string
     */
    function reduceMultiples($str, $character = ',', $trim = false)
    {
        $str = preg_replace('#'.preg_quote($character, '#').'{2,}#', $character, $str);
        if ($trim === true) {
            $str = trim($str, $character);
        }
        return $str;
    }

    /**
     * Create a Random String
     *
     * Useful for generating passwords or hashes.
     *
     * @param string $type type of random string.  basic, alpha, alunum, numeric, nozero, unique, md5, encrypt and sha1
     * @param integer $len number of characters
     * @return string
     */
    public static function randomString($type = 'alnum', $len = 8)
    {
        switch ($type) {
            case 'basic'    :
                return mt_rand();
                break;
            case 'alnum'    :
            case 'numeric'    :
            case 'nozero'    :
            case 'alpha'    :

                switch ($type) {
                    case 'alpha'    :
                        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;
                    case 'alnum'    :
                        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;
                    case 'numeric'    :
                        $pool = '0123456789';
                        break;
                    case 'nozero'    :
                        $pool = '123456789';
                        break;
                }

                $str = '';
                for ($i = 0; $i < $len; $i++) {
                    $str .= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
                }
                return $str;
                break;
            case 'unique'    :
            case 'md5'        :

                return md5(uniqid(mt_rand()));
                break;
            case 'encrypt'    :
            case 'sha1'    :

                $CI =& get_instance();
                $CI->load->helper('security');

                return do_hash(uniqid(mt_rand(), true), 'sha1');
                break;
        }
    }

    /**
     * Add's _1 to a string or increment the ending number to allow _2, _3, etc
     *
     * @param string $str required
     * @param string $separator What should the duplicate number be appended with
     * @param string $first Which number should be used for the first dupe increment
     * @return string
     */
    public static function incrementString($str, $separator = '_', $first = 1)
    {
        preg_match('/(.+)'.$separator.'([0-9]+)$/', $str, $match);
        return isset($match[2]) ? $match[1].$separator.($match[2] + 1) : $str.$separator.$first;
    }

    /**
     * Alternator
     *
     * Allows strings to be alternated.  See docs...
     *
     * @param string (as many parameters as needed)
     * @return string
     */
    public static function alternator()
    {
        static $i;
        if (func_num_args() == 0) {
            $i = 0;
            return '';
        }
        $args = func_get_args();
        return $args[($i++ % count($args))];
    }

    /**
     * Repeater function
     *
     * @param string
     * @param integer $num number of repeats
     * @return string
     */
    public static function repeater($data, $num = 1)
    {
        return (($num > 0) ? str_repeat($data, $num) : '');
    }

    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @param string
     * @return string
     */
    public static function removeInvisibleCharacters($str, $url_encoded = true)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);
        return $str;
    }
}