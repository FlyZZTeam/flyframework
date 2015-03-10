<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 14/11/26
 * Time: 上午10:40
 */

class BlogModule extends WebModule
{

    public function init()
    {
        $this->layout = 'main';
    }

    public function beforeControllerAction($c, $a)
    {
        return true;
    }
}