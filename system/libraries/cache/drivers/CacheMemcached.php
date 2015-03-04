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
class CacheMemcached extends Driver
{

    private $_memcached;
    protected $_servers = array(
        'default' => array(
            'default_host' => '127.0.0.1',
            'default_port' => 11211,
            'default_weight' => 1
        )
    );

    public function __construct($config = array())
    {
        $this->_setupMemcached($config);
    }

    public function setServers($config)
    {
        $this->_servers = $config;
        foreach ($this->_servers as $name => $cache_server) {
            if (!array_key_exists('hostname', $cache_server)) {
                $cache_server['hostname'] = '127.0.0.1';
            }

            if (!array_key_exists('port', $cache_server)) {
                $cache_server['port'] = 11211;
            }

            if (!array_key_exists('weight', $cache_server)) {
                $cache_server['weight'] = 1;
            }

            $this->_memcached->addServer(
                $cache_server['hostname'], $cache_server['port'], $cache_server['weight']
            );
        }
    }

    /**
     * Fetch from cache
     *
     * @param mixed $id unique key id
     * @return mixed data on success/false on failure
     */
    public function get($id)
    {
        $data = $this->_memcached->get($id);
        return (is_array($data)) ? $data[0] : false;
    }

    /**
     * Save
     *
     * @param string $id unique identifier
     * @param mixed $data data being cached
     * @param int $ttl time to live
     * @return boolean true on success, false on failure
     */
    public function save($id, $data, $ttl = 60)
    {
        return $this->_memcached->set($id, array($data, time(), $ttl), $ttl);
        return false;
    }

    /**
     * Delete from Cache
     *
     * @param mixed $id key to be deleted.
     * @return boolean true on success, false on failure
     */
    public function delete($id)
    {
        return $this->_memcached->delete($id);
    }

    /**
     * Clean the Cache
     *
     * @return boolean false on failure/true on success
     */
    public function clean()
    {
        return $this->_memcached->flush();
    }

    /**
     * Cache Info
     *
     * @param null $type type not supported in memcached
     * @return mixed array on success, false on failure
     */
    public function cacheInfo($type = null)
    {
        return $this->_memcached->getStats();
    }

    /**
     * Get Cache Metadata
     *
     * @param mixed $id key to get cache metadata on
     * @return mixed FALSE on failure, array on success.
     */
    public function getMetaData($id)
    {
        $stored = $this->_memcached->get($id);

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
     * Setup memcached.
     */
    private function _setupMemcached($config = array())
    {
        if (!$this->isSupported()) {
            return false;
        }
        // Try to load memcached server info from the config file.
        if (isset($config['servers'])) {
            $this->_servers = $config['servers'];
        } else if (Fly::loadConfig('config.memcached', true)) {
            $memcachedInfo = Fly::getConfig('memcached');
            if (isset($memcachedInfo['servers'])) {
                $this->_servers = $memcachedInfo['servers'];
            }
        }

        $this->_memcached = new Memcached();

        foreach ($this->_servers as $name => $cache_server) {
            if (!array_key_exists('hostname', $cache_server)) {
                $cache_server['hostname'] = '127.0.0.1';
            }

            if (!array_key_exists('port', $cache_server)) {
                $cache_server['port'] = 11211;
            }

            if (!array_key_exists('weight', $cache_server)) {
                $cache_server['weight'] = 1;
            }

            $this->_memcached->addServer(
                $cache_server['hostname'], $cache_server['port'], $cache_server['weight']
            );
        }
    }

    /**
     * Is supported
     *
     * Returns FALSE if memcached is not supported on the system.
     * If it is, we setup the memcached object & return TRUE
     */
    public function isSupported()
    {
        if (!extension_loaded('memcached')) {
            Fly::log('error', 'The Memcached Extension must be loaded to use Memcached Cache.');
            return false;
        }
        return true;
    }
}
