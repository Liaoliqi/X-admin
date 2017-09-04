<?php
namespace app\admin\controller;
use app\admin\Dict;
use \think\Db;
use app\admin\controller\Admincommon;
use think\Request;
class AdminRule extends Admincommon
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->checkLogin($request);
    }

    public function index(Request $request)
    {
        $this->checkAction('admin_rule_index');
        //url传过来当前页
        $curr=input('get.curr');
        $limitLength=Dict::LIMIT;
        $isForm=input('get.isForm');        //是否点击搜索

        //搜索
        $post=array_filter(input('post.'),function($val){$tmp=$val ===  ''; return !$tmp;});    //获取非空数组，0可以被获取到
        $curr=$curr?$curr:1;
        if(isset($isForm))    //有搜索 返回到第一页
            $curr=1;
        $limitStart=($curr-1)*$limitLength;
        list($data,$total)=$this->getInfo($post,$limitStart,$limitLength);
        //权限验证
        $auth['add']=$this->isAllow('admin_rule_add');
        $auth['edit']=$this->isAllow('admin_rule_edit');
        $auth['del']=$this->isAllow('admin_rule_del');
        $this->assign('auth',$auth);
        $this->assign('post',input('post.')?input('post.'):['controller'=>'','controller_name'=>'','action'=>'','action_name'=>'']);
        $this->assign('data',$data);
        $this->assign( 'page',getPages($total,$curr) );
        return  $this->fetch();
    }

    /**
     * @param $post
     * @param $limitStart
     * @param $limitLength
     * @return array
     * 根据查询条件获取数据,返回数据与总条目数
     */
    public function getInfo($post,$limitStart,$limitLength){
        $res=array();
        if($post){
            $conditions=array();
            if(isset($post['controller']))
                $conditions['controller']=['like','%'.$post['controller'].'%'];
            if(isset($post['controller_name']))
                $conditions['controller_name']=['like','%'.$post['controller_name'].'%'];
            if(isset($post['action']))
                $conditions['action']=['like','%'.$post['action'].'%'];
            if(isset($post['action_name']))
                $conditions['action_name']=['like','%'.$post['action_name'].'%'];

            $res[]=Db::name('admin_authoritys')->where($conditions)->order('controller', 'asc')->limit($limitStart,$limitLength)->select();
            $res[]=Db::name('admin_authoritys')->where($conditions)->count();
        }else{
            $res[]=Db::name('admin_authoritys')->order('controller', 'asc')->limit($limitStart,$limitLength)->select();
            $res[]=Db::name('admin_authoritys')->count();
        }
        return $res;
    }

    public function add(){
        $this->checkAction('admin_rule_add');
        return $this->fetch();
    }

    public function edit(){
        $this->checkAction('admin_rule_edit');
        $id=input('id');
        $data=Db::name('admin_authoritys')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 添加管理员
     */
    public function doadd(){
        $controller=input('post.controller');
        $controller_name=input('post.controller_name');
        $action=input('post.action');
        $action_name=input('post.action_name');

        $res=Db::name('admin_authoritys')->insert([
            'controller'=>$controller,
            'controller_name'=>$controller_name,
            'action'=>$action,
            'action_name'=>$action_name
        ]);
        if($res)
            echo json_encode(['success'=>1]);
        else
            echo json_encode(['success'=>0]);
    }

    /**
     * 编辑管理员
     */
    public function doedit(){
        $id=input('post.id');
        $parame=[
            'controller'=>input('post.controller'),              //控制器
            'controller_name'=>input('post.controller_name'),      //控制器名称
            'action'=>input('post.action'),       //方法
            'action_name'=>input('post.action_name')      //方法名称
        ];
        $res=Db::name('admin_authoritys')->where('id',$id)->update($parame);
        if($res)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }

    /**
     * 删除管理员
     */
    public function doDelete(){
        $id=input('post.id');
        if($id){
            $res=Db::name('admin_authoritys')->delete($id);
            if($res)
                echo json_encode(['success'=>true]);
            else
                echo json_encode(['success'=>false]);;
        }
    }
    /**
     * 修改用户状态为停用
     */
    public function doStatusStop(){
        $id=input('post.id');
        $res=Db::name('admin_group')->where('id',$id)->update(['status'=>Dict::ADMIN_USER_B,'update_time'=>time()]);
        if($res)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }

    /**
     * 修改用户状态为启用
     */
    public function doStatusStart(){
        $id=input('post.id');
        $res=Db::name('admin_group')->where('id',$id)->update(['status'=>Dict::ADMIN_USER_A,'update_time'=>time()]);
        if($res)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }
}
