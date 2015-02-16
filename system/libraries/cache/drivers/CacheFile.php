<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2006 - 2014 EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Memcached Caching Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link
 */

class CacheFile extends Driver
{

	protected $_cachePath;

	/**
	 * Constructor
	 */
	public function __construct($config = array())
	{

        if (is_array($config) && isset($config['cachePath'])) {
            $this->_cachePath = trim($config['cachePath'], '/').'/';
        } else {
		    $path = Fly::getConfig('cache_path');
		    $this->_cachePath = ($path == '') ? Fly::app()->getBasePath().'/cache/' : trim($path, '/').'/';;
        }
	}

    public function setCachePath($path)
    {
        $this->_cachePath = $path;
    }

	/**
	 * Fetch from cache
	 *
	 * @param 	mixed		unique key id
	 * @return 	mixed		data on success/false on failure
	 */
	public function get($id)
	{
		if (!file_exists($this->_cachePath.$id)) {
			return FALSE;
		}

		$data = FileHelper::readFile($this->_cachePath.$id);
		$data = unserialize($data);

		if (time() >  $data['time'] + $data['ttl']) {
			unlink($this->_cachePath.$id);
			return FALSE;
		}

		return $data['data'];
	}

	/**
	 * Save into cache
	 *
	 * @param 	string		unique key
	 * @param 	mixed		data to store
	 * @param 	int		length of time (in seconds) the cache is valid
	 *					- Default is 60 seconds
	 * @return 	boolean		true on success/false on failure
	 */
	public function save($id, $data, $ttl = 60)
	{
		$contents = array(
				'time'		=> time(),
				'ttl'		=> $ttl,
				'data'		=> $data
			);

		if (FileHelper::writeFile($this->_cachePath.$id, serialize($contents))) {
			@chmod($this->_cachePath.$id, 0777);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of item in cache
	 * @return 	boolean		true on success/false on failure
	 */
	public function delete($id)
	{
		return unlink($this->_cachePath.$id);
	}

	/**
	 * Clean the Cache
	 *
	 * @return 	boolean		false on failure/true on success
	 */
	public function clean()
	{
		return FileHelper::deleteFiles($this->_cachePath);
	}

	/**
	 * Cache Info
	 *
	 * Not supported by file-based caching
	 *
	 * @param 	string	user/filehits
	 * @return 	mixed 	FALSE
	 */
	public function cacheInfo($type = NULL)
	{
		return FileHelper::getDirFileInfo($this->_cachePath);
	}

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	mixed		FALSE on failure, array on success.
	 */
	public function getMetaData($id)
	{
		if ( ! file_exists($this->_cachePath.$id)) {
			return FALSE;
		}

		$data = FileHelper::readFile($this->_cachePath.$id);
		$data = unserialize($data);

		if (is_array($data)) {
			$mtime = filemtime($this->_cachePath.$id);

			if (!isset($data['ttl'])) {
				return FALSE;
			}

			return array(
				'expire'	=> $mtime + $data['ttl'],
				'mtime'		=> $mtime
			);
		}

		return FALSE;
	}

	/**
	 * Is supported
	 *
	 * In the file driver, check to see that the cache directory is indeed writable
	 *
	 * @return boolean
	 */
	public function isSupported()
	{
		return FileHelper::isReallyWritable($this->_cachePath);
	}

}