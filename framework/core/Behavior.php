<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Behavior is a convenient base class for behavior classes.
 *
 * @property Component $owner The owner component that this behavior is attached to.
 * @property boolean $enabled Whether this behavior is enabled.
 */
class Behavior extends Component implements IBehavior
{
    /**
     * @var bool $enabled Whether this behavior is enabled.
     */
    private $_enabled = false;
    /**
     * @var Component $owner The owner component that this behavior is attached to.
     */
    private $_owner;

    /**
     * Declares events and the corresponding event handler methods.
     * The events are defined by the {@link owner} component, while the handler
     * methods by the behavior class. The handlers will be attached to the corresponding
     * events when the behavior is attached to the {@link owner} component; and they
     * will be detached from the events when the behavior is detached from the component.
     * Make sure you've declared handler method as public.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return array();
    }

    /**
     * Attaches the behavior object to the component.
     * The default implementation will set the {@link owner} property
     * and attach event handlers as declared in {@link events}.
     * This method will also set {@link enabled} to true.
     * Make sure you've declared handler as public and call the parent implementation if you override this method.
     * @param Component $owner the component that this behavior is to be attached to.
     */
    public function attach($owner)
    {
        $this->_enabled = true;
        $this->_owner = $owner;
        $this->_attachEventHandlers();
    }

    /**
     * Detaches the behavior object from the component.
     * The default implementation will unset the {@link owner} property
     * and detach event handlers declared in {@link events}.
     * This method will also set {@link enabled} to false.
     * Make sure you call the parent implementation if you override this method.
     * @param Component $owner the component that this behavior is to be detached from.
     */
    public function detach($owner)
    {
        foreach ($this->events() as $event => $handler) {
            $owner->detachEventHandler($event, array($this, $handler));
        }
        $this->_owner = null;
        $this->_enabled = false;
    }

    /**
     * @return Component the owner component that this behavior is attached to.
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * @return boolean whether this behavior is enabled
     */
    public function getEnabled()
    {
        return $this->_enabled;
    }

    /**
     * @param boolean $value whether this behavior is enabled
     */
    public function setEnabled($value)
    {
        $value = (bool)$value;
        if ($this->_enabled != $value && $this->_owner) {
            if ($value) {
                $this->_attachEventHandlers();
            } else {
                foreach ($this->events() as $event => $handler) {
                    $this->_owner->detachEventHandler($event, array($this, $handler));
                }
            }
        }
        $this->_enabled = $value;
    }

    private function _attachEventHandlers()
    {
        $class = new ReflectionClass($this);
        foreach ($this->events() as $event => $handler) {
            if ($class->getMethod($handler)->isPublic()) {
                $this->_owner->attachEventHandler($event, array($this, $handler));
            }
        }
    }
}
