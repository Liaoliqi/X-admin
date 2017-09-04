<?php

/**
 * Created by PhpStorm.
 * User: USER022
 * Date: 2017/8/30
 * Time: 15:47
 */
namespace app\admin\model;
use \think\Model;

class AdminUser  extends Model
{
    protected $autoWriteTimestamp = true;
    public function setPasswordAttr($value)
    {
        return md5($value);
    }
}