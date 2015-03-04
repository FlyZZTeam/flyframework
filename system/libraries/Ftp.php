<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * FTP Class
 */
class Ftp
{

    public $hostName = '';
    public $userName = 'anonymous';
    public $password = '';
    public $port = 21;
    public $passive = true;
    public $debug = false;
    private $connId = false;

    /**
     * Constructor - Sets Preferences
     *
     * The constructor can be passed an array of config values
     */
    public function __construct($config = array())
    {
        if (count($config) > 0) {
            $this->initialize($config);
        }

        Fly::log('debug', "FTP Class Initialized");
    }

    /**
     * Initialize preferences
     *
     * @param array
     * @return void
     */
    public function initialize($config = array())
    {
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }

        // Prep the hostname
        $this->hostName = preg_replace('|.+?://|', '', $this->hostName);
    }

    /**
     * FTP Connect
     *
     * @param array $config the connection values
     * @return bool
     */
    public function connect($config = array())
    {
        if (count($config) > 0) {
            $this->initialize($config);
        }

        if (false === ($this->connId = @ftp_connect($this->hostName, $this->port))) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_connect');
            }
            return false;
        }

        if (!$this->_login()) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_login');
            }
            return false;
        }

        // Set passive mode if needed
        if ($this->passive == true) {
            ftp_pasv($this->connId, true);
        }

        return true;
    }

    /**
     * FTP Login
     *
     * @return bool
     */
    private function _login()
    {
        return @ftp_login($this->connId, $this->userName, $this->password);
    }

    /**
     * Validates the connection ID
     *
     * @return bool
     */
    private function _isConn()
    {
        if (!is_resource($this->connId)) {
            if ($this->debug == true) {
                $this->_error('ftp_no_connection');
            }
            return false;
        }
        return true;
    }

    /**
     * Change directory
     *
     * The second parameter lets us momentarily turn off debugging so that
     * this function can be used to test for the existence of a folder
     * without throwing an error.  There's no FTP equivalent to is_dir()
     * so we do it by trying to change to a particular directory.
     * Internally, this parameter is only used by the "mirror" function below.
     *
     * @param string
     * @param bool
     * @return bool
     */
    public function changeDir($path = '', $supress_debug = false)
    {
        if ($path == '' || !$this->_isConn()) {
            return false;
        }

        $result = @ftp_chdir($this->connId, $path);

        if ($result === false) {
            if ($this->debug == true && $supress_debug == false) {
                $this->_error('ftp_unable_to_changedir');
            }
            return false;
        }

        return true;
    }

    /**
     * Create a directory
     *
     * @param string
     * @return bool
     */
    public function mkdir($path = '', $permissions = null)
    {
        if ($path == '' || !$this->_isConn()) {
            return false;
        }

        $result = @ftp_mkdir($this->connId, $path);

        if ($result === false) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_makdir');
            }
            return false;
        }

        // Set file permissions if needed
        if (!is_null($permissions)) {
            $this->chmod($path, (int)$permissions);
        }

        return true;
    }

    /**
     * Upload a file to the server
     *
     * @param string
     * @param string
     * @param string
     * @return bool
     */
    public function upload($locpath, $rempath, $mode = 'auto', $permissions = null)
    {
        if (!$this->_isConn()) {
            return false;
        }

        if (!file_exists($locpath)) {
            $this->_error('ftp_no_source_file');
            return false;
        }

        // Set the mode if not specified
        if ($mode == 'auto') {
            // Get the file extension so we can set the upload type
            $ext = $this->_getExt($locpath);
            $mode = $this->_setType($ext);
        }

        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_put($this->connId, $rempath, $locpath, $mode);

        if ($result === false) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_upload');
            }
            return false;
        }

        // Set file permissions if needed
        if (!is_null($permissions)) {
            $this->chmod($rempath, (int)$permissions);
        }

        return true;
    }

    /**
     * Download a file from a remote server to the local server
     *
     * @param string
     * @param string
     * @param string
     * @return bool
     */
    public function download($rempath, $locpath, $mode = 'auto')
    {
        if (!$this->_isConn()) {
            return false;
        }

        // Set the mode if not specified
        if ($mode == 'auto') {
            // Get the file extension so we can set the upload type
            $ext = $this->_getExt($rempath);
            $mode = $this->_setType($ext);
        }

        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_get($this->connId, $locpath, $rempath, $mode);

        if ($result === false) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_download');
            }
            return false;
        }

        return true;
    }

    /**
     * Rename (or move) a file
     *
     * @param string
     * @param string
     * @param bool
     * @return bool
     */
    public function rename($old_file, $new_file, $move = false)
    {
        if (!$this->_isConn()) {
            return false;
        }

        $result = @ftp_rename($this->connId, $old_file, $new_file);

        if ($result === false) {
            if ($this->debug == true) {
                $msg = ($move == false) ? 'ftp_unable_to_rename' : 'ftp_unable_to_move';

                $this->_error($msg);
            }
            return false;
        }

        return true;
    }

    /**
     * Move a file
     *
     * @param string
     * @param string
     * @return bool
     */
    public function move($old_file, $new_file)
    {
        return $this->rename($old_file, $new_file, true);
    }

    /**
     * Rename (or move) a file
     *
     * @param string
     * @return bool
     */
    public function deleteFile($filepath)
    {
        if (!$this->_isConn()) {
            return false;
        }

        $result = @ftp_delete($this->connId, $filepath);

        if ($result === false) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_delete');
            }
            return false;
        }

        return true;
    }

    /**
     * Delete a folder and recursively delete everything (including sub-folders)
     * containted within it.
     *
     * @param string
     * @return bool
     */
    public function deleteDir($filepath)
    {
        if (!$this->_isConn()) {
            return false;
        }

        // Add a trailing slash to the file path if needed
        $filepath = preg_replace("/(.+?)\/*$/", "\\1/", $filepath);

        $list = $this->listFiles($filepath);

        if ($list !== false && count($list) > 0) {
            foreach ($list as $item) {
                // If we can't delete the item it's probaly a folder so
                // we'll recursively call delete_dir()
                if (!@ftp_delete($this->connId, $item)) {
                    $this->deleteDir($item);
                }
            }
        }

        $result = @ftp_rmdir($this->connId, $filepath);

        if ($result === false) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_delete');
            }
            return false;
        }

        return true;
    }

    /**
     * Set file permissions
     *
     * @param string $path the file path
     * @param string $perm the permissions
     * @return bool
     */
    public function chmod($path, $perm)
    {
        if (!$this->_isConn()) {
            return false;
        }

        // Permissions can only be set when running PHP 5
        if (!function_exists('ftp_chmod')) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_chmod');
            }
            return false;
        }

        $result = @ftp_chmod($this->connId, $perm, $path);

        if ($result === false) {
            if ($this->debug == true) {
                $this->_error('ftp_unable_to_chmod');
            }
            return false;
        }

        return true;
    }

    /**
     * FTP List files in the specified directory
     *
     * @return array
     */
    public function listFiles($path = '.')
    {
        if (!$this->_isConn()) {
            return false;
        }

        return ftp_nlist($this->connId, $path);
    }

    /**
     * Read a directory and recreate it remotely
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a mirror via FTP based on it.  Whatever the directory structure
     * of the original file path will be recreated on the server.
     *
     * @param string $locpath path to source with trailing slash
     * @param string $rempath path to destination - include the base folder with trailing slash
     * @return bool
     */
    function mirror($locpath, $rempath)
    {
        if (!$this->_isConn()) {
            return false;
        }

        // Open the local file path
        if ($fp = @opendir($locpath)) {
            // Attempt to open the remote file path.
            if (!$this->changeDir($rempath, true)) {
                // If it doesn't exist we'll attempt to create the direcotory
                if (!$this->mkdir($rempath) OR !$this->changeDir($rempath)) {
                    return false;
                }
            }

            // Recursively read the local directory
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($locpath.$file) && substr($file, 0, 1) != '.') {
                    $this->mirror($locpath.$file."/", $rempath.$file."/");
                } else if (substr($file, 0, 1) != ".") {
                    // Get the file extension so we can se the upload type
                    $ext = $this->_getExt($file);
                    $mode = $this->_setType($ext);

                    $this->upload($locpath.$file, $rempath.$file, $mode);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Extract the file extension
     *
     * @param string
     * @return string
     */
    private function _getExt($filename)
    {
        if (false === strpos($filename, '.')) {
            return 'txt';
        }

        $x = explode('.', $filename);
        return end($x);
    }

    /**
     * Set the upload type
     *
     * @param string
     * @return string
     */
    private function _setType($ext)
    {
        $text_types = array(
            'txt',
            'text',
            'php',
            'phps',
            'php4',
            'js',
            'css',
            'htm',
            'html',
            'phtml',
            'shtml',
            'log',
            'xml'
        );

        return (in_array($ext, $text_types)) ? 'ascii' : 'binary';
    }

    /**
     * Close the connection
     * @return bool
     */
    public function close()
    {
        if (!$this->_isConn()) {
            return false;
        }

        @ftp_close($this->connId);
        return true;
    }

    /**
     * Display error message
     * @param string
     * @return bool
     */
    private function _error($line)
    {
        Fly::app()->Lang->load('ftp');
        throw new FlyException(Fly::app()->Lang->line($line));
    }
}