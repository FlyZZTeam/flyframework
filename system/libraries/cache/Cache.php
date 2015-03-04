<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Caching Class
 */
class Cache extends DriverManager
{

    /**
     * Constructor
     * @param array
     */
    public function __construct($config = array())
    {
        $drivers = array(
            'apc',
            'file',
            'memcached',
            'dummy'
        );
        $this->setValidDrivers($drivers);
        $this->setAdapter('dummy');
        $this->setBackupDriver('dummy');
        $this->setDriverPrefix('Cache');
        parent::__construct($config);
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
        return $this->{$this->_adapter}->get($id);
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
        return $this->{$this->_adapter}->save($id, $data, $ttl);
    }

    /**
     * Delete from Cache
     *
     * @param mixed $id unique identifier of the item in the cache
     * @return boolean true on success/false on failure
     */
    public function delete($id)
    {
        return $this->{$this->_adapter}->delete($id);
    }

    /**
     * Clean the cache
     *
     * @return boolean false on failure/true on success
     */
    public function clean()
    {
        return $this->{$this->_adapter}->clean();
    }

    /**
     * Cache Info
     *
     * @param string $type user/filehits
     * @return mixed array on success, false on failure
     */
    public function cacheInfo($type = 'user')
    {
        return $this->{$this->_adapter}->cacheInfo($type);
    }

    /**
     * Get Cache Metadata
     *
     * @param mixed $id key to get cache metadata on
     * @return mixed return value from child method
     */
    public function getMetaData($id)
    {
        return $this->{$this->_adapter}->getMetaData($id);
    }

    /**
     * Is the requested driver supported in this environment?
     *
     * @param string The driver to test.
     * @return array
     */
    public function isSupported($driver)
    {
        static $support = array();

        if (!isset($support[$driver])) {
            $support[$driver] = $this->{$driver}->isSupported();
        }

        return $support[$driver];
    }
}