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
 * CodeIgniter Number Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/number_helper.html
 */

class NumberHelper
{

    /**
     * Formats a numbers as bytes, based on size, and adds the appropriate suffix
     *
     * @access	public
     * @param	mixed	// will be cast as int
     * @return	string
     */

    public static function byteFormat($num, $precision = 1)
    {
        Fly::app()->Lang->load('number');

        if ($num >= 1000000000000) {
            $num = round($num / 1099511627776, $precision);
            $unit = Fly::app()->Lang->line('terabyte_abbr');
        } else if ($num >= 1000000000) {
            $num = round($num / 1073741824, $precision);
            $unit = Fly::app()->Lang->line('gigabyte_abbr');
        } else if ($num >= 1000000) {
            $num = round($num / 1048576, $precision);
            $unit = Fly::app()->Lang->line('megabyte_abbr');
        } else if ($num >= 1000) {
            $num = round($num / 1024, $precision);
            $unit = Fly::app()->Lang->line('kilobyte_abbr');
        } else {
            $unit = Fly::app()->Lang->line('bytes');
            return number_format($num).' '.$unit;
        }

        return number_format($num, $precision).' '.$unit;
    }

}


/* End of file number_helper.php */
/* Location: ./system/helpers/number_helper.php */