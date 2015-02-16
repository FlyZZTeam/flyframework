<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 14/11/26
 * Time: 上午10:40
 */

class ApprModule extends WebModule
{

    public function init()
    {

    }

    public function beforeControllerAction($c, $a)
    {
        return true;
    }
}