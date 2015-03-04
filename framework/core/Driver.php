<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Simple factory class based, defines a simple factory practice standards.
 * You can inherit this class to achieve a simple framework based on the standard factory.
 */
class DriverManager extends Component
{
    /**
     * @var array Save the new drive to this array.
     */
    protected $_instance = array();
    /**
     * @var array The valid Drivers list.
     */
    protected $_validDrivers = array();
    /**
     * @var string The driving current use.
     */
    protected $_adapter = '';
    /**
     * @var string If the drive does not exist, use the default drive $_backupDriver.
     */
    protected $_backupDriver = '';
    /**
     * @var string The driver prefix.
     */
    protected $_driverPrefix = '';
    /**
     * @var array The driving component configuration.
     */
    protected $_components = array();

    /**
     * Constructor
     * @param array The DriverManager configuration.
     */
    public function __construct($config = array())
    {
        if (!empty($config)) {
            $this->_initialize($config);
        }
    }

    /**
     * Set the current used driver.
     * @param string $adapter The driver name.
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
    }

    /**
     * Set Backup Driver
     * @param string $driver The driver name.
     */
    public function setBackupDriver($driver)
    {
        if (in_array($driver, $this->_validDrivers)) {
            $this->_backupDriver = $driver;
        }
    }

    /**
     * Set cache components's configuration
     * @param array $config
     */
    public function setComponents($config)
    {
        $this->_components = $config;
    }

    /**
     * Set valid drivers
     * @param string $drivers The valid drivers.
     */
    public function setValidDrivers($drivers)
    {
        if (empty($this->_validDrivers)) {
            $this->_validDrivers = $drivers;
        } else {
            $this->_validDrivers = array_merge($this->_validDrivers, $drivers);
        }
    }

    /**
     * Set the driver prefix.
     * @param string $driverPrefix
     */
    protected function setDriverPrefix($driverPrefix)
    {
        $this->_driverPrefix = $driverPrefix;
    }

    /**
     * Initialize class properties based on the configuration array.
     *
     * @param array $config
     * @return void
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
     * Get the driver object through the drive name, e.g.:{DriverManager->XXX}
     * @param string $child The driver name.
     * @return mixed|null The driver object.
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
 * Driver class, you can inherit it for their own driver.
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
