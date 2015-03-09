<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ExceptionEvent represents the parameter for the {@link Application::onException onException} event.
 */
class ExceptionEvent extends Event
{
    /**
     * @var Exception the exception that this event is about.
     */
    public $exception;

    /**
     * Constructor.
     * @param mixed $sender sender of the event
     * @param Exception $exception the exception
     */
    public function __construct($sender, $exception)
    {
        $this->exception = $exception;
        parent::__construct($sender);
    }
}