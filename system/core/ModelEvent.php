<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ModelEvent class.
 * ModelEvent represents the event parameters needed by events raised by a model.
 */
class ModelEvent extends Event
{
    /**
     * @var boolean whether the model is in valid status and should continue its normal method execution cycles. Defaults to true.
     * if this property is set false by the event handler, the {@link Model::validate} method will quit after handling this event.
     * If true, the normal execution cycles will continue, including performing the real validations and calling
     * {@link Model::afterValidate}.
     */
    public $isValid = true;
}
