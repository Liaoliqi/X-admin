<?php
/**
 * Created by PhpStorm.
 * User: USER022
 * Date: 2017/9/1
 * Time: 11:40
 */

namespace app\admin\controller;
use think\Db;
use think\Request;

class Admincommon extends \think\Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    protected function checkLogin(Request $request){
        $login=session('?admin');
        $baseName=$request->module().'/'.$request->controller().'/'.$request->action();
        if(empty($login && 'admin/Login/index' != $baseName)){
            $this->redirect('admin/login/index');
        }
    }

    /**
     * 检查是否有访问权限
     */
    protected function checkAction($action){
        $isAllow=$this->isAllow($action);
        if(!$isAllow){
            throw new \think\Exception('您没有权限访问', 100006);
            exit;
        }
    }

    /**
     * 是否允许访问
     * @param $action
     * @return bool
     */
    protected function isAllow($action,$controller=null){
        if(empty($controller)){
            $request=  Request::instance();
            $controller=$request->controller();
        }
        $session=session('admin');
        if($session['is_admin']==1){
            return true;
        }
        //获取用户所属组
        $userGroup=Db::name('admin_user_group')->where(['user_id'=>$session['id']]) ->select();
        if(empty($userGroup))
            return false;
        foreach($userGroup as $value){
            $status=Db::name('admin_group')->where(['id'=>$value['group_id'],'status'=>1])->find();
            if(empty($status))
                continue;
            //获取组所对应的权限
            $groupRole=Db::name('admin_group_authority')->where(['group_id'=>$value['group_id']])->select();
            if(empty($groupRole))
                continue;
            $authoritys=array();
            foreach($groupRole as $val){
                $authoritys[]=$val['authority_id'];
            }
            $res=Db::name('admin_authoritys')->where('id','in',$authoritys)->where(['controller'=>$controller,'action'=>$action])->find();
            if($res)
                return true;
        }
        return false;
    }
//    public function _initialize()
//    {
//        parent::_initialize();
//        $login=session('?admin');
//        var_dump($login);
//        var_dump(session('admin'));
////        $baseName=$request->controller().'/'.$request->module().'/'.$request->action();
////        if(empty($login) && 'Login/admin/index' != $baseName){
//        if(empty($login)){
//            $this->redirect('admin/login/index');
////            $this->redirect(url('admin/login/index','',''));
//        }
////        $_SESSION['count']; // 注册Session变量Count
////        isset($PHPSESSID)?session_id($PHPSESSID):$PHPSESSID = session_id();
//        // 如果设置了$PHPSESSID，就将SessionID赋值为$PHPSESSID，否则生成SessionID
////        $_SESSION['count']++; // 变量count加1
////        setcookie('PHPSESSID', $PHPSESSID, time()+86400); // 储存SessionID到Cookie中
//    }
}