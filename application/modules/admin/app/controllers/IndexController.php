<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 14-10-29
 * Time: 下午10:57
 */

class IndexController extends Controller
{
    public function actionIndex()
    {
        echo "hello world";
        echo "<br>";
        $this->render('index', array('a' => 'This is in module.'));
    }

    public function actionDo()
    {
        echo "This is a testing234444.";
    }

    public function test()
    {
        echo "This is a testing2.";
    }
}