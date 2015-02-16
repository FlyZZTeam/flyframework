<?php
/**
 * This file contains core interfaces for Yii framework.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * IApplicationComponent is the interface that all application components must implement.
 *
 * After the application completes configuration, it will invoke the {@link init()}
 * method of every loaded application component.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 * @since 1.0
 */
interface IApplicationComponent
{
	/**
	 * Initializes the application component.
	 * This method is invoked after the application completes configuration.
	 */
	public function init();
	/**
	 * @return boolean whether the {@link init()} method has been invoked.
	 */
	public function getIsInitialized();
}

/**
 * IBehavior interfaces is implemented by all behavior classes.
 *
 * A behavior is a way to enhance a component with additional methods that
 * are defined in the behavior class and not available in the component class.
 */
interface IBehavior
{
    /**
     * Attaches the behavior object to the component.
     * @param Component $component the component that this behavior is to be attached to.
     */
    public function attach($component);

    /**
     * Detaches the behavior object from the component.
     * @param Component $component the component that this behavior is to be detached from.
     */
    public function detach($component);

    /**
     * @return boolean whether this behavior is enabled
     */
    public function getEnabled();

    /**
     * @param boolean $value whether this behavior is enabled
     */
    public function setEnabled($value);
}
