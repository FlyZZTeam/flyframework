<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */
class BlogModel extends Model
{
    public function getUser($id)
    {
        $data = Fly::db()->getWhere('user', array('uid' => $id))->resultArray();
        return $data;
    }
}