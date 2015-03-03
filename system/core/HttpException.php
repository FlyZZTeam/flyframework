<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * HttpException represents an exception caused by invalid operations of end-users.
 *
 * The HTTP error code can be obtained via {@link statusCode}.
 * Error handlers may use this status code to decide how to format the error page.
 */
class HttpException extends FlyException
{
    /**
     * @var integer HTTP status code, such as 403, 404, 500, etc.
     */
    public $statusCode;

    /**
     * Constructor.
     * @param integer $status HTTP status code, such as 404, 500, etc.
     * @param string $message error message
     * @param integer $code error code
     */
    public function __construct($status, $message = null, $code = 0)
    {
        $this->statusCode = $status;
        parent::__construct($message, $code);
    }
}
