<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 14-10-29
 * Time: 下午10:57
 */


class IndexController extends BaseController
{

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

    public function actionIndex()
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
