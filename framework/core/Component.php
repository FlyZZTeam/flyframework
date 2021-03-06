<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * CComponent is the base class for all components.
 * CComponent implements the protocol of defining, using properties and events.
 */
class Component
{
    private $_e;
    private $_m;

    /**
     * Returns a property value.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax:
     *
     * For example:
     * $value = $component->propertyName;
     *
     * @param string $name the property name or event name
     * @return mixed the property value
     * @throws FlyException if the property or event is not defined
     * @see __set
     */
    public function __get($name)
    {
        $getter = 'get'.$name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            $name = strtolower($name);
            if (!isset($this->_e[$name])) {
                $this->_e[$name] = array();
            }
            return $this->_e[$name];
        } else if (isset($this->_m[$name])) {
            return $this->_m[$name];
        } else if (is_array($this->_m)) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canGetProperty($name)))
                    return $object->$name;
            }
        }
        throw new FlyException(Fly::t('fly', 'Property "{class}.{property}" is not defined.',
            array('{class}' => get_class($this), '{property}' => $name)));
    }

    /**
     * Sets value of a component property.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax:
     *
     * For example
     * $this->propertyName = $value;
     *
     * @param string $name the property name
     * @param mixed $value the property value
     * @return mixed
     * @throws FlyException if the property/event is not defined or the property is read only.
     * @see __get
     */
    public function __set($name, $value)
    {
        $setter = 'set'.$name;
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        } else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            $name = strtolower($name);
            if (!isset($this->_e[$name]))
                $this->_e[$name] = array();
            return $this->_e[$name][] = $value;
        } else if (is_array($this->_m)) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canSetProperty($name))) {
                    return $object->$name = $value;
                }
            }
        }
        if (method_exists($this, 'get'.$name))
            throw new FlyException(Fly::t('fly', 'Property "{class}.{property}" is read only.',
                array('{class}' => get_class($this), '{property}' => $name)));
        else
            throw new FlyException(Fly::t('fly', 'Property "{class}.{property}" is not defined.',
                array('{class}' => get_class($this), '{property}' => $name)));
    }

    /**
     * Checks if a property value is null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using isset() to detect if a component property is set or not.
     *
     * For example
     * isset($this->propertyName);
     *
     * @param string $name the property name or the event name
     * @return boolean
     */
    public function __isset($name)
    {
        $getter = 'get'.$name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            $name = strtolower($name);
            return isset($this->_e[$name]) && (count($this->_e[$name]));
        } else if (is_array($this->_m)) {
            if (isset($this->_m[$name])) {
                return true;
            }
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canGetProperty($name))) {
                    return $object->$name !== null;
                }
            }
        }
        return false;
    }

    /**
     * Sets a component property to be null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using unset() to set a component property to be null.
     *
     * For example
     * unset($this->propertyName);
     *
     * @param string $name the property name or the event name
     * @throws FlyException if the property is read only.
     * @return mixed
     */
    public function __unset($name)
    {
        $setter = 'set'.$name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            unset($this->_e[strtolower($name)]);
        } else if (is_array($this->_m)) {
            if (isset($this->_m[$name])) {
                $this->detachBehavior($name);
            } else {
                foreach ($this->_m as $object) {
                    if ($object->getEnabled()) {
                        if (property_exists($object, $name)) {
                            return $object->$name = null;
                        } else if ($object->canSetProperty($name)) {
                            return $object->$setter(null);
                        }
                    }
                }
            }
        } else if (method_exists($this, 'get'.$name)) {
            throw new FlyException(Fly::t('fly', 'Property "{class}.{property}" is read only.',
                array('{class}' => get_class($this), '{property}' => $name)));
        }
    }

    /**
     * Calls the named method which is not a class method.
     * Do not call this method. This is a PHP magic method that we override
     * to implement the behavior feature.
     *
     * @param string $name the method name
     * @param array $parameters method parameters
     * @return mixed the method return value
     */
    public function __call($name, $parameters)
    {
        if ($this->_m !== null) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && method_exists($object, $name)) {
                    return call_user_func_array(array($object, $name), $parameters);
                }
            }
        }
        //PHP >= 5.3
        if (class_exists('Closure', false) && $this->canGetProperty($name) && $this->$name instanceof Closure) {
            return call_user_func_array($this->$name, $parameters);
        }
        throw new FlyException(Fly::t('fly', '{class} and its behaviors do not have a method or closure named "{name}".',
            array('{class}' => get_class($this), '{name}' => $name)));
    }

    /**
     * Returns the named behavior object.
     * The name 'asa' stands for 'as a'.
     *
     * @param string $behavior the behavior name
     * @return IBehavior the behavior object, or null if the behavior does not exist
     */
    public function asa($behavior)
    {
        return isset($this->_m[$behavior]) ? $this->_m[$behavior] : null;
    }

    /**
     * Attaches a list of behaviors to the component.
     * Each behavior is indexed by its name and should be an instance of
     * {@link IBehavior}, a string specifying the behavior class, or an
     * array of the following structure:
     * <pre>
     * array(
     *     'class'=>'path.to.BehaviorClass',
     *     'property1'=>'value1',
     *     'property2'=>'value2',
     * )
     * </pre>
     * @param array $behaviors list of behaviors to be attached to the component
     */
    public function attachBehaviors($behaviors)
    {
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehavior($name, $behavior);
        }
    }

    /**
     * Detaches all behaviors from the component.
     */
    public function detachBehaviors()
    {
        if ($this->_m !== null) {
            foreach ($this->_m as $name => $behavior) {
                $this->detachBehavior($name);
            }
            $this->_m = null;
        }
    }

    /**
     * Attaches a behavior to this component.
     * This method will create the behavior object based on the given
     * configuration. After that, the behavior object will be initialized
     * by calling its {@link IBehavior::attach} method.
     * @param string $name the behavior's name. It should uniquely identify this behavior.
     * @param mixed $behavior the behavior configuration. This is passed as the first
     * parameter to {@link FlyBase::createComponent} to create the behavior object.
     * @return IBehavior the behavior object
     */
    public function attachBehavior($name, $behavior)
    {
        if (!($behavior instanceof IBehavior)) {
            $behavior = Fly::createComponent($behavior);
        }
        $behavior->setEnabled(true);
        $behavior->attach($this);
        return $this->_m[$name] = $behavior;
    }

    /**
     * Detaches a behavior from the component.
     * The behavior's {@link IBehavior::detach} method will be invoked.
     * @param string $name the behavior's name. It uniquely identifies the behavior.
     * @return IBehavior the detached behavior. Null if the behavior does not exist.
     */
    public function detachBehavior($name)
    {
        if (isset($this->_m[$name])) {
            $this->_m[$name]->detach($this);
            $behavior = $this->_m[$name];
            unset($this->_m[$name]);
            return $behavior;
        }
    }

    /**
     * Enables all behaviors attached to this component.
     */
    public function enableBehaviors()
    {
        if ($this->_m !== null) {
            foreach ($this->_m as $behavior) {
                $behavior->setEnabled(true);
            }
        }
    }

    /**
     * Disables all behaviors attached to this component.
     */
    public function disableBehaviors()
    {
        if ($this->_m !== null) {
            foreach ($this->_m as $behavior) {
                $behavior->setEnabled(false);
            }
        }
    }

    /**
     * Enables an attached behavior.
     * A behavior is only effective when it is enabled.
     * A behavior is enabled when first attached.
     * @param string $name the behavior's name. It uniquely identifies the behavior.
     */
    public function enableBehavior($name)
    {
        if (isset($this->_m[$name])) {
            $this->_m[$name]->setEnabled(true);
        }
    }

    /**
     * Disables an attached behavior.
     * A behavior is only effective when it is enabled.
     * @param string $name the behavior's name. It uniquely identifies the behavior.
     */
    public function disableBehavior($name)
    {
        if (isset($this->_m[$name])) {
            $this->_m[$name]->setEnabled(false);
        }
    }

    /**
     * Determines whether a property is defined.
     * A property is defined if there is a getter or setter method
     * defined in the class. Note, property names are case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property is defined
     * @see canGetProperty
     * @see canSetProperty
     */
    public function hasProperty($name)
    {
        return method_exists($this, 'get'.$name) || method_exists($this, 'set'.$name);
    }

    /**
     * Determines whether a property can be read.
     * A property can be read if the class has a getter method
     * for the property name. Note, property name is case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property can be read
     * @see canSetProperty
     */
    public function canGetProperty($name)
    {
        return method_exists($this, 'get'.$name);
    }

    /**
     * Determines whether a property can be set.
     * A property can be written if the class has a setter method
     * for the property name. Note, property name is case-insensitive.
     * @param string $name the property name
     * @return boolean whether the property can be written
     * @see canGetProperty
     */
    public function canSetProperty($name)
    {
        return method_exists($this, 'set'.$name);
    }

    /**
     * Determines whether an event is defined.
     * An event is defined if the class has a method named like 'onXXX'.
     * Note, event name is case-insensitive.
     * @param string $name the event name
     * @return boolean whether an event is defined
     */
    public function hasEvent($name)
    {
        return !strncasecmp($name, 'on', 2) && method_exists($this, $name);
    }

    /**
     * Checks whether the named event has attached handlers.
     * @param string $name the event name
     * @return boolean whether an event has been attached one or several handlers
     */
    public function hasEventHandler($name)
    {
        $name = strtolower($name);
        return isset($this->_e[$name]) && count($this->_e[$name]) > 0;
    }

    /**
     * Returns the list of attached event handlers for an event.
     * @param string $name the event name
     * @return array list of attached event handlers for the event
     * @throws FlyException if the event is not defined
     */
    public function getEventHandlers($name)
    {
        if ($this->hasEvent($name)) {
            $name = strtolower($name);
            if (!isset($this->_e[$name])) {
                $this->_e[$name] = array();
            }
            return $this->_e[$name];
        } else {
            throw new FlyException(Fly::t('fly', 'Event "{class}.{event}" is not defined.',
                array('{class}' => get_class($this), '{event}' => $name)));
        }
    }

    /**
     * Attaches an event handler to an event.
     *
     * An event handler must be a valid PHP callback, i.e., a string referring to
     * a global function name, or an array containing two elements with
     * the first element being an object and the second element a method name
     * of the object.
     *
     * An event handler must be defined with the following signature,
     * <pre>
     * function handlerName($event) {}
     * </pre>
     * where $event includes parameters associated with the event.
     *
     * @param string $name the event name
     * @param callback $handler the event handler
     * @throws FlyException if the event is not defined
     * @see detachEventHandler
     */
    public function attachEventHandler($name, $handler)
    {
        if ($this->hasEvent($name)) {
            $name = strtolower($name);
            if (!isset($this->_e[$name])) {
                $this->_e[$name] = array();
            }
            $this->_e[$name][] = $handler;
            return true;
        }
        return false;
    }

    /**
     * Detaches an existing event handler.
     * This method is the opposite of {@link attachEventHandler}.
     * @param string $name event name
     * @param callback $handler the event handler to be removed
     * @return boolean if the detachment process is successful
     * @see attachEventHandler
     */
    public function detachEventHandler($name, $handler = null)
    {
        if ($this->hasEventHandler($name)) {
            $name = strtolower($name);
            if ($handler == null) {
                unset($this->_e[$name]);
                return true;
            }
            foreach ($this->_e[$name] as $i => $h) {
                if ($handler == $h) {
                    unset($this->_e[$name][$i]);
                    break;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Trigger an event.
     * This method represents the happening of an event. It invokes
     * all attached handlers for the event.
     * @param string $name the event name
     * @param Event $event the event parameter
     * @throws FlyException if the event is undefined or an event handler is invalid.
     */
    public function trigger($name, $event)
    {
        $name = strtolower($name);
        if (isset($this->_e[$name])) {
            foreach ($this->_e[$name] as $handler) {
                if (is_string($handler)) {
                    call_user_func($handler, $event);
                } else if (is_callable($handler, true)) {
                    if (is_array($handler)) {
                        // an array: 0 - object, 1 - method name
                        list($object, $method) = $handler;
                        if (is_string($object)) { // static method call
                            call_user_func($handler, $event);
                        } else if (method_exists($object, $method)) {
                            $object->$method($event);
                        } else {
                            throw new FlyException(Fly::t('fly', 'Event "{class}.{event}" is attached with an invalid handler "{handler}".',
                                array('{class}' => get_class($this), '{event}' => $name, '{handler}' => $handler[1])));
                        }
                    } else {
                        // PHP 5.3: anonymous function
                        call_user_func($handler, $event);
                    }
                } else {
                    throw new FlyException(Fly::t('fly', 'Event "{class}.{event}" is attached with an invalid handler "{handler}".',
                        array('{class}' => get_class($this), '{event}' => $name, '{handler}' => gettype($handler))));
                }
                // stop further handling if param.handled is set true
                if (($event instanceof Event) && $event->handled) {
                    return;
                }
            }
        } else if (FLY_DEBUG && !$this->hasEvent($name)) {
            throw new FlyException(Fly::t('fly', 'Event "{class}.{event}" is not defined.',
                array('{class}' => get_class($this), '{event}' => $name)));
        }
    }

    /**
     * Evaluates a PHP expression or callback under the context of this component.
     *
     * Valid PHP callback can be class method name in the form of
     * array(ClassName/Object, MethodName), or anonymous function (only available in PHP 5.3.0 or above).
     *
     * If a PHP callback is used, the corresponding function/method signature should be
     * <pre>
     * function foo($param1, $param2, ..., $component) { ... }
     * </pre>
     * where the array elements in the second parameter to this method will be passed
     * to the callback as $param1, $param2, ...; and the last parameter will be the component itself.
     *
     * If a PHP expression is used, the second parameter will be "extracted" into PHP variables
     * that can be directly accessed in the expression. See {@link http://us.php.net/manual/en/function.extract.php PHP extract}
     * for more details. In the expression, the component object can be accessed using $this.
     *
     * @param mixed $_expression_ a PHP expression or PHP callback to be evaluated.
     * @param array $_data_ additional parameters to be passed to the above expression/callback.
     * @return mixed the expression result
     */
    public function evaluateExpression($_expression_, $_data_ = array())
    {
        if (is_string($_expression_)) {
            extract($_data_);
            return eval('return '.$_expression_.';');
        } else {
            $_data_[] = $this;
            return call_user_func_array($_expression_, $_data_);
        }
    }
}

/**
 * Event is the base class for all event classes.
 *
 * It encapsulates the parameters associated with an event.
 * The {@link sender} property describes who raises the event.
 * And the {@link handled} property indicates if the event is handled.
 * If an event handler sets {@link handled} to true, those handlers
 * that are not invoked yet will not be invoked anymore.
 */
class Event extends Component
{
    /**
     * @var object the sender of this event
     */
    public $sender;
    /**
     * @var boolean whether the event is handled. Defaults to false.
     * When a handler sets this true, the rest of the uninvoked event handlers will not be invoked anymore.
     */
    public $handled = false;
    /**
     * @var mixed additional event parameters.
     */
    public $params;

    /**
     * Constructor.
     * @param mixed $sender sender of the event
     * @param mixed $params additional parameters for the event
     */
    public function __construct($sender = null, $params = null)
    {
        $this->sender = $sender;
        $this->params = $params;
    }
}