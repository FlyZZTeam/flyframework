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
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	User Agent
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/user_agent.html
 */
class UserAgent
{

	private $agent		= NULL;

	private $is_browser	= FALSE;
	private $is_robot	= FALSE;
	private $is_mobile	= FALSE;

	private $languages	= array();
	private $charsets	= array();

	private $platforms	= array();
	private $browsers	= array();
	private $mobiles	= array();
	private $robots		= array();

	private $platform	= '';
	private $browser	= '';
    private $version	= '';
	private $mobile		= '';
	private $robot		= '';

	/**
	 * Constructor
	 *
	 * Sets the User Agent and runs the compilation routine
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$this->agent = trim($_SERVER['HTTP_USER_AGENT']);
		}

		if (!is_null($this->agent)) {
			if ($this->_loadAgentFile()) {
				$this->_compileData();
			}
		}

		Fly::log('debug', "User Agent Class Initialized");
	}

	/**
	 * Compile the User Agent Data
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _loadAgentFile()
	{
        Fly::loadConfig('config.user_agents', true);
        $config = Fly::getConfig('user_agents');
        if (empty($config)) {
            return FALSE;
        }

		$return = FALSE;

		if (isset($config['platforms'])) {
			$this->platforms = $config['platforms'];
			$return = TRUE;
		}

		if (isset($config['browsers'])) {
			$this->browsers = $config['browsers'];
			$return = TRUE;
		}

		if (isset($config['mobiles'])) {
			$this->mobiles = $config['mobiles'];
			$return = TRUE;
		}

		if (isset($config['robots'])) {
			$this->robots = $config['robots'];
			$return = TRUE;
		}
        unset($config);
		return $return;
	}

	/**
	 * Compile the User Agent Data
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _compileData()
	{
		$this->_setPlatform();

		foreach (array('_setRobot', '_setBrowser', '_setMobile') as $function) {
			if ($this->$function() === TRUE)
			{
				break;
			}
		}
	}

	/**
	 * Set the Platform
	 *
	 * @access	private
	 * @return	mixed
	 */
	private function _setPlatform()
	{
		if (is_array($this->platforms) && count($this->platforms) > 0) {
			foreach ($this->platforms as $key => $val) {
				if (preg_match("|".preg_quote($key)."|i", $this->agent)) {
					$this->platform = $val;
					return TRUE;
				}
			}
		}
		$this->platform = 'Unknown Platform';
	}

	/**
	 * Set the Browser
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _setBrowser()
	{
		if (is_array($this->browsers) && count($this->browsers) > 0) {
			foreach ($this->browsers as $key => $val) {
				if (preg_match("|".preg_quote($key).".*?([0-9\.]+)|i", $this->agent, $match)) {
					$this->is_browser = TRUE;
					$this->version = $match[1];
					$this->browser = $val;
					$this->_setMobile();
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Set the Robot
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _setRobot()
	{
		if (is_array($this->robots) AND count($this->robots) > 0) {
			foreach ($this->robots as $key => $val) {
				if (preg_match("|".preg_quote($key)."|i", $this->agent)) {
					$this->is_robot = TRUE;
					$this->robot = $val;
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Set the Mobile Device
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _setMobile()
	{
		if (is_array($this->mobiles) && count($this->mobiles) > 0) {
			foreach ($this->mobiles as $key => $val) {
				if (FALSE !== (strpos(strtolower($this->agent), $key))) {
					$this->is_mobile = TRUE;
					$this->mobile = $val;
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Set the accepted languages
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setLanguages()
	{
		if ((count($this->languages) == 0) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '') {
			$languages = preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));

			$this->languages = explode(',', $languages);
		}

		if (count($this->languages) == 0) {
			$this->languages = array('Undefined');
		}
	}

	/**
	 * Set the accepted character sets
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setCharsets()
	{
		if ((count($this->charsets) == 0) AND isset($_SERVER['HTTP_ACCEPT_CHARSET']) AND $_SERVER['HTTP_ACCEPT_CHARSET'] != '') {
			$charsets = preg_replace('/(;q=.+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])));

			$this->charsets = explode(',', $charsets);
		}

		if (count($this->charsets) == 0) {
			$this->charsets = array('Undefined');
		}
	}

	/**
	 * Is Browser
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isBrowser($key = NULL)
	{
		if ( ! $this->is_browser) {
			return FALSE;
		}

		// No need to be specific, it's a browser
		if ($key === NULL) {
			return TRUE;
		}

		// Check for a specific browser
		return array_key_exists($key, $this->browsers) AND $this->browser === $this->browsers[$key];
	}

	/**
	 * Is Robot
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isRobot($key = NULL)
	{
		if ( ! $this->is_robot) {
			return FALSE;
		}

		// No need to be specific, it's a robot
		if ($key === NULL) {
			return TRUE;
		}

		// Check for a specific robot
		return array_key_exists($key, $this->robots) AND $this->robot === $this->robots[$key];
	}

	/**
	 * Is Mobile
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isMobile($key = NULL)
	{
		if ( ! $this->is_mobile) {
			return FALSE;
		}

		// No need to be specific, it's a mobile
		if ($key === NULL) {
			return TRUE;
		}

		// Check for a specific robot
		return array_key_exists($key, $this->mobiles) AND $this->mobile === $this->mobiles[$key];
	}

	/**
	 * Is this a referral from another site?
	 *
	 * @access	public
	 * @return	bool
	 */
	public function isReferral()
	{
		if ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Agent String
	 *
	 * @access	public
	 * @return	string
	 */
	public function agentString()
	{
		return $this->agent;
	}

	/**
	 * Get Platform
	 *
	 * @access	public
	 * @return	string
	 */
	public function platform()
	{
		return $this->platform;
	}

	/**
	 * Get Browser Name
	 *
	 * @access	public
	 * @return	string
	 */
	public function browser()
	{
		return $this->browser;
	}

	/**
	 * Get the Browser Version
	 *
	 * @access	public
	 * @return	string
	 */
	public function version()
	{
		return $this->version;
	}

	/**
	 * Get The Robot Name
	 *
	 * @access	public
	 * @return	string
	 */
	public function robot()
	{
		return $this->robot;
	}

	/**
	 * Get the Mobile Device
	 *
	 * @access	public
	 * @return	string
	 */
	public function mobile()
	{
		return $this->mobile;
	}

	/**
	 * Get the referrer
	 *
	 * @access	public
	 * @return	bool
	 */
	public function referrer()
	{
		return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
	}

	/**
	 * Get the accepted languages
	 *
	 * @access	public
	 * @return	array
	 */
	public function languages()
	{
		if (count($this->languages) == 0) {
			$this->_setLanguages();
		}

		return $this->languages;
	}

	/**
	 * Get the accepted Character Sets
	 *
	 * @access	public
	 * @return	array
	 */
	public function charsets()
	{
		if (count($this->charsets) == 0) {
			$this->_setCharsets();
		}

		return $this->charsets;
	}

	/**
	 * Test for a particular language
	 *
	 * @access	public
	 * @return	bool
	 */
	public function acceptLang($lang = 'en')
	{
		return (in_array(strtolower($lang), $this->languages(), TRUE));
	}

	/**
	 * Test for a particular character set
	 *
	 * @access	public
	 * @return	bool
	 */
	public function acceptCharset($charset = 'utf-8')
	{
		return (in_array(strtolower($charset), $this->charsets(), TRUE));
	}

}
