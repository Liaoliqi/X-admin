<?php
namespace app\admin\controller;
use app\admin\Dict;
use \think\Db;
use app\admin\controller\Admincommon;
class Login extends Admincommon
{
    public function index()
    {
        return  $this->fetch();
    }

    //处理登陆方法
    public function dologin(){
        $username=input('post.username');
        $pass=input('post.pass');
        $data=Db::name('admin_user')->where('user',':user')->bind(['user'=>$username])->find();
        if(empty($data)){
            echo json_encode(['success'=>false,'message'=>'登录名不存在']);
            exit;
        }else if($data['password'] != md5($pass)){
            echo json_encode(['success'=>false,'message'=>'输入的密码不正确']);
            exit;
        }else if($data['status'] != 1){
            echo json_encode(['success'=>false,'message'=>'该账号已被禁用']);
            exit;
        }
        //修改登陆时间与ip
        Db::name('admin_user')->where('id',$data['id'])->update(['login_time'=>time(),'ip'=>$this->getClientIP()]);

        unset($data['password']);unset($data['create_time']);unset($data['update_time']);
        session('admin',$data);
        echo json_encode(['success'=>true]);
    }
    //退出登录
    public function outLogin(){
        session('admin',null);
        $this->redirect('index');
    }
    //获取客户端的IP地址
    function getClientIP()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return ($ip=='::1')?'127.0.0.1':$ip;
    }
}
