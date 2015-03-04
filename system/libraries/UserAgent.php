<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
 */
class UserAgent
{

    private $agent = null;

    private $is_browser = false;
    private $is_robot = false;
    private $is_mobile = false;

    private $languages = array();
    private $charsets = array();

    private $platforms = array();
    private $browsers = array();
    private $mobiles = array();
    private $robots = array();

    private $platform = '';
    private $browser = '';
    private $version = '';
    private $mobile = '';
    private $robot = '';

    /**
     * Constructor
     *
     * Sets the User Agent and runs the compilation routine
     *
     * @return void
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
     * @return bool
     */
    private function _loadAgentFile()
    {
        Fly::loadConfig('config.user_agents', true);
        $config = Fly::getConfig('user_agents');
        if (empty($config)) {
            return false;
        }

        $return = false;

        if (isset($config['platforms'])) {
            $this->platforms = $config['platforms'];
            $return = true;
        }

        if (isset($config['browsers'])) {
            $this->browsers = $config['browsers'];
            $return = true;
        }

        if (isset($config['mobiles'])) {
            $this->mobiles = $config['mobiles'];
            $return = true;
        }

        if (isset($config['robots'])) {
            $this->robots = $config['robots'];
            $return = true;
        }
        unset($config);
        return $return;
    }

    /**
     * Compile the User Agent Data
     *
     * @return bool
     */
    private function _compileData()
    {
        $this->_setPlatform();

        foreach (array('_setRobot', '_setBrowser', '_setMobile') as $function) {
            if ($this->$function() === true) {
                break;
            }
        }
    }

    /**
     * Set the Platform
     *
     * @return mixed
     */
    private function _setPlatform()
    {
        if (is_array($this->platforms) && count($this->platforms) > 0) {
            foreach ($this->platforms as $key => $val) {
                if (preg_match("|".preg_quote($key)."|i", $this->agent)) {
                    $this->platform = $val;
                    return true;
                }
            }
        }
        $this->platform = 'Unknown Platform';
    }

    /**
     * Set the Browser
     *
     * @return bool
     */
    private function _setBrowser()
    {
        if (is_array($this->browsers) && count($this->browsers) > 0) {
            foreach ($this->browsers as $key => $val) {
                if (preg_match("|".preg_quote($key).".*?([0-9\.]+)|i", $this->agent, $match)) {
                    $this->is_browser = true;
                    $this->version = $match[1];
                    $this->browser = $val;
                    $this->_setMobile();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set the Robot
     *
     * @return bool
     */
    private function _setRobot()
    {
        if (is_array($this->robots) AND count($this->robots) > 0) {
            foreach ($this->robots as $key => $val) {
                if (preg_match("|".preg_quote($key)."|i", $this->agent)) {
                    $this->is_robot = true;
                    $this->robot = $val;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set the Mobile Device
     *
     * @return bool
     */
    private function _setMobile()
    {
        if (is_array($this->mobiles) && count($this->mobiles) > 0) {
            foreach ($this->mobiles as $key => $val) {
                if (false !== (strpos(strtolower($this->agent), $key))) {
                    $this->is_mobile = true;
                    $this->mobile = $val;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set the accepted languages
     *
     * @return void
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
     * @return void
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
     * @return bool
     */
    public function isBrowser($key = null)
    {
        if (!$this->is_browser) {
            return false;
        }

        // No need to be specific, it's a browser
        if ($key === null) {
            return true;
        }

        // Check for a specific browser
        return array_key_exists($key, $this->browsers) AND $this->browser === $this->browsers[$key];
    }

    /**
     * Is Robot
     *
     * @return bool
     */
    public function isRobot($key = null)
    {
        if (!$this->is_robot) {
            return false;
        }

        // No need to be specific, it's a robot
        if ($key === null) {
            return true;
        }

        // Check for a specific robot
        return array_key_exists($key, $this->robots) AND $this->robot === $this->robots[$key];
    }

    /**
     * Is Mobile
     *
     * @return bool
     */
    public function isMobile($key = null)
    {
        if (!$this->is_mobile) {
            return false;
        }

        // No need to be specific, it's a mobile
        if ($key === null) {
            return true;
        }

        // Check for a specific robot
        return array_key_exists($key, $this->mobiles) AND $this->mobile === $this->mobiles[$key];
    }

    /**
     * Is this a referral from another site?
     *
     * @return bool
     */
    public function isReferral()
    {
        if (!isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') {
            return false;
        }
        return true;
    }

    /**
     * Agent String
     *
     * @return string
     */
    public function agentString()
    {
        return $this->agent;
    }

    /**
     * Get Platform
     *
     * @return string
     */
    public function platform()
    {
        return $this->platform;
    }

    /**
     * Get Browser Name
     *
     * @return string
     */
    public function browser()
    {
        return $this->browser;
    }

    /**
     * Get the Browser Version
     *
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * Get The Robot Name
     *
     * @return string
     */
    public function robot()
    {
        return $this->robot;
    }

    /**
     * Get the Mobile Device
     *
     * @return string
     */
    public function mobile()
    {
        return $this->mobile;
    }

    /**
     * Get the referrer
     *
     * @return bool
     */
    public function referrer()
    {
        return (!isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    /**
     * Get the accepted languages
     *
     * @return    array
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
     * @return array
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
     * @return bool
     */
    public function acceptLang($lang = 'en')
    {
        return (in_array(strtolower($lang), $this->languages(), true));
    }

    /**
     * Test for a particular character set
     *
     * @return bool
     */
    public function acceptCharset($charset = 'utf-8')
    {
        return (in_array(strtolower($charset), $this->charsets(), true));
    }
}
