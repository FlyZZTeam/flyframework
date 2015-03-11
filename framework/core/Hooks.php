<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Hooks Class
 * Provides a mechanism to extend the base system without hacking.
 */
class Hooks
{

    /**
     * @var bool Determines wether hooks are enabled. Defaults to 'false'.
     */
    var $enabled = false;
    /**
     * @var array List of all hooks set in config/hooks.php or $config['hooks'].
     */
    var $hooks = array();
    /**
     * @var bool Determines wether hook is in progress, used to prevent infinte loops. Defaults to 'false'.
     */
    var $in_progress = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_initialize();
        Fly::log('debug', "Hooks Class Initialized");
    }

    /**
     * Initialize the Hooks Preferences
     * @return void
     */
    protected function _initialize()
    {
        // If hooks are not enabled in the config file
        // there is nothing else to do
        if (Fly::app()->getConfig('enableHooks') == false) {
            return;
        }

        // Grab the "hooks" definition file.
        // If there are no hooks, we're done.

        Fly::loadConfig('config.hooks', true, true);
        $hook = Fly::app()->getConfig('hooks');
        if (!isset($hook) || !is_array($hook)) {
            return;
        }

        $this->hooks =& $hook;
        $this->enabled = true;
    }

    /**
     * Call Hook
     * Calls a particular hook
     * @param string $which the hook name
     * @return mixed
     */
    public function callHook($which = '')
    {
        if (!$this->enabled || !isset($this->hooks[$which])) {
            return false;
        }
        if (isset($this->hooks[$which][0]) && is_array($this->hooks[$which][0])) {
            foreach ($this->hooks[$which] as $val) {
                $this->_run_hook($val);
            }
        } else {
            $this->_run_hook($this->hooks[$which]);
        }
        return true;
    }

    /**
     * Runs a particular hook
     * @param array $data The hook details
     * @return bool
     */
    private function _run_hook($data)
    {
        if (!is_array($data)) {
            return false;
        }

        // -----------------------------------
        // Safety - Prevents run-away loops
        // -----------------------------------

        // If the script being called happens to have the same
        // hook call within it a loop can happen

        if ($this->in_progress == true) {
            return false;
        }

        // -----------------------------------
        // Set file path
        // -----------------------------------

        if (!isset($data['alias'])) {
            return false;
        }

        $filepath = Fly::getPathOfAlias($data['alias']).EXT;
        if (!file_exists($filepath)) {
            return false;
        }

        require($filepath);

        // -----------------------------------
        // Set class/function name
        // -----------------------------------

        $class = false;
        $function = false;
        $params = '';

        if (isset($data['class']) && $data['class'] != '') {
            $class = $data['class'];
        }

        if (isset($data['function'])) {
            $function = $data['function'];
        }

        if (isset($data['params'])) {
            $params = $data['params'];
        }

        if ($class === false && $function === false) {
            return false;
        }

        // -----------------------------------
        // Set the in_progress flag
        // -----------------------------------

        $this->in_progress = true;

        // -----------------------------------
        // Call the requested class and/or function
        // -----------------------------------

        if ($class !== false) {
            if (class_exists($class)) {
                $HOOK = new $class();
                $HOOK->$function($params);
            }
        } else {
            if (function_exists($function)) {
                $function($params);
            }
        }

        $this->in_progress = false;
        return true;
    }
}