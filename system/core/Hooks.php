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
 * CodeIgniter Hooks Class
 *
 * Provides a mechanism to extend the base system without hacking.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/encryption.html
 */
class Hooks {

	/**
	 * Determines wether hooks are enabled
	 *
	 * @var bool
	 */
	var $enabled		= FALSE;
	/**
	 * List of all hooks set in config/hooks.php
	 *
	 * @var array
	 */
	var $hooks			= array();
	/**
	 * Determines wether hook is in progress, used to prevent infinte loops
	 *
	 * @var bool
	 */
	var $in_progress	= FALSE;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->_initialize();
		Fly::log('debug', "Hooks Class Initialized");
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize the Hooks Preferences
	 *
	 * @access	private
	 * @return	void
	 */
	protected function _initialize()
	{
		// If hooks are not enabled in the config file
		// there is nothing else to do
		if (Fly::app()->getConfig('enable_hooks') == false) {
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

	// --------------------------------------------------------------------

	/**
	 * Call Hook
	 *
	 * Calls a particular hook
	 *
	 * @access	private
	 * @param	string	the hook name
	 * @return	mixed
	 */
	//public function _call_hook($which = '')
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

	// --------------------------------------------------------------------

	/**
	 * Run Hook
	 *
	 * Runs a particular hook
	 *
	 * @access	private
	 * @param	array	the hook details
	 * @return	bool
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

// END CI_Hooks class

/* End of file Hooks.php */
/* Location: ./system/core/Hooks.php */