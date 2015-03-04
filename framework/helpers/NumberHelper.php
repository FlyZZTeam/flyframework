<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Number Helpers
 */
class NumberHelper
{

    /**
     * Formats a numbers as bytes, based on size, and adds the appropriate suffix
     * @param mixed
     * @return string
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