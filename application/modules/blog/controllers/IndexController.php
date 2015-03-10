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
        echo Fly::request()->getUrl();
        exit;
    }

    public function actionSiteInfo()
    {
        $data = array(
            'title' => '这个是我第一个博客',
            'content' => '我从来不写博客，今天写了第一个。',
            'author' => '周航军',
        );
        $this->render('site_info', $data);
    }

    public function actionUserInfo()
    {
        /*
        Fly::import('blog.models.BlogModel');
        $blogModel = new BlogModel();
        */
        $data['userInfos'] = Fly::m('blog.models.BlogModel')->getUser(1);
        $this->render('user_info', $data);
    }
}