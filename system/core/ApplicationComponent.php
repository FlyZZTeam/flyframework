<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ApplicationComponent is the base class for application component classes.
 *
 * ApplicationComponent implements the basic methods required by {@link IApplicationComponent}.
 *
 * When developing an application component, try to put application component initialization code in
 * the {@link init()} method instead of the constructor. This has the advantage that
 * the application component can be customized through application configuration.
 *
 * @property boolean $isInitialized Whether this application component has been initialized (ie, {@link init()} is invoked).
 */
abstract class ApplicationComponent extends Component implements IApplicationComponent
{
    /**
     * @var array the behaviors that should be attached to this component.
     * The behaviors will be attached to the component when {@link init} is called.
     * Please refer to {@link Model::behaviors} on how to specify the value of this property.
     */
    public $behaviors = array();
    /**
     * @var bool $isInitialized Whether this application component has been initialized (ie, {@link init()} is invoked).
     */
    private $_initialized = false;

    /**
     * Initializes the application component.
     * This method is required by {@link IApplicationComponent} and is invoked by application.
     * If you override this method, make sure to call the parent implementation
     * so that the application component can be marked as initialized.
     */
    public function init()
    {
        $this->attachBehaviors($this->behaviors);
        $this->_initialized = true;
    }

    /**
     * Checks if this application component bas been initialized.
     * @return boolean whether this application component has been initialized (ie, {@link init()} is invoked).
     */
    public function getIsInitialized()
    {
        return $this->_initialized;
    }
}
