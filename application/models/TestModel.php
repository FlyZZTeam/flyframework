<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 15/2/15
 * Time: 下午9:32
 */
class TestModel extends Model
{
    public $username = 'ddd';
    public $password;

    public function rules($group = null)
    {
        $r = array(
            'edit' => array(
                array(
                    'username,password',
                    'validator:application.models.TestValidator',
                    'message' => '{attribute} not empty'
                ),
                //array('password', 'callback:checkUser', 'message' => '{attribute} not empty'),
                //array('date', 'required', 'message' => 'date not empty'),
            ),
        );
        if ($group && isset($r[$group])) {
            return $r[$group];
        }
        return array();
    }

    public function attributeLabels()
    {
        return array('username' => 'UserName');
    }

    public function checkUser2($a, $param)
    {

        if (!empty($this->$a)) {
            $this->addError($a, 'User Name Not Empty.');
        }
    }

    public function checkUser($a)
    {
        //echo $row;
        //var_dump($row);
        //$data['nickname'] = 'xx';
        //var_dump($a);
        return !empty($a);
    }

    public function doEdit($data)
    {
        if ($this->validate($data, 'edit')) {
            echo "ok";
        } else {
            echo Fly::app()->getValidator()->getErrorString();
        }
    }
}