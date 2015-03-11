<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 15/2/15
 * Time: 下午9:32
 */
class TestValidator extends Validator
{
    public function validateValue($val)
    {
        if (empty($val)) {
            return array('Test is not empty', array());
        }
        return null;
    }
}