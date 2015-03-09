<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ErrorEvent represents the parameter for the {@link Application::onError onError} event.
 */
class ErrorEvent extends Event
{
    /**
     * @var string error code
     */
    public $code;
    /**
     * @var string error message
     */
    public $message;
    /**
     * @var string error message
     */
    public $file;
    /**
     * @var string error file
     */
    public $line;

    /**
     * Constructor.
     * @param mixed $sender sender of the event
     * @param string $code error code
     * @param string $message error message
     * @param string $file error file
     * @param integer $line error line
     */
    public function __construct($sender, $code, $message, $file, $line)
    {
        $this->code = $code;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        parent::__construct($sender);
    }
}
