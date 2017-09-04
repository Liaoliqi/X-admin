<?php
namespace app\admin\controller;
use \think\Db;
use app\admin\controller\Admincommon;
use think\Request;
class Index extends Admincommon
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->checkLogin($request);
    }

    public function index()
    {
        $auth['admin_menu']=$this->isAllow('admin_menu');
        $auth['admin_user_index']=$this->isAllow('admin_user_index','AdminUser');
        $auth['admin_group_index']=$this->isAllow('admin_group_index','AdminGroup');
        $auth['admin_rule_index']=$this->isAllow('admin_rule_index','AdminRule');
        $this->assign('auth',$auth);
        $this->assign('session',session('admin'));
        return $this->fetch();
    }
    //首页欢迎页
    public function welcome()
    {
        return $this->fetch();
    }
}
