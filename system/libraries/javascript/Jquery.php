<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Jquery Class
 */
class Jquery extends Javascript
{

    var $_javascript_folder = 'js';
    var $jquery_code_for_load = array();
    var $jquery_code_for_compile = array();
    var $jquery_corner_active = false;
    var $jquery_table_sorter_active = false;
    var $jquery_table_sorter_pager_active = false;
    var $jquery_ajax_img = '';

    public function __construct($params)
    {
        extract($params);

        if ($autoload === true) {
            $this->script();
        }

        Fly::log('debug', "Jquery Class Initialized");
    }

    /**
     * Blur
     *
     * Outputs a jQuery blur event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _blur($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'blur');
    }

    /**
     * Change
     *
     * Outputs a jQuery change event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _change($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'change');
    }

    /**
     * Click
     *
     * Outputs a jQuery click event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @param boolean $ret_false whether or not to return false
     * @return string
     */
    public function _click($element = 'this', $js = '', $ret_false = true)
    {
        if (!is_array($js)) {
            $js = array($js);
        }

        if ($ret_false) {
            $js[] = "return false;";
        }

        return $this->_addEvent($element, $js, 'click');
    }

    /**
     * Double Click
     *
     * Outputs a jQuery dblclick event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _dblclick($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'dblclick');
    }

    /**
     * Error
     *
     * Outputs a jQuery error event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _error($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'error');
    }

    /**
     * Focus
     *
     * Outputs a jQuery focus event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _focus($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'focus');
    }

    /**
     * Hover
     *
     * Outputs a jQuery hover event
     *
     * @param string $element - element
     * @param string $over - Javascript code for mouse over
     * @param string $out - Javascript code for mouse out
     * @return string
     */
    public function _hover($element = 'this', $over, $out)
    {
        $event = "\n\t$(".$this->_prepElement($element).").hover(\n\t\tfunction()\n\t\t{\n\t\t\t{$over}\n\t\t}, \n\t\tfunction()\n\t\t{\n\t\t\t{$out}\n\t\t});\n";

        $this->jquery_code_for_compile[] = $event;

        return $event;
    }

    /**
     * Keydown
     *
     * Outputs a jQuery keydown event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _keydown($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'keydown');
    }

    /**
     * Keyup
     *
     * Outputs a jQuery keydown event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _keyup($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'keyup');
    }

    /**
     * Load
     *
     * Outputs a jQuery load event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _load($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'load');
    }

    /**
     * Mousedown
     *
     * Outputs a jQuery mousedown event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _mousedown($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'mousedown');
    }

    /**
     * Mouse Out
     *
     * Outputs a jQuery mouseout event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _mouseout($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'mouseout');
    }

    /**
     * Mouse Over
     *
     * Outputs a jQuery mouseover event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _mouseover($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'mouseover');
    }

    /**
     * Mouseup
     *
     * Outputs a jQuery mouseup event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _mouseup($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'mouseup');
    }

    /**
     * Output
     *
     * Outputs script directly
     *
     * @param string $array_js The element to attach the event to
     * @return string
     */
    public function _output($array_js = '')
    {
        if (!is_array($array_js)) {
            $array_js = array($array_js);
        }

        foreach ($array_js as $js) {
            $this->jquery_code_for_compile[] = "\t$js\n";
        }
    }

    /**
     * Resize
     *
     * Outputs a jQuery resize event
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @return string
     */
    public function _resize($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'resize');
    }

    /**
     * Scroll
     *
     * Outputs a jQuery scroll event
     *
     * @param string $element The element to attach the event to
     * @param string The code to execute
     * @return string
     */
    public function _scroll($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'scroll');
    }

    /**
     * Unload
     *
     * Outputs a jQuery unload event
     *
     * @param string $element The element to attach the event to
     * @param string The code to execute
     * @return string
     */
    public function _unload($element = 'this', $js = '')
    {
        return $this->_addEvent($element, $js, 'unload');
    }

    /**
     * Add Class
     *
     * Outputs a jQuery addClass event
     *
     * @param string $element - element
     * @return string
     */
    public function _addClass($element = 'this', $class = '')
    {
        $element = $this->_prepElement($element);
        $str = "$({$element}).addClass(\"$class\");";
        return $str;
    }

    /**
     * Animate
     *
     * Outputs a jQuery animate event
     *
     * @param string $element - element
     * @param string $speed - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string $extra - Javascript callback function
     * @return string
     */
    public function _animate($element = 'this', $params = array(), $speed = '', $extra = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        $animations = "\t\t\t";

        foreach ($params as $param => $value) {
            $animations .= $param.': \''.$value.'\', ';
        }

        $animations = substr($animations, 0, -2); // remove the last ", "

        if ($speed != '') {
            $speed = ', '.$speed;
        }

        if ($extra != '') {
            $extra = ', '.$extra;
        }

        $str = "$({$element}).animate({\n$animations\n\t\t}".$speed.$extra.");";

        return $str;
    }

    /**
     * Fade In
     *
     * Outputs a jQuery hide event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _fadeIn($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).fadeIn({$speed}{$callback});";

        return $str;
    }

    /**
     * Fade Out
     *
     * Outputs a jQuery hide event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _fadeOut($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).fadeOut({$speed}{$callback});";

        return $str;
    }

    /**
     * Hide
     *
     * Outputs a jQuery hide action
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _hide($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).hide({$speed}{$callback});";

        return $str;
    }

    /**
     * Remove Class
     *
     * Outputs a jQuery remove class event
     *
     * @param string - element
     * @return string
     */
    public function _removeClass($element = 'this', $class = '')
    {
        $element = $this->_prepElement($element);
        $str = "$({$element}).removeClass(\"$class\");";
        return $str;
    }

    /**
     * Slide Up
     *
     * Outputs a jQuery slideUp event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _slideUp($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).slideUp({$speed}{$callback});";

        return $str;
    }

    /**
     * Slide Down
     *
     * Outputs a jQuery slideDown event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _slideDown($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).slideDown({$speed}{$callback});";

        return $str;
    }

    /**
     * Slide Toggle
     *
     * Outputs a jQuery slideToggle event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _slideToggle($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).slideToggle({$speed}{$callback});";

        return $str;
    }

    /**
     * Toggle
     *
     * Outputs a jQuery toggle event
     *
     * @param string - element
     * @return string
     */
    public function _toggle($element = 'this')
    {
        $element = $this->_prepElement($element);
        $str = "$({$element}).toggle();";
        return $str;
    }

    /**
     * Toggle Class
     *
     * Outputs a jQuery toggle class event
     *
     * @param string - element
     * @return string
     */
    public function _toggleClass($element = 'this', $class = '')
    {
        $element = $this->_prepElement($element);
        $str = "$({$element}).toggleClass(\"$class\");";
        return $str;
    }

    /**
     * Show
     *
     * Outputs a jQuery show event
     *
     * @param string - element
     * @param string - One of 'slow', 'normal', 'fast', or time in milliseconds
     * @param string - Javascript callback function
     * @return string
     */
    public function _show($element = 'this', $speed = '', $callback = '')
    {
        $element = $this->_prepElement($element);
        $speed = $this->_validateSpeed($speed);

        if ($callback != '') {
            $callback = ", function(){\n{$callback}\n}";
        }

        $str = "$({$element}).show({$speed}{$callback});";

        return $str;
    }

    /**
     * Updater
     *
     * An Ajax call that populates the designated DOM node with
     * returned content
     *
     * @param string $element The element to attach the event to
     * @param string $controller the controller to run the call against
     * @param string $options optional parameters
     * @return string
     */
    public function _updater($container = 'this', $controller, $options = '')
    {
        $container = $this->_prepElement($container);

        $controller = (strpos('://', $controller) === false) ? $controller : Fly::app()->Controller->createAbsoluteUrl($controller);

        // ajaxStart and ajaxStop are better choices here... but this is a stop gap
        if (Fly::getConfig('javascript_ajax_img') == '') {
            $loading_notifier = "Loading...";
        } else {
            $loading_notifier = '<img src=\''.Fly::app()->getBaseUrl(true).'/'.Fly::getConfig('javascript_ajax_img').'\' alt=\'Loading\' />';
        }

        $updater = "$($container).empty();\n"; // anything that was in... get it out
        $updater .= "\t\t$($container).prepend(\"$loading_notifier\");\n"; // to replace with an image

        $request_options = '';
        if ($options != '') {
            $request_options .= ", {";
            $request_options .= (is_array($options)) ? "'".implode("', '", $options)."'" : "'".str_replace(":", "':'", $options)."'";
            $request_options .= "}";
        }

        $updater .= "\t\t$($container).load('$controller'$request_options);";
        return $updater;
    }

    /**
     * Zebra tables
     *
     * @param string $class table name
     * @return string
     */
    public function _zebraTables($class = '', $odd = 'odd', $hover = '')
    {
        $class = ($class != '') ? '.'.$class : '';

        $zebra = "\t\$(\"table{$class} tbody tr:nth-child(even)\").addClass(\"{$odd}\");";

        $this->jquery_code_for_compile[] = $zebra;

        if ($hover != '') {
            $hover = $this->hover("table{$class} tbody tr", "$(this).addClass('hover');", "$(this).removeClass('hover');");
        }

        return $zebra;
    }

    /**
     * Corner Plugin
     *
     * http://www.malsup.com/jquery/corner/
     *
     * @param string $element target
     * @return string
     */
    public function corner($element = '', $corner_style = '')
    {
        // may want to make this configurable down the road
        $corner_location = '/plugins/jquery.corner.js';

        if ($corner_style != '') {
            $corner_style = '"'.$corner_style.'"';
        }

        return "$(".$this->_prepElement($element).").corner(".$corner_style.");";
    }

    /**
     * modal window
     *
     * Load a thickbox modal window
     *
     * @return void
     */
    public function modal($src, $relative = false)
    {
        $this->jquery_code_for_load[] = $this->external($src, $relative);
    }

    /**
     * Effect
     *
     * Load an Effect library
     *
     * @return void
     */
    public function effect($src, $relative = false)
    {
        $this->jquery_code_for_load[] = $this->external($src, $relative);
    }

    /**
     * Plugin
     *
     * Load a plugin library
     *
     * @return void
     */
    public function plugin($src, $relative = false)
    {
        $this->jquery_code_for_load[] = $this->external($src, $relative);
    }

    /**
     * UI
     *
     * Load a user interface library
     *
     * @return void
     */
    public function ui($src, $relative = false)
    {
        $this->jquery_code_for_load[] = $this->external($src, $relative);
    }

    /**
     * Sortable
     *
     * Creates a jQuery sortable
     *
     * @return void
     */
    public function sortable($element, $options = array())
    {

        if (count($options) > 0) {
            $sort_options = array();
            foreach ($options as $k => $v) {
                $sort_options[] = "\n\t\t".$k.': '.$v."";
            }
            $sort_options = implode(",", $sort_options);
        } else {
            $sort_options = '';
        }

        return "$(".$this->_prepElement($element).").sortable({".$sort_options."\n\t});";
    }

    /**
     * Table Sorter Plugin
     *
     * @param string $table table name
     * @param string $options plugin location
     * @return string
     */
    public function tablesorter($table = '', $options = '')
    {
        $this->jquery_code_for_compile[] = "\t$(".$this->_prepElement($table).").tablesorter($options);\n";
    }

    /**
     * Add Event
     *
     * Constructs the syntax for an event, and adds to into the array for compilation
     *
     * @param string $element The element to attach the event to
     * @param string $js The code to execute
     * @param string $event The event to pass
     * @return string
     */
    private function _addEvent($element, $js, $event)
    {
        if (is_array($js)) {
            $js = implode("\n\t\t", $js);
        }

        $event = "\n\t$(".$this->_prepElement($element).").{$event}(function(){\n\t\t{$js}\n\t});\n";
        $this->jquery_code_for_compile[] = $event;
        return $event;
    }

    /**
     * Compile
     *
     * As events are specified, they are stored in an array
     * This funciton compiles them all for output on a page
     *
     * @return string
     */
    public function _compile($view_var = 'script_foot', $script_tags = true)
    {
        // External references
        $external_scripts = implode('', $this->jquery_code_for_load);
        //$this->CI->load->vars(array('library_src' => $external_scripts));
        Fly::app()->setParams(array('library_src' => $external_scripts));

        if (count($this->jquery_code_for_compile) == 0) {
            // no inline references, let's just return
            return;
        }

        // Inline references
        $script = '$(document).ready(function() {'."\n";
        $script .= implode('', $this->jquery_code_for_compile);
        $script .= '});';

        $output = ($script_tags === false) ? $script : $this->inline($script);

        //$this->CI->load->vars(array($view_var => $output));
        Fly::app()->setParams(array($view_var => $output));
    }

    /**
     * Clear Compile
     *
     * Clears the array of script events collected for output
     *
     * @return void
     */
    public function _clearCompile()
    {
        $this->jquery_code_for_compile = array();
    }

    /**
     * Document Ready
     *
     * A wrapper for writing document.ready()
     *
     * @return string
     */
    public function _documentReady($js)
    {
        if (!is_array($js)) {
            $js = array($js);
        }

        foreach ($js as $script) {
            $this->jquery_code_for_compile[] = $script;
        }
    }

    /**
     * Script Tag
     *
     * Outputs the script tag that loads the jquery.js file into an HTML document
     *
     * @param string
     * @return string
     */
    public function script($library_src = '', $relative = false)
    {
        $library_src = $this->external($library_src, $relative);
        $this->jquery_code_for_load[] = $library_src;
        return $library_src;
    }

    /**
     * Prep Element
     *
     * Puts HTML element in quotes for use in jQuery code
     * unless the supplied element is the Javascript 'this'
     * object, in which case no quotes are added
     *
     * @param string
     * @return string
     */
    public function _prepElement($element)
    {
        if ($element != 'this') {
            $element = '"'.$element.'"';
        }
        return $element;
    }

    /**
     * Validate Speed
     *
     * Ensures the speed parameter is valid for jQuery
     *
     * @param string
     * @return string
     */
    private function _validateSpeed($speed)
    {
        if (in_array($speed, array('slow', 'normal', 'fast'))) {
            $speed = '"'.$speed.'"';
        } else if (preg_match("/[^0-9]/", $speed)) {
            $speed = '';
        }

        return $speed;
    }
}