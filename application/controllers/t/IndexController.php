<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Class TestController
 * 这里是一些代码的示例子
 * 包含了FlyFramework基本会用到函数类库等
 *
 * 基础解释:
 *      Fly::app():WebApplication的实例，application运行时涉及的class，function，变量，配置等都用Fly::app()管理。
 *
 *
 * 注意:
 * 本例中涉及的数据库为application/data/flytestdb.sql
 */
class IndexController extends BaseController
{
    /**
     * Do not use this method.
     * When you use,flyframework can't find view template.
     */
    /*
    public function __construct()
    {

    }*/

    /**
     * 当Controller初始化时会调用此方法
     */
    public function init()
    {
        //do some thing...
        //例如:
        //echo "Hello TestController init";
    }

    /**
     * 请注意方法的写法
     * actionIndex是一个行为，行为必须以action开始
     * 例如：
     * actionIndex,actionAdd 等
     * Action方法必须是public方法
     */
    public function actionIndex()
    {
        $data = array('name' => 'zhouhangjun', 'age' => '30', 'email' => 'zz@flyzz.net');

        /**
         * FlyFramework的模板机制
         * FlyFramework的模板分为两部分Layout和View
         *      1.Layout定义公共的模板框架
         *      2.View定义每个action对应的模板
         *
         * 如何使用Layout
         * 默认Layout Name为main
         * 可在application/layouts/目录中创建
         *
         * 设置Layout
         * $this->layout = "test";
         *
         * 如何使用模板
         * 在application/views目录中建立以Controller Name(不包括Controller后缀，Name小写)为名称目录，按当前的示例：
         * application/views/test
         * 建立模板文件，按标准的命名方式，模板文件名称对应Action名称，本例为index.php
         * 本例的模板文件路径为application/views/test/index.php
         *
         * 如何传递模板变量
         * 例如:$data
         * 可在模板文件中这样使用:
         * echo $name;
         * echo $age;
         * echo $email;
         * 模板引擎会自动将数组的key转化成变量；
         *
         * 如何使用第三方模板:
         * 可在应用最基础的Controller中引入第三方模板，模板方法由第三模板引擎决定。
         * 或者可重写FlyFramework的render等方法。
         *
         */
        /**
         * 设置自己的layout
         */
        $this->layout = "test";
        $this->render('index', $data);
    }

    /**
     * 如何获取配置文件中的变量
     */
    public function actionUseConfig()
    {
        /**
         * 获取charset
         * 对于application中定义的变量可以用Fly:app()->xxx;
         * 否则使用Fly::getConfig('xxx');
         */
        echo "<h2>The configuration in config.php</h2>";
        //方法-:
        echo "CharSet:";
        echo Fly::app()->charset;

        echo "<br>";

        //方法二:
        echo "CharSet:";
        echo Fly::getConfig('charset');

        echo "<br>";

        /**
         * cachePath未在appliction中定义
         */
        echo "CachePath:";
        echo Fly::getConfig('cachePath');

        echo "<br>";
        echo "<h2>The third party in the configuration file</h2>";

        //Use Sections
        Fly::loadConfig('config.doctypes', true);
        $data = Fly::getConfig('doctypes');
        var_dump($data);

        //Do not use sections
        /*
        Fly::loadConfig('config.doctypes');
        $data = Fly::getConfig();
        var_dump($data);
        */

        echo "<br>";
        echo "<h2>Get the custom params</h2>";

        /**
         * 获取config.php中得自定义变量
         * 使用$config['params']配置
         * 例如:
         *      $config['params'] = array('siteName' => 'Test', 'email' => 'zz@flyzz.net');
         *
         *      OR
         *
         *      $config['params'] = include('./params.php');
         */
        $data = Fly::app()->getParams();
        echo $data['siteName'];
    }

    /**
     * 如何获取GET,POST,REQUEST,COOKIE等
     */
    public function actionUseHttpParams()
    {
        /**
         * 使用HttpRequest类获取GET,POST,COOKIE等
         * 1.Fly::request()->get('xx');
         * 2.Fly::app()->request->get('xx');
         *
         * 关于URL中得GET参数:
         * 1.index.php/test/useHttpParams/page/1/order/asc
         * 2.index.php/test/useHttpParams?page=1&order=asc;
         */

        //获取get参数

        echo "page[Fly::request()->get('page')]:";
        echo Fly::request()->get('page');
        echo "<br>";
        echo "page[Fly::app()->getRequest()->get('page')]:";
        echo Fly::app()->getRequest()->get('page');

        echo "<br><br>";

        echo "order[Fly::request()->get('order')]:";
        echo Fly::request()->get('order');
        echo "<br>";
        echo "order[Fly::app()->getRequest()->get('order')]:";
        echo Fly::app()->getRequest()->get('order');

        //获取get or post参数
        echo "<br><br>";

        echo "page[Fly::request()->getParam('page')]:";
        echo Fly::request()->getParam('page');

        //get,getParam,post高级使用
        echo "<br><br>";
        echo "page[Fly::request()->get('pg', 1, true)]:";
        /**
         * param1:GET key
         * param2:如果key不存在，使用默认值
         * param3:是否进行Xss过滤,可以使用pg='s><html>';测试
         */
        echo Fly::request()->get('pg', 1, true);

    }

    /**
     * Post测试
     */
    public function actionUsePost()
    {
        //检查是否是POST请求
        if (Fly::request()->isPostRequest()) {
            echo "UserName:";
            echo "<br>";
            echo Fly::request()->post('username', 'zhouhangjun', true);
        } else {
            echo "You must use actionSubmitPost";
        }
    }

    /**
     * Post测试
     * 表单提交
     * 可以测试含有html标签的情况，默认接收端usePost已开启Xss过滤
     */
    public function actionSubmitPost()
    {
        $this->render('submitpost');
    }

    /**
     * Cookie测试
     */
    public function actionUseCookie()
    {
        /**
         * 设置Cookie{@link CookieHelper}
         * 可以在config.php中配置cookie
         * config.php中的配置项:httpRequest
         */
        if (CookieHelper::getCookie('auth')) {
            echo CookieHelper::getCookie('auth');
        } else {
            CookieHelper::setCookie('auth', 'zhouhangjun');
        }
    }

    /**
     * 如何使用日志
     */
    public function actionUseLogger()
    {
        /**
         * 系统默认的log保存在application/runtime目录下
         * 例如:
         *      log-2015-03-10.log
         *
         * 如何改变日志路径
         * $config['logPath'] = 'xxxx';
         *
         * 日志记录等级
         * $config['logThreshold'] = 0;
         *      0 = Disables logging, Error logging TURNED OFF
         *      1 = Error Messages (including PHP errors)
         *      2 = Debug Messages
         *      3 = Informational Messages
         *      4 = All Messages
         *
         * 日志日期格式
         * $config['logDateFormat'] = 'Y-m-d H:i:s';
         */
        Fly::log('debug', 'This is a use logger test', 'Test');

        /**
         * debug信息
         * 只有开启FLY_DEBUG时才会记录
         */
        Fly::trace('This is a use logger test[trace]');
    }

    /**
     * 如何使用本地化语言包
     */
    public function actionUseLocale()
    {
        /**
         * 第一个参数代表语言包
         * 第二个参数代表消息内容
         *
         * 可以在application/language/下查看，本例使用en_us,可以在application/language/en_us/application_lang.php中查看
         */
        echo Fly::t('application', 'This is a bug');

        echo "<br><br>";

        //使用替换标签
        echo Fly::t('application', "File {FileName} not found.", array('{FileName}' => 'TestController'));
    }

    /**
     * 使用随机数
     */
    public function actionUseRandom()
    {
        echo "<h1>Use Random.</h1>";
        echo StringHelper::randomString('alnum', 8);
        echo "<br>";
        echo StringHelper::randomString('numeric', 4);
        echo "<br>";
        echo StringHelper::randomString('nozero', 6);
        echo "<br>";
        echo StringHelper::randomString('alpha', 2);
    }

    /**
     * 使用加解密
     * 必须安装mcrypt_encrypt扩展
     */
    public function actionUseEncrypt()
    {
        //$e = new Encrypt();
        $e = Fly::loadClass('Encrypt');
        $str = $e->encode("zhouhangjun", '123456');
        echo $str;
        echo "<br>";
        echo $e->decode($str, '123456');
    }

    /**
     * 创建URL，获取应用URL等
     */
    public function actionUseUrl()
    {
        //获取站点URL
        echo "<h2>Get base url</h2>";
        echo Fly::app()->getBaseUrl();
        echo "<br>";
        echo Fly::app()->getBaseUrl(true);

        //获取当前相对URL
        echo "<h2>Ger current url</h2>";
        echo Fly::request()->getUrl();
        echo "<br>";
        echo Fly::request()->getHostUrl().Fly::request()->getUrl();

        //创建URL
        echo "<h2>Create Url</h2>";
        echo $this->createUrl('/user/add');
        echo "<br>";
        echo $this->createAbsoluteUrl('/user/add');
    }

    /**
     * 使用Uri，Router等
     */
    public function actionUseOther()
    {
        echo Fly::uri()->getBaseUrl();
        echo "<br><br>";
        echo Fly::router()->fetchClass();
    }

    /**
     * 使用session
     */
    public function actionUseSession()
    {
        if (Fly::app()->session['auth']) {
            echo Fly::app()->session['auth'];
        } else {
            Fly::app()->session['auth'] = "zhouhangjun";
        }
    }

    /**
     * 使用DBSession
     */
    public function actionUseDBSession()
    {
        if (Fly::app()->session['auth']) {
            echo Fly::app()->session['auth'];
        } else {
            Fly::app()->session['auth'] = "zhouhangjun";
        }
    }

    /**
     * 如何使用数据库
     */
    public function actionUseDb()
    {
        /**
         * 首先需要在config/database.php下配置数据库.
         * 本例使用PDO驱动,详情参考数据库配置文件。
         * 我们在之后的项目中都会使用PDO驱动
         *
         */

        /**
         * 使用普通sql的方式
         */

        echo "<h2>Use the sql</h2>";

        //使用最基础的查询语句
        $tableName = Fly::db()->getTableName('user');
        $query = Fly::db()->query("select * from {$tableName} where uid=1");
        var_dump($query->resultArray());

        echo "<br><br>";

        //获取表长度
        $query = Fly::db()->query("select count(*) as num from {$tableName}");
        $data = $query->fetchAssoc();
        echo $data['num'];

        echo "<br><br>";

        //遍历
        $query = Fly::db()->query("select * from {$tableName}");
        while($row = $query->fetchAssoc()) {
            echo $row['username'];
            echo "<br>";
        }

        //查询封装方式
        $sql = "SELECT * FROM {$tableName} WHERE uid = ?";
        $query = Fly::db()->query($sql, array(2));
        var_dump($query->resultArray());

        echo "<br><br>";

        /**
         * 使用AR的方式
         */
        echo "<h2>Use the Ar</h2>";

        //获取user表的全部数据
        $data = Fly::db()->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //获取uid=1的数据
        $data = Fly::db()->where(array('uid' => 1))->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //获取uid>1的用户名数据
        $data = Fly::db()->select('username')->where(array('uid>' => 1))->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //获取uid=1和uid=2的用户名数据
        $data = Fly::db()->select('username')->where('uid', 1)->orWhere('uid', 2)->get('user')->resultArray();
        var_dump($data);
        echo "<br><br>";
        $data = Fly::db()->select('username')->whereIn('uid', array(1,2))->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //使用like

        //like("%zhj")
        $data = Fly::db()->select('*')->like('username', 'zhj', 'before')->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //like("zhj%")
        $data = Fly::db()->select('*')->like('username', 'zhj', 'after')->get('user')->resultArray();
        var_dump($data);


        echo "<br><br>";

        //like("%zhj%")
        $data = Fly::db()->select('*')->like('username', 'zhj')->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //使用limit
        $data = Fly::db()->select('username')->limit(1)->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //使用limit获取从第一条起读取2行的数据
        $data = Fly::db()->select('username')->limit(2, 1)->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //使用orderBy,其他的groupBy，having查看手册
        $data = Fly::db()->select('*')->orderBy('uid', 'DESC')->orderBy('username ASC ')->get('user')->resultArray();
        var_dump($data);

        echo "<br><br>";

        //使用join
        $data = Fly::db()->select('*')->from('user')->join('user_info', 'user_info.uid=user.uid', 'left')->get()->resultArray();
        var_dump($data);

        echo "<br><br>";

        //插入数据
        $data = array(
            'username' => 'zhouhangjun',
            'password' => '123456',
            'phone' => '1365701235',
        );
        Fly::db()->insert('user', $data);
        echo Fly::db()->getInsertId();

        //修改数据
        $data = array('username' => 'zhj222');
        Fly::db()->update('user', $data, array('uid' => 1));

        //删除数据
        //Fly::db()->delete('user', array('uid' => 5));

        exit;
    }

    public function onSubmit($event)
    {
        echo "on submit";
    }

    public function onLast($event)
    {
        echo "on last";
    }

    public function checkUser($a)
    {
        //echo $row;
        //var_dump($row);
        //$data['nickname'] = 'xx';
        //var_dump($a);
        return !empty($a);
    }

    public function actionIndex2()
    {
        $model = new TestModel();
        //echo $model['username'];
        //echo $model->generateAttributeLabel("ssss_xx");
        //exit;
        if (isset($_POST['username'])) {
            $model->doEdit($_POST);
            exit;
        }
        $this->render('index', array());

    }

    public function actionIndex4()
    {

        echo Fly::app()->charset;
        exit;

        if (preg_match("/(callback:\w+(\[.*?\])?)/", "callback:ssssss[10]", $match)) {
            $rules = (array('1' => $match[1]));
            var_dump($match);
            var_dump($rules);
        }

        exit;


        //Fly::app()->db->query("select * from ssss");
        //include("sssss.php");
        //var_dump(Fly::app()->getErrorHandler()->error);

        exit;

        //Yii::app()->errorHandler->error
        echo "Hello Fly";
        //Fly::loadConfig('config.xx', true);
        /*
        $this->onsubmit = array($this, 'onSubmit');
        $this->attachEventHandler('onSubmit', array($this, 'onLast'));
        $this->detachEventHandler('onSubmit', array($this, 'onSubmit'));
        $event = new ExceptionEvent($this, new Exception());
        $this->trigger('onSubmit', $event);*/

        //include("sss.php");
        exit;
        //echo "xxx";
        //exit;
        //try {
            throw new Exceptions(Fly::t('fly','page not'));
        //} catch(Exception $e) {
            //var_dump($e);
            //echo "Hello !";
        //}
        exit;
       // try {
            $this->xx('sss');
        //} catch (Exception $e) {
           // echo "ok";
       // }
        exit;
        $c = Fly::app()->Cache;
        //var_dump($c);
        $c->save('username', array('zj', 'hz'));
        var_dump($c->get('username'));
	  echo "<br><br>";
	var_dump($c->getMetaData('username'));
	echo "<br><br>";
	var_dump($c->cacheInfo());
	$c->clean();
	var_dump($c->get('username'));
$c->File->save("zhou","zhou123");
        /*
        var_dump($c->File->getMetaData('username'));
        echo "<br><br>";
        var_dump($c->File->cacheInfo());
        //echo "<br><br>";
        $c->File->save('username', array('zhouhangjun','zhouzhou'));
        echo "<br><br>";
        print_r($c->File->get('username'));
        $c->File->save('test', "zhouhangjun");
        //$c->File->delete('username');
        $c->File->clean();*/
    }

    public function debugDB()
    {
        $db = Fly::app()->db;
        //$dbUtil = Fly::app()->DBUtil;

        echo "Query Result Array---------------------------------<br><br>";
        $query = $db->query("select * from fly_user_info where uid=1");
        var_dump($query->resultArray());

        echo "<br><br>Query Result Object---------------------------------<br><br>";
        $query = $db->query("select * from fly_user_info where uid=1");
        var_dump($query->resultObject());

        echo "<br><br>Query Result---------------------------------<br><br>";
        $query = $db->query("select * from fly_user_info where uid=1");
        var_dump($query->result());

        /*
        echo "<br><br>Simple Query---------------------------------<br><br>";
        if ($db->simpleQuery('select * from fly_user_info where uid=1')) {
            echo "Test simpleQuery success!";
        }*/

        echo "<br><br>查询封装---------------------------------<br><br>";
        $sql = "SELECT * FROM fly_user_info WHERE uid = ?";
        $query = $db->query($sql, array(1));
        var_dump($query->resultArray());

        echo "<br><br>Row---------------------------------<br><br>";
        $sql = "SELECT * FROM fly_user_info order by uid DESC";
        $query = $db->query($sql);
        echo "Row:<br>";
        var_dump($query->row());
        echo "<br>Row First:<br>";
        var_dump($query->firstRow());
        echo "<br>Row Last:<br>";
        var_dump($query->lastRow());
        echo "<br>Row Next:<br>";
        var_dump($query->nextRow());
        echo "<br>Row Prev:<br>";
        var_dump($query->previousRow());

        echo "<br><br>Row Field Number---------------------------------<br><br>";
        echo "Row Count:<br>";
        $sql = "SELECT * FROM fly_user_info order by uid DESC";
        $query = $db->query($sql);
        echo $query->getRowsCount();
        echo "<br>";
        echo $db->countAll('user_info');
        echo "<br>Field Count:<br>";
        echo $query->getFieldsCount();

        $uid = $query->getRowsCount() + 1;

        echo "<br><br>插入测试---------------------------------<br><br>";
        $db->query("insert into fly_user(username, password, phone) values('zhj', '123456', '505171269')");
        $insertId = $db->getInsertId();
        echo "Insert ID:".$insertId;
        echo "<br>";
        echo "Affected ID:".$db->affectedRows();
        $db->query("delete from fly_user where uid='{$insertId}'");

        echo "<br><br>表数据统计---------------------------------<br><br>";
        echo $db->countAll('fly_user');
        echo "<br><br>其他数据库相关---------------------------------<br><br>";
        echo "DB Version:".$db->getVersion();
        echo "<br>";
        echo "DB Platform:".$db->getPlatform();

        echo "<br><br>最后查询语句---------------------------------<br><br>";
        echo $db->getLastQuery();

        echo "<br><br>Insert Update Sql 组装---------------------------------<br><br>";
        $data = array('name' => 'zhj444', 'email' => 'hiegoer@gmai.com', 'url' => "http://www.baidu.com");
        $str = $db->insertString('fly_user', $data);
        echo "Insert Sql:<br>";
        echo $str;
        echo "<br>";
        echo "Update Sql:<br>";
        $data = array('name' => 'zhj444', 'email' => 'hiegoer@gmail.com', 'url' => 'http://www.baidu.com');
        $where = "author_id = 1 AND status = 'active'";
        $str = $db->updateString('fly_user', $data, $where);
        echo $str;

        echo "<br><br>Ar查询数据---------------------------------<br><br>";
        echo "get:<br>";
        $data = $db->get('fly_user_info')->resultArray();
        var_dump($data);
        echo "<br>get limit:<br>";
        $data = $db->get('fly_user_info', 1, 2)->resultArray();
        var_dump($data);
        echo "<br>";
        $data = $db->get('fly_user_info', 1)->resultArray();
        var_dump($data);
        echo "<br>get where:<br>";
        $data = $db->getWhere('fly_user_info', array('uid' => 1))->resultArray();
        var_dump($data);
        echo "<br>链式:<br>";
        echo "simple:<br>";
        $data = $db->select('nickname')->from('user_info')->where('uid', 1)->get()->resultArray();
        var_dump($data);
        echo "<br>";
        echo "Where:<br>";
        $data = $db->select('nickname')->from('user_info')->where(array('uid > ' => 1))->get()->resultArray();
        var_dump($data);
        echo "<br>";
        echo "Join:<br>";
        $data = $db->select('nickname,username')->from('user_info')->join('user', 'user_info.uid=user.uid', 'left')->where(array('user.uid = ' => 1))->get()->resultArray();
        var_dump($data);
        echo "<br>";
        echo "OR AND IN Where:<br>";
        echo "<br>OR<br>";
        $data = $db->select('nickname')->from('user_info')->where('uid', 1)->orWhere('uid', 2)->get()->resultArray();
        var_dump($data);
        echo "<br>In<br>";
        $data = $db->select('nickname')->from('user_info')->whereIn('uid', array(1,3))->orWhere('uid', 2)->get()->resultArray();
        var_dump($data);
        echo "<br>Not In<br>";
        $data = $db->select('nickname')->from('user_info')->whereNotIn('uid', array(1,3,4,5))->orWhere('uid', 2)->get()->resultArray();
        var_dump($data);
        echo "<br>Like<br>";
        $data = $db->select('nickname')->from('user_info')->like('nickname', 'zhj', 'before')->get()->resultArray();
        var_dump($data);
        echo "<br>Or Like<br>";
        $data = $db->select('nickname')->from('user_info')->like('nickname', 'zhj', 'before')->orLike('nickname', 'jj')->get()->resultArray();
        var_dump($data);
        echo "<br>Not Like<br>";
        $data = $db->select('nickname')->from('user_info')->notLike('nickname', '夏天')->get()->resultArray();
        var_dump($data);
        echo "<br>Or Not Like<br>";
        $data = $db->select('nickname')->from('user_info')->like('nickname', '夏天')->orNotLike('nickname', 'zhj')->get()->resultArray();
        var_dump($data);
        /*
        echo "<br>Group By<br>";
        $data = $db->select('nickname')->from('user_info')->groupBy('qq')->get()->resultArray();
        var_dump($data);
        echo "<br>Group By Having<br>";
        $data = $db->select('*')->from('user_info')->groupBy('qq')->having('uid', 1)->get()->resultArray();
        var_dump($data);
        echo "<br>Group By Or Having<br>";
        $data = $db->select('*')->from('user_info')->groupBy('qq')->having('uid', 1)->orHaving('uid', 3)->get()->resultArray();
        var_dump($data);*/
        echo "<br>distinct<br>";
        $data = $db->distinct()->select('qq')->from('user_info')->get()->resultArray();
        var_dump($data);
        echo "<br>Order By<br>";
        $data = $db->select('*')->from('user_info')->orderBy('uid', "DESC")->get()->resultArray();
        var_dump($data);
        echo "<br>Limit<br>";
        $data = $db->select('*')->from('user_info')->orderBy('uid', "DESC")->limit(1,3)->get()->resultArray();
        var_dump($data);
        echo "<br>Count All result<br>";
        $data = $db->countAllResults('user_info');
        var_dump($data);

        echo "<br><br>AR insert update---------------------------------<br><br>";

        $db->insert('user_info', array('uid'=>'100', 'nickname' => 'zhj345', 'qq' => '505'));
        echo "AffRows:".$db->affectedRows();
        $db->delete('user_info', array('uid' => '100'));
        $db->update('user_info', array('nickname' => 'hiegoer234'), array('uid' => 1));
        echo "<br>Update AffRows:".$db->affectedRows();
        echo "<br>Insert Batch:<br>";
        $data = array(
            array(
                'blog_title' => 'sss',
                'blog_author' => 'zhouhangjun',
                'blog_description' => '这个是是一个描述'
            ),
            array(
                'blog_title' => 'sss2',
                'blog_author' => 'zhouhangjun2',
                'blog_description' => '这个是是一个描述s'
            ),
        );
        $ret = $db->insertBatch('user2', $data, true);
        echo $ret;
        echo "<br>Update Batch:<br>";
        $data = array(
            array(
                'blog_id' => 1,
                'blog_title' => 'sss3334',
                'blog_author' => 'zhouhangjun5555',
                'blog_description' => '这个是是一个444描述'
            ),
            array(
                'blog_id' => 2,
                'blog_title' => 'sss23334',
                'blog_author' => 'zhouhan4444gjun24444',
                'blog_description' => '这个是是一个描述4444s'
            ),
        );
        //$ret = $db->updateBatch('user2', $data, 'blog_id', true);
        echo $ret;

        echo "<br><br>Empty Table---------------------------------<br><br>";
        $data = array(
            'uid' => 1,
            'cname' => '测试',
        );
        $db->insert('user_curriculum', $data);
        if ($db->emptyTable('user_curriculum')) {
            echo "Empty Table Success.";
        }

        $db->insert('user_curriculum', $data);
        if ($db->truncate('user_curriculum')) {
            echo "<br>";
            echo "truncate Table Success.";
        }

        echo "<br><br>表数据---------------------------------<br><br>";

        $data = $db->fetchTables();

        var_dump($data);
        if ($db->isTableExists('user')) {
            echo "<br>";
            echo 'Table user is exists';
        }

        echo "<br><br>字段数据---------------------------------<br><br>";
        $data = $db->fetchFields('user');
        var_dump($data);
        echo "<br><br>";
        if ($db->isFieldExists('uid', 'user')) {
            echo "The Field is Exists";
        }
        echo "<br><br>";
        $data = $db->getFieldData('user');
        var_dump($data);



        echo "<br><br>Escape---------------------------------<br><br>";
        $data = "''sss%_sd这个";
        echo "escape:<br>";
        echo $db->escape($data);
        echo "<br>escapeString:<br>";
        echo $db->escapeString($data);
        echo "<br>escapeLikeString:<br>";
        echo $db->escapeLikeString($data);

        //escape

    }

    public function debugDbUtil()
    {
        echo "<br>DBUtil---------------------------------<br><br>";
        $db = Fly::app()->db;
        $dbUtil = Fly::app()->getDBUtil();

        /*
        if ($dbUtil->createDataBase('flymall')) {
            echo "Data Base Create Success.";
        }*/
/*
        echo "<br>";
        if ($dbUtil->dropDataBase('flymall')) {
            echo "Drop database Success.";
        }
*//*
        if ($dbUtil->createDataBase('flymall')) {
            Fly::app()->db->selectDb('flymall');
        }
*/
/*
        $fields = array(
            'blog_id' => array(
                'type' => 'serial',
                //'constraint' => 5,
                //'unsigned' => TRUE,
                //'notnull' => true,
                //'auto_increment' => TRUE
            ),
            'blog_title' => array(
                'type' => 'character',
                'constraint' => '100',
            ),
            'blog_author' => array(
                'type' =>'character',
                'constraint' => '100',
                'default' => 'King of Town',
            ),
            'blog_description' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
        );
        $dbUtil->addField($fields);
        $dbUtil->addKey('blog_id', true);
        $dbUtil->createTable('blog');

        $dbUtil->renameTable('blog', 'newblog');

        $fields = array(
            'preferences' => array('type' => 'TEXT')
        );
        $dbUtil->addColumn('newblog', $fields);*/
        $dbUtil->dropColumn('newblog', 'blog_description');

        $fields = array(
            'preferences' => array(
                'name' => 'new_name',
                'type' => 'TEXT',
            ),
        );
        $dbUtil->modifyColumn('newblog', $fields);

    }

    public function debugDbUtil2()
    {
        $db = Fly::app()->db;
        $dbUtil = Fly::app()->getDBUtil();
        echo "<br>DBUtil2---------------------------------<br><br>";
        $data = $dbUtil->fetchDataBases();
        var_dump($data);
        if ($dbUtil->isDataBaseExists('flytest')) {
            echo "<br><br>";
            echo "Database exists";
        }
        if ($dbUtil->optimizeTable('fly_user')) {
            echo "<br><br>";
            echo "Optimize Table Success";
        }

        if ($dbUtil->repairTable('fly_user')) {
            echo "<br><br>";
            echo "Repair Table Success";
        }

        if ($dbUtil->optimizeDataBase('flytest')) {
            echo "<br><br>";
            echo "Optimize Database Success";
        }

        echo "<br><br>CSV:<br><br>";
        echo $dbUtil->getCsvFromResult($db->query('select * from fly_user_info'));
        echo "<br><br>Xml:<br><br>";
        echo $dbUtil->getXmlFromResult($db->query('select * from fly_user_info'));
        echo "<br><br>";
        var_dump($dbUtil->backup());
    }

    public function checkUserName($v)
    {

        Fly::app()->Validation->setMessage('checkUserName', '用户名不能位空');
        return false;
    }

    public function actionServer()
    {
        $config['functions']['Greetings'] = array('function' => 'IndexController.actionDo');
        //$config['functions']['update_post'] = array('function' => 'My_blog.update_entry');
        //$config['object'] = $this;
        $xmlrpcs = new XmlRpcServer($config);
        $xmlrpcs->initialize($config);
        $xmlrpcs->startService();
    }

    public function actionDo()
    {
        var_dump(Fly::app()->getErrorHandler()->error);
        exit;
        exit;
        $p = $r->outputParameters();

        $response = array(array('nickname'  => array('Smitty','string'),
            'userid'    => array('99','string'),
            'url'       => array('http://yoursite.com','string'),
            'email'     => array('jsmith@yoursite.com','string'),
            'lastname'  => array('Smith','string'),
            'firstname' => array('John','string'),
            'ss' => array($p[0], 'string'),
        ),
            'struct');
        $xmlrpc = new XmlRpc();
        return $xmlrpc->sendResponse($response);
        //$this->render('index', array());
        //$this->forward('index');
        //$this->output("This is a debug.");
    }

    public function actionDoEdit()
    {
        $this->Output("This is do edit.");
    }

    public function test()
    {
        echo "This is a testing2.";
    }

    private function xxAction()
    {
        echo "xx";
    }
}
