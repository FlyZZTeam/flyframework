<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Log used to record the running application state.
 */
class Log
{
    /**
     * @var string The log file path.
     */
    protected $_log_path;
    /**
     * @var int The threshold determines what gets logged.
     */
    protected $_threshold = 1;
    /**
     * @var string The log date fromat.
     */
    protected $_date_fmt = 'Y-m-d H:i:s';
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
        $config = Fly::getConfig();
        $logPath = $config['log_path'];

        $this->_log_path = ($logPath != '') ? $logPath : Fly::app()->getBasePath().'/runtime';

        if (!is_dir($this->_log_path) || !FileHelper::isReallyWritable($this->_log_path)) {
            $this->_enabled = false;
        }

        if (is_numeric($config['log_threshold'])) {
            $this->_threshold = $config['log_threshold'];
        }

        if ($config['log_date_format'] != '') {
            $this->_date_fmt = $config['log_date_format'];
        }
    }

    /**
     * Write Log File
     * @param string $level The error level.
     * @param string $msg The error message.
     * @param string $category The error message category.
     * @param bool whether the error is a native PHP error.
     * @return    bool
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

        $filepath = $this->_log_path.DIRECTORY_SEPARATOR.'log-'.date('Y-m-d').'.log';
        $message = '';

        if (!$fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
            return false;
        }

        $message .= '['.$level.'] ['.$category.'] '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt).' --> '.$msg."\n";
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);
        return true;
    }
}