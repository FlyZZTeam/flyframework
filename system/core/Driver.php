<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2006 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Driver Library Class
 *
 * This class enables you to create "Driver" libraries that add runtime ability
 * to extend the capabilities of a class via additional driver objects
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link
 */
class DriverManager extends Component
{

    protected $_instance = array();
    protected $_validDrivers = array ();
    protected $_adapter = '';
    protected $_backupDriver = '';
    protected $_driverPrefix = '';
    protected $_components = array();

    /**
     * Constructor
     *
     * @param array
     */
    public function __construct($config = array())
    {
        if (!empty($config)) {
            $this->_initialize($config);
        }
    }

    /**
     * Set cache adapter
     *
     * @param $adapter
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
    }

    /**
     * Set Backup Driver
     * @param $driver
     */
    public function setBackupDriver($driver)
    {
        if (in_array($driver, $this->_validDrivers)) {
            $this->_backupDriver = $driver;
        }
    }

    /**
     * Set Cache Components's Config
     *
     * @param $config
     */
    public function setComponents($config)
    {
        $this->_components = $config;
    }

    /**
     * Set valid drivers
     *
     * @param $drivers
     */
    public function setValidDrivers($drivers)
    {
        if (empty($this->_validDrivers)) {
            $this->_validDrivers = $drivers;
        } else {
            $this->_validDrivers = array_merge($this->_validDrivers, $drivers);
        }
    }

    protected function setDriverPrefix($driverPrefix)
    {
        $this->_driverPrefix = $driverPrefix;
    }

    /**
     * Initialize
     *
     * Initialize class properties based on the configuration array.
     *
     * @param	array
     * @return 	void
     */
    private function _initialize($config)
    {
        if (isset($config['_validDrivers']) && is_array($config['_validDrivers'])) {
            $this->setValidDrivers($config['_validDrivers']);
        }

        if (isset($config['components'])) {
            $this->setComponents($config['components']);
        }

        if (isset($config['backup'])) {
            $this->setBackupDriver($config['backup']);
        }
    }

    /**
     * __get()
     *
     * @param 	child
     * @return 	object
     */
    public function __get($child)
    {
        $driverName = $child;
        if (in_array(strtolower($driverName), $this->_validDrivers)) {
            $obj = null;
            $driverClass = $this->_driverPrefix.ucfirst($driverName);
            if (isset($this->_instance[$driverClass]) && is_object($this->_instance[$driverClass])) {
                $obj = $this->_instance[$driverClass];
            } else {
                $config = array();
                if (isset($this->_components[$driverClass])) {
                    $config = $this->_components[$driverClass];
                }
                $obj = new $driverClass($config);
                $this->_instance[$driverClass] = $obj;
            }

            if (!$obj->isSupported()) {
                $this->_adapter = $this->_backupDriver;
                return $this->{$this->_adapter};
            } else {
                $this->_adapter = $driverName;
            }
            return $obj;
        } else {
            return parent::__get($driverName);
        }
    }

}


/**
 * CodeIgniter Driver Class
 *
 * This class enables you to create drivers for a Library based on the Driver Library.
 * It handles the drivers' access to the parent library
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link
 */
class Driver extends Component
{
    /**
     * Driver is supported?
     * @return bool
     */
    public function isSupported()
    {
        return true;
    }
}
