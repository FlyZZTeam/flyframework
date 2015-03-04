<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * APC Caching Class
 */
class CacheApc extends Driver
{

    public function __construct($config = array())
    {
    }

    /**
     * Get
     *
     * Look for a value in the cache.  If it exists, return the data
     * if not, return FALSE
     *
     * @param string
     * @return mixed value that is stored/FALSE on failure
     */
    public function get($id)
    {
        $data = apc_fetch($id);

        return (is_array($data)) ? $data[0] : false;
    }

    /**
     * Cache Save
     *
     * @param string $id Unique Key
     * @param mixed $data Data to store
     * @param int $ttl Length of time (in seconds) to cache the data
     *
     * @return boolean true on success/false on failure
     */
    public function save($id, $data, $ttl = 60)
    {
        return apc_store($id, array($data, time(), $ttl), $ttl);
    }

    /**
     * Delete from Cache
     *
     * @param mixed $id unique identifier of the item in the cache
     * @param boolean true on success/false on failure
     */
    public function delete($id)
    {
        return apc_delete($id);
    }

    /**
     * Clean the cache
     *
     * @return boolean false on failure/true on success
     */
    public function clean()
    {
        return apc_clear_cache('user');
    }

    /**
     * Cache Info
     *
     * @param string $type user/filehits
     * @return mixed array on success, false on failure
     */
    public function cacheInfo($type = null)
    {
        return apc_cache_info($type);
    }

    /**
     * Get Cache Metadata
     *
     * @param mixed $id key to get cache metadata on
     * @return mixed array on success/false on failure
     */
    public function getMetaData($id)
    {
        $stored = apc_fetch($id);

        if (count($stored) !== 3) {
            return false;
        }

        list($data, $time, $ttl) = $stored;

        return array(
            'expire' => $time + $ttl,
            'mtime' => $time,
            'data' => $data
        );
    }

    /**
     * is_supported()
     *
     * Check to see if APC is available on this system, bail if it isn't.
     */
    public function isSupported()
    {
        if (!extension_loaded('apc') || ini_get('apc.enabled') != "1") {
            Fly::log('error', 'The APC PHP extension must be loaded to use APC Cache.');
            return false;
        }
        return true;
    }
}