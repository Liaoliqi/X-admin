<?php
namespace app\admin\controller;
use app\admin\Dict;
use \think\Db;
use app\admin\controller\Admincommon;
use think\Request;
class AdminGroup extends Admincommon
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->checkLogin($request);
    }

    public function index()
    {
        $this->checkAction('admin_group_index');
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
            $data[$k]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
        }
        //权限验证
        $auth['add']=$this->isAllow('admin_group_add');
        $auth['edit']=$this->isAllow('admin_group_edit');
        $auth['del']=$this->isAllow('admin_group_del');
        $auth['status']=$this->isAllow('admin_group_status');
        $this->assign('auth',$auth);
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
            $res[]=Db::name('admin_group')->order('id', 'desc')->limit($limitStart,$limitLength)->select();
            $res[]=Db::name('admin_group')->count();
        }else{
            $res[]=Db::name('admin_group')->order('id', 'desc')->limit($limitStart,$limitLength)->select();
            $res[]=Db::name('admin_group')->count();
        }
        return $res;
    }

    public function add(){
        $this->checkAction('admin_group_add');
        //获取所有权限信息
        $rule=array();
        $ruleData=Db::name('admin_authoritys')->order('controller', 'asc')->select();
        if($ruleData){
            foreach($ruleData as $val){
                $rule[$val['controller_name']][$val['id']]=$val;
            }
        }
        $this->assign('rule',$rule);
        return $this->fetch();
    }

    public function edit(){
        $this->checkAction('admin_group_edit');
        $id=input('id');
        $data=Db::name('admin_group')->where('id',$id)->find();
        //获取角色拥有的权限
        $group_role_ids=array();
        $roles=Db::name('admin_group_authority')->where(['group_id'=>$id])->field('authority_id')->select();
        if($roles){
            foreach($roles as $v){
                $group_role_ids[]=$v['authority_id'];
            }
        }
        //获取所有权限信息
        $rule=array();
        $ruleData=Db::name('admin_authoritys')->order('controller', 'asc')->select();
        if($ruleData){
            foreach($ruleData as $val){
                $rule[$val['controller_name']][$val['id']]=$val;
                $rule[$val['controller_name']][$val['id']]=array_merge($rule[$val['controller_name']][$val['id']],['isChecked'=>in_array($val['id'],$group_role_ids)]);
            }
        }
        $this->assign('rule',$rule);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 添加管理员
     */
    public function doadd(){
        $name=input('post.name');
        $roleIds=input('post.role_id/a');
        $describe=input('post.describe');
        //检查角色名是否已存在
        $checkName=Db::name('admin_group')->where(['name'=>$name])->find();
        if($checkName){
            echo json_encode(['success'=>0,'message'=>'角色名已存在']);
            exit;
        }
        // 启动事务
        Db::startTrans();
        $result=false;
        try{
            $addres=true;
            $res=Db::name('admin_group')->insert([
                'name'=>$name,
                'describe'=>$describe,
                'create_time'=>time(),
                'update_time'=>time()
            ]);
            if(isset($roleIds)){
                $parameter=array();
                $group_id=Db::name('admin_group')->getLastInsID();
                foreach($roleIds as $val){
                    $parameter[]=['group_id'=>$group_id,'authority_id'=>intval($val)];
                }
                $addres=Db::name('admin_group_authority')->insertAll($parameter);
            }
            $result=($res && $addres);
            if($result)
                Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }

        if($result)
            echo json_encode(['success'=>1]);
        else
            echo json_encode(['success'=>0]);
    }

    /**
     * 编辑管理员
     */
    public function doedit(){
        $id=input('post.id');
        $role_ids=input('post.role_id/a');
        $parame=[
            'name'=>input('post.name'),              //组名称
            'describe'=>input('post.describe'),      //描述
            'update_time'=>time()
        ];
        Db::startTrans();
        $result=false;
        try{
            $delRes=true;
            $addRes=true;
            $res=Db::name('admin_group')->where('id',$id)->update($parame);
            //修改权限
            $newRoles=isset($role_ids)?$role_ids:array();
            $oldRoles=array();
            $tmp=Db::name('admin_group_authority')->where(['group_id'=>$id])->select();
            if($tmp){
                foreach($tmp as $val){
                    $oldRoles[$val['id']]=$val['authority_id'];
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
                $delRes=Db::name('admin_group_authority')->delete($oldDifference);
            }
            if(!empty($newDifference)){     //添加新增的权限
                $parameter=array();
                foreach($newDifference as $act){
                    $parameter[]=['group_id'=>$id,'authority_id'=>$act];
                }
                $addRes=Db::name('admin_group_authority')->insertAll($parameter);
            }
            $result=($res && $addRes && $delRes);
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
     * 删除管理员
     */
    public function doDelete(){
        $id=input('post.id');
        if($id){
            $res=Db::name('admin_group')->delete($id);
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
