<?php
/**
 * Module is the base class for module and application classes.
 * Module mainly manages application components and sub-modules.
 *
 * @author zz@flyzz.net
 * @link http://www.flyzz.net
 * @copyright Copyright 2014 flyzz.net
 */

/**
 *
 * @property string $id The module ID.
 * @property string $basePath The root directory of the module. Defaults to the directory containing the module class.
 * @property array $params The list of user-defined parameters.
 * @property string $modulePath The directory that contains the application modules. Defaults to the 'modules' subdirectory of {@link basePath}.
 * @property Module $parentModule The parent module. Null if this module does not have a parent.
 * @property array $modules The configuration of the currently installed modules (module ID => configuration).
 * @property array $components The application components (indexed by their IDs).
 * @property array $import List of aliases to be imported.
 * @property array $aliases List of aliases to be defined. The array keys are root aliases,
 * while the array values are paths or aliases corresponding to the root aliases.
 *
 */

abstract class Module extends Component
{
	/**
	 * @var array the IDs of the application components that should be preloaded.
	 */
	public $preload = array();

    /**
     * @var array the behaviors that should be attached to the module.
     * The behaviors will be attached to the module when {@link init} is called.
     */
    public $behaviors = array();

	private $_id;
	private $_parentModule;
	private $_basePath;
	protected $_modulePath;
	private $_params;
	private $_modules = array();
	private $_moduleConfig = array();
	private $_components = array();
	private $_componentConfig = array();

	/**
	 * Constructor.
	 * @param string $id the ID of this module
	 * @param Module $parent the parent module (if any)
	 * @param mixed $config the module configuration. It can be either an array or
	 * the path of a PHP file returning the configuration array.
	 */
	public function __construct($id, $parent, $config = null)
	{
		$this->_id = $id;
		$this->_parentModule = $parent;

		// set basePath at early as possible to avoid trouble
		if (is_string($config)) {
			$config = require($config);
		}
		
		if (isset($config['base_path'])) {
			$this->setBasePath($config['base_path']);
			unset($config['base_path']);
		}
		
		Fly::setPathOfAlias($id, $this->getBasePath());
		$this->preinit();
		$this->configure($config);
		$this->preloadComponents();
		$this->init();
	}

	/**
	 * Getter magic method.
	 * This method is overridden to support accessing application components
	 * like reading module properties.
	 * @param string $name application component or property name
	 * @return mixed the named property value
	 */
	public function __get($name)
	{
		if($this->hasComponent($name)) {
			return $this->getComponent($name);
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * Checks if a property value is null.
	 * This method overrides the parent implementation by checking
	 * if the named application component is loaded.
	 * @param string $name the property name or the event name
	 * @return boolean whether the property value is null
	 */
	public function __isset($name)
	{
		if($this->hasComponent($name)) {
			return $this->getComponent($name) !== null;
		} else {
			return parent::__isset($name);
		}
	}

	/**
	 * Returns the module ID.
	 * @return string the module ID.
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Sets the module ID.
	 * @param string $id the module ID
	 */
	public function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * Returns the root directory of the module.
	 * @return string the root directory of the module. Defaults to the directory containing the module class.
	 */
	public function getBasePath()
	{
		if($this->_basePath === null) {
			$class = new ReflectionClass(get_class($this));
			$this->_basePath = dirname($class->getFileName());
		}
		return $this->_basePath;
	}

	/**
	 * Set the root directory of the module.
	 * This method can only be invoked at the beginning of the constructor.
	 * @param string $path the root directory of the module.
	 * @throws FlyException if the directory does not exist.
	 */
	public function setBasePath($path)
	{
		if (($this->_basePath = realpath($path)) === false || !is_dir($this->_basePath)) {
			throw new FlyException(Fly::t('fly','Base path "{path}" is not a valid directory.',
				array('{path}'=>$path)));
		}
	}

	/**
	 * Returns user-defined parameters.
	 * @return the Array of user-defined parameters
	 */
	public function getParams()
	{
		if ($this->_params !== null) {
			return $this->_params;
		} else {
			$this->_params = array();
			return $this->_params;
		}
	}

	/**
	 * Sets user-defined parameters.
	 * @param array $value user-defined parameters. This should be in name-value pairs.
	 */
	public function setParams($value)
	{
		foreach ($value as $k=>$v) {
			$this->_params[$k] = $v;
		}
	}

	/**
	 * Returns the directory that contains the application modules.
	 * @return string the directory that contains the application modules. Defaults to the 'modules' subdirectory of {@link basePath}.
	 */
	public function getModulePath()
	{
		if ($this->_modulePath !== null) {
			return $this->_modulePath;
		} else {
            if ($this->_parentModule) {
                return $this->_parentModule->getModulePath();
            }
            return $this->_modulePath = $this->getBasePath().DIRECTORY_SEPARATOR.'modules';
		}
	}

	/**
	 * Sets the directory that contains the application modules.
	 * @param string $value the directory that contains the application modules.
	 * @throws FlyException if the directory is invalid
	 */
	public function setModulePath($value)
	{
		if (($this->_modulePath = realpath($value)) === false || !is_dir($this->_modulePath)) {
			throw new FlyException(Fly::t('fly','The module path "{path}" is not a valid directory.',
				array('{path}'=>$value)));
		}

        /*
        if ($this->_parentModule) {
            $id = $this->_parentModule->_id;
            if ($id && $id !== '') {
                $this->_modulePath .= DIRECTORY_SEPARATOR.$id;
                if (!is_dir($this->_modulePath)) {
                    throw new FlyException(Fly::t('fly','The module path "{path}" is not a valid directory.',
                        array('{path}'=>$this->_modulePath)));
                }
                Fly::setPathOfAlias($id, $this->_modulePath);
            }
        }*/
	}

    /**
     * Configures the module with the specified configuration.
     * @param array $config the configuration array
     */
    public function configure($config)
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $setter = 'set'.$key;
                if (property_exists($this, $key) || method_exists($this, $setter)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Sets the aliases that are used in the module.
     * @param array $aliases list of aliases to be imported
     */
    public function setImport($aliases)
    {
        if (!is_array($aliases)) {
            return false;
        }
        foreach ($aliases as $alias) {
            Fly::import($alias);
        }
    }

    /**
     * Defines the root aliases.
     * @param array $mappings list of aliases to be defined. The array keys are root aliases,
     * while the array values are paths or aliases corresponding to the root aliases.
     * For example,
     * <pre>
     * array(
     *    'models'=>'application.models',              // an existing alias
     *    'extensions'=>'application.extensions',      // an existing alias
     *    'backend'=>dirname(__FILE__).'/../backend',  // a directory
     * )
     * </pre>
     */
    public function setAliases($mappings)
    {
        if (!is_array($mappings)) {
            return false;
        }
        foreach ($mappings as $name => $alias) {
            if(($path = Fly::getPathOfAlias($alias)) !== false) {
                Fly::setPathOfAlias($name, $path);
            } else {
                Fly::setPathOfAlias($name, $alias);
            }
        }
    }

	/**
	 * Returns the parent module.
	 * @return CModule the parent module. Null if this module does not have a parent.
	 */
	public function getParentModule()
	{
		return $this->_parentModule;
	}

    /**
     * Return standard module name
     *
     * @param $name string
     * @return string
     */
    public function getModuleName($name)
    {
        $name = ucfirst($name).'Module';
        return $name;
    }

	/**
	 * Retrieves the named application module.
	 * The module has to be declared in {@link modules}. A new instance will be created
	 * when calling this method with the given ID for the first time.
	 * @param string $id application module ID (case-sensitive)
	 * @return Module the module instance, null if the module is disabled or does not exist.
	 */
	public function getModule($id)
	{
		if (isset($this->_modules[$id]) || array_key_exists($id, $this->_modules)) {
			return $this->_modules[$id];
		} else {
            $config = array();
            if (isset($this->_moduleConfig[$id])) {
                $config = $this->_moduleConfig[$id];
            }
            if (!empty($config) && isset($config['class'])) {
			    $class = $config['class'];
            } else {
                $class = $this->getModuleName($id);
            }
            if (!isset($config['enabled']) || $config['enabled']) {
                Fly::trace("Loading \"$id\" module", 'system.core.Module');
                if($this === Fly::app()) {
			        $module = Fly::createComponent($class, $id, $this, $config);
			    } else {
			        $module = Fly::createComponent($class, $this->getId().'/'.$id, $this, $config);
			    }
			    return $this->_modules[$id] = $module;
            }
		}
        return null;
	}

	/**
	 * Returns a value indicating whether the specified module is installed.
	 * @param string $id the module ID
	 * @return boolean whether the specified module is installed.
	 */
	public function hasModule($id)
	{
		return isset($this->_moduleConfig[$id]) || isset($this->_modules[$id]);
	}

	/**
	 * Returns the configuration of the currently installed modules.
	 * @return array the configuration of the currently installed modules (module ID => configuration)
	 */
	public function getModules()
	{
		return $this->_moduleConfig;
	}

	/**
	 * Configures the sub-modules of this module.
	 *
	 * Call this method to declare sub-modules and configure them with their initial property values.
	 * The parameter should be an array of module configurations. Each array element represents a single module,
	 * which can be either a string representing the module ID or an ID-configuration pair representing
	 * a module with the specified ID and the initial property values.
	 *
	 * For example, the following array declares two modules:
	 * <pre>
	 * array(
	 *     'admin',                // a single module ID
	 *     'payment'=>array(       // ID-configuration pair
	 *         'server'=>'paymentserver.com',
	 *     ),
	 * )
	 * </pre>
	 *
	 * By default, the module class is determined using the expression <code>ucfirst($moduleID).'Module'</code>.
	 * And the class file is located under <code>modules/$moduleID</code>.
	 * You may override this default by explicitly specifying the 'class' option in the configuration.
	 *
	 * You may also enable or disable a module by specifying the 'enabled' option in the configuration.
	 *
	 * @param array $modules module configurations.
	 */
	public function setModules($modules)
	{
		foreach ($modules as $id => $module) {
			if (is_int($id)) {
				$id = $module;
				$module = array();
			}
			if (!isset($module['class'])) {
				Fly::setPathOfAlias($id, $this->getModulePath().DIRECTORY_SEPARATOR.$id);
				$module['class'] = ucfirst($id).'Module';
			}

			if (isset($this->_moduleConfig[$id])) {
				//合并配置文件
				$this->_moduleConfig[$id] = array_merge($this->_moduleConfig[$id], $module);
			} else {
				$this->_moduleConfig[$id] = $module;
			}
		}
	}

	/**
	 * Checks whether the named component exists.
	 * @param string $id application component ID
	 * @return boolean whether the named application component exists (including both loaded and disabled.)
	 */
	public function hasComponent($id)
	{
		return isset($this->_components[$id]) || isset($this->_componentConfig[$id]);
	}

	/**
	 * Retrieves the named application component.
	 * @param string $id application component ID (case-sensitive)
	 * @param boolean $createIfNull whether to create the component if it doesn't exist yet.
	 * @return IApplicationComponent the application component instance, null if the application component is disabled or does not exist.
	 * @see hasComponent
	 */
	public function getComponent($id, $createIfNull = true)
	{
		if (isset($this->_components[$id])) {
			return $this->_components[$id];
		} else if($createIfNull) {
            $config = false;
            if (isset($this->_componentConfig[$id])) {
			    $config = $this->_componentConfig[$id];
            }
            if (!$config || empty($config)) {
                $config = $id;
            }

            if (!is_array($config) || !isset($config['enabled']) || $config['enabled']) {
                Fly::trace("Loading \"$id\" application component", 'system.Module');

                $component = Fly::createComponent($config);
                if (!$component) {
                    return null;
                }
                if (method_exists($component, 'init')) {
                    $component->init();
                }
                return $this->_components[$id] = $component;
            }
		}
        return null;
	}

	/**
	 * Puts a component under the management of the module.
	 * method if it has not done so.
	 * @param string $id component ID
	 * @param boolean $merge whether to merge the new component configuration
	 * with the existing one. Defaults to true, meaning the previously registered
	 * component configuration with the same ID will be merged with the new configuration.
	 * If set to false, the existing configuration will be replaced completely.
	 */
	public function setComponent($id, $component, $merge = true)
	{

		if ($component === null) {
			unset($this->_components[$id]);
			return;
		} else if (isset($this->_components[$id])) {
			if (isset($component['class']) && get_class($this->_components[$id]) !== $component['class']) {
				unset($this->_components[$id]);
				//we should ignore merge here
				$this->_componentConfig[$id] = $component; 
				return;
			}

			foreach ($component as $key => $value) {
				if($key !== 'class') {
					$this->_components[$id]->$key = $value;
				}
			}
		} else if (isset($this->_componentConfig[$id]['class'], $component['class'])
			&& $this->_componentConfig[$id]['class'] !== $component['class']) {
            //we should ignore merge here
            $this->_componentConfig[$id] = $component;
			return;
		}

		if(isset($this->_componentConfig[$id]) && $merge) {
			$this->_componentConfig[$id] = array_merge($this->_componentConfig[$id], $component);
		} else {
			$this->_componentConfig[$id] = $component;
		}
	}

	/**
	 * Returns the application components.
	 * @param boolean $loadedOnly whether to return the loaded components only. If this is set false,
	 * then all components specified in the configuration will be returned, whether they are loaded or not.
	 * Loaded components will be returned as objects, while unloaded components as configuration arrays.
	 * @return array the application components (indexed by their IDs)
	 */
	public function getComponents($loadedOnly = true)
	{
		if ($loadedOnly) {
			return $this->_components;
		} else {
			return array_merge($this->_componentConfig, $this->_components);
		}
	}

	/**
	 * Sets the application components.
	 *
	 * When a configuration is used to specify a component, it should consist of
	 * the component's initial property values (name-value pairs). Additionally,
	 * a component can be enabled (default) or disabled by specifying the 'enabled' value
	 * in the configuration.
	 *
	 * If a configuration is specified with an ID that is the same as an existing
	 * component or configuration, the existing one will be replaced silently.
	 *
	 * The following is the configuration for two components:
	 * <pre>
	 * array(
	 *     'db'=>array(
	 *         'class'=>'CDbConnection',
	 *         'connectionString'=>'sqlite:path/to/file.db',
	 *     ),
	 *     'cache'=>array(
	 *         'class'=>'CDbCache',
	 *         'connectionID'=>'db',
	 *     ),
	 * )
	 * </pre>
	 *
	 * @param array $components application components(id=>component configuration or instances)
	 * @param boolean $merge whether to merge the new component configuration with the existing one.
	 * Defaults to true, meaning the previously registered component configuration of the same ID
	 * will be merged with the new configuration. If false, the existing configuration will be replaced completely.
	 */
	public function setComponents($components, $merge = true)
	{
		foreach ($components as $id => $component) {
			$this->setComponent($id, $component, $merge);
		}
	}

	/**
	 * Loads static application components.
	 */
	protected function preloadComponents()
	{
		foreach ($this->preload as $id) {
			$this->getComponent($id);
		}
	}

	/**
	 * Preinitializes the module.
	 * This method is called at the beginning of the module constructor.
	 * You may override this method to do some customized preinitialization work.
	 * Note that at this moment, the module is not configured yet.
	 * @see init
	 */
	protected function preinit()
	{
	}

	/**
	 * Initializes the module.
	 * This method is called at the end of the module constructor.
	 * Note that at this moment, the module has been configured, the behaviors
	 * have been attached and the application components have been registered.
	 * @see preinit
	 */
	protected function init()
	{
	}
}

