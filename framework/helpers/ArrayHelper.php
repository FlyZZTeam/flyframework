<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Array Helpers
 */
class ArrayHelper
{

    /**
     * Element
     * Lets you determine whether an array index is set and whether it has a value.
     * If the element is empty it returns FALSE (or whatever you specify as the default value.)
     * @param string
     * @param array
     * @param mixed
     * @return mixed depends on what the array contains
     */
    public static function element($item, $array, $default = false)
    {
        if (!isset($array[$item]) || $array[$item] === "") {
            return $default;
        }
        return $array[$item];
    }

    /**
     * Random Element - Takes an array as input and returns a random element
     * @param array
     * @return mixed depends on what the array contains
     */
    public static function randomElement($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        return $array[array_rand($array)];
    }

    /**
     * Elements
     * Returns only the array items specified.  Will return a default value if
     * it is not set.
     * @param array
     * @param array
     * @param mixed
     * @return mixed depends on what the array contains
     */
    public static function elements($items, $array, $default = false)
    {
        $return = array();
        if (!is_array($items)) {
            $items = array($items);
        }
        foreach ($items as $item) {
            if (isset($array[$item])) {
                $return[$item] = $array[$item];
            } else {
                $return[$item] = $default;
            }
        }
        return $return;
    }
}