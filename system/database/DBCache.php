<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Database Cache Class
 */
class DBCache
{

    /**
     * @var allows passing of db object so that multiple database connections and returned db objects can be supported
     */
    var $db;

    /**
     * Constructor
     * Grabs the super object instance so we can access it.
     *
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Set Cache Directory Path
     * @param string $path the path to the cache directory
     * @return bool
     */
    public function checkPath($path = '')
    {
        if ($path == '') {
            if ($this->db->cachedir == '') {
                return $this->db->cacheOff();
            }

            $path = $this->db->cachedir;
        }

        // Add a trailing slash to the path if needed
        $path = preg_replace("/(.+?)\/*$/", "\\1/", $path);

        if (!is_dir($path) || !FileHelper::isReallyWritable($path)) {

            // If the path is wrong we'll turn off caching
            return $this->db->cacheOff();
        }
        $this->db->cachedir = rtrim($path, '/').'/';

        return true;
    }

    /**
     * Return Cache Directory Path
     * @return string
     */
    public function getCachePath()
    {
        $module = Fly::app()->getRouter()->fetchModule();
        $controller = Fly::app()->getRouter()->fetchClass();
        $action = Fly::app()->getRouter()->fetchMethod();
        $info = array();
        if ($module !== '') {
            $info[] = $module;
        }
        $info[] = $controller === '' ? 'index' : $controller;
        $info[] = $action === '' ? 'index' : $action;
        $filepath = $this->db->cachedir.$this->getCacheDirName($info).'/';
        return $filepath;
    }

    /**
     * Return Dir name
     * @param $info array
     */
    protected function getCacheDirName($info)
    {
        if (is_array($info)) {
            $dir = implode('_', $info);
            return $dir;
        }
        return $info;
    }

    /**
     * Retrieve a cached query
     *
     * The URI being requested will become the name of the cache sub-folder.
     * An MD5 hash of the SQL statement will become the cache file name
     *
     * @return string
     */
    public function read($sql)
    {
        if (!$this->checkPath()) {
            return $this->db->cacheOff();
        }

        $filepath = $this->getCachePath().md5($sql);

        if (false === ($cachedata = FileHelper::readFile($filepath))) {
            return false;
        }

        return unserialize($cachedata);
    }

    /**
     * Write a query to a cache file
     * @return bool
     */
    public function write($sql, $object)
    {
        if (!$this->checkPath()) {
            return $this->db->cacheOff();
        }

        $dir_path = $this->getCachePath();

        $filename = md5($sql);

        if (!@is_dir($dir_path)) {
            if (!@mkdir($dir_path, DIR_WRITE_MODE)) {
                return false;
            }

            @chmod($dir_path, DIR_WRITE_MODE);
        }

        if (FileHelper::writeFile($dir_path.$filename, serialize($object)) === false) {
            return false;
        }

        @chmod($dir_path.$filename, FILE_WRITE_MODE);
        return true;
    }

    /**
     * Delete cache files within a particular directory
     * @return bool
     */
    public function delete($m = '', $c = '', $a = '')
    {

        $ary = array();
        if ($m !== '') {
            $ary[] = $m;
        }
        $ary[] = $c === '' ? 'index' : $c;
        $ary[] = $a === '' ? 'index' : $a;
        $dir_path = $this->db->cachedir.$this->getCacheDirName($ary).'/';

        FileHelper::deleteFiles($dir_path, true);
    }

    /**
     * Delete all existing cache files
     * @return bool
     */
    public function deleteAll()
    {
        FileHelper::deleteFiles($this->db->cachedir, true);
    }
}