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


/**
 * CodeIgniter String Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/string_helper.html
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
     * @access	public
     * @param	string
     * @return	string
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
     * @access	public
     * @param	mixed	string or array
     * @return	mixed	string or array
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
     * @access	public
     * @param	string
     * @return	string
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
     * @access	public
     * @param	string
     * @return	string
     */
    public static function quotesToEntities($str)
    {
        return str_replace(array("\'","\"","'",'"'), array("&#39;","&quot;","&#39;","&quot;"), $str);
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
     * @access	public
     * @param	string
     * @return	string
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
     * @access	public
     * @param	string
     * @param	string	the character you wish to reduce
     * @param	bool	TRUE/FALSE - whether to trim the character from the beginning/end
     * @return	string
     */
    function reduceMultiples($str, $character = ',', $trim = FALSE)
    {
        $str = preg_replace('#'.preg_quote($character, '#').'{2,}#', $character, $str);
        if ($trim === TRUE) {
            $str = trim($str, $character);
        }
        return $str;
    }

    /**
     * Create a Random String
     *
     * Useful for generating passwords or hashes.
     *
     * @access	public
     * @param	string	type of random string.  basic, alpha, alunum, numeric, nozero, unique, md5, encrypt and sha1
     * @param	integer	number of characters
     * @return	string
     */
    public static function randomString($type = 'alnum', $len = 8)
    {
        switch($type)
        {
            case 'basic'	: return mt_rand();
                break;
            case 'alnum'	:
            case 'numeric'	:
            case 'nozero'	:
            case 'alpha'	:

                    switch ($type)
                    {
                        case 'alpha'	:	$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                            break;
                        case 'alnum'	:	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                            break;
                        case 'numeric'	:	$pool = '0123456789';
                            break;
                        case 'nozero'	:	$pool = '123456789';
                            break;
                    }

                    $str = '';
                    for ($i=0; $i < $len; $i++)
                    {
                        $str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
                    }
                    return $str;
                break;
            case 'unique'	:
            case 'md5'		:

                        return md5(uniqid(mt_rand()));
                break;
            case 'encrypt'	:
            case 'sha1'	:

                        $CI =& get_instance();
                        $CI->load->helper('security');

                        return do_hash(uniqid(mt_rand(), TRUE), 'sha1');
                break;
        }
    }

    /**
     * Add's _1 to a string or increment the ending number to allow _2, _3, etc
     *
     * @param   string  $str  required
     * @param   string  $separator  What should the duplicate number be appended with
     * @param   string  $first  Which number should be used for the first dupe increment
     * @return  string
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
     * @access	public
     * @param	string (as many parameters as needed)
     * @return	string
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
     * @access	public
     * @param	string
     * @param	integer	number of repeats
     * @return	string
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
     * @access	public
     * @param	string
     * @return	string
     */
    public static function removeInvisibleCharacters($str, $url_encoded = true)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);
        return $str;
    }

}