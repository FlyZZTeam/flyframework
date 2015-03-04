<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * InlineValidator represents a validator which is defined as a method in the object being validated.
 *
 * The validation method must have the following signature:
 *
 * ~~~
 * function foo($attribute, $params)
 * ~~~
 *
 * where `$attribute` refers to the name of the attribute being validated, while `$params`
 * is an array representing the additional parameters supplied in the validation rule.
 */
class InlineValidator extends Validator
{
    /**
     * @var string|\Closure an anonymous function or the name of a model class method that will be
     * called to perform the actual validation. The signature of the method should be like the following,
     * where `$attribute` is the name of the attribute to be validated, and `$params` contains the value
     * of [[params]] that you specify when declaring the inline validation rule:
     *
     * ~~~
     * function foo($attribute, $params)
     * ~~~
     */
    public $method;
    /**
     * @var mixed additional parameters that are passed to the validation method
     */
    public $params;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $method = $this->method;
        if (is_string($method)) {
            $method = array($model, $method);
        }
        call_user_func($method, $attribute, $this->params);
    }
}
