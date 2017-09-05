<?php
namespace app\admin\controller;
use app\admin\Dict;
use \think\Db;
use app\admin\model\AdminUser as AdminUserModel;
use app\admin\controller\Admincommon;
use think\Request;
class AdminUser extends Admincommon
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->checkLogin($request);
    }

    public function index()
    {
        $this->checkAction('admin_user_index');
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

        //格式化数据
        foreach($data as $k=>$v){
            $data[$k]['login_time']=empty($v['login_time'])?'':date("Y-m-d H:i:s",$v['login_time']);
        }
        //权限验证
        $auth['add']=$this->isAllow('admin_user_add');
        $auth['edit']=$this->isAllow('admin_user_edit');
        $auth['del']=$this->isAllow('admin_user_del');
        $auth['status']=$this->isAllow('admin_user_status');
        $auth['role']=$this->isAllow('admin_user_role');
        $this->assign('auth',$auth);
        $this->assign('post',input('post.')?input('post.'):['start'=>'','end'=>'','user'=>'']);
        $this->assign('data',$data);
        $this->assign( 'page',getPages($total,$curr) );
        $this->assign('status',Dict::$ADMIN_USER_STATUS);
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
            if(isset($post['start']) || isset($post['end'])){
                $conditions['create_time']=array();
                if(isset($post['start']))
                    $conditions['create_time']=array_merge($conditions['create_time'],['>=',strtotime($post['start'])]);
                if(isset($post['end']))
                    $conditions['create_time']=array_merge($conditions['create_time'],['<=',strtotime($post['end'])]);
            }
            if(isset($post['user']))
                $conditions['user']=['like','%'.$post['user'].'%'];

            $res[]=Db::name('admin_user')->where($conditions)->order('id', 'desc')->limit($limitStart,$limitLength)->select();
            $res[]=Db::name('admin_user')->where($conditions)->count();
        }else{
            $res[]=Db::name('admin_user')->order('id', 'desc')->limit($limitStart,$limitLength)->select();
            $res[]=Db::name('admin_user')->count();
        }
        return $res;
    }

    public function add(){
        $this->checkAction('admin_user_add');
        return $this->fetch();
    }

    public function edit(){
        $this->checkAction('admin_user_edit');
        $id=input('id');
        $data=AdminUserModel::get($id)->toArray();
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function role(){
        $this->checkAction('admin_user_role');
        $id=input('get.id');
        //获取用户所属角色
        $groups=array();
        $tmp2=Db::name('admin_user_group')->where(['user_id'=>$id])->field('group_id')->select();
        if(!empty($tmp2)){
            foreach($tmp2 as $value){
                $groups[]=$value['group_id'];
            }
        }
        //获取所有角色信息
        $roleData=array();
        $tmp=Db::name('admin_group')->where(['status'=>1])->field('id,name')->order('id','desc')->select();
        if(!empty($tmp)){
            foreach($tmp as $val){
                $roleData[]=array_merge($val,['isChecked'=>in_array($val['id'],$groups)]);
            }
        }
        $this->assign('userId',$id);
        $this->assign('data',$roleData);
        return $this->fetch();
    }

    /**
     * 添加管理员
     */
    public function doadd(){
        $username=input('post.username');
        //检查登录名是否已存在
        $checkName=AdminUserModel::get(['user'=>$username]);
        if($checkName){
            echo json_encode(['success'=>0,'message'=>'登录名已存在']);
            exit;
        }
        $res=AdminUserModel::create([
            'user'=>$username,         //登录名
            'name'=>input('post.name'),              //昵称
            'password'=>input('post.pass'),         //密码
            'mobile'=>input('post.phone'),          //手机
            'email'=>input('post.email')           //邮箱
        ]);
        if($res->id)
            echo json_encode(['success'=>1]);
        else
            echo json_encode(['success'=>0]);
    }

    /**
     * 编辑管理员
     */
    public function doedit(){
        $parame=[
            'id' => input('post.id'),
            'name'=>input('post.name'),              //昵称
            'mobile'=>input('post.phone'),          //手机
            'email'=>input('post.email')           //邮箱
        ];
        $password=input('post.pass');
        if( $password != '******' )
            $parame=array_merge($parame,['password'=>$password]);
        $res=AdminUserModel::update($parame);
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
            $res=AdminUserModel::destroy($id);
            if($res)
                echo json_encode(['success'=>true]);
            else
                echo json_encode(['success'=>false]);;
        }
    }

    /**
     * 角色分配
     */
    public function dorole(){
        $user_id=input('post.id');
        $role_ids=input('post.role_id/a');
        $newRoles=isset($role_ids)?$role_ids:array();
        Db::startTrans();
        $result=false;
        try{
            $delRes=true;
            $addRes=true;
            //获取用户所属角色
            $oldRoles=array();
            $tmp=Db::name('admin_user_group')->where(['user_id'=>$user_id])->field('id,group_id')->select();
            if(!empty($tmp)){
                foreach($tmp as $value){
                    $oldRoles[$value['id']]=$value['group_id'];
                }
            }
            //获取新权限与老权限的交集
            $intersection=array_intersect($oldRoles,$newRoles);
            //获取新老权限的差集
            $oldDifference=array_diff($oldRoles,$intersection);
            $newDifference=array_diff($newRoles,$intersection);
            if(!empty($oldDifference)){     //删除多余权限
                //处理多余的权限数组  键值互换，自然排序，用主键id来删除多余的权限
                $oldDifference=array_flip($oldDifference);
                sort($oldDifference);
                $delRes=Db::name('admin_user_group')->delete($oldDifference);
            }
            if(!empty($newDifference)){     //添加新增的权限
                $parameter=array();
                foreach($newDifference as $act){
                    $parameter[]=['user_id'=>$user_id,'group_id'=>$act];
                }
                $addRes=Db::name('admin_user_group')->insertAll($parameter);
            }
            $result=($addRes && $delRes);
            if($result)
                Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }

        if($result)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }

    /**
     * 修改用户状态为停用
     */
    public function doStatusStop(){
        $id=input('post.id');
        $res=AdminUserModel::update(['id' => $id, 'status' => Dict::ADMIN_USER_B]);
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
        $res=AdminUserModel::update(['id' => $id, 'status' => Dict::ADMIN_USER_A]);
        if($res)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }
}
