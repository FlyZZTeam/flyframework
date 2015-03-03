<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * File Helpers
 */
class FileHelper
{

    /**
     * Read File
     *
     * Opens the file specfied in the path and returns it as a string.
     *
     * @param string $file path to file
     * @return string
     */
    public static function readFile($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        if (function_exists('file_get_contents')) {
            return file_get_contents($file);
        }

        if (!$fp = @fopen($file, FOPEN_READ)) {
            return false;
        }

        flock($fp, LOCK_SH);

        $data = '';
        if (filesize($file) > 0) {
            $data =& fread($fp, filesize($file));
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $data;
    }

    /**
     * Write File
     *
     * Writes data to the file specified in the path.
     * Creates a new file if non-existent.
     *
     * @param string $path path to file
     * @param string $data file data
     * @return bool
     */
    public static function writeFile($path, $data, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE)
    {
        if (!$fp = @fopen($path, $mode)) {
            return false;
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    /**
     * Delete Files
     *
     * Deletes all files contained in the supplied directory path.
     * Files must be writable or owned by the system in order to be deleted.
     * If the second parameter is set to TRUE, any directories contained
     * within the supplied base directory will be nuked as well.
     *
     * @param string $path path to file
     * @param bool $del_dir whether to delete any directories found in the path
     * @return bool
     */
    public static function deleteFiles($path, $del_dir = false, $level = 0)
    {
        // Trim the trailing slash
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!$current_dir = @opendir($path)) {
            return false;
        }

        while (false !== ($filename = @readdir($current_dir))) {
            if ($filename != "." and $filename != "..") {
                if (is_dir($path.DIRECTORY_SEPARATOR.$filename)) {
                    // Ignore empty folders
                    if (substr($filename, 0, 1) != '.') {
                        self::deleteFiles($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $level + 1);
                    }
                } else {
                    unlink($path.DIRECTORY_SEPARATOR.$filename);
                }
            }
        }
        @closedir($current_dir);

        if ($del_dir == true AND $level > 0) {
            return @rmdir($path);
        }

        return true;
    }

    /**
     * Get Filenames
     *
     * Reads the specified directory and builds an array containing the filenames.
     * Any sub-folders contained within the specified path are read as well.
     *
     * @param string $source_dir path to source
     * @param bool $include_path whether to include the path as part of the filename
     * @param bool $_recursion internal variable to determine recursion status - do not use in calls
     * @return array
     */
    public static function getFileNames($source_dir, $include_path = false, $_recursion = false)
    {
        static $_filedata = array();

        if ($fp = @opendir($source_dir)) {
            // reset the array and make sure $source_dir has a trailing slash on the initial call
            if ($_recursion === false) {
                $_filedata = array();
                $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            } else {
                $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            }

            while (false !== ($file = readdir($fp))) {
                if (@is_dir($source_dir.$file) && strncmp($file, '.', 1) !== 0) {
                    self::getFileNames($source_dir.$file.DIRECTORY_SEPARATOR, $include_path, true);
                } else if (strncmp($file, '.', 1) !== 0) {
                    $_filedata[] = ($include_path == true) ? $source_dir.$file : $file;
                }
            }
            return $_filedata;
        } else {
            return false;
        }
    }

    /**
     * Get Directory File Information
     *
     * Reads the specified directory and builds an array containing the filenames,
     * filesize, dates, and permissions
     *
     * Any sub-folders contained within the specified path are read as well.
     *
     * @param string $source_dir path to source
     * @param bool $top_level_only Look only at the top level directory specified?
     * @param bool $_recursion internal variable to determine recursion status - do not use in calls
     * @return array
     */
    public static function getDirFileInfo($source_dir, $top_level_only = true, $_recursion = false)
    {
        static $_filedata = array();
        $relative_path = $source_dir;

        if ($fp = @opendir($source_dir)) {
            // reset the array and make sure $source_dir has a trailing slash on the initial call
            if ($_recursion === false) {
                $_filedata = array();
                $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            }

            // foreach (scandir($source_dir, 1) as $file) // In addition to being PHP5+, scandir() is simply not as fast
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($source_dir.$file) AND strncmp($file, '.', 1) !== 0 AND $top_level_only === false) {
                    self::getDirFileInfo($source_dir.$file.DIRECTORY_SEPARATOR, $top_level_only, true);
                } else if (strncmp($file, '.', 1) !== 0) {
                    $_filedata[$file] = self::getFileInfo($source_dir.$file);
                    $_filedata[$file]['relative_path'] = $relative_path;
                }
            }

            return $_filedata;
        } else {
            return false;
        }
    }

    /**
     * Get File Info
     *
     * Given a file and path, returns the name, path, size, date modified
     * Second parameter allows you to explicitly declare what information you want returned
     * Options are: name, server_path, size, date, readable, writable, executable, fileperms
     * Returns FALSE if the file cannot be found.
     *
     * @param string $file path to file
     * @param mixed $returned_values array or comma separated string of information returned
     * @return array
     */
    public static function getFileInfo($file, $returned_values = array('name', 'server_path', 'size', 'date'))
    {

        if (!file_exists($file)) {
            return false;
        }

        if (is_string($returned_values)) {
            $returned_values = explode(',', $returned_values);
        }

        foreach ($returned_values as $key) {
            switch ($key) {
                case 'name':
                    $fileinfo['name'] = substr(strrchr($file, DIRECTORY_SEPARATOR), 1);
                    break;
                case 'server_path':
                    $fileinfo['server_path'] = $file;
                    break;
                case 'size':
                    $fileinfo['size'] = filesize($file);
                    break;
                case 'date':
                    $fileinfo['date'] = filemtime($file);
                    break;
                case 'readable':
                    $fileinfo['readable'] = is_readable($file);
                    break;
                case 'writable':
                    // There are known problems using is_weritable on IIS.  It may not be reliable - consider fileperms()
                    $fileinfo['writable'] = is_writable($file);
                    break;
                case 'executable':
                    $fileinfo['executable'] = is_executable($file);
                    break;
                case 'fileperms':
                    $fileinfo['fileperms'] = fileperms($file);
                    break;
            }
        }

        return $fileinfo;
    }

    /**
     * Get Mime by Extension
     *
     * Translates a file extension into a mime type based on config/mimes.php.
     * Returns FALSE if it can't determine the type, or open the mime config file
     *
     * Note: this is NOT an accurate way of determining file mime types, and is here strictly as a convenience
     * It should NOT be trusted, and should certainly NOT be used for security
     *
     * @param string $file path to file
     * @return mixed
     */
    public static function getMimeByExtension($file)
    {
        $extension = strtolower(substr(strrchr($file, '.'), 1));
        Fly::loadConfig('config.mimes', true);
        $mimes = Fly::getConfig('mimes');
        if (empty($mimes)) {
            return false;
        }

        if (array_key_exists($extension, $mimes)) {
            if (is_array($mimes[$extension])) {
                // Multiple mime types, just give the first one
                return current($mimes[$extension]);
            } else {
                return $mimes[$extension];
            }
        } else {
            return false;
        }
    }

    /**
     * Symbolic Permissions
     *
     * Takes a numeric value representing a file's permissions and returns
     * standard symbolic notation representing that value
     *
     * @param int
     * @return string
     */
    public static function getSymbolicPermissions($perms)
    {
        if (($perms & 0xC000) == 0xC000) {
            $symbolic = 's'; // Socket
        } else if (($perms & 0xA000) == 0xA000) {
            $symbolic = 'l'; // Symbolic Link
        } else if (($perms & 0x8000) == 0x8000) {
            $symbolic = '-'; // Regular
        } else if (($perms & 0x6000) == 0x6000) {
            $symbolic = 'b'; // Block special
        } else if (($perms & 0x4000) == 0x4000) {
            $symbolic = 'd'; // Directory
        } else if (($perms & 0x2000) == 0x2000) {
            $symbolic = 'c'; // Character special
        } else if (($perms & 0x1000) == 0x1000) {
            $symbolic = 'p'; // FIFO pipe
        } else {
            $symbolic = 'u'; // Unknown
        }

        // Owner
        $symbolic .= (($perms & 0x0100) ? 'r' : '-');
        $symbolic .= (($perms & 0x0080) ? 'w' : '-');
        $symbolic .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $symbolic .= (($perms & 0x0020) ? 'r' : '-');
        $symbolic .= (($perms & 0x0010) ? 'w' : '-');
        $symbolic .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

        // World
        $symbolic .= (($perms & 0x0004) ? 'r' : '-');
        $symbolic .= (($perms & 0x0002) ? 'w' : '-');
        $symbolic .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

        return $symbolic;
    }

    /**
     * Octal Permissions
     *
     * Takes a numeric value representing a file's permissions and returns
     * a three character string representing the file's octal permissions
     *
     * @param int
     * @return string
     */
    public static function getOctalPermissions($perms)
    {
        return substr(sprintf('%o', $perms), -3);
    }

    /**
     * Create a Directory Map
     *
     * Reads the specified directory and builds an array
     * representation of it.  Sub-folders contained with the
     * directory will be mapped as well.
     *
     * @param string $source_dir path to source
     * @param int $directory_depth depth of directories to traverse (0 = fully recursive, 1 = current dir, etc)
     * @return array
     */
    public static function getDirectoryMap($source_dir, $directory_depth = 0, $hidden = false)
    {
        if ($fp = @opendir($source_dir)) {
            $filedata = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

            while (false !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if (!trim($file, '.') OR ($hidden == false && $file[0] == '.')) {
                    continue;
                }

                if (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir.$file)) {
                    $filedata[$file] = self::getDirectoryMap($source_dir.$file.DIRECTORY_SEPARATOR, $new_depth, $hidden);
                } else {
                    $filedata[] = $file;
                }
            }

            closedir($fp);
            return $filedata;
        }
        return false;
    }

    /**
     * Shared environment safe version of mkdir. Supports recursive creation.
     * For avoidance of umask side-effects chmod is used.
     *
     * @static
     * @param string $dst path to be created
     * @param array $options newDirMode element used, must contain access bitmask.
     * @param boolean $recursive
     * @return boolean result of mkdir
     * @see mkdir
     */
    public static function mkdir($dst, $options = array(), $recursive = true)
    {
        $prevDir = dirname($dst);
        if ($recursive && !is_dir($dst) && !is_dir($prevDir)) {
            self::mkdir(dirname($dst), $options, true);
        }
        $mode = isset($options['newDirMode']) ? $options['newDirMode'] : 0777;
        $res = mkdir($dst, $mode);
        chmod($dst, $mode);
        return $res;
    }

    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute.  is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @return void
     */
    public static function isReallyWritable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR == '/' && @ini_get("safe_mode") == false) {
            return is_writable($file);
        }

        // For windows servers and safe_mode "on" installations we'll actually
        // write a file then read it.  Bah...
        if (is_dir($file)) {
            $file = rtrim($file, '/').'/'.md5(mt_rand(1, 100).mt_rand(1, 100));

            if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, DIR_WRITE_MODE);
            @unlink($file);
            return true;
        } else if (!is_file($file) OR ($fp = @fopen($file, FOPEN_WRITE_CREATE)) === false) {
            return false;
        }

        fclose($fp);
        return true;
    }
}