<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Parser Class
 */
class Parser
{

    var $l_delim = '{';
    var $r_delim = '}';
    var $object;

    /**
     *  Parse a template
     *
     * Parses pseudo-variables contained in the specified template view,
     * replacing them with the data in the second param
     *
     * @param string
     * @param array
     * @param bool
     * @return string
     */
    public function parse($template, $data, $return = false)
    {
        $template = Fly::app()->Controller->render($template, $data, true);
        return $this->_parse($template, $data, $return);
    }

    /**
     *  Parse a String
     *
     * Parses pseudo-variables contained in the specified string,
     * replacing them with the data in the second param
     *
     * @param string
     * @param array
     * @param bool
     * @return string
     */
    public function parseString($template, $data, $return = false)
    {
        return $this->_parse($template, $data, $return);
    }

    /**
     *  Parse a template
     *
     * Parses pseudo-variables contained in the specified template,
     * replacing them with the data in the second param
     *
     * @param string
     * @param array
     * @param bool
     * @return string
     */
    protected function _parse($template, $data, $return = false)
    {
        if ($template == '') {
            return false;
        }

        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $template = $this->_parsePair($key, $val, $template);
            } else {
                $template = $this->_parseSingle($key, (string)$val, $template);
            }
        }

        if ($return == false) {
            Fly::app()->Output->appendOutput($template);
        }

        return $template;
    }

    /**
     *  Set the left/right variable delimiters
     *
     * @param string
     * @param string
     * @return void
     */
    public function setDelimiters($l = '{', $r = '}')
    {
        $this->l_delim = $l;
        $this->r_delim = $r;
    }

    /**
     *  Parse a single key/value
     *
     * @param string
     * @param string
     * @param string
     * @return string
     */
    private function _parseSingle($key, $val, $string)
    {
        return str_replace($this->l_delim.$key.$this->r_delim, $val, $string);
    }

    /**
     *  Parse a tag pair
     *
     * Parses tag pairs:  {some_tag} string... {/some_tag}
     *
     * @param string
     * @param array
     * @param string
     * @return string
     */
    private function _parsePair($variable, $data, $string)
    {
        if (false === ($match = $this->_matchPair($string, $variable))) {
            return $string;
        }

        $str = '';
        foreach ($data as $row) {
            $temp = $match['1'];
            foreach ($row as $key => $val) {
                if (!is_array($val)) {
                    $temp = $this->_parseSingle($key, $val, $temp);
                } else {
                    $temp = $this->_parsePair($key, $val, $temp);
                }
            }

            $str .= $temp;
        }

        return str_replace($match['0'], $str, $string);
    }

    /**
     *  Matches a variable pair
     *
     * @param string
     * @param string
     * @return mixed
     */
    private function _matchPair($string, $variable)
    {
        if (!preg_match("|".preg_quote($this->l_delim).$variable.preg_quote($this->r_delim)."(.+?)".preg_quote($this->l_delim).'/'.$variable.preg_quote($this->r_delim)."|s", $string, $match)) {
            return false;
        }

        return $match;
    }
}
