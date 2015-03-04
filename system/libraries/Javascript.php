<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Javascript Class
 */
class Javascript
{

    var $_javascript_location = 'js';

    public function __construct($params = array())
    {
        $defaults = array('js_library_driver' => 'jquery', 'autoload' => true);

        foreach ($defaults as $key => $val) {
            if (isset($params[$key]) && $params[$key] !== "") {
                $defaults[$key] = $params[$key];
            }
        }

        extract($defaults);

        // make js to refer to current library
        $this->js =& Fly::loadClass('system.libraries.javascript.'.$js_library_driver, array('autoload' => $autoload));

        Fly::log('debug', "Javascript Class Initialized and loaded.  Driver used: $js_library_driver");
    }

    /**
     * Blur
     *
     * Outputs a javascript library blur event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function blur($element = 'this', $js = '')
    {
        return $this->js->_blur($element, $js);
    }

    /**
     * Change
     *
     * Outputs a javascript library change event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function change($element = 'this', $js = '')
    {
        return $this->js->_change($element, $js);
    }

    /**
     * Click
     *
     * Outputs a javascript library click event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @param boolean whether or not to return false
     * @return string
     */
    public function click($element = 'this', $js = '', $ret_false = true)
    {
        return $this->js->_click($element, $js, $ret_false);
    }

    /**
     * Double Click
     *
     * Outputs a javascript library dblclick event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function dblclick($element = 'this', $js = '')
    {
        return $this->js->_dblclick($element, $js);
    }

    /**
     * Error
     *
     * Outputs a javascript library error event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function error($element = 'this', $js = '')
    {
        return $this->js->_error($element, $js);
    }

    /**
     * Focus
     *
     * Outputs a javascript library focus event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function focus($element = 'this', $js = '')
    {
        return $this->js->_addEvent($focus, $js);
    }

    /**
     * Hover
     *
     * Outputs a javascript library hover event
     *
     * @param string - element
     * @param string - Javascript code for mouse over
     * @param string - Javascript code for mouse out
     * @return string
     */
    public function hover($element = 'this', $over, $out)
    {
        return $this->js->_hover($element, $over, $out);
    }

    /**
     * Keydown
     *
     * Outputs a javascript library keydown event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function keydown($element = 'this', $js = '')
    {
        return $this->js->_keydown($element, $js);
    }

    /**
     * Keyup
     *
     * Outputs a javascript library keydown event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function keyup($element = 'this', $js = '')
    {
        return $this->js->_keyup($element, $js);
    }

    /**
     * Load
     *
     * Outputs a javascript library load event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function load($element = 'this', $js = '')
    {
        return $this->js->_load($element, $js);
    }

    /**
     * Mousedown
     *
     * Outputs a javascript library mousedown event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function mousedown($element = 'this', $js = '')
    {
        return $this->js->_mousedown($element, $js);
    }

    /**
     * Mouse Out
     *
     * Outputs a javascript library mouseout event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function mouseout($element = 'this', $js = '')
    {
        return $this->js->_mouseout($element, $js);
    }

    /**
     * Mouse Over
     *
     * Outputs a javascript library mouseover event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function mouseover($element = 'this', $js = '')
    {
        return $this->js->_mouseover($element, $js);
    }

    /**
     * Mouseup
     *
     * Outputs a javascript library mouseup event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function mouseup($element = 'this', $js = '')
    {
        return $this->js->_mouseup($element, $js);
    }

    /**
     * Output
     *
     * Outputs the called javascript to the screen
     *
     * @param string $js The code to output
     * @return string
     */
    public function output($js)
    {
        return $this->js->_output($js);
    }

    /**
     * Ready
     *
     * Outputs a javascript library mouseup event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function ready($js)
    {
        return $this->js->_documentReady($js);
    }

    /**
     * Resize
     *
     * Outputs a javascript library resize event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function resize($element = 'this', $js = '')
    {
        return $this->js->_resize($element, $js);
    }

    /**
     * Scroll
     *
     * Outputs a javascript library scroll event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function scroll($element = 'this', $js = '')
    {
        return $this->js->_scroll($element, $js);
    }

    /**
     * Unload
     *
     * Outputs a javascript library unload event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function unload($element = 'this', $js = '')
    {
        return $this->js->_unload($element, $js);
    }

    /**
     * Add Class
     *
     * Outputs a javascript library addClass event
     *
     * @param string - element
     * @param string - Class to add
     * @return string
     */
    public function addClass($element = 'this', $class = '')
    {
        return $this->js->_addClass($element, $class);
    }

    /**
     * Animate
     *
     * Outputs a javascript library animate event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function animate($element = 'this', $params = array(), $speed = '', $extra = '')
    {
        return $this->js->_animate($element, $params, $speed, $extra);
    }

    /**
     * Fade In
     *
     * Outputs a javascript library hide event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function fadeIn($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_fadeIn($element, $speed, $callback);
    }

    /**
     * Fade Out
     *
     * Outputs a javascript library hide event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function fadeOut($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_fadeOut($element, $speed, $callback);
    }

    /**
     * Slide Up
     *
     * Outputs a javascript library slideUp event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function slideUp($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_slideUp($element, $speed, $callback);
    }

    /**
     * Remove Class
     *
     * Outputs a javascript library removeClass event
     *
     * @param string - element
     * @param string - Class to add
     * @return string
     */
    public function removeClass($element = 'this', $class = '')
    {
        return $this->js->_removeClass($element, $class);
    }

    /**
     * Slide Down
     *
     * Outputs a javascript library slideDown event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function slideDown($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_slideDown($element, $speed, $callback);
    }

    /**
     * Slide Toggle
     *
     * Outputs a javascript library slideToggle event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function slideToggle($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_slideToggle($element, $speed, $callback);
    }

    /**
     * Hide
     *
     * Outputs a javascript library hide action
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function hide($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_hide($element, $speed, $callback);
    }

    /**
     * Toggle
     *
     * Outputs a javascript library toggle event
     *
     * @param string - element
     * @return string
     */
    public function toggle($element = 'this')
    {
        return $this->js->_toggle($element);
    }

    /**
     * Toggle Class
     *
     * Outputs a javascript library toggle class event
     *
     * @param string - element
     * @return string
     */
    public function toggleClass($element = 'this', $class = '')
    {
        return $this->js->_toggleClass($element, $class);
    }

    /**
     * Show
     *
     * Outputs a javascript library show event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function show($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_show($element, $speed, $callback);
    }

    /**
     * Compile
     *
     * gather together all script needing to be output
     *
     * @param string $element The element to attach the event to
     * @return string
     */
    public function compile($view_var = 'script_foot', $script_tags = true)
    {
        $this->js->_compile($view_var, $script_tags);
    }

    /**
     * Clear Compile
     *
     * Clears any previous javascript collected for output
     *
     * @return    void
     */
    public function clearCompile()
    {
        $this->js->_clearCompile();
    }

    /**
     * External
     *
     * Outputs a <script> tag with the source as an external js file
     *
     * @param string $element The element to attach the event to
     * @return string
     */
    public function external($external_file = '', $relative = false)
    {
        if ($external_file !== '') {
            $this->_javascript_location = $external_file;
        } else {
            if (Fly::getConfig('javascript_location') != '') {
                $this->_javascript_location = Fly::getConfig('javascript_location');
            }
        }

        if ($relative === true OR strncmp($external_file, 'http://', 7) == 0 OR strncmp($external_file, 'https://', 8) == 0) {
            $str = $this->_open_script($external_file);
        } else if (strpos($this->_javascript_location, 'http://') !== false) {
            $str = $this->_open_script($this->_javascript_location.$external_file);
        } else {
            $baseUrl = Fly::app()->getBaseUrl(true).'/';
            $str = $this->_open_script($baseUrl.$this->_javascript_location.$external_file);
        }

        $str .= $this->_close_script();
        return $str;
    }

    /**
     * Inline
     *
     * Outputs a <script> tag
     *
     * @param string $element The element to attach the event to
     * @param boolean $cdata If a CDATA section should be added
     * @return string
     */
    public function inline($script, $cdata = true)
    {
        $str = $this->_open_script();
        $str .= ($cdata) ? "\n// <![CDATA[\n{$script}\n// ]]>\n" : "\n{$script}\n";
        $str .= $this->_close_script();

        return $str;
    }

    /**
     * Open Script
     *
     * Outputs an opening <script>
     *
     * @param string
     * @return string
     */
    private function _open_script($src = '')
    {
        $str = '<script type="text/javascript" charset="'.strtolower(Fly::getConfig('charset')).'"';
        $str .= ($src == '') ? '>' : ' src="'.$src.'">';
        return $str;
    }

    /**
     * Close Script
     *
     * Outputs an closing </script>
     *
     * @param string
     * @return string
     */
    private function _close_script($extra = "\n")
    {
        return "</script>$extra";
    }

    /**
     * Update
     *
     * Outputs a javascript library slideDown event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function update($element = 'this', $speed = '', $callback = '')
    {
        return $this->js->_updater($element, $speed, $callback);
    }

    /**
     * Generate JSON
     *
     * Can be passed a database result or associative array and returns a JSON formatted string
     *
     * @param mixed $result set or array
     * @param bool $match_array_type match array types (defaults to objects)
     * @return string a json formatted string
     */
    public function generateJson($result = null, $match_array_type = false)
    {
        // JSON data can optionally be passed to this function
        // either as a database result object or an array, or a user supplied array
        if (!is_null($result)) {
            if (is_object($result)) {
                $json_result = $result->result_array();
            } else if (is_array($result)) {
                $json_result = $result;
            } else {
                return $this->_prepArgs($result);
            }
        } else {
            return 'null';
        }

        $json = array();
        $_is_assoc = true;

        if (!is_array($json_result) AND empty($json_result)) {
            throw new FlyException(Fly::t('fly', "Generate JSON Failed - Illegal key, value pair."));
        } else if ($match_array_type) {
            $_is_assoc = $this->_isAssociativeArray($json_result);
        }

        foreach ($json_result as $k => $v) {
            if ($_is_assoc) {
                $json[] = $this->_prepArgs($k, true).':'.$this->generateJson($v, $match_array_type);
            } else {
                $json[] = $this->generateJson($v, $match_array_type);
            }
        }

        $json = implode(',', $json);

        return $_is_assoc ? "{".$json."}" : "[".$json."]";
    }

    /**
     * Is associative array
     *
     * Checks for an associative array
     *
     * @param type
     * @return type
     */
    public function _isAssociativeArray($arr)
    {
        foreach (array_keys($arr) as $key => $val) {
            if ($key !== $val) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prep Args
     *
     * Ensures a standard json value and escapes values
     *
     * @param type
     * @return type
     */
    public function _prepArgs($result, $is_key = false)
    {
        if (is_null($result)) {
            return 'null';
        } else if (is_bool($result)) {
            return ($result === true) ? 'true' : 'false';
        } else if (is_string($result) OR $is_key) {
            return '"'.str_replace(array(
                '\\',
                "\t",
                "\n",
                "\r",
                '"',
                '/'
            ), array(
                '\\\\',
                '\\t',
                '\\n',
                "\\r",
                '\"',
                '\/'
            ), $result).'"';
        } else if (is_scalar($result)) {
            return $result;
        }
    }
}