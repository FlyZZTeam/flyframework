<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Memcached Caching Class
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
            $path = Fly::getConfig('cachePath');
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
     * @param mixed $id unique key id
     * @return mixed data on success/false on failure
     */
    public function get($id)
    {
        if (!file_exists($this->_cachePath.$id)) {
            return false;
        }

        $data = FileHelper::readFile($this->_cachePath.$id);
        $data = unserialize($data);

        if (time() > $data['time'] + $data['ttl']) {
            unlink($this->_cachePath.$id);
            return false;
        }

        return $data['data'];
    }

    /**
     * Save into cache
     *
     * @param string $id unique key
     * @param mixed $data data to store
     * @param int $ttl length of time (in seconds) the cache is valid
     *                    - Default is 60 seconds
     * @return boolean true on success/false on failure
     */
    public function save($id, $data, $ttl = 60)
    {
        $contents = array(
            'time' => time(),
            'ttl' => $ttl,
            'data' => $data
        );

        if (FileHelper::writeFile($this->_cachePath.$id, serialize($contents))) {
            @chmod($this->_cachePath.$id, 0777);
            return true;
        }

        return false;
    }

    /**
     * Delete from Cache
     *
     * @param mixed $id unique identifier of item in cache
     * @return boolean true on success/false on failure
     */
    public function delete($id)
    {
        return unlink($this->_cachePath.$id);
    }

    /**
     * Clean the Cache
     *
     * @return boolean false on failure/true on success
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
     * @param string $type user/filehits
     * @return mixed FALSE
     */
    public function cacheInfo($type = null)
    {
        return FileHelper::getDirFileInfo($this->_cachePath);
    }

    /**
     * Get Cache Metadata
     *
     * @param mixed $id key to get cache metadata on
     * @return mixed FALSE on failure, array on success.
     */
    public function getMetaData($id)
    {
        if (!file_exists($this->_cachePath.$id)) {
            return false;
        }

        $data = FileHelper::readFile($this->_cachePath.$id);
        $data = unserialize($data);

        if (is_array($data)) {
            $mtime = filemtime($this->_cachePath.$id);

            if (!isset($data['ttl'])) {
                return false;
            }

            return array(
                'expire' => $mtime + $data['ttl'],
                'mtime' => $mtime
            );
        }

        return false;
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