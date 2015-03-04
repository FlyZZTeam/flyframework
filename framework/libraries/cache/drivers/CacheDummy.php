<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Dummy Caching Class
 */
class CacheDummy extends Driver
{

    public function __construct($config = array())
    {
    }

    /**
     * Get
     *
     * Since this is the dummy class, it's always going to return FALSE.
     *
     * @param string
     * @return Boolean FALSE
     */
    public function get($id)
    {
        return false;
    }

    /**
     * Cache Save
     *
     * @param string $id Unique Key
     * @param mixed $data Data to store
     * @param int $ttl Length of time (in seconds) to cache the data
     *
     * @return boolean TRUE, Simulating success
     */
    public function save($id, $data, $ttl = 60)
    {
        return true;
    }

    /**
     * Delete from Cache
     *
     * @param mixed $id unique identifier of the item in the cache
     * @param boolean TRUE, simulating success
     */
    public function delete($id)
    {
        return true;
    }

    /**
     * Clean the cache
     *
     * @return  boolean TRUE, simulating success
     */
    public function clean()
    {
        return true;
    }

    /**
     * Cache Info
     *
     * @param string $type user/filehits
     * @return boolean FALSE
     */
    public function cacheInfo($type = null)
    {
        return false;
    }

    /**
     * Get Cache Metadata
     *
     * @param mixed $id key to get cache metadata on
     * @return boolean FALSE
     */
    public function getMetaData($id)
    {
        return false;
    }

    /**
     * Is this caching driver supported on the system?
     * Of course this one is.
     *
     * @return TRUE;
     */
    public function isSupported()
    {
        return true;
    }
}