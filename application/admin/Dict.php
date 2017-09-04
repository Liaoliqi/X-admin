<?php
namespace app\admin;
/**
 * Created by PhpStorm.
 * User: USER022
 * Date: 2017/8/31
 * Time: 10:35
 */
class Dict
{
    //分页默认当前页
    const CURR=1;
    //分页(每页显示的条数)
    const LIMIT=10;

    //用户状态
    const ADMIN_USER_A=1;
    const ADMIN_USER_B=0;


    //用户状态
    public static $ADMIN_USER_STATUS=array(
        self::ADMIN_USER_A=>'已启用',
        self::ADMIN_USER_B=>'已停用'
    );

}