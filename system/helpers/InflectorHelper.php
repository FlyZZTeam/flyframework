<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Inflector Helpers
 */
class InflectorHelper
{

    /**
     * Singular
     *
     * Takes a plural word and makes it singular
     *
     * @param string
     * @return str
     */
    public static function singular($str)
    {
        $result = strval($str);

        $singular_rules = array(
            '/(matr)ices$/' => '\1ix',
            '/(vert|ind)ices$/' => '\1ex',
            '/^(ox)en/' => '\1',
            '/(alias)es$/' => '\1',
            '/([octop|vir])i$/' => '\1us',
            '/(cris|ax|test)es$/' => '\1is',
            '/(shoe)s$/' => '\1',
            '/(o)es$/' => '\1',
            '/(bus|campus)es$/' => '\1',
            '/([m|l])ice$/' => '\1ouse',
            '/(x|ch|ss|sh)es$/' => '\1',
            '/(m)ovies$/' => '\1\2ovie',
            '/(s)eries$/' => '\1\2eries',
            '/([^aeiouy]|qu)ies$/' => '\1y',
            '/([lr])ves$/' => '\1f',
            '/(tive)s$/' => '\1',
            '/(hive)s$/' => '\1',
            '/([^f])ves$/' => '\1fe',
            '/(^analy)ses$/' => '\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
            '/([ti])a$/' => '\1um',
            '/(p)eople$/' => '\1\2erson',
            '/(m)en$/' => '\1an',
            '/(s)tatuses$/' => '\1\2tatus',
            '/(c)hildren$/' => '\1\2hild',
            '/(n)ews$/' => '\1\2ews',
            '/([^u])s$/' => '\1',
        );

        foreach ($singular_rules as $rule => $replacement) {
            if (preg_match($rule, $result)) {
                $result = preg_replace($rule, $replacement, $result);
                break;
            }
        }
        return $result;
    }

    /**
     * Plural
     *
     * Takes a singular word and makes it plural
     *
     * @param string
     * @param bool
     * @return str
     */

    public static function plural($str, $force = false)
    {
        $result = strval($str);

        $plural_rules = array(
            '/^(ox)$/' => '\1\2en', // ox
            '/([m|l])ouse$/' => '\1ice', // mouse, louse
            '/(matr|vert|ind)ix|ex$/' => '\1ices', // matrix, vertex, index
            '/(x|ch|ss|sh)$/' => '\1es', // search, switch, fix, box, process, address
            '/([^aeiouy]|qu)y$/' => '\1ies', // query, ability, agency
            '/(hive)$/' => '\1s', // archive, hive
            '/(?:([^f])fe|([lr])f)$/' => '\1\2ves', // half, safe, wife
            '/sis$/' => 'ses', // basis, diagnosis
            '/([ti])um$/' => '\1a', // datum, medium
            '/(p)erson$/' => '\1eople', // person, salesperson
            '/(m)an$/' => '\1en', // man, woman, spokesman
            '/(c)hild$/' => '\1hildren', // child
            '/(buffal|tomat)o$/' => '\1\2oes', // buffalo, tomato
            '/(bu|campu)s$/' => '\1\2ses', // bus, campus
            '/(alias|status|virus)/' => '\1es', // alias
            '/(octop)us$/' => '\1i', // octopus
            '/(ax|cris|test)is$/' => '\1es', // axis, crisis
            '/s$/' => 's', // no change (compatibility)
            '/$/' => 's',
        );

        foreach ($plural_rules as $rule => $replacement) {
            if (preg_match($rule, $result)) {
                $result = preg_replace($rule, $replacement, $result);
                break;
            }
        }

        return $result;
    }

    /**
     * Camelize
     *
     * Takes multiple words separated by spaces or underscores and camelizes them
     *
     * @param string
     * @return str
     */
    public static function camelize($str)
    {
        $str = 'x'.strtolower(trim($str));
        $str = ucwords(preg_replace('/[\s_]+/', ' ', $str));
        return substr(str_replace(' ', '', $str), 1);
    }

    /**
     * Underscore
     *
     * Takes multiple words separated by spaces and underscores them
     *
     * @param string
     * @return str
     */
    public static function underscore($str)
    {
        return preg_replace('/[\s]+/', '_', strtolower(trim($str)));
    }

    /**
     * Humanize
     *
     * Takes multiple words separated by underscores and changes them to spaces
     *
     * @param string
     * @return str
     */
    public static function humanize($str)
    {
        return ucwords(preg_replace('/[_]+/', ' ', strtolower(trim($str))));
    }
}