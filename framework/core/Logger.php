<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Logger used to record the running application state.
 */
class Logger
{
    /**
     * @var string The log file path.
     */
    protected $_logPath;
    /**
     * @var int The threshold determines what gets logged.
     */
    protected $_threshold = 1;
    /**
     * @var string The log date format
     */
    protected $_dateFormat = 'Y-m-d H:i:s';
    /**
     * @var bool Whether to open the log? Defaults to 'true'.
     */
    protected $_enabled = true;
    /**
     * @var array The log level.
     */
    protected $_levels = array('ERROR' => '1', 'DEBUG' => '2', 'INFO' => '3', 'ALL' => '4');

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setLogPath(Fly::getConfig('logPath'));
        $this->setDateFormat(Fly::getConfig('logDateFormat'));
        $this->setThreshold(Fly::getConfig('logThreshold'));
    }

    /**
     * Set the log path.
     * @param $val The log path.
     */
    public function setLogPath($val)
    {
        $logPath = $val;
        $this->_logPath = ($logPath != '') ? $logPath : Fly::app()->getBasePath().'/runtime';
        if (!is_dir($this->_logPath) || !FileHelper::isReallyWritable($this->_logPath)) {
            $this->_enabled = false;
        }
    }

    /**
     * Returns the log path.
     * @return string
     */
    public function getLogPath()
    {
        return $this->_logPath;
    }

    /**
     * Set the log threshold.
     * @param $val The log threshold.
     */
    public function setThreshold($val)
    {
        if (is_numeric($val)) {
            $this->_threshold = $val;
        }
    }

    /**
     * Returns the log threshold.
     * @return int
     */
    public function getThreshold()
    {
        return $this->_threshold;
    }

    /**
     * Set the log date format.
     * @param $val The log date format.
     */
    public function setDateFormat($val)
    {
        if ($val != '') {
            $this->_dateFormat = $val;
        }
    }

    /**
     * Returns the log date format.
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_dateFormat;
    }

    /**
     * Write Log File
     * @param string $level The error level.
     * @param string $msg The error message.
     * @param string $category The error message category.
     * @param bool whether the error is a native PHP error.
     * @return bool
     */
    public function write($level = 'error', $msg, $category = 'application', $php_error = false)
    {
        if ($this->_enabled === false) {
            return false;
        }

        $level = strtoupper($level);
        if (!isset($this->_levels[$level]) || ($this->_levels[$level] > $this->_threshold)) {
            return false;
        }

        $filepath = $this->_logPath.DIRECTORY_SEPARATOR.'log-'.date('Y-m-d').'.log';
        $message = '';
        if (!$fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
            return false;
        }

        $message .= '['.$level.'] ['.$category.'] '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_dateFormat).' --> '.$msg."\n";
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);
        return true;
    }
}