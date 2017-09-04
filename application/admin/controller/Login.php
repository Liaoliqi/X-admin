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
        unset($data['password']);unset($data['create_time']);unset($data['update_time']);
        session('admin',$data);
        echo json_encode(['success'=>true]);
    }
    //退出登录
    public function outLogin(){
        session('admin',null);
        $this->redirect('index');
    }
}
