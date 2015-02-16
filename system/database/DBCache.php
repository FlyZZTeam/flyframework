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
 * Database Cache Class
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class DBCache
{

	var $db;	// allows passing of db object so that multiple database connections and returned db objects can be supported

	/**
	 * Constructor
	 *
	 * Grabs the CI super object instance so we can access it.
	 *
	 */
	public function __construct($db)
	{
		// Assign the main CI object to $this->CI
		// and load the file helper since we use it a lot
		$this->db = $db;
	}

	/**
	 * Set Cache Directory Path
	 *
	 * @access	public
	 * @param	string	the path to the cache directory
	 * @return	bool
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
		$path = preg_replace("/(.+?)\/*$/", "\\1/",  $path);

		if ( ! is_dir($path) || ! FileHelper::isReallyWritable($path)) {

			// If the path is wrong we'll turn off caching
			return $this->db->cacheOff();
		}
		$this->db->cachedir = rtrim($path, '/').'/';

		return TRUE;
	}

    /**
     * Return Cache Directory Path
     *
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
     *
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
	 * @access	public
	 * @return	string
	 */
	public function read($sql)
	{
		if (!$this->checkPath()) {
			return $this->db->cacheOff();
		}

		$filepath = $this->getCachePath().md5($sql);

		if (FALSE === ($cachedata = FileHelper::readFile($filepath))) {
			return FALSE;
		}

		return unserialize($cachedata);
	}

	/**
	 * Write a query to a cache file
	 *
	 * @access	public
	 * @return	bool
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
				return FALSE;
			}

			@chmod($dir_path, DIR_WRITE_MODE);
		}

		if (FileHelper::writeFile($dir_path.$filename, serialize($object)) === FALSE) {
			return FALSE;
		}

		@chmod($dir_path.$filename, FILE_WRITE_MODE);
		return TRUE;
	}

	/**
	 * Delete cache files within a particular directory
	 *
	 * @access	public
	 * @return	bool
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

		FileHelper::deleteFiles($dir_path, TRUE);
	}

	/**
	 * Delete all existing cache files
	 *
	 * @access	public
	 * @return	bool
	 */
	public function deleteAll()
	{
		FileHelper::deleteFiles($this->db->cachedir, TRUE);
	}

}